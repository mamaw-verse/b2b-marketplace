<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Dispute;
use App\Models\Fulfilment;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderFee;
use App\Models\OrderItem;
use App\Models\OrderPaymentTerm;
use App\Models\PaymentAttempt;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

final class Phase3Seeder extends Seeder
{
    /**
     * Seed sample Phase 3 data: orders in various states, fulfilments,
     * payment attempts, disputes, and ledger entries.
     */
    public function run(): void
    {
        // Use existing seeded users/shops or create fresh ones
        $seller = User::where('email', 'test@example.com')->first();
        $shop = $seller?->shop;

        if (! $shop) {
            $seller = User::factory()->create([
                'first_name' => 'Seller',
                'last_name' => 'Demo',
                'email' => 'seller-demo@example.com',
                'email_verified_at' => now(),
            ]);
            $shop = Shop::factory()->create([
                'user_id' => $seller->id,
                'status' => 'active',
                'storefront_name' => 'Maslaha Foods Co.',
                'registered_business_name' => 'Maslaha Foods Corporation',
            ]);
        }

        $buyer = User::factory()->create([
            'first_name' => 'Amina',
            'last_name' => 'Reyes',
            'email' => 'buyer-demo@example.com',
            'email_verified_at' => now(),
        ]);

        $buyer2 = User::factory()->create([
            'first_name' => 'Ibrahim',
            'last_name' => 'Santos',
            'email' => 'buyer2-demo@example.com',
            'email_verified_at' => now(),
        ]);

        // 1. Draft order
        $this->createOrder($shop, $buyer, 'draft', 'Halal Coconut Flour 25kg');

        // 2. Pending payment order
        $this->createOrder($shop, $buyer, 'pending_payment', 'Organic Halal Chicken Breast');

        // 3. Paid order (awaiting fulfilment)
        $paidOrder = $this->createOrder($shop, $buyer2, 'paid', 'Premium Halal Beef Tapa');
        $this->createPaymentAttempt($paidOrder, 'succeeded');
        LedgerEntry::create([
            'order_id' => $paidOrder->id,
            'type' => 'escrow_hold',
            'amount' => $paidOrder->order_total,
            'note' => 'Escrow hold on payment',
        ]);

        // 4. Paid + shipped order
        $shippedOrder = $this->createOrder($shop, $buyer, 'paid', 'Halal Spice Mix Assorted');
        $this->createPaymentAttempt($shippedOrder, 'succeeded');
        LedgerEntry::create([
            'order_id' => $shippedOrder->id,
            'type' => 'escrow_hold',
            'amount' => $shippedOrder->order_total,
            'note' => 'Escrow hold on payment',
        ]);
        Fulfilment::create([
            'order_id' => $shippedOrder->id,
            'status' => 'shipped',
            'carrier' => 'JRS Express',
            'tracking_code' => 'JRS'.fake()->numerify('##########'),
            'shipped_at' => now()->subDays(2),
        ]);

        // 5. Completed order
        $completedOrder = $this->createOrder($shop, $buyer2, 'completed', 'Halal Rendang Paste 500g');
        $this->createPaymentAttempt($completedOrder, 'succeeded');
        LedgerEntry::create([
            'order_id' => $completedOrder->id,
            'type' => 'escrow_hold',
            'amount' => $completedOrder->order_total,
            'note' => 'Escrow hold on payment',
        ]);
        Fulfilment::create([
            'order_id' => $completedOrder->id,
            'status' => 'delivered',
            'carrier' => 'LBC Express',
            'tracking_code' => 'LBC'.fake()->numerify('##########'),
            'shipped_at' => now()->subDays(7),
            'delivered_at' => now()->subDays(4),
        ]);
        $commissionRate = (float) config('marketplace.commission_rate', 0.01);
        $payout = round((float) $completedOrder->order_total * (1 - $commissionRate), 2);
        LedgerEntry::create([
            'order_id' => $completedOrder->id,
            'type' => 'payout',
            'amount' => $payout,
            'note' => 'Payout on completion',
        ]);

        // 6. Cancelled order
        $this->createOrder($shop, $buyer, 'cancelled', 'Halal Marshmallows');

        // 7. Refunded order (with dispute)
        $refundedOrder = $this->createOrder($shop, $buyer2, 'refunded', 'Halal Gelatin Powder');
        $this->createPaymentAttempt($refundedOrder, 'succeeded');
        LedgerEntry::create([
            'order_id' => $refundedOrder->id,
            'type' => 'escrow_hold',
            'amount' => $refundedOrder->order_total,
            'note' => 'Escrow hold on payment',
        ]);
        Fulfilment::create([
            'order_id' => $refundedOrder->id,
            'status' => 'delivered',
            'shipped_at' => now()->subDays(14),
            'delivered_at' => now()->subDays(10),
        ]);
        Dispute::create([
            'order_id' => $refundedOrder->id,
            'opened_by_user_id' => $buyer2->id,
            'type' => 'refund_only',
            'status' => 'resolved_refunded',
            'reason' => 'Product arrived damaged — packaging was wet and gelatin clumped.',
            'resolution_type' => 'full_refund',
            'resolution_amount' => $refundedOrder->order_total,
            'resolution_notes' => 'Full refund issued. Photos confirmed damage.',
            'resolved_by_user_id' => $seller->id,
            'resolved_at' => now()->subDays(8),
        ]);
        LedgerEntry::create([
            'order_id' => $refundedOrder->id,
            'type' => 'refund',
            'amount' => $refundedOrder->order_total,
            'note' => 'Dispute resolution: full_refund',
        ]);

        // 8. Order with active dispute (open)
        $disputedOrder = $this->createOrder($shop, $buyer, 'paid', 'Halal Soy Sauce 1L');
        $this->createPaymentAttempt($disputedOrder, 'succeeded');
        LedgerEntry::create([
            'order_id' => $disputedOrder->id,
            'type' => 'escrow_hold',
            'amount' => $disputedOrder->order_total,
            'note' => 'Escrow hold on payment',
        ]);
        Fulfilment::create([
            'order_id' => $disputedOrder->id,
            'status' => 'delivered',
            'shipped_at' => now()->subDays(5),
            'delivered_at' => now()->subDays(3),
        ]);
        Dispute::create([
            'order_id' => $disputedOrder->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'return_refund',
            'status' => 'open',
            'reason' => 'Received wrong variant — ordered light soy but got dark soy sauce. Need to return and get correct item.',
        ]);

        $this->command->info('Phase3Seeder: Created 8 orders in various states (draft, pending_payment, paid, shipped, completed, cancelled, refunded, disputed).');
    }

