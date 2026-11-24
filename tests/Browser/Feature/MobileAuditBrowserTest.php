<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);

    config()->set('marketplace.features.product_share_enabled', true);
    config()->set('marketplace.features.subscriptions_enabled', false);
    config()->set('humancheck.enabled', false);

    // Deterministic fixtures: fixed image, Philippine addresses and real copy, so
    // screenshots only change when the layout changes (audit T-18 / T-20).
    Storage::forgetDisk('s3');
    $imagePath = 'products/images/mobile-audit-fixture.png';
    Storage::disk('s3')->put($imagePath, file_get_contents(base_path('tests/Fixtures/product.png')));

    $companies = [
        ['Barakah Organic', 'barakah-organic', 'Quezon City', 'Metro Manila', '12 Kalayaan Avenue', '1100'],
        ['Al-Amin Foods Trading', 'al-amin-foods-trading', 'Cotabato City', 'Maguindanao', '45 Sinsuat Avenue', '9600'],
        ['Mindanao Halal Spice Company of the Philippines', 'mindanao-halal-spice-company', 'Davao City', 'Davao del Sur', '8 J.P. Laurel Avenue', '8000'],
    ];

    $products = [
        'Matcha Green Tea', 'Halal Chicken Nuggets', 'Organic Coconut Sugar', 'Dried Mango Slices',
        'Premium Medjool Dates', 'Halal-Certified Beef Tapa', 'Virgin Coconut Oil', 'Turmeric Powder',
    ];

    foreach ($companies as $index => [$name, $slug, $city, $province, $address, $zip]) {
        $shop = Shop::factory()->create([
            'user_id' => User::factory()->create()->id,
            'status' => 'active',
            'storefront_name' => $name,
            'slug' => $slug,
            'registered_business_name' => $name.' Inc.',
            'address_1' => $address,
            'address_2' => null,
            'city' => $city,
            'province' => $province,
            'zip_code' => $zip,
            'country_code' => 'PH',
            'export_readiness_status' => ['never_exported', 'exporting_regionally', 'exporting_globally'][$index],
            'value_chain_positions' => ['farm', 'processing', 'manufacturing', 'distribution', 'retail', 'food_service'],
            'average_production_lead_time' => 14,
        ]);

        // The first company owns the first three products; the rest are spread out.
        foreach ($products as $productIndex => $productName) {
            if ($productIndex % count($companies) !== $index) {
                continue;
            }

            Product::factory()->published()->create([
                'shop_id' => $shop->id,
                'name' => $productName,
                'slug' => Str::slug($productName),
                'description' => 'Sourced and packed in the Philippines, MHCB-verified and ready for wholesale orders.',
                'featured_image_path' => $imagePath,
            ]);
        }
    }
});

$mobileDevices = [
    'mobile' => 'mobile',
    'iphone15' => 'iPhone15',
    'ipad-mini' => 'iPadMini',
];

$mobilePages = [
    'home' => ['/', 'MHCB-Verified Halal Marketplace'],
    'companies' => ['/companies', 'Business Directory'],
    'company-profile' => ['/companies/barakah-organic', 'Barakah Organic'],
    'products' => ['/products', 'Product Directory'],
    'product-detail' => ['/products/matcha-green-tea', 'Matcha Green Tea'],
    'about' => ['/about', 'About 1HalalStore.com'],
    'faq' => ['/faq', 'Frequently Asked Questions'],
    'sourcing-request' => ['/request-a-product', null],
    'apply-certificate' => ['/certification/apply', 'Apply for Certificate'],
    'terms' => ['/terms', null],
    'privacy' => ['/privacy', null],
];

$mobileAuditCases = [];

foreach ($mobileDevices as $deviceKey => $deviceMethod) {
    foreach ($mobilePages as $pageKey => [$path, $needle]) {
        $mobileAuditCases["{$deviceKey} / {$pageKey}"] = [$deviceMethod, $pageKey, $path, $needle];
    }
}

test('mobile audit captures a screenshot of every public page', function (string $deviceMethod, string $pageKey, string $path, ?string $needle): void {
    $page = visit($path)->on()->{$deviceMethod}();

    if ($needle !== null) {
        $page->assertSee($needle);
    }

    $page->assertPathIs($path)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->screenshot(filename: "mobile-audit-{$deviceMethod}-{$pageKey}");
})->with($mobileAuditCases);
