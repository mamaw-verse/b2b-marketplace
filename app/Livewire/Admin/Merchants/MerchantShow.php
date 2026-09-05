<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Merchants;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Merchant Details')]
final class MerchantShow extends Component
{
    public Shop $shop;

    public ?int $previewProductId = null;

    public function mount(Shop $shop): void
    {
        Gate::authorize('merchant.manage');

        $this->shop = $shop->load([
            'user',
            'onboarding',
            'subscriptions',
            'certificationApplications',
            'latestSubscription',
        ]);
    }

    public function openPreview(int $productId): void
    {
        Gate::authorize('merchant.manage');

        $this->previewProductId = $productId;
        unset($this->previewProduct);

        \Flux\Flux::modal('product-preview')->show();
    }

    /**
     * Product loaded for the preview modal.
     */
    #[Computed]
    public function previewProduct(): ?Product
    {
        if (! $this->previewProductId) {
            return null;
        }

        return Product::with(['shop', 'variants', 'gallery', 'categories'])
            ->find($this->previewProductId);
    }

    /**
     * All products for this merchant, ordered by published-first then name.
     * Includes soft-deleted so admin has full visibility.
     *
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        return Product::with(['variants', 'categories'])
            ->withTrashed()
            ->where('shop_id', $this->shop->id)
            ->orderByRaw("status = 'published' desc")
            ->orderBy('name')
            ->get();
    }

    public function suspend(): void
    {
        Gate::authorize('merchant.manage');

        $this->shop->suspend();

        $this->dispatch('toast', message: 'Merchant suspended successfully.');
    }

    public function reactivate(): void
    {
        Gate::authorize('merchant.manage');

        $this->shop->reactivate();

        $this->dispatch('toast', message: 'Merchant reactivated successfully.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.merchants.merchant-show');
    }
}
