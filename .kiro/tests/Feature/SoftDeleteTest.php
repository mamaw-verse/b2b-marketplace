<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ═══════════════════════════════════════════════════════════════════════════
// Shop Soft-Delete
// ═══════════════════════════════════════════════════════════════════════════

test('deleting a shop sets deleted_at but keeps the record', function () {
    $shop = Shop::factory()->create();
    $shopId = $shop->id;

    $shop->delete();

    expect(Shop::find($shopId))->toBeNull(); // standard find excludes soft-deleted
    expect(Shop::withTrashed()->find($shopId))->not->toBeNull();
    expect(Shop::withTrashed()->find($shopId)->deleted_at)->not->toBeNull();
});

test('soft-deleted shop is still queryable via withTrashed', function () {
    $shop = Shop::factory()->create();
    $shopId = $shop->id;

    $shop->delete();

    $restored = Shop::withTrashed()->find($shopId);
    expect($restored)->not->toBeNull();
    expect($restored->id)->toBe($shopId);
    expect($restored->deleted_at)->not->toBeNull();
});

test('soft-deleted shop relationships still accessible', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    ProductVariant::factory()->create(['product_id' => $product->id]);

    $shop->delete();

    $trashedShop = Shop::withTrashed()->find($shop->id);

    // User relationship still accessible
    expect($trashedShop->user)->not->toBeNull();
    expect($trashedShop->user->id)->toBe($user->id);

    // Products still accessible (product not soft-deleted)
    expect($trashedShop->products)->toHaveCount(1);
});

// ═══════════════════════════════════════════════════════════════════════════
// Product Soft-Delete
// ═══════════════════════════════════════════════════════════════════════════

test('deleting a product sets deleted_at but variants remain', function () {
    $product = Product::factory()->create();
    $variant1 = ProductVariant::factory()->create(['product_id' => $product->id]);
    $variant2 = ProductVariant::factory()->create(['product_id' => $product->id]);

    $product->delete();

    // Product is soft-deleted
    expect(Product::find($product->id))->toBeNull();
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();
    expect(Product::withTrashed()->find($product->id)->deleted_at)->not->toBeNull();

    // Variants are NOT deleted — they still exist
    expect(ProductVariant::find($variant1->id))->not->toBeNull();
    expect(ProductVariant::find($variant2->id))->not->toBeNull();
});

test('product variants accessible via Product::withTrashed', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $product->delete();

    $trashedProduct = Product::withTrashed()->find($product->id);
    expect($trashedProduct->variants)->toHaveCount(1);
    expect($trashedProduct->variants->first()->id)->toBe($variant->id);
});

test('soft-deleted product still has shop relationship', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $product->delete();

    $trashedProduct = Product::withTrashed()->find($product->id);
    expect($trashedProduct->shop)->not->toBeNull();
    expect($trashedProduct->shop->id)->toBe($shop->id);
});

test('soft-deleted product still has category relationships', function () {
    $product = Product::factory()->create();
    $category = App\Models\Category::factory()->create();
    $product->categories()->attach($category->id);

    $product->delete();

    $trashedProduct = Product::withTrashed()->find($product->id);
    expect($trashedProduct->categories)->toHaveCount(1);
    expect($trashedProduct->categories->first()->id)->toBe($category->id);
});

// ═══════════════════════════════════════════════════════════════════════════
// FK Integrity — Load relationships on soft-deleted models
// ═══════════════════════════════════════════════════════════════════════════

test('FK integrity: can load all relationships on soft-deleted shop', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    ProductVariant::factory()->count(2)->create(['product_id' => $product->id]);

    $shop->delete();

    $trashedShop = Shop::withTrashed()->with(['user', 'products.variants'])->find($shop->id);

    expect($trashedShop->user->id)->toBe($user->id);
    expect($trashedShop->products)->toHaveCount(1);
    expect($trashedShop->products->first()->variants)->toHaveCount(2);
});

test('FK integrity: can load all relationships on soft-deleted product', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $variants = ProductVariant::factory()->count(3)->create(['product_id' => $product->id]);

    $product->delete();

    $trashedProduct = Product::withTrashed()->with(['shop', 'variants'])->find($product->id);

    expect($trashedProduct->shop->id)->toBe($shop->id);
    expect($trashedProduct->variants)->toHaveCount(3);
});

test('no orphaned children: deleting shop does not cascade to products', function () {
    $shop = Shop::factory()->create();
    $product1 = Product::factory()->create(['shop_id' => $shop->id]);
    $product2 = Product::factory()->create(['shop_id' => $shop->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product1->id]);

    $shop->delete();

    // Products remain untouched (not soft-deleted)
    expect(Product::find($product1->id))->not->toBeNull();
    expect(Product::find($product2->id))->not->toBeNull();
    expect(ProductVariant::find($variant->id))->not->toBeNull();
});
