<?php

declare(strict_types=1);

namespace App\Services;

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
     * Store an uploaded file to S3, create a media record, and dispatch the antivirus scan job.
     *
     * @throws ValidationException If the file fails MIME/extension/size validation.
     */
    public function store(UploadedFile $file, int $uploaderId, string $description = ''): Media
    {
        $this->validate($file);

        $extension = mb_strtolower($file->getClientOriginalExtension());
        $originalFilename = $file->getClientOriginalName();
        $storagePath = $this->generateStoragePath($extension);

        Storage::disk('s3')->putFileAs(
            dirname($storagePath),
            $file,
            basename($storagePath),
        );

        $media = Media::create([
            'original_filename' => $originalFilename,
            'extension' => $extension,
            'storage_path' => $storagePath,
            'mime_type' => $file->getMimeType(),
            'description' => $description ?: null,
            'scan_status' => 'pending',
            'uploaded_by_id' => $uploaderId,
        ]);

        ScanUploadedFileJob::dispatch($media);

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
     * Generate a unique storage path for the file.
     */
    private function generateStoragePath(string $extension): string
    {
        $uuid = Str::uuid()->toString();
        $datePrefix = now()->format('Y/m/d');

        return "media/{$datePrefix}/{$uuid}.{$extension}";
    }
}
