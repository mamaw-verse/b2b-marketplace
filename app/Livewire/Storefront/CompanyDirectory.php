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

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
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
        if ($this->query !== '') {
            $search = Shop::search($this->query);

            if ($this->categoryId) {
                $category = Category::find($this->categoryId);
                if ($category) {
                    $search->where('categories', $category->name);
                }
            }

            return $search->paginate(12);
        }

        $query = Shop::where('status', 'active');

        if ($this->categoryId) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $this->categoryId));
        }

        return $query->latest()->paginate(12);
    }

    public function render(): View
    {
        return view('livewire.storefront.company-directory');
    }
}
