<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Models\CertificationApplication;
use App\Models\Rfq;
use App\Models\Shop;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Unified portal dashboard — single entry point for all authenticated users
 * regardless of buyer-only or approved-seller account status.
 *
 * Phase 6: replaces the split account.dashboard + hub.hub-dashboard components.
 * Orders, earnings and payout stats are gated by their respective feature flags.
 */
#[Layout('layouts.portal')]
#[Title('My Portal')]
final class Dashboard extends Component
{
    /** Whether the authenticated user has an approved (active) shop. */
    #[Computed]
    public function isOnboarded(): bool
    {
        return $this->shop?->status === 'active';
    }

    /** The authenticated user's shop (null if no shop exists). */
    #[Computed]
    public function shop(): ?Shop
    {
        return Auth::user()?->shop;
    }

    /**
     * Latest certification application for the seller's shop.
     * Null if the user has no shop or has never applied.
     */
    #[Computed]
    public function certificationApplication(): ?CertificationApplication
    {
        if (! $this->shop) {
            return null;
        }

        return CertificationApplication::query()
            ->where('shop_id', $this->shop->id)
            ->latest()
            ->first();
    }

    /**
     * Active or trialing subscription for the seller's shop.
     * Null if no active subscription exists.
     */
    #[Computed]
    public function activeSubscription(): ?Subscription
    {
        if (! $this->shop) {
            return null;
        }

        return Subscription::query()
            ->where('shop_id', $this->shop->id)
            ->whereIn('status', ['active', 'trialing'])
            ->latest()
            ->first();
    }

    /**
     * Count of pending/in-progress RFQs the buyer has sent.
     * Used for the buyer's quick-stats summary.
     */
    #[Computed]
    public function buyerRfqCount(): int
    {
        return Rfq::where('user_id', Auth::id())
            ->whereNotIn('status', ['closed'])
            ->count();
    }

    /**
     * Seller shop stats for the seller's quick-stats row.
     * Only computed when the user has an onboarded shop.
     *
     * @return array{published_products: int}
     */
    #[Computed]
    public function sellerStats(): array
    {
        if (! $this->isOnboarded) {
            return ['published_products' => 0];
        }

        return [
            'published_products' => $this->shop->products()
                ->where('status', 'published')
                ->count(),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.portal.dashboard');
    }
}
