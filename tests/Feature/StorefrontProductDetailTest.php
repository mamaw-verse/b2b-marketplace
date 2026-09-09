<?php

declare(strict_types=1);

use App\Livewire\Storefront\ProductDetail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->shop = Shop::factory()->create(['status' => 'active']);
    $plan = SubscriptionPlan::factory()->create();
    Subscription::factory()->trialing()->create([
        'shop_id' => $this->shop->id,
        'subscription_plan_id' => $plan->id,
    ]);
    $this->product = Product::factory()->create(['shop_id' => $this->shop->id, 'status' => 'published']);
});

test('flagged product returns 404 on storefront', function () {
    $product = Product::factory()->published()->flagged()->create(['shop_id' => $this->shop->id]);

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->assertStatus(404);
});

test('admin_unpublished product returns 404 on storefront', function () {
    $product = Product::factory()->published()->adminUnpublished()->create(['shop_id' => $this->shop->id]);

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->assertStatus(404);
});

test('active moderation_status product is accessible on storefront', function () {
    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->assertStatus(200);
});

test('flagged product returns 404 on public api', function () {
    $product = Product::factory()->published()->flagged()->create(['shop_id' => $this->shop->id]);

    $this->getJson('/api/v1/products/'.$product->id)
        ->assertNotFound();
});

test('admin_unpublished product returns 404 on public api', function () {
    $product = Product::factory()->published()->adminUnpublished()->create(['shop_id' => $this->shop->id]);

    $this->getJson('/api/v1/products/'.$product->id)
        ->assertNotFound();
});

test('active moderation_status product is accessible on public api', function () {
    $this->getJson('/api/v1/products/'.$this->product->id)
        ->assertOk();
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

test('product detail renders sourcing section at bottom', function () {
    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->assertSee('Looking for Something Else?')
        ->assertSee('We can help.')
        ->assertSee('Custom Sourcing');
});
