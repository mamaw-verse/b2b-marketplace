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
#[Title('Reports')]
final class Reports extends Component
{
    public int $window = 30;

    public array $metrics = [];

    public function mount(): void
    {
        $this->loadMetrics();
    }

    public function updatedWindow(): void
    {
        $this->loadMetrics();
    }

    public function render()
    {
        return view('livewire.company-hub.hub.reports');
    }

    private function loadMetrics(): void
    {
        $shop = Auth::user()->shop;
        if (! $shop) {
            return;
        }

        $from = now()->subDays($this->window);
        $to = now();

        $query = Order::where('shop_id', $shop->id)
            ->whereIn('status', ['completed', 'refunded'])
            ->whereBetween('paid_at', [$from, $to]);

        $grossSales = (float) (clone $query)->sum('order_total');
        $ordersPlaced = (clone $query)->count();

        $totalRefunds = (float) DB::table('ledger_entries')
            ->join('orders', 'orders.id', '=', 'ledger_entries.order_id')
            ->where('orders.shop_id', $shop->id)
            ->whereIn('orders.status', ['completed', 'refunded'])
            ->whereBetween('orders.paid_at', [$from, $to])
            ->where('ledger_entries.type', 'refund')
            ->sum('ledger_entries.amount');

        $commissionRate = (float) config('marketplace.commission_rate', 0.01);

        $this->metrics = [
            'orders_placed' => $ordersPlaced,
            'gross_sales' => $grossSales,
            'total_refunds' => $totalRefunds,
            'platform_commission' => round($grossSales * $commissionRate, 2),
            'net_sales' => round($grossSales - $totalRefunds - ($grossSales * $commissionRate), 2),
        ];
    }
}
