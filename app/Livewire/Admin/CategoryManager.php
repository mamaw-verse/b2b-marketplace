<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Category Manager')]
final class CategoryManager extends Component
{
    public string $name = '';

    public string $description = '';

    public ?int $editingCategoryId = null;

    public bool $showFormModal = false;

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

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                $this->editingCategoryId
                    ? 'unique:categories,name,'.$this->editingCategoryId
                    : 'unique:categories,name',
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'slug' => Str::slug($this->name),
        ];

        if ($this->editingCategoryId) {
            $category = Category::findOrFail($this->editingCategoryId);
            $category->update($data);
        } else {
            Category::create($data);
        }

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->categories);
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

    /**
     * Computed slug preview for the form.
     */
    #[Computed]
    public function slugPreview(): string
    {
        return Str::slug($this->name);
    }

    public function render()
    {
        return view('livewire.admin.category-manager');
    }

    private function resetForm(): void
    {
        $this->editingCategoryId = null;
        $this->name = '';
        $this->description = '';
    }
}
