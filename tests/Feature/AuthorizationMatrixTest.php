<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopOnboarding;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Queue::fake();
});

// ═══════════════════════════════════════════════════════════════════════════
// Onboarding Routes — GET /api/v1/onboarding (only own user)
// ═══════════════════════════════════════════════════════════════════════════

test('onboarding: seller can access own onboarding', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/onboarding');

    $response->assertOk();
});

test('onboarding: admin can access onboarding endpoint', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/onboarding');

    $response->assertOk();
});

test('onboarding: buyer can access onboarding endpoint (no existing onboarding)', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');
    Sanctum::actingAs($buyer);

    $response = $this->getJson('/api/v1/onboarding');

    $response->assertOk()
        ->assertJson(['data' => null]);
});

test('onboarding: unauthenticated user gets 401', function () {
    $response = $this->getJson('/api/v1/onboarding');

    $response->assertUnauthorized();
});

// ═══════════════════════════════════════════════════════════════════════════
// Hub Products — GET /api/v1/hub/products (requires auth + verified + active shop)
// ═══════════════════════════════════════════════════════════════════════════

test('hub products: seller with active shop can list own products', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/hub/products');

    $response->assertOk();
});

test('hub products: seller without active shop gets 403', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    // No shop created
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/hub/products');

    $response->assertForbidden();
});

test('hub products: seller with inactive shop gets 403', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    Shop::factory()->inactive()->create(['user_id' => $user->id]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/hub/products');

    $response->assertForbidden();
});

test('hub products: seller-non-owner cannot access other shop products via product update', function () {
    $owner = User::factory()->create();
    $owner->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $nonOwner = User::factory()->create();
    $nonOwner->assignRole('seller');
    Shop::factory()->create(['user_id' => $nonOwner->id, 'status' => 'active']);
    Sanctum::actingAs($nonOwner);

    $response = $this->putJson("/api/v1/hub/products/{$product->id}", [
        'name' => 'Hijacked Product',
    ]);

    $response->assertForbidden();
});

test('hub products: buyer gets 403 trying to list hub products', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');
    Sanctum::actingAs($buyer);

    $response = $this->getJson('/api/v1/hub/products');

    $response->assertForbidden();
});

test('hub products: unauthenticated gets 401', function () {
    $response = $this->getJson('/api/v1/hub/products');

    $response->assertUnauthorized();
});

// ═══════════════════════════════════════════════════════════════════════════
// Admin Onboardings — GET /api/v1/admin/onboardings (admin only)
// ═══════════════════════════════════════════════════════════════════════════

test('admin onboardings: admin can list onboardings', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/onboardings');

    $response->assertOk();
});

test('admin onboardings: seller gets 403', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/admin/onboardings');

    $response->assertForbidden();
});

test('admin onboardings: buyer gets 403', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');
    Sanctum::actingAs($buyer);

    $response = $this->getJson('/api/v1/admin/onboardings');

    $response->assertForbidden();
});

test('admin onboardings: unauthenticated gets 401', function () {
    $response = $this->getJson('/api/v1/admin/onboardings');

    $response->assertUnauthorized();
});

// ═══════════════════════════════════════════════════════════════════════════
// Admin Approve — POST /api/v1/admin/onboardings/{id}/approve (admin only)
// ═══════════════════════════════════════════════════════════════════════════

test('admin approve: admin can approve onboarding', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $seller = User::factory()->create();
    $onboarding = ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Approvable Co.',
        'storefront_name' => 'Approvable Store',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-2026-00001',
        'tax_identification_number' => '111-222-333-000',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'value_chain_positions' => ['processing'],
        'capabilities' => ['packaging'],
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/admin/onboardings/{$onboarding->id}/approve");

    $response->assertOk()
        ->assertJsonPath('data.status', 'approved');
});

test('admin approve: seller gets 403', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    Sanctum::actingAs($seller);

    $onboarding = ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Test Co.',
        'storefront_name' => 'Test Store',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-2026-00002',
        'tax_identification_number' => '222-333-444-000',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'value_chain_positions' => ['processing'],
        'capabilities' => ['packaging'],
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/admin/onboardings/{$onboarding->id}/approve");

    $response->assertForbidden();
});

test('admin approve: unauthenticated gets 401', function () {
    $response = $this->postJson('/api/v1/admin/onboardings/1/approve');

    $response->assertUnauthorized();
});

// ═══════════════════════════════════════════════════════════════════════════
// Admin Categories — GET /api/v1/admin/categories (category.manage permission)
// ═══════════════════════════════════════════════════════════════════════════

test('admin categories: admin can list categories', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/categories');

    $response->assertOk();
});

test('admin categories: seller gets 403', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    Sanctum::actingAs($seller);

    $response = $this->getJson('/api/v1/admin/categories');

    $response->assertForbidden();
});

test('admin categories: buyer gets 403', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');
    Sanctum::actingAs($buyer);

    $response = $this->getJson('/api/v1/admin/categories');

    $response->assertForbidden();
});

test('admin categories: unauthenticated gets 401', function () {
    $response = $this->getJson('/api/v1/admin/categories');

    $response->assertUnauthorized();
});

// ═══════════════════════════════════════════════════════════════════════════
// Public Routes — accessible without auth
// ═══════════════════════════════════════════════════════════════════════════

test('public: search companies endpoint is accessible without auth', function () {
    $response = $this->getJson('/api/v1/search/companies');

    $response->assertOk();
});

test('public: search products endpoint is accessible without auth', function () {
    $response = $this->getJson('/api/v1/search/products');

    $response->assertOk();
});

test('public: company profile is accessible without auth', function () {
    $shop = Shop::factory()->create(['status' => 'active']);

    $response = $this->getJson("/api/v1/companies/{$shop->id}");

    $response->assertOk();
});

test('public: product detail is accessible without auth', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create(['shop_id' => $shop->id]);

    $response = $this->getJson("/api/v1/products/{$product->id}");

    $response->assertOk();
});

test('public: categories list is accessible without auth', function () {
    Category::factory()->create(['name' => 'Test Category']);

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});
