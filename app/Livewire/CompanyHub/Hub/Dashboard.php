<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Hub;

use App\Models\CertificationApplication;
use App\Models\Order;
use App\Models\Shop;
use App\Services\ShopAnalyticsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Seller Hub')]
final class Dashboard extends Component
{
    public array $counts = [];

    #[Computed]
    public function shop(): ?Shop
    {
        return Auth::user()->shop;
    }

    #[Computed]
    public function certificationApplication(): ?CertificationApplication
    {
        return CertificationApplication::query()
            ->where('shop_id', Auth::user()->shop?->id)
            ->latest('created_at')
            ->first();
    }

    public function mount(ShopAnalyticsService $analyticsService): void
    {
        $shop = Auth::user()->shop;
        if (! $shop) {
            return;
        }

        $this->counts = [
            'new_rfqs' => $shop->rfqRecipients()->where('status', 'received')->count(),
        ];

        if (config('marketplace.features.orders_enabled')) {
            $this->counts['orders_in_progress'] = Order::where('shop_id', $shop->id)->where('status', 'paid')->count();
            $this->counts['unfulfilled_orders'] = Order::where('shop_id', $shop->id)->where('status', 'paid')->whereDoesntHave('fulfilments', fn ($q) => $q->whereIn('status', ['shipped', 'delivered']))->count();
            $this->counts['draft_orders'] = Order::where('shop_id', $shop->id)->where('status', 'draft')->count();
        }

        if (config('marketplace.features.disputes_enabled')) {
            $this->counts['open_disputes'] = DB::table('disputes')->join('orders', 'orders.id', '=', 'disputes.order_id')->where('orders.shop_id', $shop->id)->whereNotIn('disputes.status', ['resolved_refunded', 'resolved_denied', 'withdrawn'])->count();
        }

        if (config('marketplace.features.subscriptions_enabled')) {
            $this->counts['subscription'] = $analyticsService->shopSubscription($shop);
        }

        $this->counts['certification'] = $analyticsService->shopCertification($shop);
        $this->counts['listings'] = $analyticsService->shopListings($shop);
    }

    public function render()
    {
        return view('livewire.company-hub.hub.dashboard');
    }
}
