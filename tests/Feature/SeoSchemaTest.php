<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\ShopCertificate;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Config::set('marketplace.features.subscriptions_enabled', false);
    Config::set('marketplace.features.reviews_enabled', false);
});

/**
 * The site origin without a trailing slash; sitewide ids are `{origin}/#organization`.
 */
function schemaBaseUrl(): string
{
    return rtrim(url('/'), '/');
}

/**
 * The single JSON-LD document in a page, decoded. Fails the test when there is
 * not exactly one `<script type="application/ld+json">`.
 *
 * @return array{'@context': string, '@graph': list<array<string, mixed>>}
 */
function schemaFromHtml(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    expect($matches[1])->toHaveCount(1);

    $decoded = json_decode($matches[1][0], true, 512, JSON_THROW_ON_ERROR);

    expect($decoded)->toBeArray();

    return $decoded;
}

/**
 * @param  array{'@graph': list<array<string, mixed>>}  $schema
 * @return list<array<string, mixed>>
 */
function schemaNodesOfType(array $schema, string $type): array
{
    return array_values(array_filter($schema['@graph'], static fn (array $node): bool => $node['@type'] === $type));
}

/**
 * Structural checks every emitted document must pass: one context, typed nodes,
 * unique absolute ids, resolvable references and no empty values.
 *
 * @param  array<string, mixed>  $schema
 */
function expectValidSchemaGraph(array $schema): void
{
    expect($schema['@context'])->toBe('https://schema.org')
        ->and($schema['@graph'])->toBeArray()->not->toBeEmpty();

    $ids = [];

    foreach ($schema['@graph'] as $node) {
        expect($node)->toHaveKey('@type');

        if (isset($node['@id'])) {
            expect($ids)->not->toContain($node['@id'])
                ->and(filter_var($node['@id'], FILTER_VALIDATE_URL))->not->toBeFalse();
            $ids[] = $node['@id'];
        }
    }

    $references = [];
    $empties = [];

    $walk = function (mixed $value, string $path) use (&$walk, &$references, &$empties): void {
        if (! is_array($value)) {
            if ($value === null || $value === '') {
                $empties[] = $path;
            }

            return;
        }

        if ($value === []) {
            $empties[] = $path;

            return;
        }

        if (array_keys($value) === ['@id']) {
            $references[] = $value['@id'];

            return;
        }

        foreach ($value as $key => $item) {
            $walk($item, $path.'/'.$key);
        }
    };

    $walk($schema['@graph'], '@graph');

    expect($empties)->toBe([]);

    foreach ($references as $reference) {
        expect($ids)->toContain($reference);
    }
}

function pageSchema(mixed $testCase, string $url): array
{
    return schemaFromHtml($testCase->get($url)->assertOk()->getContent());
}

test('every public page emits one valid JSON-LD graph with the sitewide nodes', function () {
    $product = Product::factory()->published()->create();
    $shop = Shop::factory()->create(['status' => 'active']);

    $urls = ['/', '/about', '/faq', '/terms', '/privacy', '/companies', '/products', '/certification/apply', '/request-a-product', route('storefront.product', $product), route('storefront.company', $shop)];

    foreach ($urls as $url) {
        $schema = pageSchema($this, $url);

        expectValidSchemaGraph($schema);

        $ids = array_column($schema['@graph'], '@id');

        expect($ids)->toContain(schemaBaseUrl().'/#organization')
            ->and($ids)->toContain(schemaBaseUrl().'/#website');
    }
});

test('the home page carries the full organization with a square logo and the instance country', function () {
    Config::set('marketplace.instance_country', 'MY');
    Config::set('seo.social_profiles', ['https://www.facebook.com/example']);
    Config::set('seo.contact.email', 'help@example.test');

    $organization = schemaNodesOfType(pageSchema($this, '/'), 'Organization')[0];

    expect($organization['@id'])->toBe(schemaBaseUrl().'/#organization')
        ->and($organization['areaServed'])->toBe(['@type' => 'Country', 'name' => 'Malaysia'])
        ->and($organization['sameAs'])->toBe(['https://www.facebook.com/example'])
        ->and($organization['contactPoint']['email'])->toBe('help@example.test')
        ->and($organization['logo']['@type'])->toBe('ImageObject')
        ->and($organization['logo']['width'])->toBe($organization['logo']['height'])
        ->and($organization['knowsAbout'])->toContain('Halal certification');
});

