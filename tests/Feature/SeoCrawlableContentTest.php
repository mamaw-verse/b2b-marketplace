<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCertificate;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Config;

/*
|--------------------------------------------------------------------------
| Crawlable body content (T5.5)
|--------------------------------------------------------------------------
| Everything asserted here must be in the FIRST HTML response. Crawlers that do
| not run JavaScript (most AI crawlers) never see content that Alpine or a
| deferred Livewire component adds later, so these tests read the raw response
| body rather than a rendered DOM.
*/

const CRAWLER_USER_AGENT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Config::set('marketplace.features.subscriptions_enabled', false);
    Config::set('marketplace.features.reviews_enabled', false);
});

/**
 * Fetches a page the way a crawler does and returns the raw first response body.
 */
function crawl(mixed $testCase, string $url): string
{
    return $testCase->withHeaders(['User-Agent' => CRAWLER_USER_AGENT])->get($url)->assertOk()->getContent();
}

/**
 * Number of elements with the given tag name in a raw HTML string.
 */
function tagCount(string $html, string $tag): int
{
    return preg_match_all('#<'.$tag.'\b#i', $html);
}

test('the home page returns body content and exactly one h1 to a crawler', function () {
    $html = crawl($this, '/');

    expect(tagCount($html, 'h1'))->toBe(1)
        ->and(tagCount($html, 'h2'))->toBeGreaterThan(0);
});

test('a category page lists its products and description in the first response', function () {
    $category = Category::factory()->create(['name' => 'Crawlable Foods', 'description' => 'Halal pantry staples for bulk buyers.']);
    $product = Product::factory()->published()->create(['name' => 'Crawlable Basmati']);
    $product->categories()->attach($category);

    $html = crawl($this, route('storefront.category', $category));

    expect(tagCount($html, 'h1'))->toBe(1)
        ->and($html)->toContain('Crawlable Foods')
        ->and($html)->toContain('Halal pantry staples for bulk buyers.')
        ->and($html)->toContain('Crawlable Basmati');
});

test('a product page carries its name, description, certificate status, breadcrumbs and main image in the first response', function () {
    $shop = Shop::factory()->create(['status' => 'active', 'storefront_name' => 'Crawlable Mills']);
    ShopCertificate::factory()->create(['shop_id' => $shop->id, 'status' => 'active']);
    $parent = Category::factory()->create(['name' => 'Crawlable Grains']);
    $child = Category::factory()->childOf($parent)->create(['name' => 'Crawlable Rice']);
    $product = Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Crawlable Jasmine Rice',
        'description' => '<p>Long grain rice milled in small batches.</p>',
        'featured_image_path' => 'products/rice.jpg',
    ]);
    $product->categories()->attach($child);

    $html = crawl($this, route('storefront.product', $product));

    expect(tagCount($html, 'h1'))->toBe(1)
        ->and($html)->toContain('Crawlable Jasmine Rice')
        ->and($html)->toContain('Long grain rice milled in small batches.')
        ->and($html)->toContain('Halal Certified')
        ->and($html)->toContain('aria-label="Breadcrumb"')
        ->and($html)->toContain('href="'.route('storefront.category', $parent).'"')
        ->and($html)->toContain('href="'.route('storefront.category', $child).'"')
        // The Alpine-bound gallery image must also exist as plain HTML for non-JS crawlers.
        ->and($html)->toMatch('#<img\b[^>]*\bsrc="'.preg_quote($product->featured_image_url, '#').'"[^>]*\balt="Crawlable Jasmine Rice"#s');
});

test('a company page carries its name, description, certificate status and products in the first response', function () {
    $shop = Shop::factory()->create([
        'status' => 'active',
        'storefront_name' => 'Crawlable Trading',
        'company_description' => '<p>Family-run exporter of <b>halal</b> pantry goods.</p><script>alert(1)</script>',
    ]);
    ShopCertificate::factory()->create(['shop_id' => $shop->id, 'status' => 'active']);
    Product::factory()->published()->create(['shop_id' => $shop->id, 'name' => 'Crawlable Dates']);

    $html = crawl($this, route('storefront.company', $shop));

    expect(tagCount($html, 'h1'))->toBe(1)
        ->and($html)->toContain('Crawlable Trading')
        ->and($html)->toContain('Family-run exporter of halal pantry goods.')
        ->and($html)->not->toContain('alert(1)')
        ->and($html)->toContain('Verified')
        ->and($html)->toContain('Crawlable Dates');
});

test('a product without images renders the placeholder instead of an empty Alpine gallery', function () {
    $product = Product::factory()->published()->create(['featured_image_path' => null]);

    $html = crawl($this, route('storefront.product', $product));

    expect($html)->not->toContain('x-ref="mainImg"')
        ->and($html)->toContain('No images available');
});
