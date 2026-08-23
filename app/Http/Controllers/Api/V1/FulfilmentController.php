<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\OrderDelivered;
use App\Events\OrderShipped;
use App\Http\Controllers\Controller;
use App\Models\Fulfilment;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class FulfilmentController extends Controller
{
    /**
     * Create a fulfilment record for a paid order.
     */
    public function store(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('create', [Fulfilment::class, $order]);

        $validated = $request->validate([
            'carrier' => ['nullable', 'string', 'max:120'],
            'tracking_code' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'string', 'max:500', 'url'],
        ]);

        $fulfilment = Fulfilment::create([
            'order_id' => $order->id,
            'status' => 'pending',
            'carrier' => $validated['carrier'] ?? null,
            'tracking_code' => $validated['tracking_code'] ?? null,
            'tracking_url' => $validated['tracking_url'] ?? null,
        ]);

        return response()->json([
            'data' => $this->formatFulfilment($fulfilment),
        ], 201);
    }

    /**
     * Update tracking info on a fulfilment (while pending or shipped).
     */
    public function update(Request $request, Order $order): JsonResponse
    {
        $fulfilment = $order->fulfilments()->firstOrFail();

        Gate::authorize('update', $fulfilment);

        $validated = $request->validate([
            'carrier' => ['nullable', 'string', 'max:120'],
            'tracking_code' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'string', 'max:500', 'url'],
        ]);

        $fulfilment->update($validated);

        return response()->json([
            'data' => $this->formatFulfilment($fulfilment->fresh()),
        ]);
    }

    /**
     * Mark fulfilment as shipped.
     */
    public function ship(Request $request, Order $order): JsonResponse
    {
        $fulfilment = $order->fulfilments()->firstOrFail();

        Gate::authorize('ship', $fulfilment);

        $validated = $request->validate([
            'carrier' => ['nullable', 'string', 'max:120'],
            'tracking_code' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'string', 'max:500', 'url'],
        ]);

        $fulfilment->update([
            ...$validated,
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);

        OrderShipped::dispatch($order->fresh());

        return response()->json([
            'data' => $this->formatFulfilment($fulfilment->fresh()),
        ]);
    }

    /**
     * Mark fulfilment as delivered.
     */
    public function deliver(Request $request, Order $order): JsonResponse
    {
        $fulfilment = $order->fulfilments()->firstOrFail();

        Gate::authorize('deliver', $fulfilment);

        $fulfilment->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        OrderDelivered::dispatch($order->fresh());

        return response()->json([
            'data' => $this->formatFulfilment($fulfilment->fresh()),
        ]);
    }

    /**
     * Mark fulfilment as returned to seller.
     */
    public function returnToSeller(Request $request, Order $order): JsonResponse
    {
        $fulfilment = $order->fulfilments()->firstOrFail();

        Gate::authorize('returnToSeller', $fulfilment);

        $fulfilment->update([
            'status' => 'returned_to_seller',
            'returned_at' => now(),
        ]);

        return response()->json([
            'data' => $this->formatFulfilment($fulfilment->fresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFulfilment(Fulfilment $fulfilment): array
    {
        return [
            'id' => $fulfilment->id,
            'order_id' => $fulfilment->order_id,
            'status' => $fulfilment->status,
            'carrier' => $fulfilment->carrier,
            'tracking_code' => $fulfilment->tracking_code,
            'tracking_url' => $fulfilment->tracking_url,
            'shipped_at' => $fulfilment->shipped_at?->toIso8601String(),
            'delivered_at' => $fulfilment->delivered_at?->toIso8601String(),
            'returned_at' => $fulfilment->returned_at?->toIso8601String(),
        ];
    }
}
