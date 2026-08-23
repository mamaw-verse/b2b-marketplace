<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Category;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Categories')]
final class CategoryManager extends Component
{
    public bool $showDeleteModal = false;

    public ?int $deletingCategoryId = null;

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Category>
     */
    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    public function confirmDelete(int $categoryId): void
    {
        $this->deletingCategoryId = $categoryId;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingCategoryId) {
            Category::destroy($this->deletingCategoryId);
        }

        $this->showDeleteModal = false;
        $this->deletingCategoryId = null;
        unset($this->categories);
    }

    public function render()
    {
        return view('livewire.admin.category-manager');
    }
}
