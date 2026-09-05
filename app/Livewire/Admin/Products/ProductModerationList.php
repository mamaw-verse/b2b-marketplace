<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Product Moderation Queue')]
final class ProductModerationList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $shopId = null;

    public ?int $previewProductId = null;

    protected $queryString = [
        'shopId' => ['except' => null, 'as' => 'shop_id'],
    ];

    public function mount(): void
    {
        Gate::authorize('product.moderate');
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    public function openPreview(int $productId): void
    {
        Gate::authorize('product.moderate');

        $this->previewProductId = $productId;
        unset($this->previewProduct);

        \Flux\Flux::modal('product-preview')->show();
    }

    #[Computed]
    public function previewProduct(): ?Product
    {
        if (! $this->previewProductId) {
            return null;
        }

        return Product::with(['shop', 'variants', 'gallery', 'categories'])
            ->find($this->previewProductId);
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = Product::with(['shop']);

        if ($this->search) {
            $query->where('name', 'ilike', '%'.$this->search.'%');
        }

        if ($this->statusFilter) {
            $query->where('moderation_status', $this->statusFilter);
        }

        if ($this->shopId) {
            $query->where('shop_id', $this->shopId);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function flagProduct(int $productId): void
    {
        Gate::authorize('product.moderate');

        $product = Product::findOrFail($productId);
        $product->update(['moderation_status' => 'flagged']);
        $product->searchable(); // Triggers update in typesense

        flux()->toast('Product flagged.');
    }

    public function unpublishProduct(int $productId): void
    {
        Gate::authorize('product.moderate');

        $product = Product::findOrFail($productId);
        $product->update(['moderation_status' => 'admin_unpublished']);
        $product->searchable(); // Triggers update in typesense

        flux()->toast('Product unpublished.');
    }

    public function restoreProduct(int $productId): void
    {
        Gate::authorize('product.moderate');

        $product = Product::findOrFail($productId);
        $product->update(['moderation_status' => 'active']);
        $product->searchable(); // Triggers update in typesense

        flux()->toast('Product restored to active.');
    }

    public function render()
    {
        return view('livewire.admin.products.product-moderation-list');
    }
}
