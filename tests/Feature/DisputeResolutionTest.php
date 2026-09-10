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
    if (! config('marketplace.features.disputes_enabled')) {
        $this->markTestSkipped('disputes_enabled is disabled');
    }

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

test('resolving a dispute redirects to the order page to refresh it', function () {
    $order = Order::factory()->paid()->create(['shop_id' => $this->shop->id, 'order_total' => '1000.00']);
    Dispute::factory()->create([
        'order_id' => $order->id,
        'type' => 'refund_only',
        'status' => 'open',
    ]);

    Livewire::actingAs($this->seller)
        ->test(DisputePanel::class, ['order' => $order])
        ->set('resolutionType', 'full_refund')
        ->call('resolve')
        ->assertRedirect(route('hub.orders.show', $order));
});

test('switching resolution type away from partial clears a stale amount', function () {
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
        ->set('resolutionType', 'full_refund')
        ->assertSet('resolutionAmount', null);
});

test('partial refund requires the amount to be filled in', function () {
    $order = Order::factory()->paid()->create(['shop_id' => $this->shop->id, 'order_total' => '500.00']);
    Dispute::factory()->create([
        'order_id' => $order->id,
        'type' => 'refund_only',
        'status' => 'open',
    ]);

    Livewire::actingAs($this->seller)
        ->test(DisputePanel::class, ['order' => $order])
        ->set('resolutionType', 'partial_refund')
        ->set('resolutionAmount', '')
        ->call('resolve')
        ->assertHasErrors(['resolutionAmount' => 'required']);
});

test('partial refund requires an amount within the order total', function () {
    $order = Order::factory()->paid()->create(['shop_id' => $this->shop->id, 'order_total' => '500.00']);
    Dispute::factory()->create([
        'order_id' => $order->id,
        'type' => 'refund_only',
        'status' => 'open',
    ]);

    Livewire::actingAs($this->seller)
        ->test(DisputePanel::class, ['order' => $order])
        ->set('resolutionType', 'partial_refund')
        ->set('resolutionAmount', '999.00')
        ->call('resolve')
        ->assertHasErrors(['resolutionAmount']);
});

test('an unconfirmed return_refund dispute is forced to deny-only', function () {
    $order = Order::factory()->paid()->create(['shop_id' => $this->shop->id]);
    Dispute::factory()->create([
        'order_id' => $order->id,
        'type' => 'return_refund',
        'status' => 'awaiting_return',
    ]);

    Livewire::actingAs($this->seller)
        ->test(DisputePanel::class, ['order' => $order])
        ->assertSet('resolutionType', 'denied')
        ->assertSee('Confirm Return Received')
        ->assertSee('Deny Dispute')
        ->assertDontSee('Full Refund');
});
