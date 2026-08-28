<?php

declare(strict_types=1);

use App\Livewire\Storefront\ProductDetail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->shop = Shop::factory()->create(['status' => 'active']);
    $this->product = Product::factory()->create(['shop_id' => $this->shop->id, 'status' => 'published']);
});

test('storefront product detail only loads published variants', function () {
    $published = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'PUB-1',
        'status' => 'published',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'ARCH-1',
        'status' => 'archived',
    ]);

    $component = Livewire::test(ProductDetail::class, ['product' => $this->product]);

    $loaded = $component->get('product')->variants;

    expect($loaded)->toHaveCount(1)
        ->and($loaded->first()->id)->toBe($published->id)
        ->and($loaded->pluck('status')->unique()->all())->toBe(['published']);
});

test('archived variant sku does not render on the storefront', function () {
    ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'PUB-VISIBLE',
        'status' => 'published',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'ARCH-HIDDEN',
        'status' => 'archived',
    ]);

    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->assertDontSee('ARCH-HIDDEN');
});
