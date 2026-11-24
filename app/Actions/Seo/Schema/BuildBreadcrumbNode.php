<?php

declare(strict_types=1);

namespace App\Actions\Seo\Schema;

use App\Support\Schema\SchemaId;

/**
 * BreadcrumbList for a trail that mirrors the page's own breadcrumb. A trail
 * shorter than two entries is not a breadcrumb, so nothing is emitted.
 */
final class BuildBreadcrumbNode
{
    /**
     * @param  list<array{name: string, url: string}>  $trail
     * @return array<string, mixed>
     */
    public function handle(string $pageUrl, array $trail): array
    {
        $crumbs = array_values(array_filter(
            $trail,
            static fn (mixed $crumb): bool => is_array($crumb)
                && is_string($crumb['name'] ?? null) && trim($crumb['name']) !== ''
                && is_string($crumb['url'] ?? null) && trim($crumb['url']) !== '',
        ));

        if (count($crumbs) < 2) {
            return [];
        }

        $items = [];

        foreach ($crumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => SchemaId::breadcrumb($pageUrl),
            'itemListElement' => $items,
        ];
    }
}
