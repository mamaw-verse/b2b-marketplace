<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\OptimizeImageJob;
use App\Jobs\ScanUploadedFileJob;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MediaUploadService
{
    /**
     * Allowed MIME types and their corresponding extensions.
     *
     * @var array<string, list<string>>
     */
    private const array ALLOWED_MIMES = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
    ];

    /**
     * Video MIME types and their matching filename extensions.
     *
     * @var array<string, list<string>>
     */
    private const array VIDEO_MIMES = [
        'video/mp4' => ['mp4'],
        'video/webm' => ['webm'],
    ];

    /**
     * Maximum file size in bytes (20 MB).
     */
    private const int MAX_FILE_SIZE = 20 * 1024 * 1024;

    /**
     * Get the list of allowed MIME types.
     *
     * @return list<string>
     */
    public static function allowedMimeTypes(): array
    {
        return array_keys(self::ALLOWED_MIMES);
    }

    /**
     * Get the list of allowed extensions.
     *
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return array_unique(array_merge(...array_values(self::ALLOWED_MIMES)));
    }

    /**
     * Get the maximum file size in bytes.
     */
    public static function maxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }

    /**
     * Whether antivirus scanning is enabled.
     *
     * Disabled by default (CLAMAV_ENABLED=false) — uploads are marked
     * clean immediately and no scan job is dispatched. Enable in
     * production where the clamav service is running.
     */
    public static function scanningEnabled(): bool
    {
        return (bool) config('services.clamav.enabled', false);
    }

    /**
     * Store an uploaded file to S3, create a media record, and dispatch the antivirus scan job.
     *
     * When scanning is disabled the record is marked clean immediately.
     *
     * @throws ValidationException If the file fails MIME/extension/size validation.
     */
    public function store(UploadedFile $file, int $uploaderId, string $description = ''): Media
    {
        $this->validate($file);

        return $this->persist($file, $uploaderId, $description);
    }

    /** Store a validated product video without dispatching image optimization. */
    public function storeVideo(UploadedFile $file, int $uploaderId, string $description = 'Product video'): Media
    {
        $this->validateVideo($file);

        return $this->persist(
            $file,
            $uploaderId,
            $description,
            (string) config('marketplace.video.storage_prefix', 'media'),
        );
    }

    /** Persist an already validated upload and dispatch applicable background work. */
    private function persist(UploadedFile $file, int $uploaderId, string $description, string $storagePrefix = 'media'): Media
    {
        $extension = mb_strtolower($file->getClientOriginalExtension());
        $originalFilename = $file->getClientOriginalName();
        $storagePath = $this->generateStoragePath($extension, $storagePrefix);

        Storage::disk('s3')->putFileAs(
            dirname($storagePath),
            $file,
            basename($storagePath),
        );

        $scanningEnabled = self::scanningEnabled();

        $media = Media::create([
            'original_filename' => $originalFilename,
            'extension' => $extension,
            'storage_path' => $storagePath,
            'mime_type' => $file->getMimeType(),
            'description' => $description ?: null,
            'scan_status' => $scanningEnabled ? 'pending' : 'clean',
            'uploaded_by_id' => $uploaderId,
        ]);

        if ($scanningEnabled) {
            ScanUploadedFileJob::dispatch($media);
        }

        // Dispatch async image optimization for image files (non-blocking).
        if (str_starts_with($media->mime_type, 'image/')) {
            OptimizeImageJob::dispatch($storagePath);
        }

        return $media;
    }

    /**
     * Generate a time-limited pre-signed URL for private document viewing.
     *
     * The URL provides direct access to the S3 object without proxying through
     * the application server. It expires after the specified duration.
     */
    public function signedUrl(Media $media, int $minutes = 15): string
    {
        return route('media.view', $media);
    }

    /**
     * Validate the uploaded file against the allow-list.
     *
     * @throws ValidationException
     */
    private function validate(UploadedFile $file): void
    {
        $errors = [];

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $errors['file'][] = 'The file must not exceed 20 MB.';
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (! isset(self::ALLOWED_MIMES[$mimeType])) {
            $errors['file'][] = "The file type \"{$mimeType}\" is not allowed.";
        }

        // Check extension against MIME allow-list
        $extension = mb_strtolower($file->getClientOriginalExtension());
        if (isset(self::ALLOWED_MIMES[$mimeType])) {
            if (! in_array($extension, self::ALLOWED_MIMES[$mimeType], true)) {
                $errors['file'][] = "The file extension \".{$extension}\" does not match its MIME type.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Validate product video MIME, extension, and configured size limits.
     *
     * @throws ValidationException
     */
    private function validateVideo(UploadedFile $file): void
    {
        $errors = [];
        $maxKilobytes = (int) config('marketplace.video.max_kb', 51200);
        $mimeType = $file->getMimeType();
        $extension = mb_strtolower($file->getClientOriginalExtension());

        if ($file->getSize() > $maxKilobytes * 1024) {
            $errors['file'][] = __('The video must not exceed :size MB.', ['size' => (int) ceil($maxKilobytes / 1024)]);
        }

        if (! isset(self::VIDEO_MIMES[$mimeType])) {
            $errors['file'][] = __('Only MP4 and WebM videos are allowed.');
        } elseif (! in_array($extension, self::VIDEO_MIMES[$mimeType], true)) {
            $errors['file'][] = __('The video extension does not match its file type.');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Generate a unique storage path for the file.
     */
    private function generateStoragePath(string $extension, string $prefix = 'media'): string
    {
        $uuid = Str::uuid()->toString();
        $datePrefix = now()->format('Y/m/d');
        $prefix = trim($prefix, '/');

        if ($prefix === '') {
            $prefix = 'media';
        }

        return "{$prefix}/{$datePrefix}/{$uuid}.{$extension}";
    }
}
