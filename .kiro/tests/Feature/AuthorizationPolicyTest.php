<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\ShopOnboarding;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(Database\Seeders\RolePermissionSeeder::class);
});

test('seller can view own shop', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);

    expect($user->can('view', $shop))->toBeTrue();
});

test('seller cannot view another sellers shop', function () {
    $user = User::factory()->create();
    $otherShop = Shop::factory()->create();

    expect($user->cannot('view', $otherShop))->toBeTrue();
});

test('seller can update own product', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    expect($user->can('update', $product))->toBeTrue();
});

test('seller cannot update another sellers product', function () {
    $sellerA = User::factory()->create();
    $sellerB = User::factory()->create();
    $shopB = Shop::factory()->create(['user_id' => $sellerB->id]);
    $productB = Product::factory()->create(['shop_id' => $shopB->id]);

    expect($sellerA->cannot('update', $productB))->toBeTrue();
});

test('seller can update own product variant', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    expect($user->can('update', $variant))->toBeTrue();
});

test('seller cannot update another sellers product variant', function () {
    $sellerA = User::factory()->create();
    $sellerB = User::factory()->create();
    $shopB = Shop::factory()->create(['user_id' => $sellerB->id]);
    $productB = Product::factory()->create(['shop_id' => $shopB->id]);
    $variantB = ProductVariant::factory()->create(['product_id' => $productB->id]);

    expect($sellerA->cannot('update', $variantB))->toBeTrue();
});

test('seller can view own onboarding', function () {
    $user = User::factory()->create();
    $onboarding = ShopOnboarding::create([
        'user_id' => $user->id,
        'registered_business_name' => 'Test Co',
        'storefront_name' => 'Test Store',
        'business_structure' => 'sole_proprietorship',
        'dti_sec_number' => 'DTI-001',
        'tax_identification_number' => '123-456-789',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'value_chain_positions' => ['processing'],
        'capabilities' => ['packaging'],
    ]);

    expect($user->can('view', $onboarding))->toBeTrue();
});

test('seller cannot view another sellers onboarding', function () {
    $sellerA = User::factory()->create();
    $sellerB = User::factory()->create();
    $onboarding = ShopOnboarding::create([
        'user_id' => $sellerB->id,
        'registered_business_name' => 'Other Co',
        'storefront_name' => 'Other Store',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-001',
        'tax_identification_number' => '987-654-321',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'value_chain_positions' => ['manufacturing'],
        'capabilities' => ['wholesale'],
    ]);

    expect($sellerA->cannot('view', $onboarding))->toBeTrue();
});

test('shop owner can create product only when shop is active', function () {
    $user = User::factory()->create();
    $activeShop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    expect($user->can('createProduct', $activeShop))->toBeTrue();

    // Different user with inactive shop — owner cannot create products
    $userB = User::factory()->create();
    $inactiveOwnShop = Shop::factory()->create(['user_id' => $userB->id, 'status' => 'inactive']);

    expect($userB->cannot('createProduct', $inactiveOwnShop))->toBeTrue();
});

test('admin role passes onboarding.review gate', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($admin->can('onboarding.review'))->toBeTrue();
    expect($admin->can('category.manage'))->toBeTrue();
    expect($admin->can('document.view_any'))->toBeTrue();
});

test('seller role does not pass admin gates', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    expect($seller->cannot('onboarding.review'))->toBeTrue();
    expect($seller->cannot('category.manage'))->toBeTrue();
    expect($seller->cannot('document.view_any'))->toBeTrue();
});

test('seller role passes own permission gates', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    expect($seller->can('shop.manage_own'))->toBeTrue();
    expect($seller->can('product.manage_own'))->toBeTrue();
});
