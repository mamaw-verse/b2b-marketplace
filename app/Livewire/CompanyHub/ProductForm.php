<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Product Form')]
final class ProductForm extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $name = '';

    public string $description = '';

    /** @var array<int, int> */
    public array $category_ids = [];

    /** @var array<string, array<string, mixed>> */
    public array $variantAttributesSchema = [];

    public $featured_image = null;

    public ?string $confirmingAttributeKey = null;

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->authorize('update', $product);
            $this->product = $product;
            $this->name = $product->name;
            $this->description = $product->description ?? '';
            $this->category_ids = $product->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();
            $this->variantAttributesSchema = $product->variant_attributes_schema ?? [];
        }
    }

    /**
     * Toggle a category on/off and immediately re-evaluate the schema population.
     * Using a dedicated action instead of wire:model on an array ensures the
     * updatedCategoryIds logic runs reliably on every toggle.
     */
    public function toggleCategory(int $id): void
    {
        if (in_array($id, $this->category_ids, true)) {
            $this->category_ids = array_values(array_filter(
                $this->category_ids,
                fn ($existing) => $existing !== $id
            ));
        } else {
            $this->category_ids[] = $id;
        }

        // On the create form only: auto-populate schema whenever categories change.
        if (! $this->product && empty($this->variantAttributesSchema)) {
            $this->populateSchemaFromCategories();
        }
    }

    /**
     * Replace the current schema with the merged schemas from all selected
     * categories. Works on both create and edit pages.
     */
    public function reloadSchemaFromCategories(): void
    {
        $this->variantAttributesSchema = [];
        $this->populateSchemaFromCategories();

        if (empty($this->variantAttributesSchema)) {
            \Flux\Flux::toast('None of the selected categories have options defined.', variant: 'warning');

            return;
        }

        // On edit, persist immediately so the reload survives a page refresh.
        $this->product?->update(['variant_attributes_schema' => $this->variantAttributesSchema]);

        \Flux\Flux::toast('Product options reloaded from selected categories.', variant: 'success');
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

    public function confirmRemoveAttribute(string $key): void
    {
        $this->confirmingAttributeKey = $key;
        \Flux\Flux::modal('confirm-remove-attribute')->show();
    }

    public function removeAttribute(): void
    {
        if ($this->confirmingAttributeKey === null) {
            return;
        }

        unset($this->variantAttributesSchema[$this->confirmingAttributeKey]);
        $this->confirmingAttributeKey = null;
    }

    public function save(): void
    {
        $this->validate();

        $shop = Auth::user()->shop;

        abort_unless((bool) $shop, 403, 'You must have a registered shop to manage products.');

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'variant_attributes_schema' => $this->variantAttributesSchema,
        ];

        if ($this->featured_image) {
            $path = $this->featured_image->store('products/images', 's3');
            $data['featured_image_path'] = $path;
        }

        if ($this->product) {
            $this->product->update($data);
            $product = $this->product;
        } else {
            $data['shop_id'] = $shop->id;
            $data['status'] = 'archived';
            $product = Product::create($data);
        }
        $product->categories()->sync($this->category_ids);

        \Flux\Flux::toast($this->product ? 'Product updated.' : 'Product created.', variant: 'success');

        $this->redirect(route('hub.products.edit', $product), navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function categories(): array
    {
        return Category::orderBy('name')->pluck('name', 'id')->all();
    }

    public function render()
    {
        return view('livewire.company-hub.product-form');
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'variantAttributesSchema' => 'array',
        ];
    }

    /**
     * Merge variant attribute schemas from all selected categories into the
     * current schema. Existing attributes (matched by label) are not duplicated.
     */
    private function populateSchemaFromCategories(): void
    {
        if (empty($this->category_ids)) {
            return;
        }

        $existingLabels = array_map(
            fn ($attr) => mb_strtolower(mb_trim($attr['label'] ?? '')),
            $this->variantAttributesSchema
        );

        $categories = Category::whereIn('id', $this->category_ids)
            ->whereNotNull('variant_attributes_schema')
            ->get();

        foreach ($categories as $category) {
            $schema = $category->variant_attributes_schema;

            if (empty($schema)) {
                continue;
            }

            foreach ($schema as $attr) {
                $label = mb_strtolower(mb_trim($attr['label'] ?? ''));

                if ($label === '' || in_array($label, $existingLabels, true)) {
                    continue;
                }

                $key = 'attr_'.uniqid();
                $this->variantAttributesSchema[$key] = [
                    'label' => $attr['label'] ?? '',
                    'type' => $attr['type'] ?? 'select',
                    'options' => $attr['options'] ?? '',
                    'required' => (bool) ($attr['required'] ?? false),
                ];
                $existingLabels[] = $label;
            }
        }
    }
}
