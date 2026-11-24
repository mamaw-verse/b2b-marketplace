<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Shop;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Config::set('marketplace.features.subscriptions_enabled', false);
});

function seoMetaContent(string $html, string $attribute, string $name): ?string
{
    $pattern = '/<meta\s+'.$attribute.'="'.preg_quote($name, '/').'"\s+content="([^"]*)"/i';

    return preg_match($pattern, $html, $matches) === 1 ? html_entity_decode($matches[1]) : null;
}

function seoCanonical(string $html): ?string
{
    return preg_match('/<link rel="canonical" href="([^"]*)"/', $html, $matches) === 1 ? $matches[1] : null;
}

function seoTitle(string $html): ?string
{
    return preg_match('/<title>(.*?)<\/title>/s', $html, $matches) === 1 ? trim(html_entity_decode($matches[1])) : null;
}

test('no view pushes per-page meta any more', function () {
    $matches = collect(glob(resource_path('views/livewire/**/*.blade.php'), GLOB_BRACE))
        ->merge(glob(resource_path('views/livewire/*/*.blade.php')))
        ->filter(fn (string $file) => str_contains((string) file_get_contents($file), "push('meta')"));

    expect($matches)->toBeEmpty()
        ->and(file_get_contents(resource_path('views/layouts/storefront.blade.php')))->not->toContain("stack('meta')");
});

test('every public page has a unique non-empty title and description plus one canonical', function () {
    $paths = ['/', '/about', '/faq', '/terms', '/privacy', '/companies', '/products', '/certification/apply', '/request-a-product'];

    $titles = [];
    $descriptions = [];

    foreach ($paths as $path) {
        $html = $this->get($path)->assertOk()->getContent();

        $title = seoTitle($html);
        $description = seoMetaContent($html, 'name', 'description');

        expect($title)->not->toBeEmpty("title for {$path}")
            ->and($description)->not->toBeEmpty("description for {$path}")
            ->and(substr_count($html, 'rel="canonical"'))->toBe(1, "canonical count for {$path}")
            ->and(seoCanonical($html))->toBe(url($path === '/' ? '/' : $path))
            ->and(seoMetaContent($html, 'property', 'og:url'))->toBe(seoCanonical($html));

        $titles[] = $title;
        $descriptions[] = $description;
    }

    expect(array_unique($titles))->toHaveCount(count($paths))
        ->and(array_unique($descriptions))->toHaveCount(count($paths));
});

test('canonical strips tracking and filter parameters but keeps pagination', function () {
    $html = $this->get('/products?utm_source=x&fbclid=y&gclid=z&ref=q&search=rice&category=3')->assertOk()->getContent();
    expect(seoCanonical($html))->toBe(url('/products'));

    $html = $this->get('/companies?page=2&utm_medium=email')->assertOk()->getContent();
    expect(seoCanonical($html))->toBe(url('/companies').'?page=2');
});

test('the default og image is 1200x630 with locale and site name', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect(seoMetaContent($html, 'property', 'og:image'))->toBe(asset('og-default.jpg'))
        ->and(seoMetaContent($html, 'property', 'og:image:width'))->toBe('1200')
        ->and(seoMetaContent($html, 'property', 'og:image:height'))->toBe('630')
        ->and(seoMetaContent($html, 'property', 'og:locale'))->toBe('en_US')
        ->and(seoMetaContent($html, 'property', 'og:site_name'))->toBe(config('seo.site_name'))
        ->and(seoMetaContent($html, 'name', 'twitter:card'))->toBe('summary_large_image')
        ->and(seoMetaContent($html, 'name', 'twitter:image'))->toBe(asset('og-default.jpg'));
});

test('product pages use product meta with a clean canonical', function () {
    $product = Product::factory()->published()->create(['name' => 'Canonical Product', 'description' => '<p>Premium <strong>halal</strong> rice.</p>']);

    $html = $this->get(route('storefront.product', $product).'?utm_source=newsletter&fbclid=abc')->assertOk()->getContent();

    expect(seoCanonical($html))->toBe(route('storefront.product', $product))
        ->and(seoMetaContent($html, 'property', 'og:type'))->toBe('product')
        ->and(seoMetaContent($html, 'name', 'description'))->toBe('Premium halal rice.')
        ->and(seoTitle($html))->toContain('Canonical Product');
});

test('company pages use the shop name and public description only', function () {
    $shop = Shop::factory()->create([
        'status' => 'active',
        'storefront_name' => 'Acme Halal Foods',
        'company_description' => 'Verified halal producer.',
        'phone' => '+639171234567',
        'email' => 'private@acme.test',
    ]);

    $html = $this->get(route('storefront.company', $shop))->assertOk()->getContent();

    expect(seoCanonical($html))->toBe(route('storefront.company', $shop))
        ->and(seoMetaContent($html, 'name', 'description'))->toBe('Verified halal producer.')
        ->and(seoTitle($html))->toContain('Acme Halal Foods');

    $head = substr($html, 0, (int) strpos($html, '</head>'));
    expect($head)->not->toContain('+639171234567')->and($head)->not->toContain('private@acme.test');
});

test('meta robots follows the indexing policy', function () {
    $this->get('/about')->assertSee('<meta name="robots" content="noindex, nofollow">', false);

    app()->detectEnvironment(fn (): string => 'production');
    Config::set('seo.force_noindex', false);

    $this->get('/about')->assertDontSee('name="robots"', false);
});

test('hostile product names cannot break out of head attributes', function () {
    $product = Product::factory()->published()->create(['name' => 'Evil "><script>alert(1)</script>']);

    $html = $this->get(route('storefront.product', $product))->assertOk()->getContent();
    $head = substr($html, 0, (int) strpos($html, '</head>'));

    expect($head)->not->toContain('<script>alert(1)</script>');
});
