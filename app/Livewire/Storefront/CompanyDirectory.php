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
#[Title('Company Directory')]
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
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::orderBy('name')->get();
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
                    $search->where('categories', $category->name);
                }
            }

            return $search->paginate(12);
        }

        $query = Shop::where('status', 'active')
            ->where('country_code', $instanceCountry);

        if ($this->categoryId) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $this->categoryId));
        }

        $query = match ($this->sortBy) {
            'rating' => $query->orderByRaw('average_rating DESC NULLS LAST'),
            default => $query->latest(),
        };

        return $query->paginate(12);
    }

    public function render(): View
    {
        return view('livewire.storefront.company-directory');
    }
}
