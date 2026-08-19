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
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::orderBy('name')->get();
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
