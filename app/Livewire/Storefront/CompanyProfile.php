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
            $query->marketplaceVisible()->with('variants');
        }]);
    }

    public function messageCompany(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $user = auth()->user();

        // Prevent sellers from messaging themselves
        if ($user->shop && $user->shop->id === $this->shop->id) {
            $this->dispatch('toast', message: 'You cannot message your own company.');

            return;
        }

        $conversation = \App\Models\Conversation::firstOrCreate([
            'buyer_user_id' => $user->id,
            'shop_id' => $this->shop->id,
        ]);

        if (! $conversation->threads()->where('is_default', true)->exists()) {
            $conversation->threads()->create([
                'title' => 'General',
                'is_default' => true,
            ]);
        }

        $this->redirectRoute('account.messages');
    }

    public function render(): View
    {
        return view('livewire.storefront.company-profile')
            ->title($this->shop->storefront_name);
    }
}
