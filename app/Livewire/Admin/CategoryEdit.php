<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Category;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
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

    public ?int $parent_id = null;

    public ?int $sort_order = null;

    /** @var array<int, array<string, mixed>> */
    public array $variantAttributesSchema = [];

    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->parent_id = $category->parent_id;
        $this->sort_order = $category->sort_order;
        $this->variantAttributesSchema = $category->variant_attributes_schema ?? [];
    }

    /**
     * Top-level groups available as parents (excludes self and, when this
     * category has subcategories, everything — a group can't be nested).
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function parents(): Collection
    {
        if ($this->category->isGroup()) {
            return new Collection;
        }

        return Category::roots()
            ->whereKeyNot($this->category->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
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
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('parent_id'),
                Rule::notIn([$this->category->id]),
            ],
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'variantAttributesSchema' => 'array',
        ]);

        if ($this->parent_id !== null && $this->category->isGroup()) {
            $this->addError('parent_id', __('A category with subcategories cannot be nested.'));

            return;
        }

        $this->category->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'slug' => Str::slug($this->name),
            'parent_id' => $this->parent_id,
            'sort_order' => (int) ($this->sort_order ?? 0),
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