test('sameAs and contactPoint are omitted when nothing is configured, and other pages carry only the minimal organization', function () {
    $home = schemaNodesOfType(pageSchema($this, '/'), 'Organization')[0];
    $about = schemaNodesOfType(pageSchema($this, '/about'), 'Organization')[0];

    expect($home)->not->toHaveKey('sameAs')->and($home)->not->toHaveKey('contactPoint')
        ->and($about)->not->toHaveKey('description')->and($about)->not->toHaveKey('areaServed');
});

test('the website node references its publisher and has no search action', function () {
    $website = schemaNodesOfType(pageSchema($this, '/'), 'WebSite')[0];

    expect($website['publisher'])->toBe(['@id' => schemaBaseUrl().'/#organization'])
        ->and($website['inLanguage'])->toBe('en')
        ->and($website)->not->toHaveKey('potentialAction');
});

test('each page uses the matching WebPage type and a breadcrumb that mirrors its trail', function () {
    $expectations = [
        '/' => ['WebPage', null],
        '/about' => ['AboutPage', ['Home', 'About Us']],
        '/faq' => ['WebPage', ['Home', 'Frequently Asked Questions']],
        '/terms' => ['WebPage', ['Home', 'Terms of Service']],
        '/privacy' => ['WebPage', ['Home', 'Privacy Policy']],
        '/companies' => ['CollectionPage', ['Home', 'Companies']],
        '/products' => ['CollectionPage', ['Home', 'Products']],
        '/certification/apply' => ['WebPage', ['Home', 'Apply for Certificate']],
        '/request-a-product' => ['WebPage', ['Home', 'Request a Product']],
    ];

    foreach ($expectations as $path => [$type, $trail]) {
        $schema = pageSchema($this, $path);
        $page = schemaNodesOfType($schema, $type)[0];
        $canonical = url($path === '/' ? '/' : $path);

        expect($page['url'])->toBe($canonical)
            ->and($page['@id'])->toBe($canonical.'#webpage')
            ->and($page['isPartOf'])->toBe(['@id' => schemaBaseUrl().'/#website'])
            ->and($page['inLanguage'])->toBe('en');

        $breadcrumbs = schemaNodesOfType($schema, 'BreadcrumbList');

        if ($trail === null) {
            expect($breadcrumbs)->toBeEmpty()->and($page)->not->toHaveKey('breadcrumb');

            continue;
        }

        expect(array_column($breadcrumbs[0]['itemListElement'], 'name'))->toBe($trail)
            ->and(array_column($breadcrumbs[0]['itemListElement'], 'position'))->toBe(range(1, count($trail)))
            ->and($page['breadcrumb'])->toBe(['@id' => $breadcrumbs[0]['@id']]);
    }
});

test('the about page names the marketplace organization as its main entity', function () {
    $page = schemaNodesOfType(pageSchema($this, '/about'), 'AboutPage')[0];

    expect($page['mainEntity'])->toBe(['@id' => schemaBaseUrl().'/#organization']);
});

test('legal pages carry their real modified date and other pages none', function () {
    expect(schemaNodesOfType(pageSchema($this, '/terms'), 'WebPage')[0]['dateModified'])->toBe('2026-09-16')
        ->and(schemaNodesOfType(pageSchema($this, '/privacy'), 'WebPage')[0]['dateModified'])->toBe('2026-09-16')
        ->and(schemaNodesOfType(pageSchema($this, '/faq'), 'WebPage')[0])->not->toHaveKey('dateModified')
        ->and(schemaNodesOfType(pageSchema($this, '/about'), 'AboutPage')[0])->not->toHaveKey('dateModified');
});

test('the faq page is a plain WebPage until its Q&A is a shared data source', function () {
    expect(schemaNodesOfType(pageSchema($this, '/faq'), 'FAQPage'))->toBeEmpty();
});

test('the terms and privacy pages match the date they display', function () {
    foreach (['/terms', '/privacy'] as $path) {
        $this->get($path)->assertOk()->assertSee('Last Updated: September 16, 2026');
    }
});

