<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\ShopCertificate;
use App\Support\Schema\JsonLdValidator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Config::set('marketplace.features.subscriptions_enabled', false);
    Config::set('marketplace.features.reviews_enabled', true);
    Config::set('seo.social_profiles', ['https://www.facebook.com/example']);
    Config::set('seo.contact.email', 'help@example.test');
});

/**
 * One catalogue that exercises every node type the storefront emits: a supplier
 * with a verified certificate and a rating, a single-variant product with volume
 * tiers, a multi-variant product, and a category tree holding them.
 *
 * @return array{urls: list<string>, shop: Shop, single: Product, group: Product}
 */
function jsonLdPolicyCatalogue(): array
{
    $shop = Shop::factory()->create([
        'status' => 'active',
        'storefront_name' => 'Acme Halal Foods',
        'company_description' => '<p>Verified <strong>halal</strong> producer.</p>',
        'city' => 'Cebu City',
        'province' => 'Cebu',
        'country_code' => 'PH',
        'capabilities' => ['cold_storage'],
        'value_chain_positions' => ['processing'],
        'average_rating' => 4.5,
        'total_reviews' => 3,
    ]);
    ShopCertificate::factory()->create(['shop_id' => $shop->id, 'certification_body' => 'IDCP']);

    $parent = Category::factory()->create(['name' => 'Foods', 'slug' => 'foods']);
    $child = Category::factory()->childOf($parent)->create(['name' => 'Rice', 'slug' => 'rice']);

    $single = Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Jasmine Rice',
        'description' => '<p>Premium <b>rice</b>.</p>',
        'featured_image_path' => 'products/rice.jpg',
        'average_rating' => 4.2,
        'total_reviews' => 2,
    ]);
    $single->categories()->attach($child);
    ProductVariant::factory()->published()->create([
        'product_id' => $single->id,
        'sku' => 'RC-001',
        'attributes' => ['size' => '25kg'],
        'price' => 100,
        'currency' => 'PHP',
        'minimum_order_quantity' => 10,
        'unit' => 'bag',
        'quantity_breaks' => [['min_qty' => 100, 'price' => 90.5]],
    ]);

    $group = Product::factory()->published()->create(['shop_id' => $shop->id, 'name' => 'Cooking Oil']);
    $group->categories()->attach($child);
    ProductVariant::factory()->published()->create(['product_id' => $group->id, 'sku' => 'OIL-1L', 'attributes' => ['size' => '1L'], 'price' => 50, 'quantity_breaks' => []]);
    ProductVariant::factory()->published()->create(['product_id' => $group->id, 'sku' => 'OIL-5L', 'attributes' => ['size' => '5L'], 'price' => 220, 'quantity_breaks' => []]);

    return [
        'urls' => [
            '/',
            '/about',
            '/faq',
            '/terms',
            '/privacy',
            '/companies',
            '/products',
            '/certification/apply',
            '/request-a-product',
            route('storefront.category', $parent),
            route('storefront.category', $child),
            route('storefront.product', $single),
            route('storefront.product', $group),
            route('storefront.company', $shop),
        ],
        'shop' => $shop,
        'single' => $single,
        'group' => $group,
    ];
}

/**
 * Every `@type` in a document, however deeply nested.
 *
 * @param  array<mixed>  $value
 * @return list<string>
 */
function jsonLdPolicyTypes(array $value): array
{
    $types = [];

    foreach ($value as $key => $item) {
        if ($key === '@type' && is_string($item)) {
            $types[] = $item;
        } elseif (is_array($item)) {
            array_push($types, ...jsonLdPolicyTypes($item));
        }
    }

    return $types;
}

function jsonLdPolicyDocument(string $html): array
{
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    return json_decode($matches[1] ?? '{}', true) ?: [];
}

