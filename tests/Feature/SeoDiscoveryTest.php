<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Subscription;
use App\Support\Seo\IndexingPolicy;
use App\Support\Seo\SitemapCache;
use App\Support\Seo\SitemapXml;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Config::set('marketplace.features.subscriptions_enabled', false);
    Config::set('app.url', 'https://seo.example.test');
});

function discoveryAsProduction(): void
{
    app()->detectEnvironment(fn (): string => 'production');
    Config::set('seo.force_noindex', false);
}

function discoveryCanonical(string $html): ?string
{
    return preg_match('/<link rel="canonical" href="([^"]*)"/', $html, $matches) === 1 ? $matches[1] : null;
}

function discoveryRobotsMeta(string $html): ?string
{
    return preg_match('/<meta name="robots" content="([^"]*)"/', $html, $matches) === 1 ? $matches[1] : null;
}

function discoveryTitle(string $html): ?string
{
    return preg_match('/<title>(.*?)<\/title>/s', $html, $matches) === 1 ? trim(html_entity_decode($matches[1])) : null;
}

/**
 * @return list<array<string, mixed>>
 */
function discoveryBreadcrumbNames(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    $schema = json_decode($matches[1][0] ?? '{}', true);

    foreach ($schema['@graph'] ?? [] as $node) {
        if (($node['@type'] ?? null) === 'BreadcrumbList') {
            return array_column($node['itemListElement'], 'name');
        }
    }

    return [];
}

/**
 * @return list<string>
 */
function discoveryLocs(string $xml): array
{
    preg_match_all('#<loc>(.*?)</loc>#', $xml, $matches);

    return array_map(static fn (string $loc): string => html_entity_decode($loc), $matches[1]);
}

function discoveryUrl(string $path): string
{
    return 'https://seo.example.test'.$path;
}

function discoveryCategory(string $name, ?Category $parent = null): Category
{
    $slug = str($name)->slug()->toString();

    return $parent === null
        ? Category::factory()->create(['name' => $name, 'slug' => $slug, 'description' => null])
        : Category::factory()->childOf($parent)->create(['name' => $name, 'slug' => $slug, 'description' => null]);
}

function discoveryProduct(Category $category, array $attributes = []): Product
{
    $product = Product::factory()->published()->create($attributes);
    $product->categories()->attach($category);

    return $product;
}

// ─── T3.1 Category pages ───────────────────────────────────────────────

test('a category page lists its visible products with a unique title, description and canonical', function () {
    discoveryAsProduction();

    $parent = discoveryCategory('Foods');
    $rice = discoveryCategory('Rice', $parent);
    discoveryProduct($rice, ['name' => 'Jasmine Rice']);

    $html = $this->get(route('storefront.category', $rice))->assertOk()->getContent();

    expect(discoveryCanonical($html))->toBe(route('storefront.category', $rice))
        ->and(substr_count($html, 'rel="canonical"'))->toBe(1)
        ->and(discoveryTitle($html))->toBe('Rice Products — '.config('app.name'))
        ->and($html)->toContain('<meta name="description" content="Browse halal-certified Rice products from verified suppliers on '.e(config('app.name')).'.">')
        ->and(discoveryRobotsMeta($html))->toBeNull()
        ->and($html)->toContain('<h1')->toContain('Jasmine Rice')
        ->and(discoveryBreadcrumbNames($html))->toBe(['Home', 'Products', 'Foods', 'Rice']);

    $this->get(route('storefront.category', $rice))->assertHeaderMissing('X-Robots-Tag');
});

test('a category page only shows products that are publicly visible', function () {
    $category = discoveryCategory('Spices');
    discoveryProduct($category, ['name' => 'Visible Turmeric']);

    $draft = Product::factory()->archived()->create(['name' => 'Draft Paprika']);
    $draft->categories()->attach($category);

    $flagged = Product::factory()->published()->flagged()->create(['name' => 'Flagged Cumin']);
    $flagged->categories()->attach($category);

    $suspendedShop = Shop::factory()->suspended()->create();
    $hidden = Product::factory()->published()->create(['name' => 'Suspended Pepper', 'shop_id' => $suspendedShop->id]);
    $hidden->categories()->attach($category);

    $this->get(route('storefront.category', $category))
        ->assertOk()
        ->assertSee('Visible Turmeric')
        ->assertDontSee('Draft Paprika')
        ->assertDontSee('Flagged Cumin')
        ->assertDontSee('Suspended Pepper');
});