test('listing pages emit an ItemList whose items share the detail page ids', function () {
    $shop = Shop::factory()->create(['status' => 'active', 'country_code' => config('marketplace.instance_country')]);
    $products = Product::factory()->published()->count(3)->create(['shop_id' => $shop->id]);

    $productSchema = pageSchema($this, '/products');
    $list = schemaNodesOfType($productSchema, 'ItemList')[0];

    expect($list['@id'])->toBe(url('/products').'#itemlist')
        ->and($list['itemListElement'])->toHaveCount(3)
        ->and(schemaNodesOfType($productSchema, 'CollectionPage')[0]['mainEntity'])->toBe(['@id' => $list['@id']]);

    $ids = collect($list['itemListElement'])->pluck('item.@id')->all();

    foreach ($products as $product) {
        expect($ids)->toContain(route('storefront.product', $product).'#product');
    }

    $companyList = schemaNodesOfType(pageSchema($this, '/companies'), 'ItemList')[0];

    expect(collect($companyList['itemListElement'])->pluck('item.@id')->all())
        ->toContain(route('storefront.company', $shop).'#organization');
});

test('an empty listing emits no ItemList', function () {
    $schema = pageSchema($this, '/products');

    expect(schemaNodesOfType($schema, 'ItemList'))->toBeEmpty()
        ->and(schemaNodesOfType($schema, 'CollectionPage')[0])->not->toHaveKey('mainEntity');
});

test('list positions continue across pages', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    Product::factory()->published()->count(14)->create(['shop_id' => $shop->id]);

    $list = schemaNodesOfType(pageSchema($this, '/products?page=2'), 'ItemList')[0];

    expect(array_column($list['itemListElement'], 'position'))->toBe([13, 14]);
});

test('hostile product and company names cannot break out of the JSON-LD script', function () {
    $hostile = '</script><script>alert(1)</script>';
    $shop = Shop::factory()->create(['status' => 'active', 'storefront_name' => 'Evil '.$hostile, 'company_description' => '<script>alert(2)</script><p>Honest description</p>']);
    $product = Product::factory()->published()->create(['shop_id' => $shop->id, 'name' => $hostile, 'description' => '<img src=x onerror=alert(3)>Fine']);

    foreach ([route('storefront.product', $product), route('storefront.company', $shop)] as $url) {
        $html = $this->get($url)->assertOk()->getContent();
        $schema = schemaFromHtml($html);

        expect($html)->not->toContain($hostile);
        expectValidSchemaGraph($schema);
    }

    $companyNode = schemaNodesOfType(pageSchema($this, route('storefront.company', $shop)), 'Organization');
    $supplier = collect($companyNode)->firstWhere('@id', route('storefront.company', $shop).'#organization');

    expect($supplier['name'])->toBe('Evil '.$hostile)->and($supplier['description'])->toBe('Honest description');
});

/*
|--------------------------------------------------------------------------
| Supplier (company) node
|--------------------------------------------------------------------------
*/

function supplierNode(mixed $testCase, Shop $shop): array
{
    $schema = pageSchema($testCase, route('storefront.company', $shop));

    return collect(schemaNodesOfType($schema, 'Organization'))->firstWhere('@id', route('storefront.company', $shop).'#organization');
}

test('the supplier is its own organization, a member of the marketplace, with public data only', function () {
    $shop = Shop::factory()->create([
        'status' => 'active',
        'storefront_name' => 'Acme Halal Foods',
        'company_description' => '<p>Verified <strong>halal</strong> producer.</p>',
        'city' => 'Cebu City',
        'province' => 'Cebu',
        'country_code' => 'PH',
        'capabilities' => ['cold_storage', 'private_label'],
        'value_chain_positions' => ['processing'],
    ]);

    $schema = pageSchema($this, route('storefront.company', $shop));
    $supplier = supplierNode($this, $shop);
    $page = schemaNodesOfType($schema, 'WebPage')[0];

    expect($supplier['@id'])->not->toBe(schemaBaseUrl().'/#organization')
        ->and($supplier['url'])->toBe(route('storefront.company', $shop))
        ->and($supplier['name'])->toBe('Acme Halal Foods')
        ->and($supplier['description'])->toBe('Verified halal producer.')
        ->and($supplier['address'])->toBe(['@type' => 'PostalAddress', 'addressLocality' => 'Cebu City', 'addressRegion' => 'Cebu', 'addressCountry' => 'PH'])
        ->and($supplier['areaServed'])->toBe(['@type' => 'Country', 'name' => 'Philippines'])
        ->and($supplier['knowsAbout'])->toBe(['Cold storage', 'Private label', 'Processing'])
        ->and($supplier['memberOf'])->toBe(['@id' => schemaBaseUrl().'/#organization'])
        ->and($supplier)->not->toHaveKey('logo')->and($supplier)->not->toHaveKey('image')
        ->and($page['mainEntity'])->toBe(['@id' => $supplier['@id']])
        ->and(array_column(schemaNodesOfType($schema, 'BreadcrumbList')[0]['itemListElement'], 'name'))->toBe(['Home', 'Companies', 'Acme Halal Foods']);
});

