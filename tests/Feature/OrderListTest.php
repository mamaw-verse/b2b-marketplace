<?php

declare(strict_types=1);

use App\Livewire\Account\Orders\OrderList;
use App\Models\Fulfilment;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->buyer = User::factory()->create(['email_verified_at' => now()]);
    $this->buyer->assignRole('buyer');

    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
});

test('completed tab includes refunded orders', function () {
    $completed = Order::factory()->completed()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);
    $refunded = Order::factory()->refunded()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);

    $component = Livewire::actingAs($this->buyer)
        ->test(OrderList::class)
        ->set('tab', 'completed');

    $ids = $component->instance()->orders()->pluck('id')->all();

    expect($ids)->toContain($completed->id)
        ->toContain($refunded->id);

    expect($component->instance()->tabCounts()['completed'])->toBe(2);
});

test('to_receive tab excludes completed orders', function () {
    // A delivered-but-not-yet-confirmed order belongs in To Receive.
    $awaiting = Order::factory()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
        'status' => 'paid',
    ]);
    Fulfilment::factory()->delivered()->create(['order_id' => $awaiting->id]);

    // A completed order also has a delivered fulfilment, but must NOT appear here.
    $completed = Order::factory()->completed()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);
    Fulfilment::factory()->delivered()->create(['order_id' => $completed->id]);

    $component = Livewire::actingAs($this->buyer)
        ->test(OrderList::class)
        ->set('tab', 'to_receive');

    $orders = $component->instance()->orders();

    expect($orders->pluck('id')->all())->toContain($awaiting->id)
        ->not->toContain($completed->id);

    expect($component->instance()->tabCounts()['to_receive'])->toBe(1);
});
