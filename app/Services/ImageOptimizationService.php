<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Optimizes images in-place on S3 by resizing and re-encoding to WebP.
 *
 * Reads the original image, scales it down to a maximum width, compresses
 * to WebP at the given quality, and writes the result back to the same S3
 * path — replacing the original file. Non-image files are skipped.
 */
final class ImageOptimizationService
{
    private const string DISK = 's3';

    /** Maximum width in pixels — images wider than this are scaled down. */
    private const int MAX_WIDTH = 1200;

    /** WebP encoding quality (1–100). */
    private const int QUALITY = 80;

    /** Image MIME types eligible for optimization. */
    private const array IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/bmp',
    ];

    /**
     * Optimize an image at the given S3 path in-place.
     *
     * Reads the file, scales it down to max 1200px width, encodes as WebP
     * at quality 80, and writes the result back to the same path — replacing
     * the original. The file extension is updated to `.webp`.
     *
     * @param  string  $path  S3 storage path (e.g. "media/2026/10/29/uuid.jpg")
     * @return bool True if the image was optimized; false if skipped (non-image or file missing).
     */
    public function optimize(string $path): bool
    {
        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($path)) {
            Log::warning('ImageOptimizationService: File not found.', ['path' => $path]);

            return false;
        }

        $mimeType = $disk->mimeType($path);

        if (! $this->isImage($mimeType)) {
            Log::info('ImageOptimizationService: Skipping non-image file.', [
                'path' => $path,
                'mime_type' => $mimeType,
            ]);

            return false;
        }

        $content = $disk->get($path);

        if ($content === null) {
            Log::warning('ImageOptimizationService: Could not read file content.', ['path' => $path]);

            return false;
        }

        $image = Image::fromBytes($content);

        $originalWidth = $image->width();

        // Scale down only — never upscale. Preserves aspect ratio.
        if ($originalWidth > self::MAX_WIDTH) {
            $image = $image->scale(width: self::MAX_WIDTH);
        }

        $encoded = $image->toWebp()->quality(self::QUALITY)->toString();

        $optimizedPath = $this->ensureWebpExtension($path);

        $disk->put($optimizedPath, $encoded);

        // If the extension changed (e.g. .jpg → .webp), remove the old file.
        if ($optimizedPath !== $path) {
            $disk->delete($path);
        }

        Log::info('ImageOptimizationService: Image optimized in-place.', [
            'path' => $path,
            'optimized_path' => $optimizedPath,
            'original_width' => $originalWidth,
            'final_width' => min($originalWidth, self::MAX_WIDTH),
            'original_size' => strlen($content),
            'optimized_size' => strlen($encoded),
        ]);

        return true;
    }

    /**
     * Whether the given MIME type is an image that can be optimized.
     */
    public function isImage(string $mimeType): bool
    {
        return in_array($mimeType, self::IMAGE_MIME_TYPES, true);
    }

    /**
     * Ensure the file path ends with .webp. If not, replace the extension.
     */
    private function ensureWebpExtension(string $path): string
    {
        $extension = Str::afterLast($path, '.');

        if (strtolower($extension) === 'webp') {
            return $path;
        }

        return Str::replaceLast('.'.$extension, '.webp', $path);
    }
}
