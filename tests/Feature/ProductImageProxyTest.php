<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductGallery;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('s3');
});

function createVisibleShop(): Shop
{
    $shop = Shop::factory()->create(['status' => 'active']);
    $plan = SubscriptionPlan::factory()->create();
    Subscription::factory()->active()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
    ]);

    return $shop;
}

test('guests can view the featured image of a published product', function () {
    $shop = createVisibleShop();
    Storage::disk('s3')->put('products/images/featured.webp', 'fake-image-bytes');
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'published',
        'featured_image_path' => 'products/images/featured.webp',
    ]);

    $response = $this->get(route('products.featured-image', $product));
    $response->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('public');
});

test('guests cannot view the featured image of an archived product', function () {
    $shop = createVisibleShop();
    Storage::disk('s3')->put('products/images/draft.webp', 'fake-image-bytes');
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'archived',
        'featured_image_path' => 'products/images/draft.webp',
    ]);

    $this->get(route('products.featured-image', $product))->assertForbidden();
});

test('the shop owner can view their own archived featured image but strangers cannot', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    Storage::disk('s3')->put('products/images/owner.webp', 'fake-image-bytes');
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'archived',
        'featured_image_path' => 'products/images/owner.webp',
    ]);

    $this->actingAs($owner)->get(route('products.featured-image', $product))->assertOk();

    $stranger = User::factory()->create();
    $this->actingAs($stranger)->get(route('products.featured-image', $product))->assertForbidden();
});

test('guests can view a gallery image of a published product via media.view', function () {
    $shop = createVisibleShop();
    $product = Product::factory()->create(['shop_id' => $shop->id, 'status' => 'published']);

    $media = Media::factory()->create([
        'storage_path' => 'media/gallery-public.webp',
        'original_filename' => 'gallery-public.webp',
        'mime_type' => 'image/webp',
        'scan_status' => 'clean',
    ]);
    Storage::disk('s3')->put('media/gallery-public.webp', 'fake-image-bytes');
    ProductGallery::create(['product_id' => $product->id, 'media_id' => $media->id, 'sort_order' => 0]);

    $this->get(route('media.view', $media))->assertOk();
});

test('gallery images of archived products stay private except for the owner', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'status' => 'archived']);

    $media = Media::factory()->create([
        'uploaded_by_id' => $owner->id,
        'storage_path' => 'media/gallery-private.webp',
        'original_filename' => 'gallery-private.webp',
        'mime_type' => 'image/webp',
        'scan_status' => 'clean',
    ]);
    Storage::disk('s3')->put('media/gallery-private.webp', 'fake-image-bytes');
    ProductGallery::create(['product_id' => $product->id, 'media_id' => $media->id, 'sort_order' => 0]);

    $this->get(route('media.view', $media))->assertForbidden();
    $this->actingAs($owner)->get(route('media.view', $media))->assertOk();
});

test('image url accessors return proxied app routes instead of direct s3 urls', function () {
    $shop = createVisibleShop();
    Storage::disk('s3')->put('products/images/proxied.webp', 'fake-image-bytes');
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'published',
        'featured_image_path' => 'products/images/proxied.webp',
    ]);

    expect($product->featured_image_url)->toBe(route('products.featured-image', $product));

    $media = Media::factory()->create([
        'storage_path' => 'media/gallery-proxied.webp',
        'original_filename' => 'gallery-proxied.webp',
        'mime_type' => 'image/webp',
        'scan_status' => 'clean',
    ]);
    $gallery = ProductGallery::create(['product_id' => $product->id, 'media_id' => $media->id, 'sort_order' => 0]);

    expect($gallery->fresh()->image_url)->toBe(route('media.view', $media));

    // Legacy absolute URLs stored in the DB keep passing through untouched.
    $product->update(['featured_image_path' => 'https://cdn.example.com/legacy.jpg']);
    expect($product->fresh()->featured_image_url)->toBe('https://cdn.example.com/legacy.jpg');
});

test('the owner can view their profile photo but strangers cannot', function () {
    $owner = User::factory()->create();
    Storage::disk('s3')->put('profile-photos/me.jpg', 'fake-image-bytes');
    $owner->update(['photo_path' => 'profile-photos/me.jpg']);

    $this->actingAs($owner)->get(route('users.photo', $owner))->assertOk();

    $stranger = User::factory()->create();
    $this->actingAs($stranger)->get(route('users.photo', $owner))->assertForbidden();
});

test('guests are redirected to login for profile photos', function () {
    $owner = User::factory()->create();
    Storage::disk('s3')->put('profile-photos/me.jpg', 'fake-image-bytes');
    $owner->update(['photo_path' => 'profile-photos/me.jpg']);

    $this->get(route('users.photo', $owner))->assertRedirect(route('login'));
});
