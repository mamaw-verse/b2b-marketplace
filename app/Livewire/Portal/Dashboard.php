<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Models\CertificationApplication;
use App\Models\Rfq;
use App\Models\Shop;
use App\Models\ShopCertificate;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
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
     * The best halal certificate for the stat card, chosen by status priority:
     * active > expiring_soon > under_review > expired.
     *
     * Returns null when the shop has no certificates at all.
     */
    #[Computed]
    public function bestCertificate(): ?ShopCertificate
    {
        if (! $this->shop) {
            return null;
        }

        $priority = ['active', 'expiring_soon', 'under_review', 'expired'];

        return $this->shop->certificates()->get()
            ->sortBy(fn (ShopCertificate $cert) => array_search($cert->status, $priority, true))
            ->first();
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
     * Recent RFQs the buyer has submitted, with recipients eager-loaded
     * so admin_status is available without N+1 queries.
     *
     * @return Collection<int, Rfq>
     */
    #[Computed]
    public function recentRfqs(): Collection
    {
        return Rfq::where('user_id', Auth::id())
            ->with('recipients')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    /**
     * Count of non-closed RFQs the buyer has sent.
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

    public function render(): View
    {
        return view('livewire.portal.dashboard');
    }
}
