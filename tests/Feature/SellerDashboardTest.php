<?php

declare(strict_types=1);

use App\Livewire\Portal\Dashboard;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seller = User::factory()->create(['email_verified_at' => now()]);
    $this->seller->assignRole('seller');
});

test('the seller dashboard shows a View Business link for an active shop', function () {
    $shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);

    Livewire::actingAs($this->seller)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('View Business')
        ->assertSee(route('storefront.company', $shop), false);
});

test('the shop computed property returns the sellers shop', function () {
    $shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);

    $component = Livewire::actingAs($this->seller)->test(Dashboard::class);

    expect($component->instance()->shop()->id)->toBe($shop->id);
});

test('seller action tiles are hidden when their features are switched off', function () {
    config([
        'marketplace.features.orders_enabled' => false,
        'marketplace.features.seller_rfq_queue_enabled' => false,
        'marketplace.features.payouts_enabled' => false,
        'marketplace.features.chat_enabled' => false,
    ]);
    Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);

    $stats = Livewire::actingAs($this->seller)->test(Dashboard::class)->instance()->sellerStats();

    expect($stats['orders_to_fulfil'])->toBeNull()
        ->and($stats['new_rfqs'])->toBeNull()
        ->and($stats['pending_balance'])->toBeNull()
        ->and($stats['unread_messages'])->toBeNull();

    Livewire::actingAs($this->seller)->test(Dashboard::class)
        ->assertDontSee('Orders to Fulfil')
        ->assertDontSee('Pending Payout');
});

test('seller action tiles show counts for enabled features', function () {
    config([
        'marketplace.features.orders_enabled' => true,
        'marketplace.features.seller_rfq_queue_enabled' => true,
        'marketplace.features.payouts_enabled' => false,
        'marketplace.features.chat_enabled' => false,
    ]);
    Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);

    $stats = Livewire::actingAs($this->seller)->test(Dashboard::class)->instance()->sellerStats();

    expect($stats['orders_to_fulfil'])->toBe(0)
        ->and($stats['new_rfqs'])->toBe(0)
        ->and($stats['pending_balance'])->toBeNull();

    Livewire::actingAs($this->seller)->test(Dashboard::class)
        ->assertSee('Orders to Fulfil')
        ->assertSee('New RFQs');
});