test('a group category lists its subcategories products and links only subcategories that have products', function () {
    $group = discoveryCategory('Beverages');
    $tea = discoveryCategory('Tea', $group);
    $empty = discoveryCategory('Coffee Substitutes', $group);
    discoveryProduct($tea, ['name' => 'Green Tea Leaves']);

    $html = $this->get(route('storefront.category', $group))->assertOk()->assertSee('Green Tea Leaves')->getContent();

    expect($html)->toContain('href="'.route('storefront.category', $tea).'"')
        ->and($html)->not->toContain('href="'.route('storefront.category', $empty).'"');
});

test('an empty category stays reachable but is noindex, follow', function () {
    discoveryAsProduction();

    $category = discoveryCategory('Empty Aisle');

    $html = $this->get(route('storefront.category', $category))->assertOk()->getContent();

    expect(discoveryRobotsMeta($html))->toBe('noindex, follow')
        ->and(discoveryCanonical($html))->toBe(route('storefront.category', $category));
});

test('an unknown category slug is a 404', function () {
    $this->get('/categories/does-not-exist')->assertNotFound();
});

test('category pages are self-canonical when paginated', function () {
    $category = discoveryCategory('Bulk Grains');

    foreach (range(1, 13) as $i) {
        discoveryProduct($category, ['name' => 'Grain Lot '.$i]);
    }

    $html = $this->get(route('storefront.category', $category).'?page=2&utm_source=x')->assertOk()->getContent();

    expect(discoveryCanonical($html))->toBe(route('storefront.category', $category).'?page=2');
});

test('product pages and the home page link to category pages, not the filtered directory', function () {
    $parent = discoveryCategory('Dairy');
    $child = discoveryCategory('Cheese', $parent);
    $product = discoveryProduct($child, ['name' => 'Halal Mozzarella']);

    $html = $this->get(route('storefront.product', $product))->assertOk()->getContent();

    expect($html)->toContain('href="'.route('storefront.category', $child).'"')
        ->and($html)->not->toContain('products?category='.$child->id);

    $this->get('/')->assertOk()->assertSee('href="'.route('storefront.category', $parent).'"', false);
});

// ─── T3.2 Facet and search URL policy ──────────────────────────────────

test('a category facet on the directory is noindex, follow and canonical to the category page', function () {
    discoveryAsProduction();

    $category = discoveryCategory('Sauces');
    discoveryProduct($category);

    $response = $this->get('/products?category='.$category->id)->assertOk();
    $html = $response->getContent();

    $response->assertHeader('X-Robots-Tag', 'noindex, follow');

    expect(discoveryRobotsMeta($html))->toBe('noindex, follow')
        ->and(discoveryCanonical($html))->toBe(route('storefront.category', $category));
});

test('every configured facet parameter makes a listing noindex, follow with the clean canonical', function () {
    discoveryAsProduction();

    $params = config('seo.noindex_follow_query_params');

    expect($params)->not->toBeEmpty()->toContain('category');

    foreach (['/products', '/companies'] as $listing) {
        foreach ($params as $param) {
            $response = $this->get($listing.'?'.$param.'=1&page=3')->assertOk();

            $response->assertHeader('X-Robots-Tag', 'noindex, follow');

            expect(discoveryRobotsMeta($response->getContent()))->toBe('noindex, follow', "{$listing}?{$param}")
                ->and(discoveryCanonical($response->getContent()))->toBe(url($listing), "{$listing}?{$param}");
        }
    }
});

test('tracking parameters and plain pagination stay indexable', function () {
    discoveryAsProduction();

    $tracked = $this->get('/products?utm_source=x&fbclid=y')->assertOk()->assertHeaderMissing('X-Robots-Tag');
    expect(discoveryRobotsMeta($tracked->getContent()))->toBeNull()
        ->and(discoveryCanonical($tracked->getContent()))->toBe(url('/products'));

    $paged = $this->get('/companies?page=2')->assertOk()->assertHeaderMissing('X-Robots-Tag');
    expect(discoveryRobotsMeta($paged->getContent()))->toBeNull()
        ->and(discoveryCanonical($paged->getContent()))->toBe(url('/companies').'?page=2');
});

// ─── T3.3 Sitemaps ─────────────────────────────────────────────────────

