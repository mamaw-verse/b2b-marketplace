<?php

declare(strict_types=1);

namespace App\Actions\Seo\Sitemap;

use App\Support\Seo\SitemapXml;

/**
 * /sitemap.xml: lists the child sitemaps. Children with nothing to list
 * (no categories, no companies, no products) are omitted.
 */
final class BuildSitemapIndex
{
    public function __construct(
        private readonly BuildSitemapCategories $categories,
        private readonly BuildSitemapCompanies $companies,
        private readonly BuildSitemapProducts $products,
    ) {}

    public function handle(): string
    {
        $children = [['loc' => SitemapXml::url(route('sitemap.pages', absolute: false))]];

        if ($this->categories->hasEntries()) {
            $children[] = ['loc' => SitemapXml::url(route('sitemap.categories', absolute: false))];
        }

        if ($this->companies->hasEntries()) {
            $children[] = ['loc' => SitemapXml::url(route('sitemap.companies', absolute: false))];
        }

        for ($page = 1; $page <= $this->products->fileCount(); $page++) {
            $children[] = ['loc' => SitemapXml::url(route('sitemap.products', ['page' => $page], absolute: false))];
        }

        return SitemapXml::index($children);
    }
}
