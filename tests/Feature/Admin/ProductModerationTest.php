<?php

declare(strict_types=1);

use App\Livewire\Admin\Products\ProductModerationList;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('can flag a product', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'moderation_status' => 'active',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProductModerationList::class)
        ->assertOk()
        ->call('flagProduct', $product->id)
        ->assertDispatched('toast');

    expect($product->fresh()->moderation_status)->toBe('flagged');
});

it('can unpublish a product', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'moderation_status' => 'active',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProductModerationList::class)
        ->assertOk()
        ->call('unpublishProduct', $product->id)
        ->assertDispatched('toast');

    expect($product->fresh()->moderation_status)->toBe('admin_unpublished');
});

it('can restore a product to active', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'moderation_status' => 'flagged',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProductModerationList::class)
        ->assertOk()
        ->call('restoreProduct', $product->id)
        ->assertDispatched('toast');

    expect($product->fresh()->moderation_status)->toBe('active');
});