test('the sitemap index lists the child sitemaps under the configured origin', function () {
    $category = discoveryCategory('Noodles');
    discoveryProduct($category);

    $xml = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->getContent();

    expect(discoveryLocs($xml))->toBe([
        discoveryUrl('/sitemap-pages.xml'),
        discoveryUrl('/sitemap-categories.xml'),
        discoveryUrl('/sitemap-companies.xml'),
        discoveryUrl('/sitemap-products-1.xml'),
    ])->and($xml)->not->toContain('localhost');

    $document = new DOMDocument;
    expect($document->loadXML($xml))->toBeTrue();
});

test('the static pages sitemap lists every public static route without a lastmod', function () {
    $xml = $this->get('/sitemap-pages.xml')->assertOk()->getContent();

    expect(discoveryLocs($xml))->toBe(array_map(
        fn (string $name): string => discoveryUrl(route($name, absolute: false)),
        config('seo.sitemap.static_routes'),
    ))->and($xml)->not->toContain('<lastmod>');
});

test('child sitemaps are omitted from the index and 404 when they would be empty', function () {
    expect(discoveryLocs($this->get('/sitemap.xml')->getContent()))->toBe([discoveryUrl('/sitemap-pages.xml')]);

    $this->get('/sitemap-categories.xml')->assertNotFound();
    $this->get('/sitemap-companies.xml')->assertNotFound();
    $this->get('/sitemap-products-1.xml')->assertNotFound();
});

test('the product sitemap excludes unpublished, moderated and suspended-shop products', function () {
    $category = discoveryCategory('Oils');
    $visible = discoveryProduct($category);
    $archived = Product::factory()->archived()->create();
    $flagged = Product::factory()->published()->flagged()->create();
    $suspended = Product::factory()->published()->create(['shop_id' => Shop::factory()->suspended()]);

    $locs = discoveryLocs($this->get('/sitemap-products-1.xml')->assertOk()->getContent());

    expect($locs)->toBe([discoveryUrl(route('storefront.product', $visible, absolute: false))])
        ->and($locs)->not->toContain(discoveryUrl(route('storefront.product', $archived, absolute: false)))
        ->and($locs)->not->toContain(discoveryUrl(route('storefront.product', $flagged, absolute: false)))
        ->and($locs)->not->toContain(discoveryUrl(route('storefront.product', $suspended, absolute: false)));
});

test('product sitemaps are split at the configured per-file limit', function () {
    Config::set('seo.sitemap.max_urls_per_file', 2);

    $category = discoveryCategory('Dates');

    foreach (range(1, 3) as $i) {
        discoveryProduct($category, ['name' => 'Medjool Dates '.$i]);
    }

    $index = discoveryLocs($this->get('/sitemap.xml')->getContent());

    expect($index)->toContain(discoveryUrl('/sitemap-products-1.xml'))
        ->toContain(discoveryUrl('/sitemap-products-2.xml'))
        ->not->toContain(discoveryUrl('/sitemap-products-3.xml'))
        ->and(discoveryLocs($this->get('/sitemap-products-1.xml')->getContent()))->toHaveCount(2)
        ->and(discoveryLocs($this->get('/sitemap-products-2.xml')->getContent()))->toHaveCount(1);

    $this->get('/sitemap-products-3.xml')->assertNotFound();
    $this->get('/sitemap-products-0.xml')->assertNotFound();
});

test('lastmod is the real updated_at and is stable between requests', function () {
    $category = discoveryCategory('Honey');
    $product = discoveryProduct($category);

    $first = $this->get('/sitemap-products-1.xml')->getContent();
    $second = $this->get('/sitemap-products-1.xml')->getContent();

    expect($first)->toBe($second)
        ->and($first)->toContain('<lastmod>'.$product->fresh()->updated_at->format(DATE_ATOM).'</lastmod>');
});

test('the category sitemap lists only categories that have visible products', function () {
    $group = discoveryCategory('Snacks');
    $chips = discoveryCategory('Chips', $group);
    $empty = discoveryCategory('Candy', $group);
    discoveryProduct($chips);

    $locs = discoveryLocs($this->get('/sitemap-categories.xml')->assertOk()->getContent());

    expect($locs)->toContain(discoveryUrl(route('storefront.category', $group, absolute: false)))
        ->toContain(discoveryUrl(route('storefront.category', $chips, absolute: false)))
        ->not->toContain(discoveryUrl(route('storefront.category', $empty, absolute: false)));
});

test('the company sitemap lists active companies only', function () {
    $active = Shop::factory()->create(['storefront_name' => 'Active Foods']);
    $suspended = Shop::factory()->suspended()->create(['storefront_name' => 'Suspended Foods']);

    $locs = discoveryLocs($this->get('/sitemap-companies.xml')->assertOk()->getContent());

    expect($locs)->toContain(discoveryUrl(route('storefront.company', $active, absolute: false)))
        ->not->toContain(discoveryUrl(route('storefront.company', $suspended, absolute: false)));
});

