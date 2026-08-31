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
use App\Models\Payout;
use App\Models\PayoutDisbursement;
use App\Models\PayoutItem;
use App\Models\PayoutSetting;
use App\Models\SellerPayoutAccount;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds a self-contained payout playground so the admin payout UI can be
 * exercised end-to-end without walking the RFQ -> quotation -> order flow.
 *
 * It produces, across a few shops:
 *  - Verified / pending / rejected seller payout accounts (payout gating).
 *  - Completed orders with a gross `payout` ledger entry that ARE eligible
 *    (past the dispute-hold window, no active dispute, not yet paid out).
 *  - Completed orders that are NOT eligible (still inside hold window, or
 *    have an active dispute) so the filtering is visible.
 *  - `escrow_hold` ledger entries (funds captured, not yet releasable).
 *  - `refund` ledger entries from resolved disputes (full + partial).
 *  - A "refund from payout" scenario: an order that was paid out and then
 *    clawed back via a refund ledger entry after a later dispute.
 *  - Existing Payout batches in every status (pending / processing /
 *    completed / failed) with matching PayoutItem + PayoutDisbursement rows.
 *
 * Idempotency: keyed off dedicated demo shop storefront names, so re-running
 * `db:seed --class=PayoutDemoSeeder` clears and rebuilds just this dataset.
 *
 * IMPORTANT — ledger semantics used here (production-correct, per specs):
 *   - escrow_hold: GROSS order_total captured at payment. This is the entry the
 *     payout batch releases; CreatePayoutBatchAction deducts fees from it.
 *   - payout: NOTIONAL, already NET of the Phase 3 reporting commission
 *     (config('marketplace.commission_rate')). Written at confirm-receipt for
 *     net-sales reporting only — NEVER summed as "amount released".
 *   - refund: buyer reversal from a resolved dispute.
 *   PayoutItem.ledger_entry_id references the escrow_hold entry, not the payout.
 */
final class PayoutDemoSeeder extends Seeder
{
    private const SHOP_A = 'Bismillah Trading Co. (Payout Demo)';

    private const SHOP_B = 'Al-Barakah Provisions (Payout Demo)';

    private const SHOP_C = 'Sahaba Wholesale (Payout Demo)';

