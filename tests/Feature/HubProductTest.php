<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\GalleryManager;
use App\Livewire\CompanyHub\ProductForm;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopStaff;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);
});

test('store creates a product via StoreProductRequest', function () {
    $response = $this->actingAs($this->seller, 'sanctum')
        ->postJson('/api/v1/hub/products', [
            'name' => 'Premium Halal Beef',
            'description' => 'Grass-fed, certified halal.',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Premium Halal Beef');

    $this->assertDatabaseHas('products', [
        'shop_id' => $this->shop->id,
        'name' => 'Premium Halal Beef',
        'status' => 'archived',
    ]);
});

test('active shop staff can use product routes and product edits are attributed to them', function () {
    config()->set('marketplace.features.staff_enabled', true);
    $staff = User::factory()->create(['email_verified_at' => now()]);
    $membership = ShopStaff::factory()->active($staff)->create([
        'shop_id' => $this->shop->id,
        'email' => $staff->email,
        'invited_by_id' => $this->seller->id,
    ]);

    $this->actingAs($staff)->get(route('hub.products.index'))->assertOk();

    $response = $this->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/hub/products', ['name' => 'Staff Managed Product']);

    $response->assertCreated();
    $product = Product::where('name', 'Staff Managed Product')->firstOrFail();

    expect($product->updated_by_id)->toBe($staff->id);

    $membership->update(['status' => 'revoked']);
    $this->actingAs($staff->fresh())->get(route('hub.products.index'))->assertForbidden();
    $this->actingAs($staff->fresh(), 'sanctum')->getJson('/api/v1/hub/products')->assertForbidden();
});

test('store rejects a product with no name', function () {
    $response = $this->actingAs($this->seller, 'sanctum')
        ->postJson('/api/v1/hub/products', [
            'description' => 'No name provided',
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');

    expect($response->json('error.fields'))->toHaveKey('name');
});

test('update modifies a product via UpdateProductRequest', function () {
    $product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Old Name',
    ]);

    $response = $this->actingAs($this->seller, 'sanctum')
        ->putJson("/api/v1/hub/products/{$product->id}", [
            'name' => 'New Name',
        ]);

    $response->assertOk();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'New Name',
    ]);
});

test('update rejects an invalid category id', function () {
    $product = Product::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->seller, 'sanctum')
        ->putJson("/api/v1/hub/products/{$product->id}", [
            'category_ids' => [999999],
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

test('product form shows no-contact-information notices', function () {
    Livewire::actingAs($this->seller)
        ->test(ProductForm::class)
        ->assertSee('Do not include contact information such as phone numbers, email addresses, social media links, or website URLs.')
        ->assertSee('Images must not contain contact information such as phone numbers, email addresses, social media links, or website URLs.')
        ->assertSee('Square images (1:1) work best — e.g. 1000 × 1000 px.');
});

test('gallery manager shows no-contact-information notice', function () {
    $product = Product::factory()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($this->seller)
        ->test(GalleryManager::class, ['product' => $product])
        ->assertSee('Images must not contain contact information such as phone numbers, email addresses, social media links, or website URLs.')
        ->assertSee('Square images (1:1) work best — e.g. 1000 × 1000 px each.');
});

test('product form saves rich-text html description', function () {
    Livewire::actingAs($this->seller)
        ->test(ProductForm::class)
        ->set('name', 'Halal Beef')
        ->set('description', '<h1>Premium Cut</h1><p>Grass-fed, <strong>certified halal</strong>.</p>')
        ->call('save');

    $this->assertDatabaseHas('products', [
        'shop_id' => $this->shop->id,
        'name' => 'Halal Beef',
    ]);

    $product = Product::where('name', 'Halal Beef')->first();
    expect($product->description)->toContain('<strong>certified halal</strong>')
        ->and($product->slug)->toBe('halal-beef');
});

test('product form validates and saves a custom slug', function () {
    $product = Product::factory()->create(['shop_id' => $this->shop->id, 'name' => 'Old Product Name']);

    Livewire::actingAs($this->seller)
        ->test(ProductForm::class, ['product' => $product])
        ->set('slug', 'custom-product-url')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->slug)->toBe('custom-product-url');

    Livewire::actingAs($this->seller)
        ->test(ProductForm::class, ['product' => $product->fresh()])
        ->set('slug', 'admin')
        ->call('save')
        ->assertHasErrors('slug');
});

test('product form strips disallowed html tags from description', function () {
    Livewire::actingAs($this->seller)
        ->test(ProductForm::class)
        ->set('name', 'Unsafe Product')
        ->set('description', '<p>Safe text</p><script>alert("xss")</script><img src="x" onerror="alert(1)" />')
        ->call('save');

    $product = Product::where('name', 'Unsafe Product')->first();
    expect($product->description)
        ->toContain('<p>Safe text</p>')
        ->not->toContain('<script>')
        ->not->toContain('<img');
});
