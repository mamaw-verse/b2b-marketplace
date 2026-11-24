<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Payout;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);

    config()->set('marketplace.features.payouts_enabled', true);
    config()->set('marketplace.features.orders_enabled', true);
    config()->set('marketplace.features.staff_enabled', true);
    config()->set('marketplace.features.escrow_payments_enabled', true);
    config()->set('marketplace.features.fulfilment_enabled', true);
    config()->set('marketplace.features.disputes_enabled', true);
    config()->set('marketplace.features.reviews_enabled', true);
    config()->set('marketplace.features.chat_enabled', true);
    config()->set('marketplace.features.quotations_enabled', true);
    config()->set('marketplace.features.seller_rfq_queue_enabled', true);
    config()->set('marketplace.features.subscriptions_enabled', true);
    config()->set('marketplace.features.product_video_enabled', true);
    config()->set('marketplace.features.product_share_enabled', true);
    config()->set('humancheck.enabled', false);
});

test('a seller logs in through the login form and lands on the portal dashboard', function (): void {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'storefront_name' => 'Barakah Organic Foods',
    ]);

    $page = visit('/login')->on()->iPhone15()
        ->fill('#login-email', $seller->email)
        ->fill('#login-password', 'password');

    // The login form requires a Turnstile token; in tests the verifier accepts
    // any non-empty value when humancheck is disabled.
    $page->script("document.querySelector('input[name=\"cf-turnstile-response\"]').value = 'valid-token'");

    $page->click('[data-test="login-button"]')
        ->assertPathIs('/portal/dashboard')
        ->screenshot(filename: 'seller-portal-login-landing');
});

test('seller portal pages render and capture screenshots', function (string $path, ?string $needle, string $slug): void {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $product = Product::factory()->published()->create(['shop_id' => $shop->id]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(['shop_id' => $shop->id, 'buyer_user_id' => $buyer->id]);
    $payout = Payout::factory()->create(['shop_id' => $shop->id]);

    $this->actingAs($seller);

    $resolved = strtr($path, [
        '{product}' => (string) $product->id,
        '{order}' => (string) $order->id,
        '{payout}' => (string) $payout->id,
        '{buyer}' => (string) $buyer->id,
    ]);

    $page = visit($resolved)->on()->iPhone15();

    if ($needle !== null) {
        $page->assertSee($needle);
    }

    $page->assertPathIs(strtok($resolved, '?') ?: $resolved)
        ->screenshot(filename: 'seller-portal-'.$slug);
})->with([
    'dashboard' => ['/portal/dashboard', null, 'dashboard'],
    'products' => ['/hub/products', 'Products', 'products'],
    'product-create' => ['/hub/products/create', 'Create Product', 'product-create'],
    'product-edit' => ['/hub/products/{product}/edit', 'Edit Product', 'product-edit'],
    'product-variants' => ['/hub/products/{product}/variants', null, 'product-variants'],
    'product-gallery' => ['/hub/products/{product}/gallery', null, 'product-gallery'],
    'product-documents' => ['/hub/products/{product}/documents', null, 'product-documents'],
    'product-video' => ['/hub/products/{product}/video', null, 'product-video'],
    'shop-profile' => ['/hub/shop/profile', null, 'shop-profile'],
    'certifications' => ['/hub/certifications', null, 'certifications'],
    'earnings' => ['/hub/earnings', null, 'earnings'],
    'payout-account' => ['/hub/payout-account', null, 'payout-account'],
    'payouts' => ['/hub/payouts', null, 'payouts'],
    'payout-detail' => ['/hub/payouts/{payout}', null, 'payout-detail'],
    'customers' => ['/hub/customers', null, 'customers'],
    'reports' => ['/hub/reports', null, 'reports'],
    'reviews' => ['/hub/reviews', null, 'reviews'],
    'orders' => ['/hub/orders', 'Orders', 'orders'],
    'order-create' => ['/hub/orders/create?buyerUserId={buyer}', null, 'order-create'],
    'order-detail' => ['/hub/orders/{order}', null, 'order-detail'],
    'rfqs' => ['/hub/rfqs', null, 'rfqs'],
    'messages' => ['/hub/messages', null, 'messages'],
    'team' => ['/hub/team', null, 'team'],
    'subscription' => ['/hub/subscription', null, 'subscription'],
]);
