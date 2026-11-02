<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Shop;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
#[Title('Business Directory')]
final class CompanyDirectory extends Component
{
    use WithPagination;

    public string $query = '';

    public ?int $categoryId = null;

    public string $sortBy = 'latest';

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    /**
     * Top-level groups with their subcategories for the sidebar filter.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categoryGroups(): Collection
    {
        return Category::with('children')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * The currently selected category (group or subcategory), for the
     * sidebar active state.
     */
    #[Computed]
    public function selectedCategory(): ?Category
    {
        return $this->categoryId ? Category::find($this->categoryId) : null;
    }

    /**
     * @return LengthAwarePaginator<Shop>
     */
    #[Computed]
    public function shops(): LengthAwarePaginator
    {
        $instanceCountry = config('marketplace.instance_country');

        if ($this->query !== '') {
            $search = Shop::search($this->query)
                ->where('country_code', $instanceCountry);

            if ($this->categoryId) {
                $category = Category::find($this->categoryId);
                if ($category) {
                    // Filtering by group name also matches shops tagged with
                    // its subcategories (the index carries parent names too).
                    $search->where('categories', $category->name);
                }
            }

            return $search->paginate(12);
        }

        $query = Shop::where('status', 'active')
            ->where('country_code', $instanceCountry);

        if ($this->categoryId) {
            $query->whereHas('categories', fn ($q) => $q
                ->where('categories.id', $this->categoryId)
                ->orWhere('categories.parent_id', $this->categoryId));
        }

        $query = match ($this->sortBy) {
            'rating' => $query->orderByRaw('average_rating DESC NULLS LAST'),
            default => $query->latest(),
        };

        return $query->paginate(12);
    }

    public function render(): View
    {
        $selectedCategory = $this->selectedCategory;
        $ogTitle = $selectedCategory
            ? $selectedCategory->name.' Suppliers — '.config('app.name')
            : __('Business Directory — :app', ['app' => config('app.name')]);
        $ogDescription = $selectedCategory
            ? __('Find verified halal-certified :category businesses and suppliers on :app.', ['category' => $selectedCategory->name, 'app' => config('app.name')])
            : __('Discover verified halal-certified businesses, suppliers, and manufacturers on :app.', ['app' => config('app.name')]);

        return view('livewire.storefront.company-directory', compact('ogTitle', 'ogDescription'));
    }
}
