<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

// Extra mobile-audit capture states (audit T-21). Complements MobileAuditBrowserTest,
// which only covers the logged-out, English, closed-drawer state.

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);

    config()->set('marketplace.features.product_share_enabled', true);
    config()->set('marketplace.features.subscriptions_enabled', false);
    config()->set('humancheck.enabled', false);

    $shop = Shop::factory()->create([
        'user_id' => User::factory()->create()->id,
        'status' => 'active',
        'storefront_name' => 'Barakah Organic',
        'slug' => 'barakah-organic',
        'country_code' => 'PH',
    ]);

    Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Matcha Green Tea',
        'slug' => 'matcha-green-tea',
        'description' => 'Sourced and packed in the Philippines, MHCB-verified and ready for wholesale orders.',
    ]);
});

test('mobile audit captures the open navigation drawer', function (): void {
    visit('/')->on()->mobile()
        ->click('button[aria-label="Open menu"]')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->screenshot(filename: 'mobile-audit-mobile-nav-drawer-open');
});

test('mobile audit captures the home page in Arabic (RTL)', function (): void {
    // The Saudi instance defaults to the `ar` locale (config/marketplace.php portals).
    config()->set('marketplace.instance_country', 'SA');

    visit('/')->on()->mobile()
        ->assertScript('document.documentElement.lang', 'ar')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->screenshot(filename: 'mobile-audit-mobile-home-ar');
});

test('mobile audit captures the auth pages', function (string $path, string $key): void {
    visit($path)->on()->mobile()
        ->assertPathIs($path)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->screenshot(filename: "mobile-audit-mobile-{$key}");
})->with([
    'login' => ['/login', 'login'],
    'register' => ['/register', 'register'],
]);
