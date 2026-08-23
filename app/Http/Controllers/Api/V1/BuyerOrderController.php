<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderListResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class BuyerOrderController extends Controller
{
    /**
     * List buyer's orders with optional tab filter.
     *
     * Tabs: to_pay, to_ship, to_receive, completed, disputed
     */
    public function index(Request $request): JsonResponse
    {
        $buyerId = $request->user()->id;
        $tab = $request->query('tab');

        $query = Order::forBuyer($buyerId)
            ->with(['shop'])
            ->latest();

        match ($tab) {
            'to_pay' => $query->where('status', 'pending_payment'),
            'to_ship' => $query->where('status', 'paid')
                ->where(function ($q) {
                    $q->whereDoesntHave('fulfilments')
                        ->orWhereHas('fulfilments', fn ($f) => $f->where('status', 'pending'));
                }),
            'to_receive' => $query->whereHas('fulfilments', fn ($f) => $f->whereIn('status', ['shipped', 'delivered'])),
            'completed' => $query->where('status', 'completed'),
            'disputed' => $query->whereHas('disputes', fn ($d) => $d->whereNotIn('status', ['resolved_refunded', 'resolved_denied', 'withdrawn'])),
            default => null,
        };

        $orders = $query->cursorPaginate($request->integer('limit', 20));

        return OrderListResource::collection($orders)->response();
    }

    /**
     * Show order detail for the buyer.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('view', $order);

        $order->load(['items', 'fees', 'fulfilments', 'shop']);

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Buyer cancels a pending_payment order.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('cancelAsBuyer', $order);

        if ($order->status !== 'pending_payment') {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => 'Only orders awaiting payment can be cancelled.',
                ],
            ], 409);
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'data' => new OrderResource($order->fresh()),
        ]);
    }

    /**
     * Buyer confirms receipt of a delivered order.
     */
    public function confirmReceipt(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('confirmReceipt', $order);

        $action = app(\App\Actions\ConfirmReceiptAction::class);
        $result = $action->execute($order);

        if (! $result['success']) {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => $result['error'],
                ],
            ], 409);
        }

        return response()->json([
            'data' => new OrderResource($result['order']),
        ]);
    }
}
