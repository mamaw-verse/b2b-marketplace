<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CloseRfqAction;
use App\Events\RfqCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRfqRequest;
use App\Models\Rfq;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class RfqController extends Controller
{
    /**
     * List buyer's sent RFQs.
     */
    public function index(Request $request): JsonResponse
    {
        $rfqs = $request->user()->rfqs()
            ->withCount('recipients')
            ->orderByDesc('created_at')
            ->cursorPaginate($request->integer('limit', 20));

        $data = $rfqs->map(fn (Rfq $rfq) => [
            'id' => $rfq->id,
            'title' => $rfq->title,
            'status' => $rfq->status,
            'recipients_count' => $rfq->recipients_count,
            'last_activity_at' => $rfq->last_activity_at?->toIso8601String(),
            'created_at' => $rfq->created_at->toIso8601String(),
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
     * Show a single RFQ detail.
     */
    public function show(Request $request, Rfq $rfq): JsonResponse
    {
        Gate::authorize('view', $rfq);

        $rfq->load(['recipients.shop', 'attachments']);

        return response()->json([
            'data' => [
                'id' => $rfq->id,
                'title' => $rfq->title,
                'description' => $rfq->description,
                'status' => $rfq->status,
                'buyer_snapshot' => $rfq->buyer_snapshot,
                'recipients' => $rfq->recipients->map(fn ($r) => [
                    'id' => $r->id,
                    'shop_id' => $r->shop_id,
                    'shop_name' => $r->shop->storefront_name,
                    'status' => $r->status,
                ]),
                'attachments' => $rfq->attachments->map(fn ($m) => [
                    'media_id' => $m->id,
                    'original_filename' => $m->original_filename,
                ]),
                'last_activity_at' => $rfq->last_activity_at?->toIso8601String(),
                'closed_at' => $rfq->closed_at?->toIso8601String(),
                'created_at' => $rfq->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new RFQ.
     */
    public function store(CreateRfqRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verify buyer profile exists
        $buyerProfile = $user->buyerProfile;
        if (! $buyerProfile) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'A buyer profile is required before sending an RFQ.',
                    'fields' => [
                        'buyer_profile' => ['Please complete your buyer profile before submitting a request for quotation.'],
                    ],
                ],
            ], 422);
        }

        // Verify shop is active
        $shop = Shop::where('id', $request->validated('shop_id'))
            ->where('status', 'active')
            ->first();

        if (! $shop) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The selected shop is not available.',
                    'fields' => [
                        'shop_id' => ['The selected shop must be active to receive RFQs.'],
                    ],
                ],
            ], 422);
        }

        $rfq = DB::transaction(function () use ($user, $request, $buyerProfile, $shop) {
            $rfq = Rfq::create([
                'user_id' => $user->id,
                'title' => $request->validated('title'),
                'description' => $request->validated('description'),
                'status' => 'open',
                'buyer_snapshot' => $buyerProfile->toSnapshot(),
                'last_activity_at' => now(),
            ]);

            // Create the single recipient
            $rfq->recipients()->create([
                'shop_id' => $shop->id,
                'status' => 'received',
                'last_activity_at' => now(),
            ]);

            // Attach media files
            $mediaIds = $request->validated('attachment_media_ids') ?? [];
            if (! empty($mediaIds)) {
                $rfq->attachments()->attach($mediaIds);
            }

            return $rfq;
        });

        $rfq->load(['recipients.shop', 'attachments']);

        RfqCreated::dispatch($rfq);

        return response()->json([
            'data' => [
                'id' => $rfq->id,
                'title' => $rfq->title,
                'description' => $rfq->description,
                'status' => $rfq->status,
                'buyer_snapshot' => $rfq->buyer_snapshot,
                'recipients' => $rfq->recipients->map(fn ($r) => [
                    'id' => $r->id,
                    'shop_id' => $r->shop_id,
                    'shop_name' => $r->shop->storefront_name,
                    'status' => $r->status,
                ]),
                'attachments' => $rfq->attachments->map(fn ($m) => [
                    'media_id' => $m->id,
                    'original_filename' => $m->original_filename,
                ]),
                'last_activity_at' => $rfq->last_activity_at->toIso8601String(),
                'created_at' => $rfq->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Close an RFQ manually.
     */
    public function close(Request $request, Rfq $rfq, CloseRfqAction $action): JsonResponse
    {
        Gate::authorize('close', $rfq);

        $result = $action->execute($rfq);

        if (! $result['success']) {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => $result['error'],
                ],
            ], 409);
        }

        return response()->json([
            'data' => [
                'id' => $result['rfq']->id,
                'status' => 'closed',
                'closed_at' => $result['rfq']->closed_at->toIso8601String(),
                'quotations_not_awarded_count' => $result['quotations_not_awarded_count'],
            ],
        ]);
    }
}