    public function run(): void
    {
        // Make sure the payout tunables exist (hold days, threshold, fees).
        $this->call(PayoutSettingsSeeder::class);

        $holdDays = PayoutSetting::getDisputeHoldDays();          // default 7
        $commissionRate = PayoutSetting::getCommissionFeeRate();  // default 0.03 (real payout fee)
        $reportingRate = (float) config('marketplace.commission_rate', 0.01); // Phase 3 notional

        $this->reset();

        // Shop A: verified account, the main eligible-payout playground.
        $shopA = $this->makeShop(self::SHOP_A, 'seller-payoutA@example.com', 'Yusuf', 'Dela Cruz');
        SellerPayoutAccount::factory()->verified()->create([
            'shop_id' => $shopA->id,
            'account_holder_name' => 'Bismillah Trading Co.',
        ]);

        // Shop B: verified account, used for existing payout batches + claw-back.
        $shopB = $this->makeShop(self::SHOP_B, 'seller-payoutB@example.com', 'Fatima', 'Mangotara');
        SellerPayoutAccount::factory()->verified()->create([
            'shop_id' => $shopB->id,
            'account_holder_name' => 'Al-Barakah Provisions',
        ]);

        // Shop C: UNVERIFIED account — should never appear as eligible.
        $shopC = $this->makeShop(self::SHOP_C, 'seller-payoutC@example.com', 'Omar', 'Abdullah');
        SellerPayoutAccount::factory()->create([
            'shop_id' => $shopC->id,
            'verification_status' => 'pending',
        ]);

        $buyer = $this->makeBuyer('payout-buyer@example.com', 'Khadija', 'Ramos');

        // ── Shop A: three eligible completed orders (past hold window) ──
        // These will show up in /admin/payouts/eligible and can be batched.
        $this->completedEligibleOrder($shopA, $buyer, 'Halal Wagyu Cuts 10kg', 8500.00, $holdDays + 5, $reportingRate);
        $this->completedEligibleOrder($shopA, $buyer, 'Zamzam Dates 20kg Carton', 3200.00, $holdDays + 12, $reportingRate);
        $this->completedEligibleOrder($shopA, $buyer, 'Cold-Pressed Olive Oil 12x1L', 4750.00, $holdDays + 3, $reportingRate);

        // ── Shop A: NOT eligible — still inside the dispute-hold window ──
        $this->completedEligibleOrder($shopA, $buyer, 'Fresh Halal Lamb 15kg', 6100.00, max(1, $holdDays - 4), $reportingRate);

        // ── Shop A: NOT eligible — completed but has an OPEN dispute ──
        $disputedOrder = $this->completedEligibleOrder($shopA, $buyer, 'Basmati Rice 50kg Sack', 2900.00, $holdDays + 8, $reportingRate);
        Dispute::create([
            'order_id' => $disputedOrder->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'return_refund',
            'status' => 'open',
            'reason' => 'Two sacks were torn on arrival; requesting return and refund.',
        ]);

        // ── Shop A: paid order still in escrow (escrow_hold, no payout yet) ──
        $this->paidEscrowOrder($shopA, $buyer, 'Halal Chicken Franks 24x500g', 5400.00);

        // ── Shop A: refunded order — full refund ledger entry from dispute ──
        $this->fullRefundOrder($shopA, $buyer, 'Halal Beef Salami (recalled batch)', 3800.00);

        // ── Shop A: partial refund — refund + remainder payout ledger ──
        $this->partialRefundOrder($shopA, $buyer, 'Assorted Spice Set (short-shipped)', 4200.00, 1500.00, $commissionRate);

        // ── Shop B: existing payout batches in each status ──
        $this->existingPayoutBatch($shopB, $buyer, 'pending', 2, $commissionRate);
        $this->existingPayoutBatch($shopB, $buyer, 'processing', 1, $commissionRate);
        $this->existingPayoutBatch($shopB, $buyer, 'completed', 3, $commissionRate);
        $this->existingPayoutBatch($shopB, $buyer, 'failed', 1, $commissionRate);

        // ── Shop B: "refund from payout" claw-back scenario ──
        // Order was completed, paid out (has PayoutItem in a COMPLETED batch),
        // then a late dispute forced a refund recorded against the ledger.
        $this->refundFromPayoutOrder($shopB, $buyer, 'Halal Gelatin Drums (late defect claim)', 7200.00, $commissionRate);

        // ── Shop C: eligible-looking order but account UNVERIFIED (gated out) ──
        $this->completedEligibleOrder($shopC, $buyer, 'Halal Tuna Flakes 48-pack', 5000.00, $holdDays + 6, $reportingRate);

        $this->command?->info('PayoutDemoSeeder: seeded 3 demo shops, payout accounts, mixed ledger entries (escrow_hold/payout/refund), refund-from-payout claw-back, and payout batches in all statuses.');
        $this->command?->info('  Admin queue:    /admin/payouts');
        $this->command?->info('  Eligible list:  /admin/payouts/eligible');
    }

