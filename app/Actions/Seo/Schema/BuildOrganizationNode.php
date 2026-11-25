<?php

declare(strict_types=1);

namespace App\Actions\Seo\Schema;

use App\Support\Schema\Places;
use App\Support\Schema\SchemaId;
use App\Support\Seo\EntityProfile;

/**
 * The marketplace operator (not a supplier company).
 *
 * Every page carries the minimal node so references to it resolve inside the
 * document; the home page carries the full one.
 */
final class BuildOrganizationNode
{
    public function __construct(private readonly EntityProfile $profile) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(bool $full = false): array
    {
        $node = [
            '@type' => 'Organization',
            '@id' => SchemaId::organization(),
            'name' => $this->profile->name(),
            'url' => SchemaId::base().'/',
            'logo' => $this->logo(),
        ];

        if (! $full) {
            return $node;
        }

        return $node + [
            'description' => $this->profile->description(),
            'sameAs' => $this->profile->sameAs(),
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
     * @return array<string, mixed>|null
     */
    private function contactPoint(): ?array
    {
        $email = $this->profile->contactEmail();

        if ($email === null) {
            return null;
        }

        return ['@type' => 'ContactPoint', 'contactType' => 'customer support', 'email' => $email];
    }
}
