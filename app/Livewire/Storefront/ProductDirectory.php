<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
#[Title('Product Directory')]
final class ProductDirectory extends Component
{
    use WithPagination;

    public string $query = '';

    /**
     * Selected category id — may be a top-level group (matches products in
     * any of its subcategories) or a subcategory (exact match).
     */
    #[Url(as: 'category')]
    public ?int $categoryId = null;

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    /**
     * Top-level groups whose subcategories (or themselves) have published
     * products, for the sidebar tree. Each group carries its children's
     * published_products_count and an aggregate count.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categoryGroups(): Collection
    {
        return Category::with(['children' => fn ($q) => $q
            ->withCount(['products as published_products_count' => fn ($p) => $p->marketplaceVisible()]),
        ])
            ->withCount(['products as own_published_products_count' => fn ($q) => $q->marketplaceVisible()])
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->each(function (Category $group): void {
                $group->published_products_count = $group->children->isNotEmpty()
                    ? $group->children->sum('published_products_count')
                    : $group->own_published_products_count;
            })
            ->values();
    }

    /**
     * The currently selected category (group or subcategory), for the
     * results header.
     */
    #[Computed]
    public function selectedCategory(): ?Category
    {
        return $this->categoryId ? Category::find($this->categoryId) : null;
    }

    /**
     * Total published product count (for the hero stats).
     */
    #[Computed]
    public function totalProductCount(): int
    {
        return Product::marketplaceVisible()->count();
    }

    /**
     * Total active supplier count (for the hero stats).
     */
    #[Computed]
    public function totalSupplierCount(): int
    {
        return Product::marketplaceVisible()
            ->distinct('shop_id')
            ->count('shop_id');
    }

    /**
     * Selectable (leaf) categories that have published products — used for
     * the "N categories" hero stat.
     */
    #[Computed]
    public function totalCategoryCount(): int
    {
        return Category::leaves()
            ->whereHas('products', fn ($q) => $q->marketplaceVisible())
            ->count();
    }

    /**
     * @return LengthAwarePaginator<Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        if ($this->query !== '') {
            $search = Product::search($this->query);

            if ($this->categoryId) {
                $category = Category::find($this->categoryId);
                if ($category) {
                    // Filtering by group name also matches subcategory-tagged
                    // products (the index carries parent names too).
                    $search->where('categories', $category->name);
                }
            }

            return $search->paginate(12);
        }

        $query = Product::marketplaceVisible()
            ->with(['shop', 'variants', 'categories']);

        if ($this->categoryId) {
            $query->whereHas('categories', fn ($q) => $q
                ->where('categories.id', $this->categoryId)
                ->orWhere('categories.parent_id', $this->categoryId));
        }

        return $query->latest()->paginate(12);
    }

    public function render(): View
    {
        return view('livewire.storefront.product-directory');
    }
}