test('no private shop field appears anywhere in the head or JSON-LD of a company or product page', function () {
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
    $product = Product::factory()->published()->create(['shop_id' => $shop->id]);
    ProductVariant::factory()->published()->create(['product_id' => $product->id]);

    $secrets = ['SECRET REGISTERED CORP', '+63 917 123 4567', 'private@acme-secret.test', '12 Secret Street', 'Unit 99 Hidden Block', '6099', 'DTI-SECRET-4242', '987-654-321-000', 'LTO-SECRET-7777', 'REG-SECRET-8888'];

    foreach ([route('storefront.company', $shop), route('storefront.product', $product)] as $url) {
        $html = $this->get($url)->assertOk()->getContent();
        $head = substr($html, 0, (int) strpos($html, '</head>'));

        foreach ($secrets as $secret) {
            expect($head)->not->toContain($secret);
        }
    }
});

test('only active verified unexpired certificates are published, by body name', function () {
    $shop = Shop::factory()->create(['status' => 'active']);

    ShopCertificate::factory()->create(['shop_id' => $shop->id, 'certification_body' => 'IDCP']);
    ShopCertificate::factory()->otherBody('Custom Halal Board')->create(['shop_id' => $shop->id]);
    ShopCertificate::factory()->expired()->create(['shop_id' => $shop->id, 'certification_body' => 'HDIP']);
    ShopCertificate::factory()->underReview()->create(['shop_id' => $shop->id, 'certification_body' => 'PRIME_CERTIFICATION']);
    ShopCertificate::factory()->create(['shop_id' => $shop->id, 'certification_body' => 'PRIME_CERTIFICATION', 'is_verified' => false]);
    ShopCertificate::factory()->create(['shop_id' => $shop->id, 'certification_body' => 'HDIP', 'expiry_date' => now()->subDay()]);

    $certifications = supplierNode($this, $shop)['hasCertification'];

    expect(array_column($certifications, 'name'))->toBe(['IDCP', 'Custom Halal Board'])
        ->and($certifications[0]['issuedBy'])->toBe(['@type' => 'Organization', 'name' => 'IDCP'])
        ->and($certifications[1]['issuedBy'])->toBe(['@type' => 'Organization', 'name' => 'Custom Halal Board'])
        ->and($certifications[0]['certificationStatus'])->toBe('https://schema.org/CertificationActive')
        ->and(json_encode($certifications))->not->toContain('registration')->and(json_encode($certifications))->not->toContain('expiry');
});

test('a supplier without a publishable certificate has no hasCertification', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    ShopCertificate::factory()->expired()->create(['shop_id' => $shop->id]);

    expect(supplierNode($this, $shop))->not->toHaveKey('hasCertification');
});

test('company and product aggregateRating need reviews enabled, at least one review and a rating', function () {
    $shop = Shop::factory()->create(['status' => 'active', 'average_rating' => 4.5, 'total_reviews' => 3]);
    $product = Product::factory()->published()->create(['shop_id' => $shop->id, 'average_rating' => 4.2, 'total_reviews' => 2]);
    ProductVariant::factory()->published()->create(['product_id' => $product->id]);

    expect(supplierNode($this, $shop))->not->toHaveKey('aggregateRating')
        ->and(productNode($this, $product))->not->toHaveKey('aggregateRating');

    Config::set('marketplace.features.reviews_enabled', true);

    expect(supplierNode($this, $shop)['aggregateRating'])->toBe(['@type' => 'AggregateRating', 'ratingValue' => 4.5, 'reviewCount' => 3, 'bestRating' => 5, 'worstRating' => 1])
        ->and(productNode($this, $product)['aggregateRating'])->toBe(['@type' => 'AggregateRating', 'ratingValue' => 4.2, 'reviewCount' => 2, 'bestRating' => 5, 'worstRating' => 1]);

    $shop->update(['total_reviews' => 0]);
    $product->update(['total_reviews' => 0]);

    expect(supplierNode($this, $shop->fresh()))->not->toHaveKey('aggregateRating')
        ->and(productNode($this, $product->fresh()))->not->toHaveKey('aggregateRating');
});

/*
|--------------------------------------------------------------------------
| Product node
|--------------------------------------------------------------------------
*/

function productNode(mixed $testCase, Product $product): array
{
    $schema = pageSchema($testCase, route('storefront.product', $product));
    $id = route('storefront.product', $product).'#product';

    return collect($schema['@graph'])->firstWhere('@id', $id);
}

