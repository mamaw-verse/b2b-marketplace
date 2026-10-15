<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class CategoryNav extends Component
{
    public ?int $selectedId = null;

    /**
     * Top-level groups with their subcategories.
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

    public function selectCategory(?int $id): void
    {
        $this->selectedId = $id;
        $this->dispatch('category-selected', categoryId: $id);
    }

    public function render(): View
    {
        return view('livewire.storefront.category-nav');
    }
}