    private function createOrder(Shop $shop, User $buyer, string $status, string $productName): Order
    {
        $quantity = fake()->numberBetween(5, 50);
        $unitPrice = fake()->randomFloat(2, 150, 5000);
        $subtotal = round($quantity * $unitPrice, 2);
        $shippingFee = fake()->randomFloat(2, 150, 1500);
        $orderTotal = round($subtotal + $shippingFee, 2);

        $timestamps = match ($status) {
            'pending_payment' => ['submitted_at' => now()->subDays(1)],
            'paid' => ['submitted_at' => now()->subDays(3), 'paid_at' => now()->subDays(2)],
            'completed' => ['submitted_at' => now()->subDays(14), 'paid_at' => now()->subDays(13), 'completed_at' => now()->subDays(3)],
            'cancelled' => ['submitted_at' => now()->subDays(5), 'cancelled_at' => now()->subDays(4)],
            'refunded' => ['submitted_at' => now()->subDays(20), 'paid_at' => now()->subDays(19)],
            default => [],
        };

        $order = Order::create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'status' => $status,
            'customer_business_name' => fake()->company(),
            'customer_contact_name' => $buyer->first_name.' '.$buyer->last_name,
            'customer_contact_phone' => fake()->phoneNumber(),
            'customer_contact_email' => $buyer->email,
            'billing_address_1' => fake()->streetAddress(),
            'billing_city' => fake()->city(),
            'billing_province' => fake()->state(),
            'billing_zip_code' => fake()->postcode(),
            'billing_country_code' => 'PH',
            'shipping_address_1' => fake()->streetAddress(),
            'shipping_city' => fake()->city(),
            'shipping_province' => fake()->state(),
            'shipping_zip_code' => fake()->postcode(),
            'shipping_country_code' => 'PH',
            'currency' => 'PHP',
            'items_total' => $subtotal,
            'fees_total' => $shippingFee,
            'order_total' => $orderTotal,
            ...$timestamps,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'description' => $productName,
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['kg', 'sack', 'box', 'pcs', 'bottle']),
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'sort_order' => 0,
        ]);

        OrderFee::create([
            'order_id' => $order->id,
            'label' => 'Shipping',
            'amount' => $shippingFee,
            'sort_order' => 0,
        ]);

        OrderPaymentTerm::create([
            'order_id' => $order->id,
            'type' => 'full',
            'amount' => $orderTotal,
        ]);

        return $order;
    }

    private function createPaymentAttempt(Order $order, string $status): PaymentAttempt
    {
        $paymentTerm = $order->paymentTerms()->first();

        return PaymentAttempt::create([
            'order_payment_term_id' => $paymentTerm->id,
            'status' => $status,
            'dlt_merchant_transaction_id' => 'order-'.$order->id.'-'.fake()->unique()->uuid(),
            'dlt_quick_link_token' => fake()->uuid(),
            'dlt_payment_url' => 'https://checkout-ql.dltc.com.ph/?demo=true',
            'dlt_payment_status' => $status === 'succeeded' ? 'PAID' : null,
            'succeeded_at' => $status === 'succeeded' ? $order->paid_at : null,
        ]);
    }
}
