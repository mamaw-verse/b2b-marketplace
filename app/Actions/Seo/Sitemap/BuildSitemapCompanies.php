<?php

declare(strict_types=1);

namespace App\Actions\Seo\Sitemap;

use App\Support\Seo\SitemapXml;
use App\Support\Seo\Visibility;
use Illuminate\Support\Carbon;

/**
 * Publicly visible companies (same rules as `EnsureCanonicalSlug`, including
 * subscription gating). `lastmod` is the newest of the shop and its
 * certificates, the only things the profile shows that live outside `shops`.
 */
final class BuildSitemapCompanies
{
    public function handle(): string
    {
        $entries = [];

        $query = Visibility::shops()
            ->select(['shops.id', 'shops.slug', 'shops.updated_at'])
            ->selectRaw('(select max(shop_certificates.updated_at) from shop_certificates where shop_certificates.shop_id = shops.id) as certificates_updated_at');

        foreach ($query->lazyById(1000, 'shops.id', 'id') as $shop) {
            $lastModified = $shop->updated_at;

            if ($shop->certificates_updated_at !== null) {
                $lastModified = max($lastModified, Carbon::parse($shop->certificates_updated_at));
            }

            $entries[] = [
                'loc' => SitemapXml::url(route('storefront.company', $shop, absolute: false)),
                'lastmod' => $lastModified,
            ];
        }

        return SitemapXml::urlset($entries);
    }

    public function hasEntries(): bool
    {
        return Visibility::shops()->exists();
    }
}
