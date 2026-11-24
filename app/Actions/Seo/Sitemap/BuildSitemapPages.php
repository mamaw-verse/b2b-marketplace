<?php

declare(strict_types=1);

namespace App\Actions\Seo\Sitemap;

use App\Support\Seo\SitemapXml;

/**
 * Static public pages. They have no real "last modified" source, so none is sent.
 */
final class BuildSitemapPages
{
    public function handle(): string
    {
        $entries = [];

        foreach ((array) config('seo.sitemap.static_routes', []) as $routeName) {
            $entries[] = ['loc' => SitemapXml::url(route((string) $routeName, absolute: false))];
        }

        return SitemapXml::urlset($entries);
    }
}
