<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);

    config()->set('marketplace.features.product_share_enabled', true);
    config()->set('marketplace.features.subscriptions_enabled', false);
    config()->set('humancheck.enabled', false);

    $owner = User::factory()->create();
    $shop = Shop::factory()->create([
        'user_id' => $owner->id,
        'status' => 'active',
        'storefront_name' => 'Barakah Organic',
        'slug' => 'barakah-organic',
    ]);
    Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Matcha Green Tea',
        'slug' => 'matcha-green-tea',
    ]);
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
        ->screenshot(filename: "mobile-audit-{$deviceMethod}-{$pageKey}");
})->with($mobileAuditCases);
