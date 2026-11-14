<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Shop;
use App\Models\SlugRedirect;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

test('shop and product factories generate slugs automatically', function () {
    $shop = Shop::factory()->create(['storefront_name' => 'Al Barakah Foods']);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Halal Chicken Nuggets',
    ]);

    expect($shop->slug)->toBe('al-barakah-foods')
        ->and($product->slug)->toBe('halal-chicken-nuggets');
});

test('changing a name does not automatically change its slug', function () {
    $product = Product::factory()->create(['name' => 'Original Name']);

    $product->update(['name' => 'Updated Name']);

    expect($product->fresh()->slug)->toBe('original-name');
});

test('slug edits create redirect bindings and reverting removes the redirect', function () {
    $product = Product::factory()->create(['name' => 'Original Name']);
    $originalSlug = $product->slug;

    $product->update(['slug' => 'new-product-name']);

    expect(SlugRedirect::query()
        ->where('sluggable_type', Product::class)
        ->where('slug', $originalSlug)
        ->value('sluggable_id'))->toBe($product->id);

    expect((new Product)->resolveRouteBinding($originalSlug, 'slug')?->is($product->fresh()))
        ->toBeTrue();

    $product->update(['slug' => $originalSlug]);

    expect(SlugRedirect::query()
        ->where('sluggable_type', Product::class)
        ->where('slug', $originalSlug)
        ->exists())->toBeFalse();
});

test('slug binding supports current slugs and legacy numeric ids', function () {
    $product = Product::factory()->create(['name' => 'Binding Test Product']);
    $boundModel = new Product;

    expect($boundModel->resolveRouteBinding($product->slug, 'slug')?->is($product))
        ->toBeTrue()
        ->and($boundModel->resolveRouteBinding((string) $product->id, 'slug')?->is($product))
        ->toBeTrue();
});

test('storefront slug URLs are canonical and legacy IDs preserve the query string', function () {
    config(['marketplace.features.subscriptions_enabled' => false]);

    $product = Product::factory()->published()->create(['name' => 'Canonical Product']);

    $this->get(route('storefront.product', $product))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('storefront.product', $product).'">', false)
        ->assertSee('<meta property="og:url"         content="'.route('storefront.product', $product).'">', false);

    $this->get('/products/'.$product->id.'?campaign=legacy&source=email')
        ->assertStatus(301)
        ->assertRedirect(route('storefront.product', $product).'?campaign=legacy&source=email');
});

test('company slug URLs are canonical and legacy IDs redirect', function () {
    config(['marketplace.features.subscriptions_enabled' => false]);

    $shop = Shop::factory()->create(['storefront_name' => 'Canonical Company']);

    $this->get(route('storefront.company', $shop))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('storefront.company', $shop).'">', false)
        ->assertSee('<meta property="og:url"         content="'.route('storefront.company', $shop).'">', false);

    $oldSlug = $shop->slug;
    $shop->update(['slug' => 'renamed-canonical-company']);

    $this->get('/companies/'.$oldSlug.'?ref=old-link')
        ->assertStatus(301)
        ->assertRedirect(route('storefront.company', $shop).'?ref=old-link');

    $this->get('/companies/'.$shop->id.'?ref=old-link')
        ->assertStatus(301)
        ->assertRedirect(route('storefront.company', $shop).'?ref=old-link');
});

test('old slug URLs redirect to the current slug and unknown values return 404', function () {
    config(['marketplace.features.subscriptions_enabled' => false]);

    $product = Product::factory()->published()->create(['name' => 'Original Route Product']);
    $oldSlug = $product->slug;
    $product->update(['slug' => 'renamed-route-product']);

    $this->get('/products/'.$oldSlug.'?ref=old-link')
        ->assertStatus(301)
        ->assertRedirect(route('storefront.product', $product).'?ref=old-link');

    $this->get('/products/not-a-real-product')->assertNotFound();
});

test('unpublished products and suspended shops keep returning 404', function () {
    config(['marketplace.features.subscriptions_enabled' => false]);

    $unpublished = Product::factory()->create(['name' => 'Unpublished Product']);
    $this->get('/products/'.$unpublished->slug)->assertNotFound();
    $this->get('/products/'.$unpublished->id)->assertNotFound();

    $suspendedShop = Shop::factory()->suspended()->create(['storefront_name' => 'Suspended Company']);
    $this->get('/companies/'.$suspendedShop->slug)->assertNotFound();
    $this->get('/companies/'.$suspendedShop->id)->assertNotFound();
});

test('public API payloads expose slugs and canonical URLs while retaining numeric ID binding', function () {
    config(['marketplace.features.subscriptions_enabled' => false]);

    $shop = Shop::factory()->create(['storefront_name' => 'API Slug Shop']);
    $product = Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'API Slug Product',
    ]);

    $this->getJson('/api/v1/products/'.$product->id)
        ->assertOk()
        ->assertJsonPath('data.slug', $product->slug)
        ->assertJsonPath('data.canonical_url', route('storefront.product', $product));

    expect(route('hub.products.edit', $product))->toEndWith('/hub/products/'.$product->id.'/edit');

    $this->getJson('/api/v1/companies/'.$shop->id)
        ->assertOk()
        ->assertJsonPath('data.slug', $shop->slug)
        ->assertJsonPath('data.canonical_url', route('storefront.company', $shop));
});

test('slug backfill is idempotent and includes soft-deleted rows', function () {
    $shop = Shop::factory()->create(['storefront_name' => 'Backfill Shop']);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Backfill Product',
    ]);
    $shop->delete();
    $product->delete();

    DB::statement('ALTER TABLE shops ALTER COLUMN slug DROP NOT NULL');
    DB::statement('ALTER TABLE products ALTER COLUMN slug DROP NOT NULL');
    DB::table('shops')->where('id', $shop->id)->update(['slug' => null]);
    DB::table('products')->where('id', $product->id)->update(['slug' => null]);

    expect(Artisan::call('slugs:backfill'))->toBe(0);

    $backfilledShopSlug = DB::table('shops')->where('id', $shop->id)->value('slug');
    $backfilledProductSlug = DB::table('products')->where('id', $product->id)->value('slug');

    expect($backfilledShopSlug)->toBe('backfill-shop')
        ->and($backfilledProductSlug)->toBe('backfill-product');

    expect(Artisan::call('slugs:backfill'))->toBe(0);

    expect(DB::table('shops')->where('id', $shop->id)->value('slug'))->toBe($backfilledShopSlug)
        ->and(DB::table('products')->where('id', $product->id)->value('slug'))->toBe($backfilledProductSlug);

    DB::statement('ALTER TABLE shops ALTER COLUMN slug SET NOT NULL');
    DB::statement('ALTER TABLE products ALTER COLUMN slug SET NOT NULL');
});
