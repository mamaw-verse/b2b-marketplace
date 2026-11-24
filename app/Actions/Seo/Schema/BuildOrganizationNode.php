<?php

declare(strict_types=1);

namespace App\Actions\Seo\Schema;

use App\Support\Schema\Places;
use App\Support\Schema\SchemaId;

/**
 * The marketplace operator (not a supplier company).
 *
 * Every page carries the minimal node so references to it resolve inside the
 * document; the home page carries the full one.
 */
final class BuildOrganizationNode
{
    /**
     * @return array<string, mixed>
     */
    public function handle(bool $full = false): array
    {
        $node = [
            '@type' => 'Organization',
            '@id' => SchemaId::organization(),
            'name' => config('seo.site_name'),
            'url' => SchemaId::base().'/',
            'logo' => $this->logo(),
        ];

        if (! $full) {
            return $node;
        }

        return $node + [
            'description' => __(':app is a B2B marketplace that connects buyers with verified halal-certified suppliers, manufacturers and products across the halal supply chain.', ['app' => config('seo.site_name')]),
            'sameAs' => $this->sameAs(),
            'contactPoint' => $this->contactPoint(),
            'areaServed' => Places::instanceCountry(),
            'knowsAbout' => ['Halal supply chain', 'Halal certification', 'B2B sourcing'],
        ];
    }

    /**
     * Square logo as an ImageObject; omitted when the asset is missing.
     *
     * @return array<string, mixed>|null
     */
    private function logo(): ?array
    {
        $path = (string) config('seo.logo_path');
        $file = public_path($path);

        if ($path === '' || ! is_file($file)) {
            return null;
        }

        $size = @getimagesize($file);
        $url = asset($path);

        return [
            '@type' => 'ImageObject',
            '@id' => SchemaId::logo(),
            'url' => $url,
            'contentUrl' => $url,
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
        ];
    }

    /**
     * @return list<string>
     */
    private function sameAs(): array
    {
        return array_values(array_filter(
            (array) config('seo.social_profiles', []),
            static fn (mixed $url): bool => is_string($url) && filter_var($url, FILTER_VALIDATE_URL) !== false,
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function contactPoint(): ?array
    {
        $email = config('seo.contact.email');

        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return ['@type' => 'ContactPoint', 'contactType' => 'customer support', 'email' => $email];
    }
}
