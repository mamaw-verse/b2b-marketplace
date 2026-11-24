<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Models\Category;
use App\Models\Product;
use App\Support\Seo\CanonicalUrl;
use App\Support\Seo\IndexingPolicy;
use App\Support\Seo\SeoData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Head and schema data for a public category landing page (T3.1).
 *
 * Reads only the category and counts of what the page itself shows.
 */
final class BuildCategorySeoData
{
    public function __construct(
        private readonly BuildProductSeoData $productSeo,
        private readonly IndexingPolicy $policy,
    ) {}

    /**
     * @param  LengthAwarePaginator<Product>  $products  The page of products being shown.
     */
    public function handle(Category $category, LengthAwarePaginator $products): SeoData
    {
        $url = route('storefront.category', $category);

        // Empty categories, and pages past the last one, stay reachable but are never indexed.
        $robots = $this->policy->robotsDirective(request())
            ?? ($products->isEmpty() ? 'noindex, follow' : null);

        return new SeoData(
            title: $this->title($category),
            description: $this->description($category),
            canonical: CanonicalUrl::withPage($url, $products->currentPage()),
            robots: $robots,
            pageType: 'CollectionPage',
            breadcrumbs: $this->breadcrumbs($category),
            listItems: collect($products->items())
                ->filter(fn (mixed $product): bool => $product instanceof Product)
                ->map(fn (Product $product): array => $this->productSeo->listItem($product))
                ->values()
                ->all(),
            listOffset: ($products->currentPage() - 1) * $products->perPage(),
        );
    }

    public function title(Category $category): string
    {
        return __(':category Products — :app', ['category' => $category->name, 'app' => config('app.name')]);
    }

    /**
     * The category's own description when it has one, otherwise a sentence that
     * names the category so every category page has a unique description.
     */
    public function description(Category $category): string
    {
        if (filled($category->description)) {
            return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $category->description)) ?? ''), 160);
        }

        return __('Browse halal-certified :category products from verified suppliers on :app.', ['category' => $category->name, 'app' => config('app.name')]);
    }

    /**
     * Home › Products › [parent group] › category — the trail the page itself shows.
     *
     * @return list<array{name: string, url: string}>
     */
    public function breadcrumbs(Category $category): array
    {
        $trail = [
            ['name' => __('Home'), 'url' => route('home')],
            ['name' => __('Products'), 'url' => route('storefront.products')],
        ];

        $category->loadMissing('parent');

        if ($category->parent !== null) {
            $trail[] = ['name' => $category->parent->name, 'url' => route('storefront.category', $category->parent)];
        }

        $trail[] = ['name' => $category->name, 'url' => route('storefront.category', $category)];

        return $trail;
    }
}
