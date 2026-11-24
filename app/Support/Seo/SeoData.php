<?php

declare(strict_types=1);

namespace App\Support\Seo;

/**
 * Everything a page can tell the shared head about itself.
 *
 * Null means "use the safe fallback" (layout title, default OG image, clean
 * request URL as canonical, robots decided by IndexingPolicy).
 */
final readonly class SeoData
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $canonical = null,
        public ?string $robots = null,
        public string $ogType = 'website',
        public ?string $image = null,
        public ?string $imageAlt = null,
        public ?int $imageWidth = null,
        public ?int $imageHeight = null,
    ) {}
}