test('a single-variant product is a Product whose offer equals the displayed price, currency, MOQ, unit and tiers', function () {
    $shop = Shop::factory()->create(['status' => 'active', 'storefront_name' => 'Rice Co']);
    $parent = Category::factory()->create(['name' => 'Foods']);
    $child = Category::factory()->childOf($parent)->create(['name' => 'Rice']);
    $product = Product::factory()->published()->create(['shop_id' => $shop->id, 'name' => 'Jasmine Rice', 'description' => '<p>Premium <b>rice</b>.</p>', 'featured_image_path' => 'products/rice.jpg']);
    $product->categories()->attach($child);
    $variant = ProductVariant::factory()->published()->create([
        'product_id' => $product->id,
        'sku' => 'RC-001',
        'attributes' => ['size' => '25kg'],
        'price' => 100,
        'currency' => 'MYR',
        'minimum_order_quantity' => 10,
        'unit' => 'bag',
        'quantity_breaks' => [['min_qty' => 100, 'price' => 90.5], ['min_qty' => 500, 'price' => 80]],
    ]);

    $html = $this->get(route('storefront.product', $product))->assertOk()->getContent();
    $node = collect(schemaFromHtml($html)['@graph'])->firstWhere('@id', route('storefront.product', $product).'#product');

    expect($node['@type'])->toBe('Product')
        ->and($node['name'])->toBe('Jasmine Rice')
        ->and($node['description'])->toBe('Premium rice.')
        ->and($node['sku'])->toBe('RC-001')
        ->and($node['category'])->toBe('Foods > Rice')
        ->and($node['image'])->toBe([$product->featured_image_url])
        ->and($node['brand'])->toBe(['@id' => route('storefront.company', $shop).'#organization'])
        ->and($node)->not->toHaveKey('manufacturer')
        ->and($node['offers']['price'])->toBe('100.00')
        ->and($node['offers']['priceCurrency'])->toBe('MYR')
        ->and($node['offers']['eligibleQuantity'])->toBe(['@type' => 'QuantitativeValue', 'minValue' => 10, 'unitText' => 'bag'])
        ->and(array_column($node['offers']['priceSpecification'], 'price'))->toBe(['100.00', '90.50', '80.00'])
        ->and(array_map(fn (array $spec): int => $spec['eligibleQuantity']['minValue'], $node['offers']['priceSpecification']))->toBe([10, 100, 500])
        ->and($node['offers']['seller'])->toBe(['@id' => route('storefront.company', $shop).'#organization'])
        ->and($node['offers'])->not->toHaveKey('availability')
        ->and($node['offers'])->not->toHaveKey('priceValidUntil')
        ->and($node['offers'])->not->toHaveKey('shippingDetails')
        ->and($node['offers'])->not->toHaveKey('hasMerchantReturnPolicy')
        ->and($node)->not->toHaveKey('review');

    // The same values are on the rendered page (price tiers are rendered into the Alpine state).
    expect($html)->toContain('RC-001')->and($html)->toContain('MYR');
    expect(schemaNodesOfType(schemaFromHtml($html), 'ItemPage'))->toBeEmpty();
});

test('a multi-variant product is a ProductGroup with a variant per published SKU', function () {
    $product = Product::factory()->published()->create(['name' => 'Cooking Oil']);
    ProductVariant::factory()->published()->create(['product_id' => $product->id, 'sku' => 'OIL-1L', 'attributes' => ['size' => '1L', 'grade' => 'A'], 'price' => 50, 'quantity_breaks' => []]);
    ProductVariant::factory()->published()->create(['product_id' => $product->id, 'sku' => 'OIL-5L', 'attributes' => ['size' => '5L', 'grade' => 'A'], 'price' => 220, 'quantity_breaks' => []]);
    ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'OIL-ARCHIVED', 'status' => 'archived']);

    $node = productNode($this, $product);

    expect($node['@type'])->toBe('ProductGroup')
        ->and($node)->not->toHaveKey('offers')->and($node)->not->toHaveKey('sku')
        ->and($node['variesBy'])->toBe(['https://schema.org/size'])
        ->and(array_column($node['hasVariant'], 'sku'))->toBe(['OIL-1L', 'OIL-5L'])
        ->and(array_column(array_column($node['hasVariant'], 'offers'), 'price'))->toBe(['50.00', '220.00'])
        ->and($node['hasVariant'][0]['additionalProperty'])->toBe([['@type' => 'PropertyValue', 'name' => 'Size', 'value' => '1L'], ['@type' => 'PropertyValue', 'name' => 'Grade', 'value' => 'A']])
        ->and($node['hasVariant'][0])->not->toHaveKey('offers.priceSpecification');
});