test('every public page type renders a valid JSON-LD document', function () {
    $catalogue = jsonLdPolicyCatalogue();
    $validator = new JsonLdValidator;
    $failures = [];
    $types = [];

    foreach ($catalogue['urls'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();
        $errors = $validator->validateHtml($html);

        if ($errors !== []) {
            $failures[$url] = $errors;
        }

        array_push($types, ...jsonLdPolicyTypes(jsonLdPolicyDocument($html)));
    }

    expect($failures)->toBe([]);

    // Guard against a vacuous pass: the catalogue really did exercise each emitted type.
    foreach (['Organization', 'WebSite', 'WebPage', 'CollectionPage', 'AboutPage', 'FAQPage', 'Question', 'BreadcrumbList', 'ItemList', 'Product', 'ProductGroup', 'Offer', 'UnitPriceSpecification', 'AggregateRating', 'Certification', 'PostalAddress'] as $type) {
        expect($types)->toContain($type);
    }
});

test('hostile names stay valid, parseable JSON-LD and cannot add a script element', function () {
    $hostile = '</script><script>alert(1)</script> & "quotes"';
    $shop = Shop::factory()->create(['status' => 'active', 'storefront_name' => $hostile]);
    $product = Product::factory()->published()->create(['shop_id' => $shop->id, 'name' => $hostile]);
    ProductVariant::factory()->published()->create(['product_id' => $product->id, 'price' => 10, 'quantity_breaks' => []]);

    foreach ([route('storefront.company', $shop), route('storefront.product', $product), '/companies', '/products'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect(substr_count($html, 'application/ld+json'))->toBe(1)
            ->and((new JsonLdValidator)->validateHtml($html))->toBe([])
            ->and(substr($html, 0, (int) strpos($html, '</head>')))->not->toContain('<script>alert(1)</script>');
    }
});

test('no private shop field reaches the head, JSON-LD, sitemaps, llms.txt or robots.txt', function () {
    app()->detectEnvironment(fn (): string => 'production');
    Config::set('seo.force_noindex', false);

    $shop = Shop::factory()->create([
        'status' => 'active',
        'registered_business_name' => 'SECRET REGISTERED CORP',
        'phone' => '+63 917 123 4567',
        'email' => 'private@acme-secret.test',
        'address_1' => '12 Secret Street',
        'address_2' => 'Unit 99 Hidden Block',
        'zip_code' => '6099',
        'dti_sec_number' => 'DTI-SECRET-4242',
        'tax_identification_number' => '987-654-321-000',
        'fda_lto_number' => 'LTO-SECRET-7777',
    ]);
    ShopCertificate::factory()->create(['shop_id' => $shop->id, 'registration_number' => 'REG-SECRET-8888']);

    $category = Category::factory()->create(['name' => 'Foods', 'slug' => 'foods']);
    $product = Product::factory()->published()->create(['shop_id' => $shop->id]);
    $product->categories()->attach($category);
    ProductVariant::factory()->published()->create(['product_id' => $product->id]);

    $secrets = ['SECRET REGISTERED CORP', '+63 917 123 4567', 'private@acme-secret.test', '12 Secret Street', 'Unit 99 Hidden Block', '6099', 'DTI-SECRET-4242', '987-654-321-000', 'LTO-SECRET-7777', 'REG-SECRET-8888'];

    $pages = ['/', '/companies', '/products', route('storefront.category', $category), route('storefront.company', $shop), route('storefront.product', $product)];

    foreach ($pages as $url) {
        $html = $this->get($url)->assertOk()->getContent();
        $head = substr($html, 0, (int) strpos($html, '</head>'));

        foreach ($secrets as $secret) {
            expect($head)->not->toContain($secret);
        }
    }

    $machineReadable = [
        route('sitemap.index'),
        route('sitemap.pages'),
        route('sitemap.categories'),
        route('sitemap.companies'),
        route('sitemap.products', ['page' => 1]),
        '/llms.txt',
        '/robots.txt',
    ];

    // The 4-digit zip code could match a timestamp or id in an XML file, so the long, unique secrets stand in for it here.
    $distinctSecrets = array_filter($secrets, static fn (string $secret): bool => strlen($secret) > 5);

    foreach ($machineReadable as $url) {
        $body = $this->get($url)->assertOk()->getContent();

        foreach ($distinctSecrets as $secret) {
            expect($body)->not->toContain($secret);
        }
    }
});
