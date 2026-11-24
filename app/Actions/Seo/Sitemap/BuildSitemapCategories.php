<?php

declare(strict_types=1);

namespace App\Actions\Seo\Sitemap;

use App\Models\Category;
use App\Support\Seo\SitemapXml;
use App\Support\Seo\Visibility;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Category landing pages that have at least one visible product, directly or
 * through a subcategory (the same rule the page uses to decide `noindex`).
 * `lastmod` is the newest of the category itself and its visible products.
 */
final class BuildSitemapCategories
{
    public function handle(): string
    {
        return SitemapXml::urlset($this->entries());
    }

    public function hasEntries(): bool
    {
        return $this->entries()->isNotEmpty();
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: CarbonInterface}>
     */
    private function entries(): Collection
    {
        $latestProduct = DB::table('product_categories')
            ->join('products', 'products.id', '=', 'product_categories.product_id')
            ->whereIn('products.id', Visibility::products()->select('products.id'))
            ->groupBy('product_categories.category_id')
            ->selectRaw('product_categories.category_id as category_id, MAX(products.updated_at) as latest')
            ->pluck('latest', 'category_id');

        $categories = Category::query()->orderBy('id')->get(['id', 'parent_id', 'slug', 'updated_at']);

        // A group's products are its own plus its subcategories'.
        $latestByCategory = [];

        foreach ($categories as $category) {
            $own = $latestProduct->get($category->id);

            if ($own === null) {
                continue;
            }

            foreach (array_filter([$category->id, $category->parent_id]) as $id) {
                $latestByCategory[$id] = max($latestByCategory[$id] ?? $own, $own);
            }
        }

        return $categories
            ->filter(fn (Category $category): bool => isset($latestByCategory[$category->id]))
            ->map(fn (Category $category): array => [
                'loc' => SitemapXml::url(route('storefront.category', $category, absolute: false)),
                'lastmod' => max($category->updated_at, Carbon::parse($latestByCategory[$category->id])),
            ])
            ->values();
    }
}
