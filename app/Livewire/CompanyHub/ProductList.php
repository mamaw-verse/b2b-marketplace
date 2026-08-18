<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Products')]
final class ProductList extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $productId): void
    {
        $product = $this->findOwnedProduct($productId);

        $product->update([
            'status' => $product->status === 'published' ? 'archived' : 'published',
        ]);
    }

    /**
     * @return LengthAwarePaginator<Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $shop = Auth::user()->shop;

        if (! $shop) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        return Product::withCount('variants')
            ->where('shop_id', $shop->id)
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.company-hub.product-list');
    }

    private function findOwnedProduct(int $productId): Product
    {
        $shop = Auth::user()->shop;

        return Product::where('shop_id', $shop->id)->findOrFail($productId);
    }
}
