<?php

declare(strict_types=1);

use App\Jobs\ScanUploadedFileJob;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('s3');
    Queue::fake();
    $this->service = app(MediaUploadService::class);
    $this->user = User::factory()->create();
});

test('accepts a valid PDF upload', function () {
    $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

    $media = $this->service->store($file, $this->user->id, 'business permit');

    expect($media)->toBeInstanceOf(Media::class)
        ->and($media->original_filename)->toBe('document.pdf')
        ->and($media->extension)->toBe('pdf')
        ->and($media->mime_type)->toBe('application/pdf')
        ->and($media->description)->toBe('business permit')
        ->and($media->scan_status)->toBe('pending')
        ->and($media->uploaded_by_id)->toBe($this->user->id);

    Storage::disk('s3')->assertExists($media->storage_path);
    Queue::assertPushed(ScanUploadedFileJob::class, fn ($job) => $job->media->id === $media->id);
});

test('accepts a valid JPEG image upload', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 640, 480);

    $media = $this->service->store($file, $this->user->id);

    expect($media->extension)->toBe('jpg')
        ->and($media->mime_type)->toBe('image/jpeg');

    Storage::disk('s3')->assertExists($media->storage_path);
});

test('accepts a valid PNG image upload', function () {
    $file = UploadedFile::fake()->image('cert.png', 800, 600);

    $media = $this->service->store($file, $this->user->id);

    expect($media->extension)->toBe('png')
        ->and($media->mime_type)->toBe('image/png');
});

test('rejects an exe file with disallowed MIME type', function () {
    $file = UploadedFile::fake()->create('malware.exe', 500, 'application/x-msdownload');

    $this->service->store($file, $this->user->id);
})->throws(ValidationException::class);

test('rejects a file exceeding max size', function () {
    // Create a file larger than 20MB
    $file = UploadedFile::fake()->create('large.pdf', 21000, 'application/pdf');

    $this->service->store($file, $this->user->id);
})->throws(ValidationException::class);

test('rejects mismatched extension and MIME type', function () {
    // A file with PDF MIME but .exe extension
    $file = UploadedFile::fake()->create('sneaky.exe', 500, 'application/pdf');

    $this->service->store($file, $this->user->id);
})->throws(ValidationException::class);

test('dispatches ScanUploadedFileJob after successful upload', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 1024, 'application/pdf');

    $media = $this->service->store($file, $this->user->id);

    Queue::assertPushed(ScanUploadedFileJob::class, function ($job) use ($media) {
        return $job->media->id === $media->id;
    });
});

test('media record has pending scan status on creation', function () {
    $file = UploadedFile::fake()->image('cert.jpg');

    $media = $this->service->store($file, $this->user->id);

    expect($media->fresh()->scan_status)->toBe('pending');
});

test('generates a view URL for media via the authenticated media route', function () {
    $media = Media::factory()->create([
        'uploaded_by_id' => $this->user->id,
        'storage_path' => 'media/2026/08/14/test-file.pdf',
    ]);

    $url = $this->service->signedUrl($media, 15);

    // signedUrl returns the authenticated media proxy route, not a direct S3 URL.
    // This ensures private documents are served through the app with auth checks.
    expect($url)->toBeString()
        ->and($url)->toContain(route('media.view', $media, false));
});