test('subscription gating decides which companies and products are listed', function () {
    Config::set('marketplace.features.subscriptions_enabled', true);

    $category = discoveryCategory('Frozen');
    $subscribed = Shop::factory()->create();
    Subscription::factory()->active()->create(['shop_id' => $subscribed->id]);
    $lapsed = Shop::factory()->create();

    $liveProduct = discoveryProduct($category, ['shop_id' => $subscribed->id]);
    $lapsedProduct = discoveryProduct($category, ['shop_id' => $lapsed->id]);

    $companies = discoveryLocs($this->get('/sitemap-companies.xml')->assertOk()->getContent());
    $products = discoveryLocs($this->get('/sitemap-products-1.xml')->assertOk()->getContent());

    expect($companies)->toBe([discoveryUrl(route('storefront.company', $subscribed, absolute: false))])
        ->and($products)->toBe([discoveryUrl(route('storefront.product', $liveProduct, absolute: false))])
        ->and($products)->not->toContain(discoveryUrl(route('storefront.product', $lapsedProduct, absolute: false)));
});

test('sitemaps are rebuilt after the data they list changes', function () {
    $category = discoveryCategory('Pasta');
    $first = discoveryProduct($category);

    $before = discoveryLocs($this->get('/sitemap-products-1.xml')->getContent());

    $second = discoveryProduct($category);

    $after = discoveryLocs($this->get('/sitemap-products-1.xml')->getContent());

    expect($before)->toHaveCount(1)
        ->and($after)->toHaveCount(2)
        ->and($after)->toContain(discoveryUrl(route('storefront.product', $second, absolute: false)));

    $first->update(['status' => 'archived']);

    expect(discoveryLocs($this->get('/sitemap-products-1.xml')->getContent()))->toBe([
        discoveryUrl(route('storefront.product', $second, absolute: false)),
    ]);
});

test('sitemaps 404 when disabled and robots.txt advertises them only when enabled', function () {
    discoveryAsProduction();

    $this->get('/robots.txt')->assertSee('Sitemap: '.url('/sitemap.xml'), false);

    Config::set('seo.sitemap_enabled', false);

    $this->get('/sitemap.xml')->assertNotFound();
    $this->get('/sitemap-pages.xml')->assertNotFound();
    $this->get('/robots.txt')->assertDontSee('Sitemap:');
});

test('sitemap xml escapes reserved characters', function () {
    $xml = SitemapXml::urlset([['loc' => 'https://seo.example.test/p?a=1&b=<2>"\'']]);

    $document = new DOMDocument;

    expect($xml)->toContain('&amp;')->toContain('&lt;2&gt;')
        ->and($document->loadXML($xml))->toBeTrue()
        ->and($xml)->not->toContain('<2>');
});

test('no static sitemap files exist in public', function () {
    expect(glob(public_path('sitemap*.xml')))->toBeEmpty();
});

// ─── T3.4 hreflang ─────────────────────────────────────────────────────

function discoveryPortals(array $urls): array
{
    $registry = [
        'PH' => 'en',
        'MY' => 'ms',
        'SA' => 'ar',
    ];

    $portals = [];

    foreach ($registry as $country => $locale) {
        $portals[] = [
            'country_code' => $country,
            'display_name' => $country,
            'card_heading' => mb_strtoupper($country),
            'flag' => '🏳',
            'locale' => $locale,
            'url' => $urls[$country] ?? null,
            'landmark_image' => '/images/countries/'.mb_strtolower($country).'.jpg',
        ];
    }

    return $portals;
}

test('hreflang is not emitted when no portal urls are configured', function () {
    Config::set('marketplace.portals', discoveryPortals([]));

    expect($this->get('/')->assertOk()->getContent())->not->toContain('hreflang=');
});

test('hreflang lists every configured portal with a self reference on the home page only', function () {
    Config::set('marketplace.instance_country', 'PH');
    Config::set('marketplace.portals', discoveryPortals([
        'PH' => 'https://ph.example.test',
        'MY' => 'https://my.example.test/',
    ]));

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<link rel="alternate" hreflang="en-PH" href="https://ph.example.test/">')
        ->toContain('<link rel="alternate" hreflang="ms-MY" href="https://my.example.test/">')
        ->not->toContain('hreflang="ar-SA"')
        ->not->toContain('hreflang="x-default"')
        ->and($this->get('/about')->getContent())->not->toContain('hreflang=');
});

