<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Finance;

use App\Models\Order;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Platform Analytics')]
final class AnalyticsDashboard extends Component
{
    public string $from = '';

    public string $to = '';

    public array $metrics = [];

    public function mount(): void
    {
        $this->from = now()->subDays(30)->format('Y-m-d');
        $this->to = now()->format('Y-m-d');
        $this->loadMetrics();
    }

    public function updatedFrom(): void
    {
        $this->loadMetrics();
    }

    public function updatedTo(): void
    {
        $this->loadMetrics();
    }

    public function render()
    {
        return view('livewire.admin.finance.analytics-dashboard');
    }

    private function loadMetrics(): void
    {
        $from = $this->from ? \Carbon\Carbon::parse($this->from)->startOfDay() : now()->subDays(30);
        $to = $this->to ? \Carbon\Carbon::parse($this->to)->endOfDay() : now();

        // GMV
        $gmv = (float) Order::whereIn('status', ['completed', 'refunded'])
            ->whereBetween('paid_at', [$from, $to])
            ->sum('order_total');

        // Total orders
        $totalOrders = Order::whereIn('status', ['completed', 'refunded'])
            ->whereBetween('paid_at', [$from, $to])
            ->count();

        // Payout metrics
        $totalPayoutsDisbursed = 0.0;
        $totalTransactionFees = 0.0;
        $totalCommissionFees = 0.0;

        if (config('marketplace.features.payouts_enabled')) {
            $totalPayoutsDisbursed = (float) DB::table('payouts')
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$from, $to])
                ->sum('net_amount');

            $totalTransactionFees = (float) DB::table('payouts')
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$from, $to])
                ->sum('total_transaction_fees');

            $totalCommissionFees = (float) DB::table('payouts')
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$from, $to])
                ->sum('total_commission_fees');
        }

        // Platform average rating
        $platformAverageRating = (float) Shop::where('total_reviews', '>', 0)
            ->avg('average_rating');

        // Total active sellers
        $totalActiveSellers = Shop::where('status', 'active')->count();

        $this->metrics = [
            'gmv' => $gmv,
            'total_orders' => $totalOrders,
            'total_payouts_disbursed' => $totalPayoutsDisbursed,
            'total_transaction_fees_collected' => $totalTransactionFees,
            'total_commission_fees_collected' => $totalCommissionFees,
            'platform_average_rating' => round($platformAverageRating, 2),
            'total_active_sellers' => $totalActiveSellers,
        ];
    }
}
