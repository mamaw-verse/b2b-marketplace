<?php

declare(strict_types=1);

namespace App\Actions\Seo\Schema;

use App\Support\Schema\SchemaGraph;
use App\Support\Schema\SchemaId;

/**
 * No SearchAction: marketplace search is not a stable GET URL (the `/products`
 * query is Livewire state, not a `?search=` parameter), so there is nothing a
 * crawler could call.
 */
final class BuildWebSiteNode
{
    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => SchemaId::website(),
            'url' => SchemaId::base().'/',
            'name' => config('seo.site_name'),
            'publisher' => SchemaGraph::ref(SchemaId::organization()),
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
        ];
    }
}
