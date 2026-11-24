<?php

declare(strict_types=1);

namespace App\Actions\Seo\Schema;

use App\Support\Schema\SchemaGraph;
use App\Support\Schema\SchemaId;

/**
 * The page wrapper: WebPage, CollectionPage, AboutPage or FAQPage.
 */
final class BuildWebPageNode
{
    private const TYPES = ['WebPage', 'CollectionPage', 'AboutPage', 'FAQPage', 'ItemPage'];

    /**
     * @return array<string, mixed>
     */
    public function handle(
        string $type,
        string $canonical,
        ?string $name,
        ?string $description,
        ?string $imageUrl = null,
        ?int $imageWidth = null,
        ?int $imageHeight = null,
        bool $hasBreadcrumb = false,
        ?string $mainEntityId = null,
        ?string $dateModified = null,
    ): array {
        return [
            '@type' => in_array($type, self::TYPES, true) ? $type : 'WebPage',
            '@id' => SchemaId::webPage($canonical),
            'url' => $canonical,
            'name' => $name,
            'description' => $description,
            'isPartOf' => SchemaGraph::ref(SchemaId::website()),
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'breadcrumb' => $hasBreadcrumb ? SchemaGraph::ref(SchemaId::breadcrumb($canonical)) : null,
            'primaryImageOfPage' => $imageUrl === null ? null : [
                '@type' => 'ImageObject',
                'url' => $imageUrl,
                'contentUrl' => $imageUrl,
                'width' => $imageWidth,
                'height' => $imageHeight,
            ],
            'mainEntity' => $mainEntityId === null ? null : SchemaGraph::ref($mainEntityId),
            'dateModified' => $dateModified,
        ];
    }
}
