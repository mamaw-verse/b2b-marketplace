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

    public $featured_image = null;

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->authorize('update', $product);
            $this->product = $product;
            $this->name = $product->name;
            $this->description = $product->description ?? '';
            $this->category_ids = $product->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();
        }
    }

    public function save(): void
    {
        $this->validate();

        $shop = Auth::user()->shop;

        abort_unless((bool) $shop, 403, 'You must have a registered shop to manage products.');

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
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
        ];
    }
}
