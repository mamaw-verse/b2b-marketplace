<?php

declare(strict_types=1);

use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderPaymentTerm;
use App\Models\PaymentAttempt;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

// ──────────────────────────────────────────────────────────────
// Buyer Transaction History (GET /api/v1/transactions)
// ──────────────────────────────────────────────────────────────

test('buyer sees their own succeeded payment attempts in transaction list', function () {
    $buyer = User::factory()->create();
    $order = Order::factory()->paid()->create(['buyer_user_id' => $buyer->id]);
    $term = OrderPaymentTerm::factory()->create(['order_id' => $order->id]);
    PaymentAttempt::factory()->succeeded()->create([
        'order_payment_term_id' => $term->id,
        'dlt_amount' => '5000.00',
    ]);

    $response = $this->actingAs($buyer, 'sanctum')
        ->getJson('/api/v1/transactions');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'payment')
        ->assertJsonPath('data.0.status', 'paid')
        ->assertJsonPath('data.0.amount', '5000.00')
        ->assertJsonPath('data.0.order_id', $order->id);
});

test('buyer sees refunds in transaction list', function () {
    $buyer = User::factory()->create();
    $order = Order::factory()->refunded()->create(['buyer_user_id' => $buyer->id]);
    LedgerEntry::factory()->refund()->create([
        'order_id' => $order->id,
        'amount' => '2500.00',
    ]);

    $response = $this->actingAs($buyer, 'sanctum')
        ->getJson('/api/v1/transactions');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'refund')
        ->assertJsonPath('data.0.status', 'refunded')
        ->assertJsonPath('data.0.amount', '2500.00');
});

test('buyer cannot see other users transactions', function () {
    $buyer = User::factory()->create();
    $otherBuyer = User::factory()->create();

    $otherOrder = Order::factory()->paid()->create(['buyer_user_id' => $otherBuyer->id]);
    $term = OrderPaymentTerm::factory()->create(['order_id' => $otherOrder->id]);
    PaymentAttempt::factory()->succeeded()->create(['order_payment_term_id' => $term->id]);

    $response = $this->actingAs($buyer, 'sanctum')
        ->getJson('/api/v1/transactions');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

test('date range filter works correctly for buyer transactions', function () {
    $buyer = User::factory()->create();
    $order = Order::factory()->paid()->create(['buyer_user_id' => $buyer->id]);
    $term = OrderPaymentTerm::factory()->create(['order_id' => $order->id]);

    // Payment from 10 days ago
    PaymentAttempt::factory()->succeeded()->create([
        'order_payment_term_id' => $term->id,
        'dlt_amount' => '3000.00',
        'succeeded_at' => now()->subDays(10),
    ]);

    // Payment from today
    PaymentAttempt::factory()->succeeded()->create([
        'order_payment_term_id' => $term->id,
        'dlt_amount' => '4000.00',
        'succeeded_at' => now(),
    ]);

    // Filter only today
    $response = $this->actingAs($buyer, 'sanctum')
        ->getJson('/api/v1/transactions?from='.now()->format('Y-m-d').'&to='.now()->format('Y-m-d'));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.amount', '4000.00');
});

test('status filter works for buyer transactions', function () {
    $buyer = User::factory()->create();
    $order = Order::factory()->paid()->create(['buyer_user_id' => $buyer->id]);
    $term = OrderPaymentTerm::factory()->create(['order_id' => $order->id]);
    PaymentAttempt::factory()->succeeded()->create([
        'order_payment_term_id' => $term->id,
        'dlt_amount' => '1000.00',
    ]);
    LedgerEntry::factory()->refund()->create([
        'order_id' => $order->id,
        'amount' => '500.00',
    ]);

    // Filter only refunded
    $response = $this->actingAs($buyer, 'sanctum')
        ->getJson('/api/v1/transactions?status=refunded');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'refund');
});

// ──────────────────────────────────────────────────────────────
// Seller Earnings (GET /api/v1/hub/earnings)
// ──────────────────────────────────────────────────────────────

test('seller earnings endpoint returns expected summary with pending balance', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    // Create completed orders with escrow holds
    $order1 = Order::factory()->completed()->create(['shop_id' => $shop->id]);
    $order2 = Order::factory()->completed()->create(['shop_id' => $shop->id]);

    LedgerEntry::factory()->escrowHold()->create(['order_id' => $order1->id, 'amount' => '10000.00']);
    LedgerEntry::factory()->escrowHold()->create(['order_id' => $order2->id, 'amount' => '15000.00']);

    $response = $this->actingAs($seller, 'sanctum')
        ->getJson('/api/v1/hub/earnings');

    $response->assertOk()
        ->assertJsonPath('data.summary.total_earned', '0.00')
        ->assertJsonPath('data.summary.total_released', '0.00')
        ->assertJsonPath('data.summary.pending_balance', '25000.00')
        ->assertJsonPath('data.summary.total_fees_paid', '0.00');
});

