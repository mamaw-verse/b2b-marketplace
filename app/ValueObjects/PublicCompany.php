<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\Shop;
use App\Models\ShopCertificate;
use App\Support\Schema\PlainText;

/**
 * The public-data allow-list for a supplier company (plan §3, guardrail B14).
 *
 * Schema, meta and llms builders take this object instead of the Shop model so
 * they cannot reach phone, email, street address, postcode, registration or tax
 * numbers, FDA licence or geolocation: those fields are simply not copied here.
 */
final readonly class PublicCompany
{
    /**
     * @param  list<string>  $capabilities
     * @param  list<string>  $valueChainPositions
     * @param  list<PublicCertification>  $certifications
     */
    public function __construct(
        public string $name,
        public string $url,
        public ?string $description,
        public ?string $city,
        public ?string $province,
        public ?string $countryCode,
        public array $capabilities,
        public array $valueChainPositions,
        public array $certifications,
        public ?float $averageRating,
        public int $totalReviews,
    ) {}

    public static function fromShop(Shop $shop): self
    {
        $shop->loadMissing('certificates');

        $certifications = [];

        foreach ($shop->certificates as $certificate) {
            if (! $certificate instanceof ShopCertificate) {
                continue;
            }

            $public = PublicCertification::fromCertificate($certificate);

            if ($public !== null) {
                $certifications[$public->name.'|'.$public->issuer] = $public;
            }
        }

        $rating = (float) $shop->average_rating;

        return new self(
            name: (string) $shop->storefront_name,
            url: route('storefront.company', $shop),
            description: PlainText::from($shop->company_description),
            city: self::text($shop->city),
            province: self::text($shop->province),
            countryCode: self::text($shop->country_code),
            capabilities: self::labels($shop->capabilities),
            valueChainPositions: self::labels($shop->value_chain_positions),
            certifications: array_values($certifications),
            averageRating: $rating > 0 ? $rating : null,
            totalReviews: (int) $shop->total_reviews,
        );
    }

    /**
     * Whether the supplier holds at least one publishable halal certificate.
     */
    public function isHalalCertified(): bool
    {
        return $this->certifications !== [];
    }

    private static function text(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Humanise stored keys the same way the company page does (`cold_chain` → `Cold chain`).
     *
     * @return list<string>
     */
    private static function labels(mixed $values): array
    {
        $labels = [];

        foreach ((array) $values as $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $labels[] = ucfirst(str_replace('_', ' ', trim($value)));
        }

        return array_values(array_unique($labels));
    }
}
