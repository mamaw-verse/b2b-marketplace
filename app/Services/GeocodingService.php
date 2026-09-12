<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\RetryGeocodeJob;
use App\Models\ShopOnboarding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class GeocodingService
{
    /**
     * Geocode the onboarding address and update the geo_location column.
     *
     * Fails gracefully — saves the address even if geocoding errors,
     * and dispatches a retry job on failure.
     */
    public function geocodeOnboarding(ShopOnboarding $onboarding): void
    {
        $address = $this->buildAddressString($onboarding);

        if (! $address) {
            return;
        }

        try {
            $coordinates = $this->geocode($address);

            if ($coordinates) {
                $this->updateGeoLocation($onboarding, $coordinates['lat'], $coordinates['lng']);
            }
        } catch (Throwable $e) {
            Log::warning('GeocodingService: Geocoding failed, scheduling retry.', [
                'onboarding_id' => $onboarding->id,
                'error' => $e->getMessage(),
            ]);

            RetryGeocodeJob::dispatch($onboarding);
        }
    }

    /**
     * Attempt to geocode an address string.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $address): ?array
    {
        $provider = config('services.geocoding.provider', 'google');
        $apiKey = config('services.geocoding.api_key');

        if (! $apiKey) {
            Log::info('GeocodingService: No API key configured, skipping geocode.');

            return null;
        }

        return match ($provider) {
            'google' => $this->geocodeWithGoogle($address, $apiKey),
            'mapbox' => $this->geocodeWithMapbox($address, $apiKey),
            default => null,
        };
    }

    /**
     * Update the geo_location PostGIS column on the onboarding record.
     */
    public function updateGeoLocation(ShopOnboarding $onboarding, float $lat, float $lng): void
    {
        DB::statement(
            'UPDATE shop_onboardings SET geo_location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$lng, $lat, $onboarding->id],
        );
    }

    /**
     * Build a single address string from onboarding fields.
     */
    private function buildAddressString(ShopOnboarding $onboarding): string
    {
        return collect([
            $onboarding->address_1,
            $onboarding->address_2,
            $onboarding->city,
            $onboarding->province,
            $onboarding->zip_code,
            $onboarding->country_code,
        ])->filter()->implode(', ');
    }

    /**
     * Geocode with Google Maps Geocoding API.
     *
     * @return array{lat: float, lng: float}|null
     */
    private function geocodeWithGoogle(string $address, string $apiKey): ?array
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key' => $apiKey,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $results = $response->json('results', []);

        if (empty($results)) {
            return null;
        }

        $location = $results[0]['geometry']['location'] ?? null;

        return $location ? ['lat' => $location['lat'], 'lng' => $location['lng']] : null;
    }

    /**
     * Geocode with Mapbox Geocoding API.
     *
     * @return array{lat: float, lng: float}|null
     */
    private function geocodeWithMapbox(string $address, string $apiKey): ?array
    {
        $response = Http::get("https://api.mapbox.com/geocoding/v5/mapbox.places/{$address}.json", [
            'access_token' => $apiKey,
            'limit' => 1,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $features = $response->json('features', []);

        if (empty($features)) {
            return null;
        }

        $center = $features[0]['center'] ?? null;

        return $center ? ['lat' => $center[1], 'lng' => $center[0]] : null;
    }
}
