<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\VideoManager;
use App\Models\Media;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Config::set('marketplace.features.product_video_enabled', true);
    Config::set('marketplace.features.subscriptions_enabled', false);
    Config::set('services.clamav.enabled', false);
    Storage::fake('s3');
});

/**
 * @return array{0: User, 1: Product}
 */
function videoUploadFixture(): array
{
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'archived',
        'moderation_status' => 'active',
    ]);

    return [$owner, $product];
}

test('an owner can upload an accepted video through the hub component', function (string $filename, string $mime, string $expectedMime): void {
    [$owner, $product] = videoUploadFixture();

    Livewire::actingAs($owner)
        ->test(VideoManager::class, ['product' => $product])
        ->set('newVideo', UploadedFile::fake()->create($filename, 100, $mime))
        ->call('uploadVideo')
        ->assertHasNoErrors();

    $product->refresh();
    $media = $product->video;

    expect($media)->not->toBeNull()
        ->and($media->scan_status)->toBe('clean')
        ->and($media->mime_type)->toBe($expectedMime)
        ->and($media->uploaded_by_id)->toBe($owner->id)
        ->and($product->updated_by_id)->toBe($owner->id)
        ->and(Product::query()->where('video_media_id', $media->id)->count())->toBe(1);

    Storage::disk('s3')->assertExists($media->storage_path);
})->with([
    ['clip.mp4', 'video/mp4', 'video/mp4'],
    ['clip.webm', 'video/webm', 'video/webm'],
]);

test('video manager suggests a square aspect ratio', function (): void {
    [$owner, $product] = videoUploadFixture();

    Livewire::actingAs($owner)
        ->test(VideoManager::class, ['product' => $product])
        ->assertSee('Square video (1:1) works best — e.g. 1080 × 1080 px.');
});

test('a MOV upload is rejected and nothing is linked', function (): void {
    [$owner, $product] = videoUploadFixture();

    Livewire::actingAs($owner)
        ->test(VideoManager::class, ['product' => $product])
        ->set('newVideo', UploadedFile::fake()->create('clip.mov', 100, 'video/quicktime'))
        ->call('uploadVideo')
        ->assertHasErrors('newVideo');

    expect($product->fresh()->video_media_id)->toBeNull();
});

test('video uploads use the configured storage prefix while other media stays under media', function (): void {
    [$owner, $product] = videoUploadFixture();
    Config::set('marketplace.video.storage_prefix', 'test-media/videos');

    Livewire::actingAs($owner)
        ->test(VideoManager::class, ['product' => $product])
        ->set('newVideo', UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4'))
        ->call('uploadVideo')
        ->assertHasNoErrors();

    $video = $product->fresh()->video;
    $document = app(MediaUploadService::class)->store(
        UploadedFile::fake()->create('spec.pdf', 10, 'application/pdf'),
        $owner->id,
    );

    expect($video->storage_path)->toStartWith('test-media/videos/')
        ->and($document->storage_path)->toStartWith('media/');
});

test('the purge command only deletes prefixed videos and clears product references', function (): void {
    [, $product] = videoUploadFixture();
    $prefix = 'test-media/videos';

    $linked = Media::factory()->create([
        'original_filename' => 'linked.mp4',
        'extension' => 'mp4',
        'mime_type' => 'video/mp4',
        'storage_path' => $prefix.'/2026/01/01/linked.mp4',
        'scan_status' => 'clean',
    ]);
    $orphan = Media::factory()->create([
        'original_filename' => 'orphan.mp4',
        'extension' => 'mp4',
        'mime_type' => 'video/mp4',
        'storage_path' => $prefix.'/2026/01/01/orphan.mp4',
        'scan_status' => 'clean',
    ]);
    $other = Media::factory()->create([
        'original_filename' => 'photo.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'storage_path' => 'products/images/photo.jpg',
        'scan_status' => 'clean',
    ]);

    foreach ([$linked, $orphan, $other] as $media) {
        Storage::disk('s3')->put($media->storage_path, 'test');
    }

    $product->update(['video_media_id' => $linked->id]);

    $this->artisan('media:purge-videos', ['--prefix' => $prefix])->assertSuccessful();

    expect(Media::query()->whereKey($linked->id)->exists())->toBeTrue()
        ->and(Storage::disk('s3')->exists($linked->storage_path))->toBeTrue();

    $this->artisan('media:purge-videos', ['--prefix' => $prefix, '--force' => true])->assertSuccessful();

    expect($product->fresh()->video_media_id)->toBeNull()
        ->and(Media::query()->whereKey($linked->id)->exists())->toBeFalse()
        ->and(Media::query()->whereKey($orphan->id)->exists())->toBeFalse()
        ->and(Media::query()->whereKey($other->id)->exists())->toBeTrue()
        ->and(Storage::disk('s3')->exists($linked->storage_path))->toBeFalse()
        ->and(Storage::disk('s3')->exists($other->storage_path))->toBeTrue();
});

test('replacing a video leaves only the new media referenced', function (): void {
    [$owner, $product] = videoUploadFixture();
    $oldMedia = Media::factory()->create([
        'uploaded_by_id' => $owner->id,
        'original_filename' => 'old.mp4',
        'extension' => 'mp4',
        'mime_type' => 'video/mp4',
        'storage_path' => 'media/old.mp4',
        'scan_status' => 'clean',
    ]);
    $product->update(['video_media_id' => $oldMedia->id]);

    Livewire::actingAs($owner)
        ->test(VideoManager::class, ['product' => $product])
        ->set('newVideo', UploadedFile::fake()->create('new.mp4', 100, 'video/mp4'))
        ->call('uploadVideo')
        ->assertHasNoErrors();

    $newMediaId = $product->fresh()->video_media_id;

    expect($newMediaId)->not->toBeNull()
        ->and($newMediaId)->not->toBe($oldMedia->id)
        ->and($oldMedia->fresh()->videoProducts)->toHaveCount(0)
        ->and(Media::findOrFail($newMediaId)->videoProducts)->toHaveCount(1);
});
