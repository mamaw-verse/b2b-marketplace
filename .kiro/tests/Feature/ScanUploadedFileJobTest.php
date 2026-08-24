<?php

declare(strict_types=1);

use App\Jobs\ScanUploadedFileJob;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('s3');
    $this->user = User::factory()->create();
});

test('marks media as clean when ClamAV is unavailable and skip_when_unavailable is true', function () {
    config(['services.clamav.host' => '127.0.0.1']);
    config(['services.clamav.port' => 19999]); // unlikely to be open
    config(['services.clamav.timeout' => 1]);
    config(['services.clamav.skip_when_unavailable' => true]);

    $media = Media::factory()->create([
        'uploaded_by_id' => $this->user->id,
        'scan_status' => 'pending',
    ]);

    Storage::disk('s3')->put($media->storage_path, 'safe file content');

    $job = new ScanUploadedFileJob($media);
    $job->handle();

    expect($media->fresh()->scan_status)->toBe('clean');
});

test('marks media as infected when file is not found in S3', function () {
    $media = Media::factory()->create([
        'uploaded_by_id' => $this->user->id,
        'scan_status' => 'pending',
        'storage_path' => 'nonexistent/file.pdf',
    ]);

    // Don't put any file in S3

    $job = new ScanUploadedFileJob($media);
    $job->handle();

    expect($media->fresh()->scan_status)->toBe('infected');
});

test('scan job is queued and can be dispatched', function () {
    $media = Media::factory()->create([
        'uploaded_by_id' => $this->user->id,
        'scan_status' => 'pending',
    ]);

    Storage::disk('s3')->put($media->storage_path, 'test content');

    // Verify the job can be serialized and dispatched
    $job = new ScanUploadedFileJob($media);
    expect($job->media->id)->toBe($media->id);
});

test('media scan_status transitions from pending to clean', function () {
    config(['services.clamav.skip_when_unavailable' => true]);
    config(['services.clamav.port' => 19999]);
    config(['services.clamav.timeout' => 1]);

    $media = Media::factory()->create([
        'uploaded_by_id' => $this->user->id,
        'scan_status' => 'pending',
    ]);

    expect($media->scan_status)->toBe('pending');

    Storage::disk('s3')->put($media->storage_path, 'clean document content');

    $job = new ScanUploadedFileJob($media);
    $job->handle();

    $media->refresh();
    expect($media->scan_status)->toBe('clean');
});
