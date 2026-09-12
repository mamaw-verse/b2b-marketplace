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
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
#[Title('Product Directory')]
final class ProductDirectory extends Component
{
    use WithPagination;

    public string $query = '';

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
     * Categories with product counts for the tab bar.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::withCount([
            'products as published_products_count' => fn ($q) => $q->marketplaceVisible(),
        ])
            ->orderBy('name')
            ->get()
            ->filter(fn ($c) => $c->published_products_count > 0)
            ->values();
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
                    $search->where('categories', $category->name);
                }
            }

            return $search->paginate(12);
        }

        $query = Product::marketplaceVisible()
            ->with(['shop', 'variants', 'categories']);

        if ($this->categoryId) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $this->categoryId));
        }

        return $query->latest()->paginate(12);
    }

    public function render(): View
    {
        return view('livewire.storefront.product-directory');
    }
}
