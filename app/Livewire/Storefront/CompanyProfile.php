<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Models\Conversation;
use App\Models\Shop;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
final class CompanyProfile extends Component
{
    public ?Shop $shop = null;

    public function mount(Shop $shop): void
    {
        if (config('marketplace.features.subscriptions_enabled')) {
            $visible = $shop->status === 'active' && $shop->hasActiveOrTrialingSubscription();

            if (! $visible) {
                // Own shop: redirect to the subscription page with a helpful message.
                // Other shops: 404 — the profile simply does not exist for them yet.
                if (auth()->check() && auth()->user()->shop?->id === $shop->id) {
                    session()->flash('message', __('Subscribe to make your business profile visible on the marketplace.'));
                    $this->redirect(route('hub.subscription'), navigate: true);

                    return;
                }

                abort(404);
            }
        } else {
            abort_unless($shop->status === 'active', 404);
        }

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

        $conversation = Conversation::firstOrCreate([
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
            ->title($this->shop?->storefront_name ?? '');
    }
}
