<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
final class ProductDetail extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        $product->loadMissing('shop');

        $visible = $product->status === 'published'
            && $product->moderation_status === 'active'
            && $product->shop->status === 'active';

        if ($visible && config('marketplace.features.subscriptions_enabled')) {
            $visible = $product->shop->hasActiveOrTrialingSubscription();
        }

        abort_unless($visible, 404);

        $this->product = $product;
        $this->product->load([
            // Only published variants are shown publicly; archived variants
            // must never appear on the storefront (uses the existing scope).
            'variants' => fn ($query) => $query->published(),
            'gallery',
            'documents',
            'categories',
            'shop',
        ]);
    }

    public function render(): View
    {
        return view('livewire.storefront.product-detail')
            ->title($this->product->name);
    }
}
