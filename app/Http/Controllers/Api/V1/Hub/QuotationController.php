<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Hub;

use App\Events\QuotationSubmitted;
use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\RfqRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class QuotationController extends Controller
{
    /**
     * Create a draft quotation for an RFQ recipient.
     */
    public function store(Request $request, RfqRecipient $rfqRecipient): JsonResponse
    {
        Gate::authorize('create', [Quotation::class, $rfqRecipient]);

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:30'],
            'items.*.unit_price' => ['required', 'numeric', 'gt:0'],
            'attachment_media_ids' => ['nullable', 'array'],
            'attachment_media_ids.*' => ['integer', 'exists:media,id'],
        ]);

        $quotation = DB::transaction(function () use ($rfqRecipient, $validated) {
            $quotation = Quotation::create([
                'rfq_id' => $rfqRecipient->rfq_id,
                'rfq_recipient_id' => $rfqRecipient->id,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'currency' => 'PHP',
            ]);

            $totalAmount = 0;
            foreach ($validated['items'] as $index => $item) {
                $subtotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $totalAmount += $subtotal;
                $quotation->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                    'sort_order' => $index,
                ]);
            }

            $quotation->update(['total_amount' => $totalAmount]);

            if (! empty($validated['attachment_media_ids'])) {
                $quotation->attachments()->attach($validated['attachment_media_ids']);
            }

            return $quotation;
        });

        return response()->json([
            'data' => $this->formatQuotation($quotation->fresh(['items', 'attachments'])),
        ], 201);
    }

    /**
     * Update a draft quotation.
     */
    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        Gate::authorize('update', $quotation);

        if ($quotation->isImmutable()) {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => 'Only draft quotations can be edited.',
                ],
            ], 409);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:30'],
            'items.*.unit_price' => ['required', 'numeric', 'gt:0'],
            'attachment_media_ids' => ['nullable', 'array'],
            'attachment_media_ids.*' => ['integer', 'exists:media,id'],
        ]);

        DB::transaction(function () use ($quotation, $validated) {
            $quotation->update(['notes' => $validated['notes'] ?? null]);

            // Replace all items
            $quotation->items()->delete();
            $totalAmount = 0;
            foreach ($validated['items'] as $index => $item) {
                $subtotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $totalAmount += $subtotal;
                $quotation->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                    'sort_order' => $index,
                ]);
            }

            $quotation->update(['total_amount' => $totalAmount]);

            // Sync attachments
            $mediaIds = $validated['attachment_media_ids'] ?? [];
            $quotation->attachments()->sync($mediaIds);
        });

        return response()->json([
            'data' => $this->formatQuotation($quotation->fresh(['items', 'attachments'])),
        ]);
    }

    /**
     * Delete a draft quotation.
     */
    public function destroy(Request $request, Quotation $quotation): JsonResponse
    {
        Gate::authorize('delete', $quotation);

        if ($quotation->isImmutable()) {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => 'Only draft quotations can be deleted.',
                ],
            ], 409);
        }

        $quotation->delete();

        return response()->json(null, 204);
    }

    /**
     * Submit a draft quotation (becomes immutable).
     */
    public function submit(Request $request, Quotation $quotation): JsonResponse
    {
        Gate::authorize('submit', $quotation);

        if (! $quotation->isDraft()) {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => 'Only draft quotations can be submitted.',
                ],
            ], 409);
        }

        if ($quotation->items()->count() === 0) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'A quotation must have at least one line item to be submitted.',
                    'fields' => ['items' => ['At least one line item is required.']],
                ],
            ], 422);
        }

        // Capture buyer snapshot at submission time
        $buyerProfile = $quotation->rfq->user->buyerProfile;
        $buyerSnapshot = $buyerProfile ? $buyerProfile->toSnapshot() : $quotation->rfq->buyer_snapshot;

        $quotation->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'valid_until' => now()->addDays(14),
            'buyer_snapshot' => $buyerSnapshot,
        ]);

        // Recalculate total from items
        $quotation->recalculateTotal();

        // Update activity timestamps
        $quotation->rfqRecipient->touchActivity();
        $quotation->rfq->touchActivity();

        QuotationSubmitted::dispatch($quotation->fresh());

        return response()->json([
            'data' => $this->formatQuotation($quotation->fresh(['items', 'attachments'])),
        ]);
    }

    /**
     * Withdraw a submitted quotation.
     */
    public function withdraw(Request $request, Quotation $quotation): JsonResponse
    {
        Gate::authorize('withdraw', $quotation);

        if (! $quotation->isSubmitted()) {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => 'Only submitted quotations can be withdrawn.',
                ],
            ], 409);
        }

        $quotation->update(['status' => 'withdrawn']);

        $quotation->rfq->touchActivity();

        return response()->json([
            'data' => [
                'id' => $quotation->id,
                'status' => 'withdrawn',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatQuotation(Quotation $quotation): array
    {
        return [
            'id' => $quotation->id,
            'rfq_id' => $quotation->rfq_id,
            'rfq_recipient_id' => $quotation->rfq_recipient_id,
            'status' => $quotation->status,
            'notes' => $quotation->notes,
            'currency' => $quotation->currency,
            'total_amount' => $quotation->total_amount,
            'valid_until' => $quotation->valid_until?->toIso8601String(),
            'submitted_at' => $quotation->submitted_at?->toIso8601String(),
            'buyer_snapshot' => $quotation->buyer_snapshot,
            'items' => $quotation->items->map(fn ($item) => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
                'sort_order' => $item->sort_order,
            ]),
            'attachments' => $quotation->attachments->map(fn ($m) => [
                'media_id' => $m->id,
                'original_filename' => $m->original_filename,
            ]),
            'created_at' => $quotation->created_at->toIso8601String(),
        ];
    }
}