test('a variant without a usable price emits no offer', function () {
    $product = Product::factory()->published()->create();
    ProductVariant::factory()->published()->create(['product_id' => $product->id, 'price' => 0, 'quantity_breaks' => []]);

    $node = productNode($this, $product);

    expect($node['@type'])->toBe('Product')->and($node)->not->toHaveKey('offers');
});

test('a product with no published variant has no sku and no offer', function () {
    $product = Product::factory()->published()->create();

    $node = productNode($this, $product);

    expect($node)->not->toHaveKey('sku')->and($node)->not->toHaveKey('offers');
});

test('product halal signal comes only from the supplier certificate, never a product certification', function () {
    $certified = Shop::factory()->create(['status' => 'active']);
    ShopCertificate::factory()->create(['shop_id' => $certified->id]);
    $plain = Shop::factory()->create(['status' => 'active']);

    $withCert = Product::factory()->published()->create(['shop_id' => $certified->id]);
    $without = Product::factory()->published()->create(['shop_id' => $plain->id]);

    $certifiedNode = productNode($this, $withCert);
    $plainNode = productNode($this, $without);

    expect($certifiedNode['additionalProperty'])->toContain(['@type' => 'PropertyValue', 'name' => 'Supplier halal certification', 'value' => 'Active'])
        ->and($certifiedNode)->not->toHaveKey('hasCertification')
        ->and($plainNode['additionalProperty'] ?? [])->not->toContain(['@type' => 'PropertyValue', 'name' => 'Supplier halal certification', 'value' => 'Active']);
});

test('the product breadcrumb mirrors the page: parent group and primary category, each linking to its category page', function () {
    $parent = Category::factory()->create(['name' => 'Foods']);
    $child = Category::factory()->childOf($parent)->create(['name' => 'Rice']);
    $product = Product::factory()->published()->create(['name' => 'Jasmine Rice']);
    $product->categories()->attach($child);

    $html = $this->get(route('storefront.product', $product))->assertOk()->getContent();
    $crumbs = schemaNodesOfType(schemaFromHtml($html), 'BreadcrumbList')[0]['itemListElement'];

    expect(array_column($crumbs, 'name'))->toBe(['Products', 'Foods', 'Rice', 'Jasmine Rice'])
        ->and(array_column($crumbs, 'item'))->toBe([
            route('storefront.products'),
            route('storefront.category', $parent),
            route('storefront.category', $child),
            route('storefront.product', $product),
        ])
        ->and($html)->toContain('href="'.route('storefront.category', $parent).'"')
        ->and($html)->toContain('href="'.route('storefront.category', $child).'"');

    $flat = Product::factory()->published()->create(['name' => 'Plain Item']);
    $flatCrumbs = schemaNodesOfType(pageSchema($this, route('storefront.product', $flat)), 'BreadcrumbList')[0]['itemListElement'];

    expect(array_column($flatCrumbs, 'name'))->toBe(['Products', 'Plain Item']);
});

test('the product page wrapper points at the product as its main entity and its own featured image', function () {
    $product = Product::factory()->published()->create(['featured_image_path' => 'products/rice.jpg']);

    $schema = pageSchema($this, route('storefront.product', $product));
    $page = schemaNodesOfType($schema, 'WebPage')[0];

    expect($page['mainEntity'])->toBe(['@id' => route('storefront.product', $product).'#product'])
        ->and($page['primaryImageOfPage']['url'])->toBe($product->featured_image_url);

    $withoutImage = Product::factory()->published()->create(['featured_image_path' => null]);

    expect(schemaNodesOfType(pageSchema($this, route('storefront.product', $withoutImage)), 'WebPage')[0])->not->toHaveKey('primaryImageOfPage');
});

test('non-public products and companies render no page and so no schema', function () {
    $draft = Product::factory()->archived()->create();
    $suspended = Shop::factory()->suspended()->create();

    $this->get(route('storefront.product', $draft))->assertNotFound();
    $this->get(route('storefront.company', $suspended))->assertNotFound();
});

test('the JSON-LD is rendered once and only in the head', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $head = substr($html, 0, (int) strpos($html, '</head>'));

    expect(substr_count($html, 'application/ld+json'))->toBe(1)->and(substr_count($head, 'application/ld+json'))->toBe(1);
});
