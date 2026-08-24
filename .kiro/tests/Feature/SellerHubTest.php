<?php

declare(strict_types=1);

use App\Models\Dispute;
use App\Models\Fulfilment;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function setupHubScenario(): array
{
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $buyer = User::factory()->create();

    return [$seller, $shop, $buyer];
}

describe('Seller Hub', function () {
    test('dashboard returns correct counts', function () {
        [$seller, $shop, $buyer] = setupHubScenario();

        // Draft order
        Order::factory()->create(['shop_id' => $shop->id, 'buyer_user_id' => $buyer->id, 'status' => 'draft']);
        // Paid order (in progress)
        $paidOrder = Order::factory()->paid()->create(['shop_id' => $shop->id, 'buyer_user_id' => $buyer->id]);
        // Paid order with dispute
        $disputedOrder = Order::factory()->paid()->create(['shop_id' => $shop->id, 'buyer_user_id' => $buyer->id]);
        Fulfilment::factory()->delivered()->create(['order_id' => $disputedOrder->id]);
        Dispute::factory()->create(['order_id' => $disputedOrder->id, 'opened_by_user_id' => $buyer->id, 'status' => 'open']);

        $response = $this->actingAs($seller)->getJson('/api/v1/hub/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.draft_orders', 1)
            ->assertJsonPath('data.orders_in_progress', 2)
            ->assertJsonPath('data.open_disputes', 1);
    });

    test('customers list returns only buyers who have transacted with this shop', function () {
        [$seller, $shop, $buyer] = setupHubScenario();
        $stranger = User::factory()->create();

        // Buyer has an order with this shop
        Order::factory()->completed()->create(['shop_id' => $shop->id, 'buyer_user_id' => $buyer->id]);

        $response = $this->actingAs($seller)->getJson('/api/v1/hub/customers');

        $response->assertOk();
        $customerIds = collect($response->json('data'))->pluck('id')->all();
        expect($customerIds)->toContain($buyer->id)
            ->not->toContain($stranger->id);
    });

    test('reports: gross sales = sum of order totals for completed/refunded orders in window', function () {
        [$seller, $shop, $buyer] = setupHubScenario();

        $order1 = Order::factory()->completed()->create([
            'shop_id' => $shop->id,
            'buyer_user_id' => $buyer->id,
            'order_total' => 50000.00,
            'paid_at' => now()->subDays(5),
        ]);
        $order2 = Order::factory()->completed()->create([
            'shop_id' => $shop->id,
            'buyer_user_id' => $buyer->id,
            'order_total' => 30000.00,
            'paid_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($seller)->getJson('/api/v1/hub/reports?window=30');

        $response->assertOk();
        $data = $response->json('data');
        expect((float) $data['gross_sales'])->toBe(80000.00)
            ->and($data['orders_placed'])->toBe(2);
    });

    test('reports: net sales = gross - refunds - commission', function () {
        [$seller, $shop, $buyer] = setupHubScenario();

        $order = Order::factory()->create([
            'shop_id' => $shop->id,
            'buyer_user_id' => $buyer->id,
            'status' => 'refunded',
            'order_total' => 100000.00,
            'paid_at' => now()->subDays(5),
        ]);

        LedgerEntry::factory()->refund()->create([
            'order_id' => $order->id,
            'amount' => 10000.00,
        ]);

        $response = $this->actingAs($seller)->getJson('/api/v1/hub/reports?window=30');

        $response->assertOk();
        $data = $response->json('data');
        // gross = 100000, refunds = 10000, commission = 100000 * 0.01 = 1000
        // net = 100000 - 10000 - 1000 = 89000
        expect((float) $data['gross_sales'])->toBe(100000.00)
            ->and((float) $data['total_refunds'])->toBe(10000.00)
            ->and((float) $data['platform_commission'])->toBe(1000.00)
            ->and((float) $data['net_sales'])->toBe(89000.00);
    });

    test('reports: new vs returning customers counted correctly', function () {
        [$seller, $shop, $buyer] = setupHubScenario();
        $newBuyer = User::factory()->create();

        // Buyer has an old order (before window) and a new order
        Order::factory()->completed()->create([
            'shop_id' => $shop->id,
            'buyer_user_id' => $buyer->id,
            'order_total' => 10000,
            'paid_at' => now()->subDays(60),
        ]);
        Order::factory()->completed()->create([
            'shop_id' => $shop->id,
            'buyer_user_id' => $buyer->id,
            'order_total' => 20000,
            'paid_at' => now()->subDays(5),
        ]);

        // New buyer only has orders in current window
        Order::factory()->completed()->create([
            'shop_id' => $shop->id,
            'buyer_user_id' => $newBuyer->id,
            'order_total' => 15000,
            'paid_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($seller)->getJson('/api/v1/hub/reports?window=30');

        $response->assertOk();
        $data = $response->json('data');
        expect($data['new_customers'])->toBe(1)
            ->and($data['returning_customers'])->toBe(1);
    });

    test('leaderboards: top products ranked by revenue correctly', function () {
        [$seller, $shop, $buyer] = setupHubScenario();

        $order = Order::factory()->completed()->create([
            'shop_id' => $shop->id,
            'buyer_user_id' => $buyer->id,
            'paid_at' => now()->subDays(5),
        ]);

        // Clear any factory-created items and add explicit ones
        $order->items()->delete();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'description' => 'Product A',
            'subtotal' => 50000,
            'quantity' => 10,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'description' => 'Product B',
            'subtotal' => 30000,
            'quantity' => 5,
        ]);

        $response = $this->actingAs($seller)->getJson('/api/v1/hub/leaderboards?type=products&rank_by=revenue');

        $response->assertOk();
        $data = $response->json('data');
        expect(count($data))->toBeGreaterThanOrEqual(2)
            ->and($data[0]['product_name'])->toBe('Product A')
            ->and($data[0]['rank'])->toBe(1)
            ->and((float) $data[0]['revenue'])->toBe(50000.00);
    });

    test('leaderboards: time window filter works', function () {
        [$seller, $shop, $buyer] = setupHubScenario();

        // Old order (outside 30-day window)
        $oldOrder = Order::factory()->completed()->create([
            'shop_id' => $shop->id,
            'buyer_user_id' => $buyer->id,
            'paid_at' => now()->subDays(60),
        ]);
        OrderItem::factory()->create(['order_id' => $oldOrder->id, 'description' => 'Old Product', 'subtotal' => 99999]);

        // Recent order
        $recentOrder = Order::factory()->completed()->create([
            'shop_id' => $shop->id,
            'buyer_user_id' => $buyer->id,
            'paid_at' => now()->subDays(5),
        ]);
        OrderItem::factory()->create(['order_id' => $recentOrder->id, 'description' => 'Recent Product', 'subtotal' => 10000]);

        $response = $this->actingAs($seller)->getJson('/api/v1/hub/leaderboards?type=products&rank_by=revenue&window=30');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('product_name')->all();
        expect($names)->toContain('Recent Product')
            ->not->toContain('Old Product');
    });

    test('non-seller cannot access hub endpoints', function () {
        $buyer = User::factory()->create(); // No shop

        $this->actingAs($buyer)->getJson('/api/v1/hub/dashboard')->assertForbidden();
        $this->actingAs($buyer)->getJson('/api/v1/hub/customers')->assertForbidden();
        $this->actingAs($buyer)->getJson('/api/v1/hub/reports')->assertForbidden();
        $this->actingAs($buyer)->getJson('/api/v1/hub/leaderboards')->assertForbidden();
    });
});
