<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateOrderAction;
use App\Actions\SubmitOrderAction;
use App\Events\OrderCancelled;
use App\Events\OrderSubmittedForPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderListResource;
use App\Http\Resources\OrderResource;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Quotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HubOrderController extends Controller
{
    /**
     * Create a new order from a quotation.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        Gate::authorize('accessHub');
        Gate::authorize('create', Order::class);

        $shop = $request->user()->activeShop;
        $quotationId = $request->validated('quotation_id');
        if ($quotationId !== null) {
            $quotation = Quotation::with('rfqRecipient')->findOrFail($quotationId);
            Gate::authorize('createOrder', $quotation);
        }

        $result = (new CreateOrderAction)->execute(
            $shop,
            $quotationId,
            $request->validated('buyer_user_id'),
        );

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
        ], 201);
    }

    /**
     * List orders for the seller's shop.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('accessHub');
        $shop = $request->user()->activeShop;

        $query = Order::forShop($shop->id);

        if ($request->has('status')) {
            $query->inStatus($request->query('status'));
        }

        $orders = $query->cursorPaginate(20);

        return OrderListResource::collection($orders)->response();
    }

    /**
     * Show a single order with full details.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('view', $order);

        $order->load(['items', 'fees', 'paymentTerms', 'fulfilments', 'disputes']);

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Update a draft order.
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        Gate::authorize('update', $order);

        if ($order->status !== 'draft') {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => 'Only draft orders can be edited.',
                ],
            ], 409);
        }

        DB::transaction(function () use ($order, $request) {
            $validated = $request->validated();

            // Update snapshot fields (partial update — only what's provided)
            $snapshotFields = [
                'customer_business_name',
                'customer_contact_name',
                'customer_contact_phone',
                'customer_contact_email',
                'billing_address_1',
                'billing_address_2',
                'billing_city',
                'billing_province',
                'billing_zip_code',
                'billing_country_code',
                'shipping_address_1',
                'shipping_address_2',
                'shipping_city',
                'shipping_province',
                'shipping_zip_code',
                'shipping_country_code',
                'currency',
            ];

            $updates = array_intersect_key($validated, array_flip($snapshotFields));
            if ($updates !== []) {
                $order->update($updates);
            }

            // Full replace items if key present
            if (array_key_exists('items', $validated)) {
                $order->items()->delete();

                $itemRows = [];
                foreach ($validated['items'] as $index => $item) {
                    $itemRows[] = [
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => round((float) $item['quantity'] * (float) $item['unit_price'], 2),
                        'sort_order' => $index,
                    ];
                }

                if ($itemRows !== []) {
                    $order->items()->createMany($itemRows);
                }
            }

            // Full replace fees if key present
            if (array_key_exists('fees', $validated)) {
                $order->fees()->delete();

                if ($validated['fees'] !== []) {
                    $order->fees()->createMany($validated['fees']);
                }
            }

            // Recalculate totals
            $itemsTotal = $order->items()->sum('subtotal');
            $feesTotal = $order->fees()->sum('amount');
            $order->update([
                'items_total' => $itemsTotal,
                'fees_total' => $feesTotal,
                'order_total' => $itemsTotal + $feesTotal,
            ]);
        });

        return response()->json([
            'data' => new OrderResource($order->fresh(['items', 'fees'])),
        ]);
    }

    /**
     * Delete a draft order.
     */
    public function destroy(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('delete', $order);

        if ($order->status !== 'draft') {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => 'Only draft orders can be deleted.',
                ],
            ], 409);
        }

        $order->delete();

        return response()->json(null, 204);
    }

    /**
     * Submit a draft order to the buyer.
     */
    public function submit(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('submit', $order);

        $result = (new SubmitOrderAction)->execute($order);

        if (! $result['success']) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $result['error'],
                    'fields' => ['items' => ['Add at least one order item.']],
                ],
            ], 422);
        }

        OrderSubmittedForPayment::dispatch($result['order']);

        return response()->json([
            'data' => new OrderResource($result['order']),
        ]);
    }

    /**
     * Recall a submitted order back to draft.
     */
    public function recall(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('recall', $order);

        $order->update([
            'status' => 'draft',
            'submitted_at' => null,
        ]);

        return response()->json([
            'data' => new OrderResource($order->fresh()),
        ]);
    }

    /**
     * Cancel an order as the seller.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('cancelAsSeller', $order);

        DB::transaction(function () use ($order) {
            $previousStatus = $order->status;

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            if ($previousStatus === 'paid') {
                LedgerEntry::create([
                    'order_id' => $order->id,
                    'type' => 'refund',
                    'amount' => $order->order_total,
                    'note' => 'Seller cancelled order — full refund',
                ]);
            }
        });

        OrderCancelled::dispatch($order->fresh());

        return response()->json([
            'data' => new OrderResource($order->fresh()),
        ]);
    }
}
