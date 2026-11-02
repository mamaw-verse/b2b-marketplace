<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ImageOptimizationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Asynchronously optimizes an image stored on S3.
 *
 * Dispatched with the S3 storage path. The job reads the file, checks its
 * MIME type, and if it's an image, scales it down and re-encodes it as WebP
 * in-place. Non-image files are silently skipped.
 */
final class OptimizeImageJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 30;

    /**
     * @param  string  $storagePath  S3 path of the uploaded file (e.g. "media/2026/10/29/uuid.jpg").
     */
    public function __construct(
        public readonly string $storagePath,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ImageOptimizationService $optimizer): void
    {
        $optimized = $optimizer->optimize($this->storagePath);

        if (! $optimized) {
            Log::info('OptimizeImageJob: Skipped (non-image or file missing).', [
                'path' => $this->storagePath,
            ]);
        }
    }
}
