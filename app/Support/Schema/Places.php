<?php

declare(strict_types=1);

namespace App\Support\Schema;

/**
 * Country nodes built from the per-deployment `marketplace.portals` registry,
 * never from a hard-coded country.
 */
final class Places
{
    /**
     * The country this deployment serves (`MARKETPLACE_INSTANCE_COUNTRY`).
     *
     * @return array{'@type': string, name: string}|null
     */
    public static function instanceCountry(): ?array
    {
        return self::country((string) config('marketplace.instance_country'));
    }

    /**
     * @return array{'@type': string, name: string}|null
     */
    public static function country(?string $countryCode): ?array
    {
        $code = mb_strtoupper(trim((string) $countryCode));

        if ($code === '') {
            return null;
        }

        $portal = collect((array) config('marketplace.portals', []))->firstWhere('country_code', $code);
        $name = is_array($portal) ? trim((string) ($portal['display_name'] ?? '')) : '';

        return ['@type' => 'Country', 'name' => $name !== '' ? $name : $code];
    }
}
