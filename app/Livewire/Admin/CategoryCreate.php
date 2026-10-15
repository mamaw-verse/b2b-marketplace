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
#[Title('Create Category')]
final class CategoryCreate extends Component
{
    public string $name = '';

    public string $description = '';

    public ?int $parent_id = null;

    public ?int $sort_order = null;

    /** @var array<int, array<string, mixed>> */
    public array $variantAttributesSchema = [];

    /**
     * Top-level groups available as parents.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function parents(): Collection
    {
        return Category::roots()->orderBy('sort_order')->orderBy('name')->get();
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
            'name' => ['required', 'string', 'max:150', 'unique:categories,name'],
            'description' => 'nullable|string|max:1000',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('parent_id'),
            ],
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'variantAttributesSchema' => 'array',
        ]);

        Category::create([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'slug' => Str::slug($this->name),
            'parent_id' => $this->parent_id,
            'sort_order' => (int) ($this->sort_order ?? 0),
            'variant_attributes_schema' => $this->variantAttributesSchema,
        ]);

        Flux::toast('Category created.', variant: 'success');

        $this->redirect(route('admin.categories.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.category-create');
    }
}
