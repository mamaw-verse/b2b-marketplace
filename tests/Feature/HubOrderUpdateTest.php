<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);
});

test('update bulk-creates order items and recalculates totals', function () {
    $order = Order::factory()->create([
        'shop_id' => $this->shop->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->seller, 'sanctum')
        ->putJson("/api/v1/hub/orders/{$order->id}", [
            'items' => [
                ['description' => 'Item A', 'quantity' => 2, 'unit' => 'kg', 'unit_price' => 100],
                ['description' => 'Item B', 'quantity' => 3, 'unit' => 'box', 'unit_price' => 50],
            ],
            'fees' => [
                ['label' => 'Shipping', 'amount' => 25],
            ],
        ]);

    $response->assertOk();

    // 2 items created via createMany
    $this->assertDatabaseCount('order_items', 2);
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'description' => 'Item A',
        'subtotal' => '200.00',
    ]);
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'description' => 'Item B',
        'subtotal' => '150.00',
    ]);

    // Fee created via createMany
    $this->assertDatabaseHas('order_fees', [
        'order_id' => $order->id,
        'label' => 'Shipping',
        'amount' => '25.00',
    ]);

    // Totals recalculated: items 350 + fees 25 = 375
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'items_total' => '350.00',
        'fees_total' => '25.00',
        'order_total' => '375.00',
    ]);
});

test('update replaces existing items (delete + bulk recreate)', function () {
    $order = Order::factory()->create([
        'shop_id' => $this->shop->id,
        'status' => 'draft',
    ]);

    // First update seeds 2 items
    $this->actingAs($this->seller, 'sanctum')
        ->putJson("/api/v1/hub/orders/{$order->id}", [
            'items' => [
                ['description' => 'Old 1', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 10],
                ['description' => 'Old 2', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 10],
            ],
        ])->assertOk();

    // Second update replaces with a single item
    $this->actingAs($this->seller, 'sanctum')
        ->putJson("/api/v1/hub/orders/{$order->id}", [
            'items' => [
                ['description' => 'New Only', 'quantity' => 5, 'unit' => 'kg', 'unit_price' => 20],
            ],
        ])->assertOk();

    $this->assertDatabaseCount('order_items', 1);
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'description' => 'New Only',
        'subtotal' => '100.00',
    ]);
});
