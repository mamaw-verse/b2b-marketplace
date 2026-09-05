<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Order;
use App\Models\OrderPaymentTerm;
use App\Models\ProductVariant;
use App\Models\Quotation;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateOrderAction
{
    /**
     * Create an order from an awarded quotation or as a standalone order for an existing customer.
     *
     * @return array{success: bool, order: Order, error?: string}
     */
    public function execute(Shop $shop, ?int $quotationId = null, ?int $buyerUserId = null): array
    {
        if ($quotationId !== null) {
            return $this->createFromQuotation($shop, $quotationId);
        }

        if ($buyerUserId !== null) {
            return $this->createStandalone($shop, $buyerUserId);
        }

        return [
            'success' => false,
            'order' => new Order,
            'error' => 'Either a quotation ID or buyer user ID must be provided.',
        ];
    }

    /**
     * Create an order from an awarded quotation.
     *
     * @return array{success: bool, order: Order, error?: string}
     */
    private function createFromQuotation(Shop $shop, int $quotationId): array
    {
        return DB::transaction(function () use ($quotationId): array {
            $quotation = Quotation::where('id', $quotationId)
                ->lockForUpdate()
                ->first();

            if (! $quotation || $quotation->status !== 'awarded') {
                return [
                    'success' => false,
                    'order' => new Order,
                    'error' => 'Quotation does not exist or is not in awarded status.',
                ];
            }

            // Ensure no existing order for this quotation
            if (Order::where('quotation_id', $quotationId)->exists()) {
                return [
                    'success' => false,
                    'order' => new Order,
                    'error' => 'An order already exists for this quotation.',
                ];
            }

            $quotation->load(['items', 'rfq.user', 'rfqRecipient']);

            $buyerUser = $quotation->rfq->user;
            $buyerSnapshot = $quotation->buyer_snapshot ?? [];

            // Build buyer snapshot columns
            $snapshotColumns = $this->buildBuyerSnapshotColumns($buyerUser, $buyerSnapshot);

            // Create order
            $order = Order::create([
                'buyer_user_id' => $quotation->rfq->user_id,
                'shop_id' => $quotation->rfqRecipient->shop_id,
                'quotation_id' => $quotation->id,
                'currency' => $quotation->currency,
                'status' => 'draft',
                ...$snapshotColumns,
                'items_total' => '0.00',
                'fees_total' => '0.00',
                'order_total' => '0.00',
            ]);

            // Copy quotation items → order items
            $itemsTotal = '0.00';

            foreach ($quotation->items as $quotationItem) {
                $itemData = $this->resolveItemData($quotationItem);

                $subtotal = bcmul((string) $quotationItem->quantity, $itemData['unit_price'], 2);

                $order->items()->create([
                    'product_id' => $quotationItem->product_id,
                    'variant_id' => $quotationItem->variant_id,
                    'variant_snapshot' => $quotationItem->variant_snapshot,
                    'description' => $itemData['description'],
                    'quantity' => $quotationItem->quantity,
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $subtotal,
                    'sort_order' => $quotationItem->sort_order,
                ]);

                $itemsTotal = bcadd($itemsTotal, $subtotal, 2);
            }

            // Update totals
            $order->update([
                'items_total' => $itemsTotal,
                'fees_total' => '0.00',
                'order_total' => $itemsTotal,
            ]);

            // Create default payment term
            OrderPaymentTerm::create([
                'order_id' => $order->id,
                'type' => 'full',
                'amount' => '0.00',
            ]);

            return [
                'success' => true,
                'order' => $order->load(['items', 'fees']),
            ];
        });
    }

    /**
     * Create a standalone order for an existing customer.
     *
     * @return array{success: bool, order: Order, error?: string}
     */
    private function createStandalone(Shop $shop, int $buyerUserId): array
    {
        return DB::transaction(function () use ($shop, $buyerUserId): array {
            $buyer = User::find($buyerUserId);

            if (! $buyer) {
                return [
                    'success' => false,
                    'order' => new Order,
                    'error' => 'Buyer user does not exist.',
                ];
            }

            // Validate buyer is in seller's customer list
            if (! $this->isExistingCustomer($shop, $buyerUserId)) {
                return [
                    'success' => false,
                    'order' => new Order,
                    'error' => 'Buyer is not an existing customer of this shop.',
                ];
            }

            // Pre-fill snapshot from most recent order or quotation
            $snapshotColumns = $this->prefillSnapshotFromHistory($shop, $buyer);

            $order = Order::create([
                'buyer_user_id' => $buyerUserId,
                'shop_id' => $shop->id,
                'currency' => 'PHP',
                'status' => 'draft',
                ...$snapshotColumns,
                'items_total' => '0.00',
                'fees_total' => '0.00',
                'order_total' => '0.00',
            ]);

            // Create default payment term
            OrderPaymentTerm::create([
                'order_id' => $order->id,
                'type' => 'full',
                'amount' => '0.00',
            ]);

            return [
                'success' => true,
                'order' => $order->load(['items', 'fees']),
            ];
        });
    }

    /**
     * Resolve unit and unit_price from a quotation item using snapshot/variant/freeform fallback.
     *
     * The description is always taken directly from the quotation item itself — it's
     * the full, human-composed text (e.g. "Product Name — variant desc") built by
     * QuotationBuilder::selectVariant(). The variant/snapshot 'description' fields
     * only hold the variant's own raw description fragment, not that composed text,
     * so they must never be used to override it (this previously caused order item
     * descriptions to mismatch their quotation item).
     *
     * @return array{description: string, unit: string, unit_price: string}
     */
    private function resolveItemData(object $quotationItem): array
    {
        // Priority 1: variant_snapshot is non-null — use snapshot for unit only;
        // the negotiated price is always on the quotation item itself.
        if ($quotationItem->variant_snapshot !== null) {
            return [
                'description' => $quotationItem->description,
                'unit' => $quotationItem->variant_snapshot['unit'] ?? $quotationItem->unit,
                'unit_price' => (string) $quotationItem->unit_price,
            ];
        }

        // Priority 2: variant_id is set — read from ProductVariant
        if ($quotationItem->variant_id !== null) {
            $variant = ProductVariant::find($quotationItem->variant_id);

            if ($variant) {
                return [
                    'description' => $quotationItem->description,
                    'unit' => $variant->unit,
                    'unit_price' => (string) $quotationItem->unit_price,
                ];
            }
        }

        // Priority 3: freeform item — copy directly
        return [
            'description' => $quotationItem->description,
            'unit' => $quotationItem->unit,
            'unit_price' => (string) $quotationItem->unit_price,
        ];
    }

    /**
     * Build order snapshot columns from quotation's buyer_snapshot and buyer user.
     *
     * @param  array<string, mixed>  $buyerSnapshot
     * @return array<string, string|null>
     */
    private function buildBuyerSnapshotColumns(User $buyerUser, array $buyerSnapshot): array
    {
        $billingAddress = $buyerSnapshot['billing_address'] ?? [];
        $shippingAddress = $buyerSnapshot['shipping_address'] ?? [];

        return [
            'customer_business_name' => $buyerSnapshot['business_name'] ?? null,
            'customer_contact_name' => mb_trim($buyerUser->first_name.' '.$buyerUser->last_name),
            'customer_contact_phone' => $buyerSnapshot['contact_phone'] ?? $buyerUser->phone ?? null,
            'customer_contact_email' => $buyerSnapshot['contact_email'] ?? $buyerUser->email,
            'billing_address_1' => $billingAddress['address_1'] ?? null,
            'billing_city' => $billingAddress['city'] ?? null,
            'billing_province' => $billingAddress['province'] ?? null,
            'billing_zip_code' => $billingAddress['zip_code'] ?? null,
            'billing_country_code' => $billingAddress['country_code'] ?? 'PH',
            'shipping_address_1' => $shippingAddress['address_1'] ?? null,
            'shipping_city' => $shippingAddress['city'] ?? null,
            'shipping_province' => $shippingAddress['province'] ?? null,
            'shipping_zip_code' => $shippingAddress['zip_code'] ?? null,
            'shipping_country_code' => $shippingAddress['country_code'] ?? 'PH',
        ];
    }

    /**
     * Check if the buyer is an existing customer of the shop.
     */
    private function isExistingCustomer(Shop $shop, int $buyerUserId): bool
    {
        // Check for prior awarded quotation with this shop
        $hasAwardedQuotation = Quotation::where('status', 'awarded')
            ->whereHas('rfqRecipient', fn ($q) => $q->where('shop_id', $shop->id))
            ->whereHas('rfq', fn ($q) => $q->where('user_id', $buyerUserId))
            ->exists();

        if ($hasAwardedQuotation) {
            return true;
        }

        // Check for prior order with this shop
        return Order::where('shop_id', $shop->id)
            ->where('buyer_user_id', $buyerUserId)
            ->exists();
    }

    /**
     * Pre-fill snapshot from the buyer's most recent order or quotation with this shop.
     *
     * @return array<string, string|null>
     */
    private function prefillSnapshotFromHistory(Shop $shop, User $buyer): array
    {
        // Try most recent order with this shop
        $recentOrder = Order::where('shop_id', $shop->id)
            ->where('buyer_user_id', $buyer->id)
            ->latest()
            ->first();

        if ($recentOrder) {
            return [
                'customer_business_name' => $recentOrder->customer_business_name,
                'customer_contact_name' => $recentOrder->customer_contact_name,
                'customer_contact_phone' => $recentOrder->customer_contact_phone,
                'customer_contact_email' => $recentOrder->customer_contact_email,
                'billing_address_1' => $recentOrder->billing_address_1,
                'billing_city' => $recentOrder->billing_city,
                'billing_province' => $recentOrder->billing_province,
                'billing_zip_code' => $recentOrder->billing_zip_code,
                'billing_country_code' => $recentOrder->billing_country_code,
                'shipping_address_1' => $recentOrder->shipping_address_1,
                'shipping_city' => $recentOrder->shipping_city,
                'shipping_province' => $recentOrder->shipping_province,
                'shipping_zip_code' => $recentOrder->shipping_zip_code,
                'shipping_country_code' => $recentOrder->shipping_country_code,
            ];
        }

        // Fall back to most recent awarded quotation's buyer_snapshot
        $recentQuotation = Quotation::where('status', 'awarded')
            ->whereHas('rfqRecipient', fn ($q) => $q->where('shop_id', $shop->id))
            ->whereHas('rfq', fn ($q) => $q->where('user_id', $buyer->id))
            ->with('rfq.user')
            ->latest()
            ->first();

        if ($recentQuotation) {
            $buyerUser = $recentQuotation->rfq->user;
            $buyerSnapshot = $recentQuotation->buyer_snapshot ?? [];

            return $this->buildBuyerSnapshotColumns($buyerUser, $buyerSnapshot);
        }

        // No history — return nulls with required country defaults
        return [
            'customer_business_name' => null,
            'customer_contact_name' => mb_trim($buyer->first_name.' '.$buyer->last_name),
            'customer_contact_phone' => $buyer->phone ?? null,
            'customer_contact_email' => $buyer->email,
            'billing_address_1' => null,
            'billing_city' => null,
            'billing_province' => null,
            'billing_zip_code' => null,
            'billing_country_code' => 'PH',
            'shipping_address_1' => null,
            'shipping_city' => null,
            'shipping_province' => null,
            'shipping_zip_code' => null,
            'shipping_country_code' => 'PH',
        ];
    }
}
