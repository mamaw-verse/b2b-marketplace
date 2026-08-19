<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Hub;

use App\Http\Controllers\Controller;
use App\Models\RfqRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class RfqRecipientController extends Controller
{
    /**
     * List RFQs received by the seller's shop.
     */
    public function index(Request $request): JsonResponse
    {
        $shop = $request->user()->shop;

        if (! $shop) {
            return response()->json(['data' => [], 'meta' => ['next_cursor' => null, 'prev_cursor' => null, 'per_page' => 20]]);
        }

        $query = RfqRecipient::where('shop_id', $shop->id)
            ->with(['rfq.user.buyerProfile'])
            ->orderByDesc('created_at');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $rfqs = $query->cursorPaginate($request->integer('limit', 20));

        $data = $rfqs->map(fn (RfqRecipient $r) => [
            'rfq_recipient_id' => $r->id,
            'rfq_id' => $r->rfq_id,
            'title' => $r->rfq->title,
            'buyer_business_name' => $r->rfq->buyer_snapshot['business_name'] ?? null,
            'status' => $r->status,
            'last_activity_at' => $r->last_activity_at?->toIso8601String(),
            'created_at' => $r->created_at->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'next_cursor' => $rfqs->nextCursor()?->encode(),
                'prev_cursor' => $rfqs->previousCursor()?->encode(),
                'per_page' => $rfqs->perPage(),
            ],
        ]);
    }

    /**
     * Show RFQ detail from the seller perspective.
     */
    public function show(Request $request, RfqRecipient $rfqRecipient): JsonResponse
    {
        Gate::authorize('view', $rfqRecipient);

        $rfqRecipient->load(['rfq.attachments', 'rfq.user.buyerProfile']);

        return response()->json([
            'data' => [
                'rfq_recipient_id' => $rfqRecipient->id,
                'rfq_id' => $rfqRecipient->rfq_id,
                'title' => $rfqRecipient->rfq->title,
                'description' => $rfqRecipient->rfq->description,
                'buyer_snapshot' => $rfqRecipient->rfq->buyer_snapshot,
                'status' => $rfqRecipient->status,
                'decline_reason' => $rfqRecipient->decline_reason,
                'reviewed_at' => $rfqRecipient->reviewed_at?->toIso8601String(),
                'attachments' => $rfqRecipient->rfq->attachments->map(fn ($m) => [
                    'media_id' => $m->id,
                    'original_filename' => $m->original_filename,
                ]),
                'last_activity_at' => $rfqRecipient->last_activity_at?->toIso8601String(),
                'created_at' => $rfqRecipient->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Mark as reviewed (also auto-triggered on first view in Livewire).
     */
    public function review(Request $request, RfqRecipient $rfqRecipient): JsonResponse
    {
        Gate::authorize('review', $rfqRecipient);

        if ($rfqRecipient->status !== 'received') {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => 'RFQ has already been reviewed.',
                ],
            ], 409);
        }

        $rfqRecipient->update([
            'status' => 'reviewed',
            'reviewed_at' => now(),
            'last_activity_at' => now(),
        ]);

        $rfqRecipient->rfq->touchActivity();

        return response()->json([
            'data' => [
                'rfq_recipient_id' => $rfqRecipient->id,
                'status' => 'reviewed',
                'reviewed_at' => $rfqRecipient->fresh()->reviewed_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Mark as in_progress.
     */
    public function inProgress(Request $request, RfqRecipient $rfqRecipient): JsonResponse
    {
        Gate::authorize('inProgress', $rfqRecipient);

        $rfqRecipient->update([
            'status' => 'in_progress',
            'last_activity_at' => now(),
        ]);

        $rfqRecipient->rfq->touchActivity();

        return response()->json([
            'data' => [
                'rfq_recipient_id' => $rfqRecipient->id,
                'status' => 'in_progress',
            ],
        ]);
    }

    /**
     * Decline (no_bid) with optional reason.
     */
    public function decline(Request $request, RfqRecipient $rfqRecipient): JsonResponse
    {
        Gate::authorize('decline', $rfqRecipient);

        $rfqRecipient->update([
            'status' => 'no_bid',
            'decline_reason' => $request->input('decline_reason'),
            'last_activity_at' => now(),
        ]);

        $rfqRecipient->rfq->touchActivity();

        return response()->json([
            'data' => [
                'rfq_recipient_id' => $rfqRecipient->id,
                'status' => 'no_bid',
                'decline_reason' => $rfqRecipient->decline_reason,
            ],
        ]);
    }
}
