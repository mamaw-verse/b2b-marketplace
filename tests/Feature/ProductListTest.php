<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\ProductList;
use App\Models\Product;
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

test('component renders and shows seller products', function () {
    $published = Product::factory()->published()->create(['shop_id' => $this->shop->id]);
    $archived = Product::factory()->archived()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($this->seller)
        ->test(ProductList::class)
        ->assertSee($published->name)
        ->assertSee($archived->name);
});

test('confirmToggleStatus sets confirming state for a published product', function () {
    $product = Product::factory()->published()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($this->seller)
        ->test(ProductList::class)
        ->call('confirmToggleStatus', $product->id, 'archive')
        ->assertSet('confirmingProductId', $product->id)
        ->assertSet('confirmingAction', 'archive');
});

test('confirmToggleStatus sets confirming state for an archived product', function () {
    $product = Product::factory()->archived()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($this->seller)
        ->test(ProductList::class)
        ->call('confirmToggleStatus', $product->id, 'publish')
        ->assertSet('confirmingProductId', $product->id)
        ->assertSet('confirmingAction', 'publish');
});

test('toggleStatus archives a published product', function () {
    $product = Product::factory()->published()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($this->seller)
        ->test(ProductList::class)
        ->call('confirmToggleStatus', $product->id, 'archive')
        ->call('toggleStatus');

    expect($product->fresh()->status)->toBe('archived');
});

test('toggleStatus publishes an archived product', function () {
    $product = Product::factory()->archived()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($this->seller)
        ->test(ProductList::class)
        ->call('confirmToggleStatus', $product->id, 'publish')
        ->call('toggleStatus');

    expect($product->fresh()->status)->toBe('published');
});

test('toggleStatus resets confirming state after execution', function () {
    $product = Product::factory()->published()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($this->seller)
        ->test(ProductList::class)
        ->call('confirmToggleStatus', $product->id, 'archive')
        ->call('toggleStatus')
        ->assertSet('confirmingProductId', null)
        ->assertSet('confirmingAction', null);
});

test('toggleStatus does nothing when no product is being confirmed', function () {
    $product = Product::factory()->published()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($this->seller)
        ->test(ProductList::class)
        ->call('toggleStatus');

    // Product status unchanged — early return guard works
    expect($product->fresh()->status)->toBe('published');
});

test('seller cannot toggle status of a product belonging to another shop', function () {
    $otherShop = Shop::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create(['shop_id' => $otherShop->id]);

    Livewire::actingAs($this->seller)
        ->test(ProductList::class)
        ->call('confirmToggleStatus', $product->id, 'archive')
        ->call('toggleStatus');

    // Status must not change — ownership check prevents it
    expect($product->fresh()->status)->toBe('published');
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('status filter shows only published products', function () {
    $published = Product::factory()->published()->create(['shop_id' => $this->shop->id]);
    $archived = Product::factory()->archived()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($this->seller)
        ->test(ProductList::class)
        ->set('statusFilter', 'published')
        ->assertSee($published->name)
        ->assertDontSee($archived->name);
});

test('status filter shows only archived products', function () {
    $published = Product::factory()->published()->create(['shop_id' => $this->shop->id]);
    $archived = Product::factory()->archived()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($this->seller)
        ->test(ProductList::class)
        ->set('statusFilter', 'archived')
        ->assertSee($archived->name)
        ->assertDontSee($published->name);
});
