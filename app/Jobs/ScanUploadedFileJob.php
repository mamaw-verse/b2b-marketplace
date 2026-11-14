<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ScanUploadedFileJob implements ShouldQueue
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

    public function __construct(
        public readonly Media $media,
    ) {}

    /**
     * Execute the job — scan the file for malware using ClamAV.
     *
     * When scanning is disabled (CLAMAV_ENABLED=false) any pending record
     * is marked clean immediately — this settles rows dispatched before
     * the flag was turned off. If ClamAV is enabled but unavailable and
     * the config allows, the file is marked as 'clean' (for development
     * environments without ClamAV installed).
     */
    public function handle(): void
    {
        if (! (bool) config('services.clamav.enabled', false)) {
            if ($this->media->scan_status !== 'clean') {
                $this->markStatus('clean');
            }

            Log::info('ScanUploadedFileJob: Scanning disabled, marked clean.', [
                'media_id' => $this->media->id,
            ]);

            return;
        }

        $fileStream = Storage::disk('s3')->readStream($this->media->storage_path);

        if (! is_resource($fileStream)) {
            Log::warning('ScanUploadedFileJob: File not found in S3.', [
                'media_id' => $this->media->id,
                'path' => $this->media->storage_path,
            ]);
            $this->markStatus('infected');

            return;
        }

        try {
            $scanResult = $this->scanWithClamAv($fileStream);
        } finally {
            fclose($fileStream);
        }

        $this->markStatus($scanResult);

        Log::info('ScanUploadedFileJob: Scan complete.', [
            'media_id' => $this->media->id,
            'result' => $scanResult,
        ]);
    }

    /**
     * Scan file content using ClamAV via TCP socket connection.
     *
     * Uses the INSTREAM command to scan content without writing to disk.
     * Returns 'clean' or 'infected'.
     *
     * @param  resource  $fileStream
     */
    private function scanWithClamAv(mixed $fileStream): string
    {
        $host = config('services.clamav.host', 'localhost');
        $port = (int) config('services.clamav.port', 3310);
        $timeout = (int) config('services.clamav.timeout', 30);

        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if ($socket === false) {
            Log::warning('ScanUploadedFileJob: ClamAV connection failed.', [
                'host' => $host,
                'port' => $port,
                'error' => "{$errno}: {$errstr}",
            ]);

            // In dev environments without ClamAV, default to clean
            if (config('services.clamav.skip_when_unavailable', false)) {
                return 'clean';
            }

            // In production, fail the job so it retries
            $this->fail(new RuntimeException("ClamAV unavailable: {$errno} {$errstr}"));

            return 'pending';
        }

        try {
            // Send INSTREAM command
            fwrite($socket, "zINSTREAM\0");

            // Send file content in chunks (max 2MB per chunk per ClamAV protocol)
            $chunkSize = 2 * 1024 * 1024;
            while (! feof($fileStream)) {
                $chunk = fread($fileStream, $chunkSize);

                if ($chunk === false) {
                    throw new RuntimeException('Unable to read uploaded file stream.');
                }

                if ($chunk === '') {
                    if (feof($fileStream)) {
                        break;
                    }

                    continue;
                }

                // 4-byte big-endian size prefix
                fwrite($socket, pack('N', strlen($chunk)).$chunk);
            }

            // End stream with zero-length chunk
            fwrite($socket, pack('N', 0));

            // Read response
            $response = '';
            while (! feof($socket)) {
                $response .= fread($socket, 4096);
            }

            $response = mb_trim($response);

            // ClamAV response format: "stream: OK" or "stream: <virus_name> FOUND"
            if (str_contains($response, 'OK')) {
                return 'clean';
            }

            Log::warning('ScanUploadedFileJob: Threat detected.', [
                'media_id' => $this->media->id,
                'response' => $response,
            ]);

            return 'infected';
        } finally {
            fclose($socket);
        }
    }

    /**
     * Update the media record's scan_status.
     */
    private function markStatus(string $status): void
    {
        $this->media->update(['scan_status' => $status]);
    }
}
