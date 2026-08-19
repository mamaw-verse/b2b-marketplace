<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ShopOnboarding;
use App\Services\GeocodingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class RetryGeocodeJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    public function __construct(
        public readonly ShopOnboarding $onboarding,
    ) {}

    /**
     * Retry geocoding for an onboarding record.
     */
    public function handle(GeocodingService $geocodingService): void
    {
        $address = collect([
            $this->onboarding->address_1,
            $this->onboarding->address_2,
            $this->onboarding->city,
            $this->onboarding->province,
            $this->onboarding->zip_code,
            $this->onboarding->country_code,
        ])->filter()->implode(', ');

        if (! $address) {
            return;
        }

        $coordinates = $geocodingService->geocode($address);

        if ($coordinates) {
            $geocodingService->updateGeoLocation($this->onboarding, $coordinates['lat'], $coordinates['lng']);

            Log::info('RetryGeocodeJob: Geocoding succeeded on retry.', [
                'onboarding_id' => $this->onboarding->id,
            ]);
        } else {
            Log::warning('RetryGeocodeJob: Geocoding still failed.', [
                'onboarding_id' => $this->onboarding->id,
            ]);
        }
    }
}
