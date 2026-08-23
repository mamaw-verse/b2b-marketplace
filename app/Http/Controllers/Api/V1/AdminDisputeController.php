<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ResolveDisputeAction;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class AdminDisputeController extends Controller
{
    /**
     * List escalated disputes for admin review.
     */
    public function index(Request $request): JsonResponse
    {
        $disputes = Dispute::where('status', 'escalated')
            ->with(['order.shop', 'openedBy'])
            ->latest('escalated_at')
            ->cursorPaginate($request->integer('limit', 20));

        return response()->json([
            'data' => $disputes->map(fn (Dispute $d) => [
                'id' => $d->id,
                'order_id' => $d->order_id,
                'type' => $d->type,
                'status' => $d->status,
                'reason' => $d->reason,
                'buyer' => [
                    'id' => $d->opened_by_user_id,
                    'name' => $d->openedBy->first_name.' '.$d->openedBy->last_name,
                ],
                'shop' => [
                    'id' => $d->order->shop_id,
                    'name' => $d->order->shop->storefront_name,
                ],
                'escalated_at' => $d->escalated_at?->toIso8601String(),
                'created_at' => $d->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'next_cursor' => $disputes->nextCursor()?->encode(),
                'prev_cursor' => $disputes->previousCursor()?->encode(),
                'per_page' => $disputes->perPage(),
            ],
        ]);
    }

    /**
     * Admin arbitrates an escalated dispute.
     */
    public function arbitrate(Request $request, Dispute $dispute): JsonResponse
    {
        Gate::authorize('arbitrate', $dispute);

        $validated = $request->validate([
            'resolution_type' => ['required', 'string', 'in:full_refund,partial_refund,denied'],
            'resolution_amount' => ['nullable', 'numeric', 'min:0'],
            'resolution_notes' => ['required', 'string', 'max:2000'],
        ]);

        $result = (new ResolveDisputeAction)->execute($dispute, $request->user(), $validated);

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
                'id' => $result['dispute']->id,
                'status' => $result['dispute']->status,
                'resolution_type' => $result['dispute']->resolution_type,
                'resolution_amount' => $result['dispute']->resolution_amount,
                'resolved_at' => $result['dispute']->resolved_at?->toIso8601String(),
            ],
        ]);
    }
}
