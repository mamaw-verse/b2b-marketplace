<?php

declare(strict_types=1);

use App\Jobs\IndexProductJob;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(Database\Seeders\RolePermissionSeeder::class);
    Queue::fake();
    Storage::fake('s3');
});

test('seller with non-active shop gets 403 on POST /hub/products', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    Shop::factory()->for($user)->create(['status' => 'suspended']);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/hub/products', [
        'name' => 'Test Product',
    ]);

    $response->assertForbidden();
});

test('seller with active shop can create product', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    Shop::factory()->for($user)->create(['status' => 'active']);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/hub/products', [
        'name' => 'Halal Coconut Oil',
        'description' => 'Cold-pressed virgin coconut oil',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Halal Coconut Oil')
        ->assertJsonPath('data.status', 'archived');
});

test('product with zero published variants returns 422 on publish', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    $shop = Shop::factory()->for($user)->create(['status' => 'active']);
    $product = Product::factory()->for($shop)->create(['status' => 'archived']);
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/hub/products/{$product->id}/publish");

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.fields.variants.0', 'Product must have at least one published variant before publishing.');
});

test('publish succeeds with published variant and dispatches IndexProductJob', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    $shop = Shop::factory()->for($user)->create(['status' => 'active']);
    $product = Product::factory()->for($shop)->create(['status' => 'archived']);
    ProductVariant::factory()->for($product)->published()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/hub/products/{$product->id}/publish");

    $response->assertOk()
        ->assertJsonPath('data.status', 'published');

    Queue::assertPushed(IndexProductJob::class, function (IndexProductJob $job) use ($product) {
        return $job->product->id === $product->id;
    });
});

test('duplicate SKU within same product returns 422', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    $shop = Shop::factory()->for($user)->create(['status' => 'active']);
    $product = Product::factory()->for($shop)->create();
    ProductVariant::factory()->for($product)->create(['sku' => 'DUPLICATE-SKU']);
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/hub/products/{$product->id}/variants", [
        'sku' => 'DUPLICATE-SKU',
        'price' => 100.00,
        'minimum_order_quantity' => 10,
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['error' => ['fields' => ['sku']]]);
});

test('malformed quantity_breaks returns 422', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    $shop = Shop::factory()->for($user)->create(['status' => 'active']);
    $product = Product::factory()->for($shop)->create();
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/hub/products/{$product->id}/variants", [
        'sku' => 'SKU-001',
        'price' => 100.00,
        'minimum_order_quantity' => 10,
        'quantity_breaks' => [
            ['invalid_key' => 'not valid'],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['error' => ['fields' => ['quantity_breaks.0.min_qty', 'quantity_breaks.0.price']]]);
});

test('valid variant creation returns 201', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    $shop = Shop::factory()->for($user)->create(['status' => 'active']);
    $product = Product::factory()->for($shop)->create();
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/hub/products/{$product->id}/variants", [
        'sku' => 'VCO-500ML',
        'price' => 250.50,
        'minimum_order_quantity' => 5,
        'quantity_breaks' => [
            ['min_qty' => 50, 'price' => 230.00],
            ['min_qty' => 100, 'price' => 210.00],
        ],
        'unit' => 'bottle',
        'status' => 'published',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.sku', 'VCO-500ML')
        ->assertJsonPath('data.price', '250.50')
        ->assertJsonPath('data.minimum_order_quantity', 5)
        ->assertJsonPath('data.status', 'published');
});

test('gallery upload returns 201', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    $shop = Shop::factory()->for($user)->create(['status' => 'active']);
    $product = Product::factory()->for($shop)->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('product-photo.jpg', 800, 600);

    $response = $this->postJson("/api/v1/hub/products/{$product->id}/gallery", [
        'image' => $file,
        'sort_order' => 1,
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'product_id', 'media_id', 'sort_order']]);

    Storage::disk('s3')->assertExists(
        $response->json('data.media.storage_path')
    );
});
