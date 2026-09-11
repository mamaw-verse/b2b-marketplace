<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\Orders\OrderDetail;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    config(['marketplace.features.orders_enabled' => true]);

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);
});

test('order detail renders disputes section with status reason and resolution', function () {
    $order = Order::factory()->paid()->create(['shop_id' => $this->shop->id]);
    Dispute::factory()->resolvedRefunded()->create([
        'order_id' => $order->id,
        'reason' => 'Item arrived damaged in transit',
        'resolution_notes' => 'Refund approved in full',
    ]);

    Livewire::actingAs($this->seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->assertSee('Disputes')
        ->assertSee('Item arrived damaged in transit')
        ->assertSee('Refund approved in full')
        ->assertSee('Refund amount');
});

test('order detail hides disputes section when there are none', function () {
    $order = Order::factory()->paid()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($this->seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->assertDontSee('Refund amount');
});