    /**
     * Remove any previously-seeded demo data so re-seeding is clean.
     */
    private function reset(): void
    {
        $shopIds = Shop::whereIn('storefront_name', [self::SHOP_A, self::SHOP_B, self::SHOP_C])->pluck('id');

        if ($shopIds->isEmpty()) {
            return;
        }

        $orderIds = Order::whereIn('shop_id', $shopIds)->pluck('id');
        $payoutIds = Payout::whereIn('shop_id', $shopIds)->pluck('id');

        PayoutDisbursement::whereIn('payout_id', $payoutIds)->delete();
        PayoutItem::whereIn('payout_id', $payoutIds)->delete();
        Payout::whereIn('id', $payoutIds)->delete();

        LedgerEntry::whereIn('order_id', $orderIds)->delete();
        Dispute::whereIn('order_id', $orderIds)->delete();
        Fulfilment::whereIn('order_id', $orderIds)->delete();

        $termIds = OrderPaymentTerm::whereIn('order_id', $orderIds)->pluck('id');
        PaymentAttempt::whereIn('order_payment_term_id', $termIds)->delete();
        OrderPaymentTerm::whereIn('order_id', $orderIds)->delete();
        OrderItem::whereIn('order_id', $orderIds)->delete();
        OrderFee::whereIn('order_id', $orderIds)->delete();
        Order::whereIn('id', $orderIds)->delete();

        SellerPayoutAccount::whereIn('shop_id', $shopIds)->delete();
        // Leave shops/users in place; they are re-used via firstOrCreate.
    }

