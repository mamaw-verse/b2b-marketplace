<?php

declare(strict_types=1);

namespace App\Actions\Seo\Schema;

use App\Support\Schema\SchemaId;

/**
 * ItemList for a listing page. Each entry is a stub that shares the `@id` of
 * the entity's own detail page, and only entities the page actually shows are
 * passed in.
 */
final class BuildItemListNode
{
    /**
     * @param  list<array<string, mixed>>  $items  Stubs with @type, @id, url, name and optional image.
     * @param  int  $offset  Items on earlier pages, so positions continue across pagination.
     * @return array<string, mixed>
     */
    public function handle(string $pageUrl, array $items, int $offset = 0): array
    {
        $elements = [];

        foreach (array_values($items) as $index => $item) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $offset + $index + 1,
                'item' => $item,
            ];
        }

        return [
            '@type' => 'ItemList',
            '@id' => SchemaId::itemList($pageUrl),
            'itemListElement' => $elements,
        ];
    }
}
