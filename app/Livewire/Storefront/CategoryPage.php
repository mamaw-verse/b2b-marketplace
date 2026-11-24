<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Actions\Seo\BuildCategorySeoData;
use App\Models\Category;
use App\Models\Product;
use App\Support\Seo\SeoResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Public, indexable category landing page at /categories/{slug} (T3.1).
 *
 * A top-level group lists the products of all its subcategories; a
 * subcategory lists its own. Same visibility rules as the product directory.
 */
#[Layout('layouts.storefront')]
#[Title('Categories')]
final class CategoryPage extends Component
{
    use WithPagination;

    public Category $category;

    public function mount(Category $category): void
    {
        $this->category = $category->loadMissing('parent');
    }

    /**
     * @return Builder<Product>
     */
    private function visibleProducts(): Builder
    {
        return Product::marketplaceVisible()
            ->whereHas('categories', fn (Builder $categories) => $categories
                ->where('categories.id', $this->category->id)
                ->orWhere('categories.parent_id', $this->category->id));
    }

    /**
     * Subcategories that have at least one visible product, with that count.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function subcategories(): Collection
    {
        return $this->category->children()
            ->withCount(['products as published_products_count' => fn (Builder $products) => $products->marketplaceVisible()])
            ->get()
            ->filter(fn (Category $child): bool => $child->published_products_count > 0)
            ->values();
    }

    #[Computed]
    public function totalProductCount(): int
    {
        return $this->visibleProducts()->count();
    }

    #[Computed]
    public function totalSupplierCount(): int
    {
        return $this->visibleProducts()->distinct()->count('products.shop_id');
    }

    /**
     * @return LengthAwarePaginator<Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return $this->visibleProducts()
            ->with(['shop.certificates', 'variants', 'categories'])
            ->latest()
            ->orderByDesc('products.id')
            ->paginate(12);
    }

    public function render(): View
    {
        app(SeoResolver::class)->set(app(BuildCategorySeoData::class)->handle($this->category, $this->products));

        return view('livewire.storefront.category-page');
    }
}
