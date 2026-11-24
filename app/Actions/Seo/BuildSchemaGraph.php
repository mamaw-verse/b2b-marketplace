<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Actions\Seo\Schema\BuildBreadcrumbNode;
use App\Actions\Seo\Schema\BuildItemListNode;
use App\Actions\Seo\Schema\BuildOrganizationNode;
use App\Actions\Seo\Schema\BuildWebPageNode;
use App\Actions\Seo\Schema\BuildWebSiteNode;
use App\Support\Schema\SchemaGraph;
use App\Support\Schema\SchemaId;
use App\Support\Seo\SeoData;

/**
 * Assembles the single JSON-LD document for a page from the resolved head
 * values plus whatever entity nodes the page contributed through SeoData.
 */
final class BuildSchemaGraph
{
    public function __construct(
        private readonly BuildOrganizationNode $organization,
        private readonly BuildWebSiteNode $website,
        private readonly BuildWebPageNode $webPage,
        private readonly BuildBreadcrumbNode $breadcrumb,
        private readonly BuildItemListNode $itemList,
    ) {}

    /**
     * @param  string|null  $imageUrl  The page's own image; null when only the generic default applies.
     */
    public function handle(
        ?SeoData $seo,
        string $title,
        ?string $description,
        string $canonical,
        ?string $imageUrl = null,
        ?int $imageWidth = null,
        ?int $imageHeight = null,
    ): SchemaGraph {
        $graph = new SchemaGraph;

        // Sitewide entities ride on every page so references resolve in-document;
        // only the home page carries the full organization description.
        $graph->add($this->organization->handle(full: $seo?->isHome ?? false));
        $graph->add($this->website->handle());

        foreach ($seo?->schemaNodes ?? [] as $node) {
            $graph->add($node);
        }

        $graph->add($this->breadcrumb->handle($canonical, $seo?->breadcrumbs ?? []));
        $graph->add($this->itemList->handle($canonical, $seo?->listItems ?? [], $seo?->listOffset ?? 0));

        $mainEntityId = $seo?->mainEntityId;

        if ($mainEntityId === null && $graph->has(SchemaId::itemList($canonical))) {
            $mainEntityId = SchemaId::itemList($canonical);
        }

        $graph->add($this->webPage->handle(
            type: $seo?->pageType ?? 'WebPage',
            canonical: $canonical,
            name: $title,
            description: $description,
            imageUrl: $imageUrl,
            imageWidth: $imageWidth,
            imageHeight: $imageHeight,
            hasBreadcrumb: $graph->has(SchemaId::breadcrumb($canonical)),
            mainEntityId: $mainEntityId,
            dateModified: $seo?->dateModified,
        ));

        return $graph;
    }
}
