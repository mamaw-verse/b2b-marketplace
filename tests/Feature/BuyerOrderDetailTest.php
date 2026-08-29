<?php

declare(strict_types=1);

use App\Livewire\Account\Orders\OrderDetail;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->buyer = User::factory()->create(['email_verified_at' => now()]);
    $this->buyer->assignRole('buyer');

    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
});

test('buyer order detail shows back to orders link', function () {
    $order = Order::factory()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);

    Livewire::actingAs($this->buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->assertSee('Back to Orders');
});

test('back link points to the orders index route', function () {
    $order = Order::factory()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);

    $this->actingAs($this->buyer)
        ->get(route('account.orders.show', $order))
        ->assertOk()
        ->assertSee(route('account.orders.index'), false);
});

test('cancelling a pending payment order dispatches the OrderCancelled event', function () {
    Event::fake([App\Events\OrderCancelled::class]);

    $order = Order::factory()->pendingPayment()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);

    Livewire::actingAs($this->buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('cancel');

    expect($order->fresh()->status)->toBe('cancelled');

    Event::assertDispatched(App\Events\OrderCancelled::class, fn ($event) => $event->order->id === $order->id);
});

test('cancelling a non-pending order does not dispatch the OrderCancelled event', function () {
    Event::fake([App\Events\OrderCancelled::class]);

    $order = Order::factory()->paid()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);

    Livewire::actingAs($this->buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('cancel');

    expect($order->fresh()->status)->toBe('paid');

    Event::assertNotDispatched(App\Events\OrderCancelled::class);
});
