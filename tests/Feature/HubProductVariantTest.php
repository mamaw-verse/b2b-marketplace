<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);
    $this->product = Product::factory()->create(['shop_id' => $this->shop->id]);
});

test('storeVariant creates a variant via StoreProductVariantRequest', function () {
    $response = $this->actingAs($this->seller, 'sanctum')
        ->postJson("/api/v1/hub/products/{$this->product->id}/variants", [
            'sku' => 'API-SKU-001',
            'price' => '250.00',
            'minimum_order_quantity' => 5,
            'unit' => 'kg',
            'status' => 'published',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.sku', 'API-SKU-001');

    $this->assertDatabaseHas('product_variants', [
        'product_id' => $this->product->id,
        'sku' => 'API-SKU-001',
        'minimum_order_quantity' => 5,
        'currency' => 'PHP',
    ]);
});

test('storeVariant rejects duplicate sku within the same product', function () {
    ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'DUPE-SKU',
    ]);

    $response = $this->actingAs($this->seller, 'sanctum')
        ->postJson("/api/v1/hub/products/{$this->product->id}/variants", [
            'sku' => 'DUPE-SKU',
            'price' => '100.00',
            'minimum_order_quantity' => 1,
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.fields.sku.0', fn ($msg) => is_string($msg));
});

test('storeVariant rejects missing required fields', function () {
    $response = $this->actingAs($this->seller, 'sanctum')
        ->postJson("/api/v1/hub/products/{$this->product->id}/variants", [
            'sku' => '',
            'price' => '0',
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');

    $fields = $response->json('error.fields');
    expect($fields)->toHaveKeys(['sku', 'price', 'minimum_order_quantity']);
});

test('updateVariant updates a variant via UpdateProductVariantRequest', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'ORIG-SKU',
        'unit' => 'pcs',
    ]);

    $response = $this->actingAs($this->seller, 'sanctum')
        ->putJson("/api/v1/hub/products/{$this->product->id}/variants/{$variant->id}", [
            'price' => '999.00',
            'unit' => 'crate',
        ]);

    $response->assertOk();

    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'price' => '999.00',
        'unit' => 'crate',
    ]);
});

test('updateVariant allows keeping the same sku (unique ignore self)', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'KEEP-SKU',
    ]);

    $response = $this->actingAs($this->seller, 'sanctum')
        ->putJson("/api/v1/hub/products/{$this->product->id}/variants/{$variant->id}", [
            'sku' => 'KEEP-SKU',
            'price' => '55.00',
        ]);

    $response->assertOk();
});
