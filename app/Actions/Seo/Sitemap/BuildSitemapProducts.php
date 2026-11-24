<?php

declare(strict_types=1);

namespace App\Actions\Seo\Sitemap;

use App\Support\Seo\SitemapXml;
use App\Support\Seo\Visibility;

/**
 * Marketplace-visible products, split into files of at most
 * `seo.sitemap.max_urls_per_file` URLs (50 000 per the sitemap protocol).
 */
final class BuildSitemapProducts
{
    public function perFile(): int
    {
        return max(1, (int) config('seo.sitemap.max_urls_per_file', 50000));
    }

    /**
     * Number of product sitemap files; zero when nothing is visible.
     */
    public function fileCount(): int
    {
        return (int) ceil(Visibility::products()->count() / $this->perFile());
    }

    public function handle(int $page): string
    {
        $products = Visibility::products()
            ->select(['products.id', 'products.slug', 'products.updated_at'])
            ->orderBy('products.id')
            ->forPage($page, $this->perFile())
            ->get();

        return SitemapXml::urlset($products->map(fn ($product): array => [
            'loc' => SitemapXml::url(route('storefront.product', $product, absolute: false)),
            'lastmod' => $product->updated_at,
        ])->all());
    }
}