test('x-default points at the configured default portal', function () {
    Config::set('marketplace.instance_country', 'MY');
    Config::set('seo.hreflang.x_default_country', 'PH');
    Config::set('marketplace.portals', discoveryPortals([
        'PH' => 'https://ph.example.test',
        'MY' => 'https://my.example.test',
    ]));

    expect($this->get('/')->getContent())->toContain('<link rel="alternate" hreflang="x-default" href="https://ph.example.test/">');
});

test('hreflang needs this instance and at least one other portal to be configured', function () {
    Config::set('marketplace.instance_country', 'PH');

    Config::set('marketplace.portals', discoveryPortals(['MY' => 'https://my.example.test']));
    expect($this->get('/')->getContent())->not->toContain('hreflang=');

    Config::set('marketplace.portals', discoveryPortals(['PH' => 'https://ph.example.test']));
    expect($this->get('/')->getContent())->not->toContain('hreflang=');
});

test('the html language and og locale follow the effective locale, not a cross-country alternate', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<html lang="'.str_replace('_', '-', app()->getLocale()).'"')
        ->and($html)->toContain('property="og:locale"');
});

// ─── T5.2 llms.txt ─────────────────────────────────────────────────────

test('llms.txt only exists where indexing is allowed', function () {
    $this->get('/llms.txt')->assertNotFound();

    discoveryAsProduction();
    Config::set('seo.force_noindex', true);
    $this->get('/llms.txt')->assertNotFound();

    Config::set('seo.force_noindex', false);
    $this->get('/llms.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    expect(glob(public_path('llms*.txt')))->toBeEmpty();
});

test('llms.txt is a markdown guide that links only public pages under the configured origin', function () {
    discoveryAsProduction();
    discoveryProduct(discoveryCategory('Noodles'));
    Shop::factory()->create(['status' => 'active', 'email' => 'private@acme.test', 'phone' => '+639171234567']);

    $body = $this->get('/llms.txt')->assertOk()->getContent();

    preg_match_all('/\]\((https?:[^)]+)\)/', $body, $links);

    expect($body)->toStartWith('# '.config('seo.site_name')."\n\n> ")
        ->and($body)->toContain('## Key pages')->toContain('## Directories')
        ->and($links[1])->not->toBeEmpty()
        ->and($body)->not->toContain('private@acme.test')->not->toContain('+639171234567');

    foreach ($links[1] as $link) {
        expect($link)->toStartWith('https://seo.example.test/');
    }

    // Every link is a public, indexable route, never a private area.
    foreach (app(IndexingPolicy::class)->privatePathPatterns() as $pattern) {
        expect($body)->not->toContain('https://seo.example.test/'.$pattern);
    }
});

test('llms.txt lists only top-level categories that have visible products', function () {
    discoveryAsProduction();
    $snacks = discoveryCategory('Snacks');
    $chips = discoveryCategory('Chips', $snacks);
    discoveryCategory('Candy');
    discoveryProduct($chips);

    $body = $this->get('/llms.txt')->getContent();

    expect($body)->toContain('- [Snacks]('.discoveryUrl(route('storefront.category', $snacks, absolute: false)).')')
        ->not->toContain('[Chips]')
        ->not->toContain('[Candy]');
});

test('llms.txt states a capability only while its flag is on', function () {
    discoveryAsProduction();

    Config::set('marketplace.features.escrow_payments_enabled', false);
    expect($this->get('/llms.txt')->getContent())->not->toContain('escrow');

    app(SitemapCache::class)->flush();
    Config::set('marketplace.features.escrow_payments_enabled', true);
    expect($this->get('/llms.txt')->getContent())->toContain('Order payments are protected by escrow.');
});

test('llms.txt is served from the sitemap cache and refreshed when it is flushed', function () {
    discoveryAsProduction();
    $category = discoveryCategory('Oils');
    $product = Product::factory()->published()->create();

    // Built and cached while the category has no product.
    expect($this->get('/llms.txt')->getContent())->not->toContain('[Oils]');

    // A pivot write fires no model event, so the cached copy is still served...
    $product->categories()->attach($category);
    expect($this->get('/llms.txt')->getContent())->not->toContain('[Oils]');

    // ...until the sitemap cache is flushed.
    app(SitemapCache::class)->flush();
    expect($this->get('/llms.txt')->getContent())->toContain('[Oils]');
});
