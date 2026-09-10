<?php

declare(strict_types=1);

use App\Livewire\Account\Disputes\DisputeDetail;
use App\Models\Dispute;
use App\Models\DisputeItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    if (! config('marketplace.features.disputes_enabled')) {
        $this->markTestSkipped('disputes_enabled is disabled');
    }

    $this->seed(RolePermissionSeeder::class);

    $this->buyer = User::factory()->create(['email_verified_at' => now()]);
    $this->buyer->assignRole('buyer');

    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    $this->order = Order::factory()->completed()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);
});

test('opening the modal loads the dispute and shows the form', function () {
    $dispute = Dispute::factory()->create([
        'order_id' => $this->order->id,
        'opened_by_user_id' => $this->buyer->id,
    ]);

    Livewire::actingAs($this->buyer)
        ->test(DisputeDetail::class, ['order' => $this->order])
        ->assertSet('show', false)
        ->dispatch('open-dispute-detail-modal', disputeId: $dispute->id)
        ->assertSet('show', true)
        ->assertSet('dispute.id', $dispute->id)
        ->assertSee($dispute->reason);
});

test('the modal shows disputed items with their descriptions and quantities', function () {
    $orderItem = OrderItem::factory()->create([
        'order_id' => $this->order->id,
        'description' => 'Halal Beef Cubes',
        'quantity' => 10,
        'unit' => 'kg',
    ]);

    $dispute = Dispute::factory()->create([
        'order_id' => $this->order->id,
        'opened_by_user_id' => $this->buyer->id,
    ]);
    DisputeItem::factory()->create([
        'dispute_id' => $dispute->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 3,
    ]);

    Livewire::actingAs($this->buyer)
        ->test(DisputeDetail::class, ['order' => $this->order])
        ->dispatch('open-dispute-detail-modal', disputeId: $dispute->id)
        ->assertSee('Disputed Items')
        ->assertSee('Halal Beef Cubes')
        ->assertSee('3');
});

test('withdrawing a dispute closes the modal and notifies the parent', function () {
    $dispute = Dispute::factory()->create([
        'order_id' => $this->order->id,
        'opened_by_user_id' => $this->buyer->id,
        'status' => 'open',
    ]);

    Livewire::actingAs($this->buyer)
        ->test(DisputeDetail::class, ['order' => $this->order])
        ->dispatch('open-dispute-detail-modal', disputeId: $dispute->id)
        ->call('withdraw')
        ->assertSet('show', false)
        ->assertDispatched('dispute-updated');

    expect($dispute->fresh()->status)->toBe('withdrawn');
});

test('a buyer cannot open a dispute that does not belong to the order', function () {
    $otherOrder = Order::factory()->completed()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);
    $foreignDispute = Dispute::factory()->create([
        'order_id' => $otherOrder->id,
        'opened_by_user_id' => $this->buyer->id,
    ]);

    Livewire::actingAs($this->buyer)
        ->test(DisputeDetail::class, ['order' => $this->order])
        ->call('openModal', $foreignDispute->id)
        ->assertSet('show', false);
})->throws(ModelNotFoundException::class);
