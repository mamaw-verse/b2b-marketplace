<?php

declare(strict_types=1);

use App\Services\ImageOptimizationService;
use Illuminate\Http\Testing\File;
use Illuminate\Http\Testing\FileFactory;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Storage::fake('s3');
});

/**
 * Create a real GD image in-memory so Intervention can read it back.
 */
function makeTestImage(int $width = 2000, int $height = 1500, string $format = 'jpg'): File
{
    return (new FileFactory)->image("test.{$format}", $width, $height);
}

function makeService(): ImageOptimizationService
{
    return new ImageOptimizationService;
}

test('optimizes a wide image by scaling it down to max 1200px width', function () {
    $image = makeTestImage(width: 2000, height: 1500);
    $path = 'media/'.now()->format('Y/m/d').'/test.jpg';
    Storage::disk('s3')->put($path, $image->getContent());

    $optimized = makeService()->optimize($path);

    expect($optimized)->toBeTrue();

    // The optimized file should now have a .webp extension at the same path.
    $webpPath = str_replace('.jpg', '.webp', $path);
    expect(Storage::disk('s3')->exists($webpPath))->toBeTrue();
    expect(Storage::disk('s3')->exists($path))->toBeFalse();

    // The optimized image should be non-empty and smaller than the original.
    expect(Storage::disk('s3')->size($webpPath))->toBeGreaterThan(0);
    expect(Storage::disk('s3')->size($webpPath))->toBeLessThan(strlen($image->getContent()));
});

test('does not upscale an image smaller than max width', function () {
    $image = makeTestImage(width: 800, height: 600);
    $path = 'media/'.now()->format('Y/m/d').'/small.jpg';
    Storage::disk('s3')->put($path, $image->getContent());

    $optimized = makeService()->optimize($path);

    expect($optimized)->toBeTrue();

    $webpPath = str_replace('.jpg', '.webp', $path);
    expect(Storage::disk('s3')->exists($webpPath))->toBeTrue();

    // Small image should not be upscaled — optimized size should be <= original.
    expect(Storage::disk('s3')->size($webpPath))->toBeGreaterThan(0);
    expect(Storage::disk('s3')->size($webpPath))->toBeLessThanOrEqual(strlen($image->getContent()));
});

test('converts the file extension to webp', function () {
    $image = makeTestImage(width: 2000, height: 1000, format: 'png');
    $path = 'media/'.now()->format('Y/m/d').'/photo.png';
    Storage::disk('s3')->put($path, $image->getContent());

    makeService()->optimize($path);

    $webpPath = str_replace('.png', '.webp', $path);
    expect(Storage::disk('s3')->exists($webpPath))->toBeTrue();
    expect(Storage::disk('s3')->exists($path))->toBeFalse();
});

test('keeps the path as-is when already webp', function () {
    $image = makeTestImage(width: 2000, height: 1000, format: 'webp');
    $path = 'media/'.now()->format('Y/m/d').'/already.webp';
    Storage::disk('s3')->put($path, $image->getContent());

    makeService()->optimize($path);

    // Should stay at the same path since it's already .webp.
    expect(Storage::disk('s3')->exists($path))->toBeTrue();
});

test('skips non-image files like PDFs', function () {
    $path = 'media/'.now()->format('Y/m/d').'/document.pdf';
    Storage::disk('s3')->put($path, '%PDF-1.4 fake PDF content');

    $optimized = makeService()->optimize($path);

    expect($optimized)->toBeFalse();
    // Original file should be untouched.
    expect(Storage::disk('s3')->exists($path))->toBeTrue();
});

test('returns false when the file does not exist', function () {
    $optimized = makeService()->optimize('media/nonexistent/file.jpg');

    expect($optimized)->toBeFalse();
});

test('reduces the file size after optimization', function () {
    // Create a large JPEG that will benefit from WebP compression.
    $image = makeTestImage(width: 2000, height: 2000);
    $path = 'media/'.now()->format('Y/m/d').'/large.jpg';
    Storage::disk('s3')->put($path, $image->getContent());

    $originalSize = Storage::disk('s3')->size($path);

    makeService()->optimize($path);

    $webpPath = str_replace('.jpg', '.webp', $path);
    $optimizedSize = Storage::disk('s3')->size($webpPath);

    // The WebP version should be smaller than the original JPEG.
    expect($optimizedSize)->toBeLessThan($originalSize);
});

test('isImage correctly identifies image MIME types', function () {
    $service = makeService();

    expect($service->isImage('image/jpeg'))->toBeTrue();
    expect($service->isImage('image/png'))->toBeTrue();
    expect($service->isImage('image/webp'))->toBeTrue();
    expect($service->isImage('image/gif'))->toBeTrue();
    expect($service->isImage('application/pdf'))->toBeFalse();
    expect($service->isImage('application/msword'))->toBeFalse();
});
