<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Hub;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Seller Hub')]
final class Dashboard extends Component
{
    public array $counts = [];

    public function mount(): void
    {
        $shop = Auth::user()->shop;
        if (! $shop) {
            return;
        }

        $this->counts = [
            'new_rfqs' => $shop->rfqRecipients()->where('status', 'received')->count(),
            'orders_in_progress' => Order::where('shop_id', $shop->id)->where('status', 'paid')->count(),
            'unfulfilled_orders' => Order::where('shop_id', $shop->id)->where('status', 'paid')->whereDoesntHave('fulfilments', fn ($q) => $q->whereIn('status', ['shipped', 'delivered']))->count(),
            'draft_orders' => Order::where('shop_id', $shop->id)->where('status', 'draft')->count(),
            'open_disputes' => DB::table('disputes')->join('orders', 'orders.id', '=', 'disputes.order_id')->where('orders.shop_id', $shop->id)->whereNotIn('disputes.status', ['resolved_refunded', 'resolved_denied', 'withdrawn'])->count(),
        ];
    }

    public function render()
    {
        return view('livewire.company-hub.hub.dashboard');
    }
}
