<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Category;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Edit Category')]
final class CategoryEdit extends Component
{
    public Category $category;

    public string $name = '';

    public string $description = '';

    /** @var array<int, array<string, mixed>> */
    public array $variantAttributesSchema = [];

    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->variantAttributesSchema = $category->variant_attributes_schema ?? [];
    }

    public function addAttribute(): void
    {
        $key = 'attr_'.uniqid();
        $this->variantAttributesSchema[$key] = [
            'label' => '',
            'type' => 'select',
            'options' => '',
            'required' => false,
        ];
    }

    public function removeAttribute(string $key): void
    {
        unset($this->variantAttributesSchema[$key]);
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:150', 'unique:categories,name,'.$this->category->id],
            'description' => 'nullable|string|max:1000',
            'variantAttributesSchema' => 'array',
        ]);

        $this->category->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'slug' => Str::slug($this->name),
            'variant_attributes_schema' => $this->variantAttributesSchema,
        ]);

        Flux::toast('Category updated.', variant: 'success');

        $this->redirect(route('admin.categories.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.category-edit');
    }
}
