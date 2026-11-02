<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\ProductList;
use App\Livewire\Storefront\CompanyProfile;
use App\Livewire\Storefront\ProductDetail;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    config()->set('marketplace.features.subscriptions_enabled', true);
});

// ─── Product list hub page banner ────────────────────────────────────────────

test('product list shows subscription banner when shop has no subscription', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    Livewire::actingAs($seller)
        ->test(ProductList::class)
        ->assertSee(__('Your business profile and products are not visible on the marketplace'));
});

test('product list shows subscription banner when shop is trialing', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    Subscription::factory()->trialing()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($seller)
        ->test(ProductList::class)
        ->assertSee(__('Your business profile and products are not visible on the marketplace'));
});

test('product list hides subscription banner when shop has active subscription', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    Subscription::factory()->active()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($seller)
        ->test(ProductList::class)
        ->assertDontSee(__('Your business profile and products are not visible on the marketplace'));
});

test('product list hides subscription banner when subscriptions feature is disabled', function () {
    config()->set('marketplace.features.subscriptions_enabled', false);

    $seller = User::factory()->create();
    $seller->assignRole('seller');
    Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    Livewire::actingAs($seller)
        ->test(ProductList::class)
        ->assertDontSee(__('Your business profile and products are not visible on the marketplace'));
});

// ─── Company profile storefront gating ───────────────────────────────────────

test('company profile is accessible when shop has active subscription', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    Subscription::factory()->active()->create(['shop_id' => $shop->id]);

    Livewire::test(CompanyProfile::class, ['shop' => $shop])
        ->assertOk();
});

test('company profile returns 404 when shop is trialing', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    Subscription::factory()->trialing()->create(['shop_id' => $shop->id]);

    Livewire::test(CompanyProfile::class, ['shop' => $shop])
        ->assertStatus(404);
});

test('company profile returns 404 for unsubscribed shop when viewed by public', function () {
    $shop = Shop::factory()->create(['status' => 'active']);

    Livewire::test(CompanyProfile::class, ['shop' => $shop])
        ->assertStatus(404);
});

test('company profile returns 404 for unsubscribed shop when viewed by another user', function () {
    $otherSeller = User::factory()->create();
    $otherSeller->assignRole('seller');
    Shop::factory()->create(['user_id' => $otherSeller->id, 'status' => 'active']);

    $targetShop = Shop::factory()->create(['status' => 'active']);

    Livewire::actingAs($otherSeller)
        ->test(CompanyProfile::class, ['shop' => $targetShop])
        ->assertStatus(404);
});

test('own unsubscribed company profile redirects owner to subscription page with message', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    Livewire::actingAs($seller)
        ->test(CompanyProfile::class, ['shop' => $shop])
        ->assertRedirect(route('hub.subscription'));
});

test('own unsubscribed company profile flashes a message before redirecting', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    Livewire::actingAs($seller)
        ->test(CompanyProfile::class, ['shop' => $shop]);

    expect(session('message'))->toBe(__('Subscribe to make your business profile visible on the marketplace.'));
});

// ─── Product detail storefront gating ────────────────────────────────────────

test('product detail is accessible when shop has active subscription', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    Subscription::factory()->active()->create(['shop_id' => $shop->id]);
    $product = Product::factory()->published()->create(['shop_id' => $shop->id]);

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->assertOk();
});

test('product detail returns 404 for unsubscribed shop', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create(['shop_id' => $shop->id]);

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->assertStatus(404);
});
