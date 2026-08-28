<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\Disputes\DisputePanel;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);
});

test('partial refund amount input is shown only when partial_refund resolution is selected', function () {
    $order = Order::factory()->paid()->create(['shop_id' => $this->shop->id]);
    Dispute::factory()->create([
        'order_id' => $order->id,
        'type' => 'refund_only',
        'status' => 'open',
    ]);

    $component = Livewire::actingAs($this->seller)
        ->test(DisputePanel::class, ['order' => $order]);

    // Full refund (default) — no amount input.
    $component->assertSet('resolutionType', 'full_refund')
        ->assertDontSee('Amount');

    // Selecting partial_refund reveals the amount input.
    $component->set('resolutionType', 'partial_refund')
        ->assertSee('Amount');
});

test('partial refund amount is passed through to the resolve action', function () {
    $order = Order::factory()->paid()->create(['shop_id' => $this->shop->id]);
    Dispute::factory()->create([
        'order_id' => $order->id,
        'type' => 'refund_only',
        'status' => 'open',
    ]);

    Livewire::actingAs($this->seller)
        ->test(DisputePanel::class, ['order' => $order])
        ->set('resolutionType', 'partial_refund')
        ->set('resolutionAmount', '150.00')
        ->assertSet('resolutionAmount', '150.00');
});
