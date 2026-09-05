<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Finance;

use App\Models\Order;
use App\Models\Shop;
use App\Services\GoogleAnalyticsService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Platform Dashboard')]
final class AnalyticsDashboard extends Component
{
    public string $period = '30d';

    public string $from = '';

    public string $to = '';

    public string $customFrom = '';

    public string $customTo = '';

    public array $metrics = [];

    public bool $gaConfigured = false;

    public function mount(): void
    {
        $this->gaConfigured = app(GoogleAnalyticsService::class)->isConfigured();
        $this->applyPeriod();
        $this->loadMetrics();
    }

    public function updatedPeriod(): void
    {
        $this->applyPeriod();
        $this->loadMetrics();
    }

    public function updatedCustomFrom(): void
    {
        if ($this->period === 'custom' && $this->customFrom !== '' && $this->customTo !== '') {
            $this->from = $this->customFrom;
            $this->to = $this->customTo;
            $this->loadMetrics();
        }
    }

    public function updatedCustomTo(): void
    {
        if ($this->period === 'custom' && $this->customFrom !== '' && $this->customTo !== '') {
            $this->from = $this->customFrom;
            $this->to = $this->customTo;
            $this->loadMetrics();
        }
    }

    public function render()
    {
        return view('livewire.admin.finance.analytics-dashboard');
    }

    private function applyPeriod(): void
    {
        match ($this->period) {
            'today' => [
                $this->from = now()->startOfDay()->format('Y-m-d'),
                $this->to = now()->endOfDay()->format('Y-m-d'),
            ],
            '7d' => [
                $this->from = now()->subDays(7)->format('Y-m-d'),
                $this->to = now()->format('Y-m-d'),
            ],
            '30d' => [
                $this->from = now()->subDays(30)->format('Y-m-d'),
                $this->to = now()->format('Y-m-d'),
            ],
            '90d' => [
                $this->from = now()->subDays(90)->format('Y-m-d'),
                $this->to = now()->format('Y-m-d'),
            ],
            'this_month' => [
                $this->from = now()->startOfMonth()->format('Y-m-d'),
                $this->to = now()->format('Y-m-d'),
            ],
            'last_month' => [
                $this->from = now()->subMonth()->startOfMonth()->format('Y-m-d'),
                $this->to = now()->subMonth()->endOfMonth()->format('Y-m-d'),
            ],
            default => [
                $this->from = now()->subDays(30)->format('Y-m-d'),
                $this->to = now()->format('Y-m-d'),
            ],
        };
    }

    private function loadMetrics(): void
    {
        $from = $this->from ? \Carbon\Carbon::parse($this->from)->startOfDay() : now()->subDays(30);
        $to = $this->to ? \Carbon\Carbon::parse($this->to)->endOfDay() : now();

        $gmv = (float) Order::whereIn('status', ['completed', 'refunded'])
            ->whereBetween('paid_at', [$from, $to])
            ->sum('order_total');

        $totalOrders = Order::whereIn('status', ['completed', 'refunded'])
            ->whereBetween('paid_at', [$from, $to])
            ->count();

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

        $platformAverageRating = (float) Shop::where('total_reviews', '>', 0)
            ->avg('average_rating');

        $totalActiveSellers = Shop::where('status', 'active')->count();

        $gaOverview = app(GoogleAnalyticsService::class)->getOverview(
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        );

        $analyticsService = app(\App\Services\ShopAnalyticsService::class);

        $this->metrics = [
            'gmv' => $gmv,
            'total_orders' => $totalOrders,
            'total_payouts_disbursed' => $totalPayoutsDisbursed,
            'total_transaction_fees_collected' => $totalTransactionFees,
            'total_commission_fees_collected' => $totalCommissionFees,
            'platform_average_rating' => round($platformAverageRating, 2),
            'total_active_sellers' => $totalActiveSellers,
            'ga' => $gaOverview,
            'subscriptions' => $analyticsService->platformSubscriptions(),
            'certifications' => $analyticsService->platformCertifications(),
            'rfqs' => $analyticsService->platformRfqs($from, $to),
            'sponsored_content' => $analyticsService->platformSponsoredContent(),
            'revenue_trend' => $analyticsService->platformRevenueTrend($from, $to),
            'subscription_trend' => $analyticsService->platformSubscriptionTrend(),
            'certification_trend' => $analyticsService->platformCertificationTrend(),
        ];
    }
}
