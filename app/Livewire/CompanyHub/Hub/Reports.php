<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Hub;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        // Payout metrics (graceful fallback)
        $totalEarned = 0.0;
        $totalReleased = 0.0;
        if (Schema::hasTable('payout_items') && Schema::hasTable('payouts')) {
            $totalEarned = (float) DB::table('payout_items')
                ->join('payouts', 'payouts.id', '=', 'payout_items.payout_id')
                ->join('orders', 'orders.id', '=', 'payout_items.order_id')
                ->where('orders.shop_id', $shop->id)
                ->where('payouts.status', 'completed')
                ->whereBetween('payouts.completed_at', [$from, $to])
                ->sum('payout_items.net_amount');

            $totalReleased = (float) DB::table('payouts')
                ->where('shop_id', $shop->id)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$from, $to])
                ->sum('net_amount');
        }

        // Pending balance
        $pendingQuery = DB::table('ledger_entries')
            ->join('orders', 'orders.id', '=', 'ledger_entries.order_id')
            ->where('orders.shop_id', $shop->id)
            ->where('orders.status', 'completed')
            ->where('ledger_entries.type', 'escrow_hold');

        if (Schema::hasTable('payout_items')) {
            $pendingQuery->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('payout_items')
                    ->whereColumn('payout_items.order_id', 'ledger_entries.order_id');
            });
        }

        $pendingBalance = (float) $pendingQuery->sum('ledger_entries.amount');

        $this->metrics = [
            'orders_placed' => $ordersPlaced,
            'gross_sales' => $grossSales,
            'total_refunds' => $totalRefunds,
            'platform_commission' => round($grossSales * $commissionRate, 2),
            'net_sales' => round($grossSales - $totalRefunds - ($grossSales * $commissionRate), 2),
            'total_earned' => $totalEarned,
            'pending_balance' => $pendingBalance,
            'total_released' => $totalReleased,
            'average_rating' => (float) $shop->average_rating,
            'total_reviews' => (int) $shop->total_reviews,
        ];
    }
}
