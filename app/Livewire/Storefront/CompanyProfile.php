<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Models\Shop;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
final class CompanyProfile extends Component
{
    public Shop $shop;

    public function mount(Shop $shop): void
    {
        $this->shop = $shop;
        $this->shop->load(['categories', 'certificates', 'products' => function ($query) {
            $query->where('status', 'published')->with('variants');
        }]);
    }

    public function render(): View
    {
        return view('livewire.storefront.company-profile')
            ->title($this->shop->storefront_name);
    }
}
