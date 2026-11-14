<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Shop;
use App\Models\SlugRedirect;
use App\Models\User;

beforeEach(function (): void {
    config()->set('marketplace.features.subscriptions_enabled', false);
    config()->set('humancheck.enabled', false);
});

function slugBrowserFixture(string $slug = 'halal-chicken-nuggets'): array
{
    $owner = User::factory()->create();
    $shop = Shop::factory()->create([
        'user_id' => $owner->id,
        'status' => 'active',
        'storefront_name' => 'Al Barakah Foods',
        'slug' => 'al-barakah-foods',
    ]);
    $product = Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Halal Chicken Nuggets',
        'slug' => $slug,
    ]);

    return [$owner, $shop, $product];
}

test('a published product is reachable by slug with canonical metadata', function (): void {
    [, , $product] = slugBrowserFixture();

    $page = visit('/products/'.$product->slug)
        ->assertSee('Halal Chicken Nuggets')
        ->assertPathIs('/products/'.$product->slug)
        ->assertSourceHas('rel="canonical"')
        ->assertSourceHas('property="og:url"');

    expect($page->attribute('link[rel="canonical"]', 'href'))
        ->toContain('/products/'.$product->slug)
        ->and($page->attribute('meta[property="og:url"]', 'content'))
        ->toContain('/products/'.$product->slug);
});

test('a legacy numeric product URL 301 redirects to the canonical slug and keeps the query string', function (): void {
    [, , $product] = slugBrowserFixture();

    visit('/products/'.$product->id.'?ref=campaign')
        ->assertSee('Halal Chicken Nuggets')
        ->assertPathIs('/products/'.$product->slug)
        ->assertQueryStringHas('ref', 'campaign');
});

test('editing a product slug keeps the old URL working via a 301 redirect', function (): void {
    [$owner, , $product] = slugBrowserFixture('old-product-slug');

    $this->actingAs($owner);

    $page = visit('/hub/products/'.$product->id.'/edit')
        ->assertSee('Product URL slug')
        ->fill('[wire\\:model="slug"]', 'renamed-product-slug')
        ->click('Update Product');

    $page->assertSee('Product updated.');

    expect($product->fresh()->slug)->toBe('renamed-product-slug')
        ->and(SlugRedirect::query()->where('slug', 'old-product-slug')->exists())->toBeTrue();

    visit('/products/old-product-slug')
        ->assertSee('Halal Chicken Nuggets')
        ->assertPathIs('/products/renamed-product-slug');
});

test('a numeric product slug is rejected by the product form', function (): void {
    [$owner, , $product] = slugBrowserFixture('valid-product-slug');

    $this->actingAs($owner);

    visit('/hub/products/'.$product->id.'/edit')
        ->assertSee('Product URL slug')
        ->fill('[wire\\:model="slug"]', '12345')
        ->click('Update Product')
        ->assertSee('cannot be numeric');

    expect($product->fresh()->slug)->toBe('valid-product-slug');
});

test('unpublished products still return 404 on their slug URL', function (): void {
    [, $shop] = slugBrowserFixture();
    $archived = Product::factory()->archived()->create([
        'shop_id' => $shop->id,
        'name' => 'Draft Product',
        'slug' => 'draft-product',
    ]);

    visit('/products/'.$archived->slug)->assertSee('404');
});

test('a shop is reachable by slug with canonical metadata and legacy IDs redirect', function (): void {
    [, $shop] = slugBrowserFixture();

    $page = visit('/companies/'.$shop->slug)
        ->assertSee('Al Barakah Foods')
        ->assertPathIs('/companies/'.$shop->slug)
        ->assertSourceHas('rel="canonical"');

    expect($page->attribute('link[rel="canonical"]', 'href'))
        ->toContain('/companies/'.$shop->slug);

    visit('/companies/'.$shop->id)
        ->assertSee('Al Barakah Foods')
        ->assertPathIs('/companies/'.$shop->slug);
});

test('hub routes keep binding by ID while public routes bind by slug', function (): void {
    [$owner, , $product] = slugBrowserFixture();

    $this->actingAs($owner);

    visit('/hub/products/'.$product->id.'/edit')->assertSee('Edit Product');
    visit('/hub/products/'.$product->slug.'/edit')->assertSee('404');
});

test('the public API keeps ID binding and exposes the slug and canonical URL', function (): void {
    [, , $product] = slugBrowserFixture();

    $this->getJson('/api/v1/products/'.$product->id)
        ->assertOk()
        ->assertJsonPath('data.slug', $product->slug)
        ->assertJsonPath('data.canonical_url', route('storefront.product', $product));

    $this->getJson('/api/v1/products/'.$product->slug)->assertNotFound();
});
