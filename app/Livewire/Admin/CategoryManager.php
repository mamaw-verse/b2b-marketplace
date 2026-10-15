<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Category;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Categories')]
final class CategoryManager extends Component
{
    public ?int $deletingCategoryId = null;

    /**
     * Top-level groups with their subcategories, in display order.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categoryGroups()
    {
        return Category::with('children')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function confirmDelete(int $categoryId): void
    {
        $category = Category::find($categoryId);

        if (! $category) {
            return;
        }

        if ($category->isGroup()) {
            Flux::toast(__('Delete its subcategories first.'), variant: 'warning');

            return;
        }

        $this->deletingCategoryId = $categoryId;
        Flux::modal('confirm-delete-category')->show();
    }

    public function delete(): void
    {
        if ($this->deletingCategoryId) {
            Category::destroy($this->deletingCategoryId);
        }

        $this->deletingCategoryId = null;
        unset($this->categoryGroups);
    }

    public function render()
    {
        return view('livewire.admin.category-manager');
    }
}
