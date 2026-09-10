<?php

declare(strict_types=1);

use App\Events\OrderCancelled;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    if (! config('marketplace.features.orders_enabled')) {
        $this->markTestSkipped('orders_enabled is disabled');
    }

    $this->seed(RolePermissionSeeder::class);

    $this->buyer = User::factory()->create(['email_verified_at' => now()]);
    $this->buyer->assignRole('buyer');

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);
});

test('buyer cancel endpoint dispatches the OrderCancelled event', function () {
    Event::fake([OrderCancelled::class]);

    $order = Order::factory()->pendingPayment()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);

    $this->actingAs($this->buyer, 'sanctum')
        ->postJson("/api/v1/orders/{$order->id}/cancel")
        ->assertOk();

    expect($order->fresh()->status)->toBe('cancelled');

    Event::assertDispatched(OrderCancelled::class, fn ($event) => $event->order->id === $order->id);
});

test('seller cancel endpoint dispatches the OrderCancelled event', function () {
    Event::fake([OrderCancelled::class]);

    $order = Order::factory()->paid()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);

    $this->actingAs($this->seller, 'sanctum')
        ->postJson("/api/v1/hub/orders/{$order->id}/cancel")
        ->assertOk();

    expect($order->fresh()->status)->toBe('cancelled');

    Event::assertDispatched(OrderCancelled::class, fn ($event) => $event->order->id === $order->id);
});
