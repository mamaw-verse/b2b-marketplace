<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopStaff;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config()->set('marketplace.features.product_video_enabled', true);
    config()->set('marketplace.features.staff_enabled', true);
    config()->set('marketplace.features.subscriptions_enabled', false);
    config()->set('services.clamav.enabled', false);
    config()->set('humancheck.enabled', false);
    config()->set('filesystems.disks.s3.temporary_url', 'http://minio:9000');
    Storage::forgetDisk('s3');
});

/**
 * @return array{0: User, 1: Shop, 2: Product, 3: Media}
 */
function videoBrowserFixture(string $scanStatus = 'clean', string $productStatus = 'published'): array
{
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    $storagePath = 'media/e2e/'.str()->uuid().'.mp4';
    Storage::disk('s3')->put($storagePath, file_get_contents(e2eVideoFixture()));
    $media = Media::factory()->create([
        'uploaded_by_id' => $owner->id,
        'original_filename' => 'product.mp4',
        'extension' => 'mp4',
        'mime_type' => 'video/mp4',
        'storage_path' => $storagePath,
        'scan_status' => $scanStatus,
    ]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => $productStatus,
        'moderation_status' => 'active',
        'video_media_id' => $media->id,
    ]);

    return [$owner, $shop, $product, $media];
}

test('the feature flag hides the player, the video tab, and the hub route', function (): void {
    [$owner, , $product] = videoBrowserFixture();

    config()->set('marketplace.features.product_video_enabled', false);

    visit('/products/'.$product->slug)
        ->assertDontSee('Product video')
        ->assertNotPresent('video[controls]');

    $this->actingAs($owner);

    visit('/hub/products/'.$product->id.'/edit')->assertDontSee('Video');
    visit('/hub/products/'.$product->id.'/video')->assertSee('404');
});

test('a clean video plays on a published product with the featured image as poster', function (): void {
    [, , $product, $media] = videoBrowserFixture();

    $product->update(['featured_image_path' => 'products/images/nuggets.jpg']);
    Storage::disk('s3')->put('products/images/nuggets.jpg', 'fake-image');

    $page = visit('/products/'.$product->slug)
        ->assertSee('Product video')
        ->assertPresent('video > source');

    expect($page->attribute('video > source', 'src'))
        ->toContain('/media/'.$media->id.'/view')
        ->and($page->attribute('video > source', 'type'))->toBe('video/mp4')
        ->and($page->attribute('video[controls]', 'poster'))->toContain('/products/'.$product->id.'/featured-image')
        ->and($page->attribute('video[controls]', 'playsinline'))->not->toBeNull();
});

test('the video element is inline-playable for WebKit (Safari and iOS)', function (): void {
    [, , $product, $media] = videoBrowserFixture();

    $page = visit('/products/'.$product->slug)
        ->assertSee('Product video')
        ->assertPresent('video > source');

    expect($page->attribute('video[controls]', 'playsinline'))->not->toBeNull()
        ->and($page->attribute('video[controls]', 'controls'))->not->toBeNull()
        ->and($page->attribute('video[controls]', 'preload'))->toBe('metadata')
        ->and($page->attribute('video > source', 'src'))->toContain('/media/'.$media->id.'/view');
})->group('safari');

test('pending and infected videos never render publicly', function (string $scanStatus): void {
    [, , $product] = videoBrowserFixture(scanStatus: $scanStatus);

    visit('/products/'.$product->slug)
        ->assertSee($product->name)
        ->assertDontSee('Product video')
        ->assertNotPresent('video[controls]');
})->with(['pending', 'infected']);

test('the owner sees a scan notice for pending and infected videos in the hub', function (string $scanStatus, string $notice): void {
    [$owner, , $product] = videoBrowserFixture(scanStatus: $scanStatus);

    $this->actingAs($owner);

    visit('/hub/products/'.$product->id.'/video')
        ->assertSee($notice)
        ->assertNotPresent('video[controls]');
})->with([
    ['pending', 'will be available after its security scan is complete'],
    ['infected', 'did not pass the security scan'],
]);

test('the hub exposes a single MP4/WebM upload dropzone', function (): void {
    [$owner, , $product] = videoBrowserFixture();

    $product->update(['video_media_id' => null]);
    $this->actingAs($owner);

    $page = visit('/hub/products/'.$product->id.'/video')
        ->assertSee('No video yet')
        ->assertSee('MP4 or WebM — up to 50 MB')
        ->assertPresent('#product-video-input');

    expect($page->attribute('#product-video-input', 'accept'))->toBe('video/mp4,video/webm,.mp4,.webm')
        ->and($page->attribute('#product-video-input', 'multiple'))->toBeNull()
        ->and($page->attribute('#product-video-input', 'wire:model'))->toBe('newVideo');
});

test('the owner can remove the video from the hub', function (): void {
    [$owner, , $product] = videoBrowserFixture();

    $this->actingAs($owner);

    visit('/hub/products/'.$product->id.'/video')
        ->assertSee('Remove video')
        ->click('Remove video')
        ->click('[wire\\:click="removeVideo"]')
        ->assertSee('Video removed.');

    expect($product->fresh()->video_media_id)->toBeNull();
});

test('active staff can manage the shop video but a different seller cannot', function (): void {
    [$owner, $shop, $product] = videoBrowserFixture();
    $staff = User::factory()->create();
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);

    $this->actingAs($staff);
    visit('/hub/products/'.$product->id.'/video')->assertSee('Product video');

    $stranger = User::factory()->create();
    Shop::factory()->create(['user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger);
    visit('/hub/products/'.$product->id.'/video')->assertSee('403');
});

test('anonymous visitors get a redirect to the presigned object and drafts stay private', function (): void {
    [, , $published, $publishedMedia] = videoBrowserFixture();
    [, , $draft, $draftMedia] = videoBrowserFixture(productStatus: 'archived');

    // The redirect is asserted through the HTTP-layer response headers.
    $response = $this->get('/media/'.$publishedMedia->id.'/view');
    $response->assertRedirect();

    expect($response->headers->get('Location'))->toContain(basename($publishedMedia->storage_path))
        ->and($response->headers->get('Cache-Control'))->toContain('private')
        ->and($response->headers->get('Cache-Control'))->toContain('max-age=300');

    $this->get('/media/'.$draftMedia->id.'/view')->assertForbidden();
});
