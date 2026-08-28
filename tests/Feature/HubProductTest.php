<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

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