    private function makeShop(string $storefrontName, string $email, string $first, string $last): Shop
    {
        $seller = User::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => $first,
                'last_name' => $last,
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]
        );

        if (! $seller->hasRole('seller')) {
            $seller->assignRole('seller');
        }

        $shop = Shop::where('storefront_name', $storefrontName)->first();

        if ($shop) {
            return $shop;
        }

        return Shop::factory()->create([
            'user_id' => $seller->id,
            'status' => 'active',
            'storefront_name' => $storefrontName,
            'registered_business_name' => Str::of($storefrontName)->before(' (')->append(' Corporation')->toString(),
        ]);
    }

    private function makeBuyer(string $email, string $first, string $last): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => $first,
                'last_name' => $last,
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]
        );
    }

    /**
     * Completed order with:
     *   - a GROSS escrow_hold ledger entry (order_total), which the payout batch releases;
     *   - a NOTIONAL payout ledger entry (net of the Phase 3 reporting commission).
     * Eligible for payout only when $completedDaysAgo > dispute_hold_days.
     */
    private function completedEligibleOrder(Shop $shop, User $buyer, string $productName, float $orderTotal, int $completedDaysAgo, float $reportingRate): Order
    {
        $order = $this->makeOrder($shop, $buyer, 'completed', $productName, $orderTotal, [
            'submitted_at' => now()->subDays($completedDaysAgo + 6),
            'paid_at' => now()->subDays($completedDaysAgo + 5),
            'completed_at' => now()->subDays($completedDaysAgo),
        ]);

        $this->makePaymentAttempt($order, 'succeeded');

        $this->ledger($order, 'escrow_hold', $orderTotal, 'Escrow hold on payment');
        Fulfilment::create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'carrier' => 'LBC Express',
            'tracking_code' => 'LBC'.fake()->numerify('##########'),
            'shipped_at' => now()->subDays($completedDaysAgo + 3),
            'delivered_at' => now()->subDays($completedDaysAgo + 1),
        ]);

        // Notional payout entry written at confirm-receipt (net of reporting
        // commission). Reporting-only; the batch releases the escrow_hold above.
        $notionalPayout = round($orderTotal * (1 - $reportingRate), 2);
        $this->ledger($order, 'payout', $notionalPayout, 'Payout on completion (notional, net of commission)');

        return $order;
    }

    /**
     * Paid order whose funds are still escrowed (no payout ledger yet).
     */
    private function paidEscrowOrder(Shop $shop, User $buyer, string $productName, float $orderTotal): Order
    {
        $order = $this->makeOrder($shop, $buyer, 'paid', $productName, $orderTotal, [
            'submitted_at' => now()->subDays(3),
            'paid_at' => now()->subDays(2),
        ]);

        $this->makePaymentAttempt($order, 'succeeded');
        $this->ledger($order, 'escrow_hold', $orderTotal, 'Escrow hold on payment');

        Fulfilment::create([
            'order_id' => $order->id,
            'status' => 'shipped',
            'carrier' => 'JRS Express',
            'tracking_code' => 'JRS'.fake()->numerify('##########'),
            'shipped_at' => now()->subDay(),
        ]);

        return $order;
    }

    /**
     * Refunded order with a full-refund dispute + refund ledger entry.
     */
    private function fullRefundOrder(Shop $shop, User $buyer, string $productName, float $orderTotal): Order
    {
        $order = $this->makeOrder($shop, $buyer, 'refunded', $productName, $orderTotal, [
            'submitted_at' => now()->subDays(20),
            'paid_at' => now()->subDays(19),
        ]);

        $this->makePaymentAttempt($order, 'succeeded');
        $this->ledger($order, 'escrow_hold', $orderTotal, 'Escrow hold on payment');

        Fulfilment::create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'shipped_at' => now()->subDays(16),
            'delivered_at' => now()->subDays(13),
        ]);

        Dispute::create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'refund_only',
            'status' => 'resolved_refunded',
            'reason' => 'Halal certification could not be verified for this batch.',
            'resolution_type' => 'full_refund',
            'resolution_amount' => $orderTotal,
            'resolution_notes' => 'Full refund issued after cert audit failed.',
            'resolved_by_user_id' => $shop->user_id,
            'resolved_at' => now()->subDays(11),
        ]);

        $this->ledger($order, 'refund', $orderTotal, 'Dispute resolution: full_refund');

        return $order;
    }

    /**
     * Partial-refund order: refund ledger entry + remainder payout ledger entry
     * (mirrors ResolveDisputeAction's partial_refund path).
     */
    private function partialRefundOrder(Shop $shop, User $buyer, string $productName, float $orderTotal, float $refundAmount, float $commissionRate): Order
    {
        $order = $this->makeOrder($shop, $buyer, 'refunded', $productName, $orderTotal, [
            'submitted_at' => now()->subDays(18),
            'paid_at' => now()->subDays(17),
        ]);

        $this->makePaymentAttempt($order, 'succeeded');
        $this->ledger($order, 'escrow_hold', $orderTotal, 'Escrow hold on payment');

        Fulfilment::create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'shipped_at' => now()->subDays(14),
            'delivered_at' => now()->subDays(11),
        ]);

        Dispute::create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'refund_only',
            'status' => 'resolved_refunded',
            'reason' => 'Three of the eight spice tins were missing from the carton.',
            'resolution_type' => 'partial_refund',
            'resolution_amount' => $refundAmount,
            'resolution_notes' => 'Partial refund for the short-shipped items.',
            'resolved_by_user_id' => $shop->user_id,
            'resolved_at' => now()->subDays(9),
        ]);

        $this->ledger($order, 'refund', $refundAmount, 'Dispute resolution: partial_refund');

        $remainder = round($orderTotal - $refundAmount, 2);
        $payout = round($remainder * (1 - $commissionRate), 2);
        if ($payout > 0) {
            $this->ledger($order, 'payout', $payout, 'Partial payout after dispute resolution');
        }

        return $order;
    }

    /**
     * "Refund from payout" claw-back: order was completed, batched, and paid out
     * (PayoutItem sits in a COMPLETED payout), then a late defect claim forced a
     * refund recorded against the ledger. Net ledger position goes negative,
     * which surfaces on the financial ledger as money owed back.
     */
    private function refundFromPayoutOrder(Shop $shop, User $buyer, string $productName, float $orderTotal, float $commissionRate): Order
    {
        $order = $this->makeOrder($shop, $buyer, 'refunded', $productName, $orderTotal, [
            'submitted_at' => now()->subDays(40),
            'paid_at' => now()->subDays(39),
            'completed_at' => now()->subDays(30),
        ]);

        $this->makePaymentAttempt($order, 'succeeded');
        $escrowLedger = $this->ledger($order, 'escrow_hold', $orderTotal, 'Escrow hold on payment');

        Fulfilment::create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'shipped_at' => now()->subDays(36),
            'delivered_at' => now()->subDays(33),
        ]);

        // Notional payout entry from confirm-receipt (net of reporting commission).
        $notionalPayout = round($orderTotal * (1 - $commissionRate), 2);
        $this->ledger($order, 'payout', $notionalPayout, 'Payout on completion (notional, net of commission)');

        // A completed payout batch that already disbursed this order. Fees are
        // computed from the GROSS escrowed amount; the item links the escrow_hold.
        $txFee = PayoutSetting::getTransactionFeeAmount();
        $commFee = round($orderTotal * $commissionRate, 2);
        $net = round($orderTotal - $txFee - $commFee, 2);

        $payout = Payout::create([
            'shop_id' => $shop->id,
            'status' => 'completed',
            'currency' => 'PHP',
            'gross_amount' => $orderTotal,
            'total_transaction_fees' => $txFee,
            'total_commission_fees' => $commFee,
            'net_amount' => $net,
            'orders_count' => 1,
            'released_by_id' => $this->adminId(),
            'released_at' => now()->subDays(28),
            'completed_at' => now()->subDays(28),
        ]);

        PayoutItem::create([
            'payout_id' => $payout->id,
            'order_id' => $order->id,
            'ledger_entry_id' => $escrowLedger->id,
            'escrowed_amount' => $orderTotal,
            'transaction_fee' => $txFee,
            'commission_fee' => $commFee,
            'net_amount' => $net,
        ]);

        PayoutDisbursement::factory()->succeeded()->create([
            'payout_id' => $payout->id,
            'amount' => $net,
            'attempted_at' => now()->subDays(28),
            'succeeded_at' => now()->subDays(28),
        ]);

        // Late dispute → refund recorded AFTER the payout (the claw-back).
        Dispute::create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'refund_only',
            'status' => 'resolved_refunded',
            'reason' => 'Latent contamination discovered after use; lab report attached.',
            'resolution_type' => 'full_refund',
            'resolution_amount' => $orderTotal,
            'resolution_notes' => 'Refund clawed back from an already-released payout.',
            'resolved_by_user_id' => $this->adminId() ?? $shop->user_id,
            'resolved_at' => now()->subDays(2),
        ]);

        $this->ledger($order, 'refund', $orderTotal, 'Refund from payout (post-disbursement claw-back)');

        return $order;
    }

    /**
     * Build an existing payout batch (with items + disbursements) in a status.
     */
    private function existingPayoutBatch(Shop $shop, User $buyer, string $status, int $ordersCount, float $commissionRate): Payout
    {
        $txFeeEach = PayoutSetting::getTransactionFeeAmount();

        $items = [];
        $gross = 0.0;
        $totalTx = 0.0;
        $totalComm = 0.0;
        $totalNet = 0.0;

        for ($i = 0; $i < $ordersCount; $i++) {
            $orderTotal = fake()->randomFloat(2, 2000, 9000);
            $order = $this->makeOrder($shop, $buyer, 'completed', 'Batched item '.($i + 1), $orderTotal, [
                'submitted_at' => now()->subDays(25),
                'paid_at' => now()->subDays(24),
                'completed_at' => now()->subDays(15),
            ]);
            $this->makePaymentAttempt($order, 'succeeded');
            $escrowLedger = $this->ledger($order, 'escrow_hold', $orderTotal, 'Escrow hold on payment');
            $notionalPayout = round($orderTotal * (1 - $commissionRate), 2);
            $this->ledger($order, 'payout', $notionalPayout, 'Payout on completion (notional, net of commission)');
            Fulfilment::create([
                'order_id' => $order->id,
                'status' => 'delivered',
                'shipped_at' => now()->subDays(20),
                'delivered_at' => now()->subDays(18),
            ]);

            $commFee = round($orderTotal * $commissionRate, 2);
            $net = round($orderTotal - $txFeeEach - $commFee, 2);

            $gross += $orderTotal;
            $totalTx += $txFeeEach;
            $totalComm += $commFee;
            $totalNet += $net;

            $items[] = [
                'order_id' => $order->id,
                'ledger_entry_id' => $escrowLedger->id,
                'escrowed_amount' => $orderTotal,
                'transaction_fee' => $txFeeEach,
                'commission_fee' => $commFee,
                'net_amount' => $net,
            ];
        }

        $attrs = [
            'shop_id' => $shop->id,
            'status' => $status,
            'currency' => 'PHP',
            'gross_amount' => round($gross, 2),
            'total_transaction_fees' => round($totalTx, 2),
            'total_commission_fees' => round($totalComm, 2),
            'net_amount' => round($totalNet, 2),
            'orders_count' => $ordersCount,
        ];

        $attrs += match ($status) {
            'processing' => ['released_by_id' => $this->adminId(), 'released_at' => now()->subMinutes(20)],
            'completed' => ['released_by_id' => $this->adminId(), 'released_at' => now()->subDays(10), 'completed_at' => now()->subDays(10)],
            'failed' => ['released_by_id' => $this->adminId(), 'released_at' => now()->subDays(1), 'failed_at' => now()->subDays(1), 'failure_reason' => 'DLT disbursement rejected: beneficiary account name mismatch.'],
            default => [],
        };

        $payout = Payout::create($attrs);

        foreach ($items as $item) {
            PayoutItem::create(['payout_id' => $payout->id, ...$item]);
        }

        // Disbursement history to match the status.
        match ($status) {
            'completed' => PayoutDisbursement::factory()->succeeded()->create([
                'payout_id' => $payout->id,
                'amount' => $payout->net_amount,
                'attempted_at' => now()->subDays(10),
                'succeeded_at' => now()->subDays(10),
            ]),
            'failed' => PayoutDisbursement::factory()->failed()->create([
                'payout_id' => $payout->id,
                'amount' => $payout->net_amount,
                'attempted_at' => now()->subDays(1),
                'failed_at' => now()->subDays(1),
            ]),
            'processing' => PayoutDisbursement::factory()->create([
                'payout_id' => $payout->id,
                'amount' => $payout->net_amount,
                'attempted_at' => now()->subMinutes(20),
            ]),
            default => null,
        };

        return $payout;
    }

    /**
     * @param  array<string, mixed>  $timestamps
     */
    private function makeOrder(Shop $shop, User $buyer, string $status, string $productName, float $orderTotal, array $timestamps = []): Order
    {
        $shippingFee = fake()->randomFloat(2, 150, 800);
        $subtotal = round($orderTotal - $shippingFee, 2);
        if ($subtotal <= 0) {
            $subtotal = $orderTotal;
            $shippingFee = 0.0;
        }
        $quantity = fake()->numberBetween(5, 40);
        $unitPrice = round($subtotal / $quantity, 2);

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
            'unit' => fake()->randomElement(['kg', 'sack', 'box', 'carton', 'pcs']),
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

    private function makePaymentAttempt(Order $order, string $status): PaymentAttempt
    {
        $term = $order->paymentTerms()->first();

        return PaymentAttempt::create([
            'order_payment_term_id' => $term->id,
            'status' => $status,
            'dlt_merchant_transaction_id' => 'order'.$order->id.Str::upper(Str::random(8)),
            'dlt_quick_link_token' => fake()->uuid(),
            'dlt_payment_url' => 'https://checkout-ql.dltc.com.ph/?demo=true',
            'dlt_payment_status' => $status === 'succeeded' ? 'PAID' : null,
            'succeeded_at' => $status === 'succeeded' ? ($order->paid_at ?? now()) : null,
        ]);
    }

    private function ledger(Order $order, string $type, float $amount, string $note): LedgerEntry
    {
        return LedgerEntry::create([
            'order_id' => $order->id,
            'type' => $type,
            'amount' => $amount,
            'note' => $note,
        ]);
    }

    private function adminId(): ?int
    {
        return User::role('admin')->value('id');
    }
}
