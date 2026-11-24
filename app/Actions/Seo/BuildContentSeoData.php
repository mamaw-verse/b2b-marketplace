<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Support\Seo\SeoData;

/**
 * Generic public page (home, directories, about, legal). Title is optional:
 * when omitted the layout's Livewire #[Title] is used.
 */
final class BuildContentSeoData
{
    /**
     * @param  string  $pageType  WebPage, CollectionPage, AboutPage, ...
     * @param  string|null  $crumb  Name of this page in a Home › {crumb} trail; with $crumbUrl.
     * @param  list<array<string, mixed>>  $listItems  Stubs for the entities a listing page shows.
     */
    public function handle(
        string $description,
        ?string $title = null,
        string $pageType = 'WebPage',
        ?string $crumb = null,
        ?string $crumbUrl = null,
        bool $isHome = false,
        ?string $mainEntityId = null,
        ?string $dateModified = null,
        array $listItems = [],
        int $listOffset = 0,
    ): SeoData {
        return new SeoData(
            title: $title,
            description: $description,
            pageType: $pageType,
            breadcrumbs: $crumb !== null && $crumbUrl !== null
                ? [['name' => __('Home'), 'url' => route('home')], ['name' => $crumb, 'url' => $crumbUrl]]
                : [],
            mainEntityId: $mainEntityId,
            isHome: $isHome,
            listItems: $listItems,
            listOffset: $listOffset,
            dateModified: $dateModified,
        );
    }
}
