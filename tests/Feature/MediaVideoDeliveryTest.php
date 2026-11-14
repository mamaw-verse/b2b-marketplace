<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\VideoManager;
use App\Models\Media;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopStaff;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Config::set('marketplace.features.product_video_enabled', true);
    Config::set('marketplace.features.subscriptions_enabled', false);
    Storage::fake('s3');
});

/**
 * @return array{0: User, 1: Media, 2: Product}
 */
function createVideoDeliveryFixture(string $productStatus = 'published', string $scanStatus = 'clean'): array
{
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    $media = Media::factory()->create([
        'uploaded_by_id' => $owner->id,
        'original_filename' => 'product.mp4',
        'extension' => 'mp4',
        'mime_type' => 'video/mp4',
        'storage_path' => 'media/product.mp4',
        'scan_status' => $scanStatus,
    ]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => $productStatus,
        'moderation_status' => 'active',
        'video_media_id' => $media->id,
    ]);

    return [$owner, $media, $product];
}

test('clean videos on marketplace-visible products redirect to a private ranged object URL', function () {
    [, $media] = createVideoDeliveryFixture();

    $response = $this->get(route('media.view', $media));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('product.mp4')
        ->and($response->headers->get('Cache-Control'))->toContain('private')->toContain('max-age=300');
});

test('videos are hidden when the product video feature is disabled', function () {
    [, $media] = createVideoDeliveryFixture();
    Config::set('marketplace.features.product_video_enabled', false);

    $this->get(route('media.view', $media))->assertNotFound();
});

test('pending or infected videos are not public', function (string $scanStatus) {
    [, $media] = createVideoDeliveryFixture(scanStatus: $scanStatus);

    $this->get(route('media.view', $media))->assertNotFound();
})->with(['pending', 'infected']);

test('videos on non-visible products remain private to the product owner', function () {
    [$owner, $media] = createVideoDeliveryFixture(productStatus: 'archived');

    $this->get(route('media.view', $media))->assertForbidden();
    $this->actingAs($owner)->get(route('media.view', $media))->assertRedirect();
});

test('active staff can view their shop video while revoked staff cannot', function () {
    Config::set('marketplace.features.staff_enabled', true);
    [$owner, $media, $product] = createVideoDeliveryFixture(productStatus: 'archived');
    $staff = User::factory()->create(['email_verified_at' => now()]);
    $membership = ShopStaff::factory()->active($staff)->create([
        'shop_id' => $product->shop_id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);

    $this->actingAs($staff)->get(route('media.view', $media))->assertRedirect();

    $membership->update(['status' => 'revoked']);
    $this->actingAs($staff->fresh())->get(route('media.view', $media))->assertForbidden();
});

test('admins can access a clean video on an unpublished product', function () {
    $this->seed(RolePermissionSeeder::class);
    [, $media] = createVideoDeliveryFixture(productStatus: 'archived');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('media.view', $media))->assertRedirect();
});

test('the video management route is feature gated and authorizes the product owner', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $this->actingAs($owner)->get(route('hub.products.video', $product))->assertOk();

    Config::set('marketplace.features.product_video_enabled', false);
    $this->get(route('hub.products.video', $product))->assertNotFound();
    $this->get(route('hub.products.edit', $product))->assertOk()->assertDontSee('Video');
});

test('a different seller cannot manage another shop product video', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $stranger = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($stranger)->get(route('hub.products.video', $product))->assertForbidden();
});

test('replacing a product video leaves only the new media referenced', function () {
    Config::set('services.clamav.enabled', false);
    Queue::fake();
    [$owner, $oldMedia, $product] = createVideoDeliveryFixture(productStatus: 'archived');
    $upload = UploadedFile::fake()->create('replacement.mp4', 100, 'video/mp4');

    Livewire::actingAs($owner)
        ->test(VideoManager::class, ['product' => $product])
        ->set('newVideo', $upload)
        ->call('uploadVideo')
        ->assertHasNoErrors();

    $newMediaId = $product->fresh()->video_media_id;

    expect($newMediaId)->not->toBe($oldMedia->id)
        ->and($oldMedia->fresh()->videoProducts)->toHaveCount(0)
        ->and(Media::findOrFail($newMediaId)->videoProducts)->toHaveCount(1);
});