test('seller earnings shows ledger entries in transaction list', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $order = Order::factory()->completed()->create(['shop_id' => $shop->id]);

    LedgerEntry::factory()->escrowHold()->create(['order_id' => $order->id]);
    LedgerEntry::factory()->payout()->create(['order_id' => $order->id]);

    $response = $this->actingAs($seller, 'sanctum')
        ->getJson('/api/v1/hub/earnings');

    $response->assertOk()
        ->assertJsonCount(2, 'data.transactions');
});

test('seller without active shop cannot access earnings', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $response = $this->actingAs($seller, 'sanctum')
        ->getJson('/api/v1/hub/earnings');

    $response->assertForbidden();
});

// ──────────────────────────────────────────────────────────────
// Admin Ledger (GET /api/v1/admin/ledger)
// ──────────────────────────────────────────────────────────────

test('admin ledger shows platform-wide entries', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $order1 = Order::factory()->paid()->create();
    $order2 = Order::factory()->completed()->create();

    LedgerEntry::factory()->escrowHold()->create(['order_id' => $order1->id]);
    LedgerEntry::factory()->payout()->create(['order_id' => $order2->id]);
    LedgerEntry::factory()->refund()->create(['order_id' => $order1->id]);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/ledger');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('admin can filter ledger by type', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $order = Order::factory()->paid()->create();
    LedgerEntry::factory()->escrowHold()->create(['order_id' => $order->id]);
    LedgerEntry::factory()->refund()->create(['order_id' => $order->id]);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/ledger?type=refund');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'refund');
});

test('admin can filter ledger by date range', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $order = Order::factory()->paid()->create();
    LedgerEntry::factory()->escrowHold()->create([
        'order_id' => $order->id,
        'created_at' => now()->subDays(30),
    ]);
    LedgerEntry::factory()->refund()->create([
        'order_id' => $order->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/ledger?from='.now()->format('Y-m-d').'&to='.now()->format('Y-m-d'));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'refund');
});

test('non-admin cannot access admin ledger', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $response = $this->actingAs($seller, 'sanctum')
        ->getJson('/api/v1/admin/ledger');

    $response->assertForbidden();
});

test('unauthenticated user cannot access admin ledger', function () {
    $response = $this->getJson('/api/v1/admin/ledger');

    $response->assertUnauthorized();
});

test('cursor pagination works correctly for buyer transactions', function () {
    $buyer = User::factory()->create();
    $order = Order::factory()->paid()->create(['buyer_user_id' => $buyer->id]);
    $term = OrderPaymentTerm::factory()->create(['order_id' => $order->id]);

    // Create 5 payments
    for ($i = 0; $i < 5; $i++) {
        PaymentAttempt::factory()->succeeded()->create([
            'order_payment_term_id' => $term->id,
            'dlt_amount' => (string) (($i + 1) * 1000).'.00',
            'succeeded_at' => now()->subMinutes(5 - $i),
        ]);
    }

    // Page 1 with limit 2
    $response = $this->actingAs($buyer, 'sanctum')
        ->getJson('/api/v1/transactions?limit=2');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['meta' => ['next_cursor']]);

    // Ensure next_cursor exists
    $nextCursor = $response->json('meta.next_cursor');
    expect($nextCursor)->not->toBeNull();

    // Page 2
    $response2 = $this->actingAs($buyer, 'sanctum')
        ->getJson('/api/v1/transactions?limit=2&cursor='.$nextCursor);

    $response2->assertOk()
        ->assertJsonCount(2, 'data');
});

test('admin can filter ledger by shop_id', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $shop1 = Shop::factory()->create();
    $shop2 = Shop::factory()->create();
    $order1 = Order::factory()->paid()->create(['shop_id' => $shop1->id]);
    $order2 = Order::factory()->paid()->create(['shop_id' => $shop2->id]);

    LedgerEntry::factory()->escrowHold()->create(['order_id' => $order1->id]);
    LedgerEntry::factory()->refund()->create(['order_id' => $order2->id]);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/ledger?shop_id='.$shop1->id);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.order_id', $order1->id);
});
