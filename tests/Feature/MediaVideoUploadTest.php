<?php

declare(strict_types=1);

use App\Jobs\OptimizeImageJob;
use App\Jobs\ReconcileMediaOrphansJob;
use App\Jobs\ScanUploadedFileJob;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function videoUploadFile(string $filename, string $mimeType, int $bytes = 1024, ?string $clientMimeType = null): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'video-upload-');
    file_put_contents($path, str_repeat('v', $bytes));

    return new class($path, $filename, $clientMimeType ?? $mimeType, UPLOAD_ERR_OK, true, $mimeType) extends UploadedFile
    {
        public function __construct(
            string $path,
            string $originalName,
            ?string $mimeType,
            int $error,
            bool $test,
            private readonly string $detectedMimeType,
        ) {
            parent::__construct($path, $originalName, $mimeType, $error, $test);
        }

        public function getMimeType(): ?string
        {
            return $this->detectedMimeType;
        }

        public function __destruct()
        {
            if (is_file($this->getPathname())) {
                unlink($this->getPathname());
            }
        }
    };
}

test('video uploads accept matching mp4 and webm types without image optimization', function (string $filename, string $mimeType) {
    Config::set('services.clamav.enabled', false);
    Storage::fake('s3');
    Queue::fake();

    $uploader = User::factory()->create();
    $media = app(MediaUploadService::class)->storeVideo(videoUploadFile($filename, $mimeType), $uploader->id);

    expect($media->extension)->toBe(pathinfo($filename, PATHINFO_EXTENSION))
        ->and($media->mime_type)->toBe($mimeType)
        ->and($media->scan_status)->toBe('clean');

    Queue::assertNotPushed(OptimizeImageJob::class);
    Queue::assertNotPushed(ScanUploadedFileJob::class);
})->with([
    'mp4' => ['product.mp4', 'video/mp4'],
    'webm' => ['product.webm', 'video/webm'],
]);

test('video uploads reject unsupported, mismatched, and oversized files', function (string $filename, string $mimeType, int $bytes, string $message, ?string $clientMimeType = null) {
    Config::set('marketplace.video.max_kb', 1);
    $uploader = User::factory()->create();

    try {
        app(MediaUploadService::class)->storeVideo(videoUploadFile($filename, $mimeType, $bytes, $clientMimeType), $uploader->id);
    } catch (ValidationException $exception) {
        expect($exception->errors()['file'])->toContain($message);

        return;
    }

    $this->fail('The invalid video upload should have been rejected.');
})->with([
    'mov files are unsupported' => ['product.mov', 'video/quicktime', 512, 'Only MP4 and WebM videos are allowed.'],
    'mismatched extension is rejected' => ['product.webm', 'video/mp4', 512, 'The video extension does not match its file type.'],
    'oversized files are rejected' => ['product.mp4', 'video/mp4', 2048, 'The video must not exceed 1 MB.'],
    'renamed executables are rejected' => ['product.mp4', 'application/x-dosexec', 512, 'Only MP4 and WebM videos are allowed.', 'video/mp4'],
]);

test('the document store method continues rejecting video files', function () {
    $uploader = User::factory()->create();

    expect(fn () => app(MediaUploadService::class)->store(videoUploadFile('product.mp4', 'video/mp4'), $uploader->id))
        ->toThrow(ValidationException::class);
});

test('every foreign key referencing media id is included in orphan reconciliation', function () {
    // PostgreSQL: join key_column_usage → referential_constraints → constraint_column_usage
    // to find all FK columns that reference media.id.
    $foreignKeys = DB::select(<<<'SQL'
        SELECT kcu.table_name, kcu.column_name
        FROM information_schema.key_column_usage kcu
        JOIN information_schema.referential_constraints rc
            ON rc.constraint_name = kcu.constraint_name
            AND rc.constraint_schema = kcu.constraint_schema
        JOIN information_schema.key_column_usage ccu
            ON ccu.constraint_name = rc.unique_constraint_name
            AND ccu.constraint_schema = rc.unique_constraint_schema
        WHERE ccu.table_name = 'media'
          AND ccu.column_name = 'id'
        ORDER BY kcu.table_name, kcu.column_name
    SQL);

    expect($foreignKeys)->not->toBeEmpty();

    foreach ($foreignKeys as $foreignKey) {
        expect(ReconcileMediaOrphansJob::REFERENCES[$foreignKey->table_name] ?? [])
            ->toContain($foreignKey->column_name);
    }
});
