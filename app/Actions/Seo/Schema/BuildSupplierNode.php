<?php

declare(strict_types=1);

namespace App\Actions\Seo\Schema;

use App\Support\Schema\Places;
use App\Support\Schema\SchemaGraph;
use App\Support\Schema\SchemaId;
use App\ValueObjects\PublicCertification;
use App\ValueObjects\PublicCompany;

/**
 * A supplier company as an `Organization`, kept distinct from the marketplace
 * organization it is a member of.
 *
 * Takes the PublicCompany allow-list, never the Shop model. There is no company
 * logo upload (decision D7), so no `logo`/`image` is emitted rather than reusing
 * the marketplace's.
 */
final class BuildSupplierNode
{
    /**
     * @param  bool  $full  false returns the identifying stub used where the page only references the supplier (e.g. a product page).
     * @return array<string, mixed>
     */
    public function handle(PublicCompany $company, bool $full = true): array
    {
        $node = [
            '@type' => 'Organization',
            '@id' => SchemaId::company($company->url),
            'url' => $company->url,
            'name' => $company->name,
            'memberOf' => SchemaGraph::ref(SchemaId::organization()),
        ];

        if (! $full) {
            return $node;
        }

        return $node + [
            'description' => $company->description,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $company->city,
                'addressRegion' => $company->province,
                'addressCountry' => $company->countryCode === null ? null : mb_strtoupper($company->countryCode),
            ],
            'areaServed' => Places::country($company->countryCode),
            'knowsAbout' => array_values(array_unique([...$company->capabilities, ...$company->valueChainPositions])),
            'hasCertification' => array_map($this->certification(...), $company->certifications),
            'aggregateRating' => $this->rating($company),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function certification(PublicCertification $certification): array
    {
        return [
            '@type' => 'Certification',
            'name' => $certification->name,
            'issuedBy' => $certification->issuer === null ? null : [
                '@type' => 'Organization',
                'name' => $certification->issuer,
            ],
            'certificationStatus' => 'https://schema.org/CertificationActive',
        ];
    }

    /**
     * Only with reviews switched on, at least one review and a rating to show.
     *
     * @return array<string, mixed>|null
     */
    private function rating(PublicCompany $company): ?array
    {
        if (! config('marketplace.features.reviews_enabled') || $company->totalReviews < 1 || $company->averageRating === null) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => round($company->averageRating, 1),
            'reviewCount' => $company->totalReviews,
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }
}
