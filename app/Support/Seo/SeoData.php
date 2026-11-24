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
    /**
     * @param  list<array{name: string, url: string}>  $breadcrumbs  Trail mirroring the page's breadcrumb; fewer than two entries emits none.
     * @param  list<array<string, mixed>>  $schemaNodes  Extra top-level schema.org nodes (supplier, product, ...).
     * @param  list<array{q: string, a: string}>  $faqEntries  Questions and answers visible on the page; emitted as Question nodes.
     * @param  list<array<string, mixed>>  $listItems  `ListItem.item` stubs for the entities a listing page shows.
     */
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
        public string $pageType = 'WebPage',
        public array $breadcrumbs = [],
        public array $schemaNodes = [],
        public ?string $mainEntityId = null,
        public bool $isHome = false,
        public array $listItems = [],
        public int $listOffset = 0,
        public ?string $dateModified = null,
        public array $faqEntries = [],
    ) {}
}
