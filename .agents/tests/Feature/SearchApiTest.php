<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Use the null scout driver in tests to avoid Typesense dependency
    config(['scout.driver' => 'null']);
});

test('GET /api/v1/categories returns category list', function () {
    Category::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonStructure(['data'])
        ->assertJsonCount(3, 'data');

    // Verify each category has expected fields
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'name', 'slug'],
        ],
    ]);
});

test('GET /api/v1/categories returns empty list when no categories exist', function () {
    $response = $this->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

test('GET /api/v1/companies/{shop} returns company profile with relationships', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    $categories = Category::factory()->count(2)->create();
    $shop->categories()->attach($categories);

    $product = Product::factory()->published()->create(['shop_id' => $shop->id]);
    ProductVariant::factory()->published()->create(['product_id' => $product->id]);

    $response = $this->getJson("/api/v1/companies/{$shop->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'storefront_name',
                'registered_business_name',
                'city',
                'province',
                'country_code',
                'value_chain_positions',
                'capabilities',
                'export_readiness_status',
                'categories',
                'certificates',
                'products',
            ],
        ])
        ->assertJsonPath('data.id', $shop->id)
        ->assertJsonPath('data.storefront_name', $shop->storefront_name)
        ->assertJsonCount(2, 'data.categories')
        ->assertJsonCount(1, 'data.products');
});

test('GET /api/v1/companies/{shop} returns 404 for non-existent shop', function () {
    $response = $this->getJson('/api/v1/companies/99999');

    $response->assertNotFound();
});

test('GET /api/v1/products/{product} returns product detail with variants', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create(['shop_id' => $shop->id]);
    $categories = Category::factory()->count(2)->create();
    $product->categories()->attach($categories);

    ProductVariant::factory()->count(3)->published()->create(['product_id' => $product->id]);

    $response = $this->getJson("/api/v1/products/{$product->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'description',
                'shop_id',
                'status',
                'variants',
                'gallery',
                'documents',
                'categories',
                'shop',
            ],
        ])
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.name', $product->name)
        ->assertJsonCount(3, 'data.variants')
        ->assertJsonCount(2, 'data.categories')
        ->assertJsonPath('data.shop.id', $shop->id);
});

test('GET /api/v1/products/{product} returns 404 for non-existent product', function () {
    $response = $this->getJson('/api/v1/products/99999');

    $response->assertNotFound();
});

test('GET /api/v1/search/companies returns correct response structure', function () {
    // With null driver, search returns empty results but the response structure is valid
    Shop::factory()->count(3)->create(['status' => 'active']);

    $response = $this->getJson('/api/v1/search/companies?q=test');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['found', 'search_time_ms', 'next_cursor'],
        ]);
});

test('GET /api/v1/search/products returns correct response structure', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    Product::factory()->count(3)->published()->create(['shop_id' => $shop->id]);

    $response = $this->getJson('/api/v1/search/products?q=test');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['found', 'search_time_ms', 'next_cursor'],
        ]);
});

test('search companies accepts category and province filters', function () {
    $response = $this->getJson('/api/v1/search/companies?q=halal&category=food&province=manila');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['found', 'search_time_ms', 'next_cursor'],
        ]);
});

test('search products accepts category and shop_id filters', function () {
    $response = $this->getJson('/api/v1/search/products?q=organic&category=ingredients&shop_id=1');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['found', 'search_time_ms', 'next_cursor'],
        ]);
});
