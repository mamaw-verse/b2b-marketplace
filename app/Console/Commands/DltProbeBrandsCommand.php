<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DltPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Debug command: fire a real GET /api/v1/collection/apn/brands request
 * against the configured DLT endpoint and dump the full request + response
 * to the terminal and to the application log.
 *
 * Usage:
 *   vendor/bin/sail artisan dlt:probe-brands
 *   vendor/bin/sail artisan dlt:probe-brands --fresh-token
 */
final class DltProbeBrandsCommand extends Command
{
    protected $signature = 'dlt:probe-brands
        {--fresh-token : Flush the cached bearer token and re-authenticate first}';

    protected $description = 'Fire a real DLT /brands request and dump full request/response for debugging';

    public function handle(DltPaymentService $dlt): int
    {
        if ($this->option('fresh-token')) {
            Cache::forget('dlt:bearer_token');
            $this->line('<fg=yellow>Cached token flushed — will re-authenticate.</>');
        }

        $this->line('');
        $this->line('<fg=cyan>── DLT Probe: /api/v1/collection/apn/brands ──</>');
        $this->line('');
        $this->line('  base_url:            '.config('services.dlt.base_url'));
        $this->line('  merchant_account_id: '.config('services.dlt.merchant_account_id'));
        $this->line('  service_code:        '.config('services.dlt.service_code'));
        $this->line('');

        // Resolve the token separately so we can show it truncated.
        try {
            $token = $dlt->getBearerToken();
            $this->line('  bearer_token:        '.mb_substr($token, 0, 20).'… ('.mb_strlen($token).' chars)');
        } catch (Throwable $e) {
            $this->error('  Failed to obtain bearer token: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line('');
        $this->line('<fg=cyan>── Sending request ──</>');
        $this->line('');

        // Capture the raw response ourselves so we can show every detail.
        $baseUrl = rtrim(config('services.dlt.base_url', ''), '/');
        $endpoint = $baseUrl.'/api/v1/collection/apn/brands';
        $params = [
            'merchant_account_id' => config('services.dlt.merchant_account_id'),
            'service_code' => config('services.dlt.service_code'),
        ];

        $this->line('  GET '.$endpoint);
        $this->line('  Query: '.http_build_query($params));
        $this->line('');

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->get($endpoint, $params);

            $this->line('<fg=cyan>── Response ──</>');
            $this->line('');
            $this->line('  HTTP Status: <fg='.($response->successful() ? 'green' : 'red').'>'.$response->status().'</>');
            $this->line('');
            $this->line('  Headers:');
            foreach ($response->headers() as $name => $values) {
                $this->line('    '.$name.': '.implode(', ', $values));
            }
            $this->line('');
            $this->line('  Body:');

            // Pretty-print JSON if parseable, otherwise raw.
            $decoded = json_decode($response->body(), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->line(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $this->line($response->body());
            }

            $this->line('');

            if ($response->successful()) {
                $brands = $response->json('data', []);
                $this->info('  ✓ Success — '.count($brands).' brand(s) returned.');
            } else {
                $this->error('  ✗ Request failed (HTTP '.$response->status().')');
                $this->line('  Check storage/logs/laravel.log for the full log entry.');
            }
        } catch (Throwable $e) {
            $this->error('  Connection error: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line('');
        $this->line('<fg=yellow>Full request + response also written to storage/logs/laravel.log (DLT: Get brands).</>');
        $this->line('');

        return self::SUCCESS;
    }
}
