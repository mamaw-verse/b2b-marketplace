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
        $this->product = $product;
        $this->product->load(['variants', 'gallery', 'documents', 'categories', 'shop']);
    }

    public function render(): View
    {
        return view('livewire.storefront.product-detail')
            ->title($this->product->name);
    }
}
