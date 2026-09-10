<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

/**
 * Houses the raw DB aggregations and reporting queries for the seller hub.
 *
 * Extracted from SellerHubController to keep controllers thin and to make
 * the analytics logic independently testable.
 */
final class ShopAnalyticsService
{
    /**
     * Dashboard to-do widget counts for a shop.
     *
     * @return array<string, int>
     */
    public function dashboardCounts(Shop $shop): array
    {
        $newRfqs = $shop->rfqRecipients()
            ->where('status', 'received')
            ->count();

        $ordersInProgress = Order::where('shop_id', $shop->id)
            ->where('status', 'paid')
            ->count();

        $unfulfilledOrders = Order::where('shop_id', $shop->id)
            ->where('status', 'paid')
            ->whereDoesntHave('fulfilments', fn ($q) => $q->whereIn('status', ['shipped', 'delivered']))
            ->count();

        $draftOrders = Order::where('shop_id', $shop->id)
            ->where('status', 'draft')
            ->count();

        $openDisputes = DB::table('disputes')
            ->join('orders', 'orders.id', '=', 'disputes.order_id')
            ->where('orders.shop_id', $shop->id)
            ->whereNotIn('disputes.status', ['resolved_refunded', 'resolved_denied', 'withdrawn'])
            ->count();

        $pendingQuotations = DB::table('quotations')
            ->join('rfq_recipients', 'rfq_recipients.id', '=', 'quotations.rfq_recipient_id')
            ->where('rfq_recipients.shop_id', $shop->id)
            ->where('quotations.status', 'submitted')
            ->count();

        return [
            'new_rfqs' => $newRfqs,
            'orders_in_progress' => $ordersInProgress,
            'unfulfilled_orders' => $unfulfilledOrders,
            'draft_orders' => $draftOrders,
            'open_disputes' => $openDisputes,
            'pending_quotations' => $pendingQuotations,
        ];
    }

    /**
     * Cursor-paginated list of buyers who have transacted with this shop.
     *
     * @return CursorPaginator<int, User>
     */
    public function customers(Shop $shop, int $limit = 20): CursorPaginator
    {
        return User::where(function ($query) use ($shop) {
            $query->whereHas('orders', fn ($q) => $q->where('shop_id', $shop->id))
                ->orWhereHas('rfqs', function ($q) use ($shop) {
                    $q->whereHas('recipients', function ($r) use ($shop) {
                        $r->where('shop_id', $shop->id)
                            ->whereHas('quotations', fn ($qt) => $qt->where('status', 'awarded'));
                    });
                });
        })->select('id', 'first_name', 'last_name', 'email')
            ->withCount(['orders' => fn ($q) => $q->where('shop_id', $shop->id)])
            ->withMax(['orders' => fn ($q) => $q->where('shop_id', $shop->id)], 'created_at')
            ->cursorPaginate($limit);
    }

    /**
     * Sales & fulfilment report metrics over a time window.
     *
     * @return array<string, mixed>
     */
    public function reports(Shop $shop, int $windowDays, CarbonInterface $from, CarbonInterface $to): array
    {
        $ordersQuery = Order::where('shop_id', $shop->id)
            ->whereIn('status', ['completed', 'refunded'])
            ->whereBetween('paid_at', [$from, $to]);

        $ordersPlaced = (clone $ordersQuery)->count();
        $grossSales = (float) (clone $ordersQuery)->sum('order_total');

        $totalRefunds = (float) DB::table('ledger_entries')
            ->join('orders', 'orders.id', '=', 'ledger_entries.order_id')
            ->where('orders.shop_id', $shop->id)
            ->whereIn('orders.status', ['completed', 'refunded'])
            ->whereBetween('orders.paid_at', [$from, $to])
            ->where('ledger_entries.type', 'refund')
            ->sum('ledger_entries.amount');

        $commissionRate = (float) config('marketplace.commission_rate', 0.01);
        $platformCommission = round($grossSales * $commissionRate, 2);
        $netSales = round($grossSales - $totalRefunds - $platformCommission, 2);

        $totalCustomers = (clone $ordersQuery)->distinct('buyer_user_id')->count('buyer_user_id');
        $returningCustomers = (clone $ordersQuery)
            ->whereIn('buyer_user_id', function ($sub) use ($shop, $from) {
                $sub->select('buyer_user_id')
                    ->from('orders')
                    ->where('shop_id', $shop->id)
                    ->where('paid_at', '<', $from);
            })
            ->distinct('buyer_user_id')
            ->count('buyer_user_id');
        $newCustomers = $totalCustomers - $returningCustomers;

        $itemsPurchased = (int) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.shop_id', $shop->id)
            ->whereIn('orders.status', ['completed', 'refunded'])
            ->whereBetween('orders.paid_at', [$from, $to])
            ->sum('order_items.quantity');

        $totalEarned = 0.0;
        $totalReleased = 0.0;
        if (config('marketplace.features.payouts_enabled')) {
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

        $pendingBalance = $this->pendingBalance($shop);

        return [
            'window_days' => $windowDays,
            'new_customers' => $newCustomers,
            'returning_customers' => $returningCustomers,
            'orders_placed' => $ordersPlaced,
            'items_purchased' => $itemsPurchased,
            'gross_sales' => $grossSales,
            'total_refunds' => $totalRefunds,
            'platform_commission' => $platformCommission,
            'net_sales' => $netSales,
            'total_earned' => $totalEarned,
            'pending_balance' => $pendingBalance,
            'total_released' => $totalReleased,
            'average_rating' => (float) $shop->average_rating,
            'total_reviews' => (int) $shop->total_reviews,
        ];
    }

    /**
     * Escrow_hold ledger entries for completed orders without a payout_items row.
     */
    public function pendingBalance(Shop $shop): float
    {
        $pendingQuery = DB::table('ledger_entries')
            ->join('orders', 'orders.id', '=', 'ledger_entries.order_id')
            ->where('orders.shop_id', $shop->id)
            ->where('orders.status', 'completed')
            ->where('ledger_entries.type', 'escrow_hold');

        if (config('marketplace.features.payouts_enabled')) {
            $pendingQuery->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('payout_items')
                    ->whereColumn('payout_items.order_id', 'ledger_entries.order_id');
            });
        }

        return (float) $pendingQuery->sum('ledger_entries.amount');
    }

    /**
     * Leaderboard rows for a shop.
     *
     * @return array<int, array<string, mixed>>
     */
    public function leaderboards(Shop $shop, string $type, string $rankBy, int $limit, int $windowDays = 0): array
    {
        $orderCondition = function ($query) use ($shop, $windowDays) {
            $query->where('orders.shop_id', $shop->id)
                ->whereIn('orders.status', ['completed', 'refunded']);
            if ($windowDays > 0) {
                $query->where('orders.paid_at', '>=', now()->subDays($windowDays));
            }
        };

        return match ($type) {
            'products' => $this->topProducts($orderCondition, $rankBy, $limit),
            'customers' => $this->topCustomers($orderCondition, $rankBy, $limit),
            default => [],
        };
    }

    /**
     * Daily revenue & order count trend for chart rendering.
     *
     * @return array<int, array{date: string, gmv: float, orders: int}>
     */
    public function platformRevenueTrend(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = Order::whereIn('status', ['completed', 'refunded'])
            ->whereBetween('paid_at', [$from, $to])
            ->select(
                DB::raw('DATE(paid_at) as date'),
                DB::raw('SUM(order_total) as gmv'),
                DB::raw('COUNT(*) as orders'),
            )
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->orderBy('date')
            ->get();

        return $rows->map(fn ($row) => [
            'date' => $row->date,
            'gmv' => (float) $row->gmv,
            'orders' => (int) $row->orders,
        ])->toArray();
    }

    /**
     * Monthly subscription growth trend.
     *
     * @return array<int, array{month: string, active: int, trial: int, cancelled: int}>
     */
    public function platformSubscriptionTrend(): array
    {
        $rows = DB::table('subscriptions')
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active"),
                DB::raw("SUM(CASE WHEN status = 'trialing' THEN 1 ELSE 0 END) as trial"),
                DB::raw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
            )
            ->groupBy(DB::raw("TO_CHAR(created_at, 'YYYY-MM')"))
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($row) => [
            'month' => $row->month,
            'active' => (int) $row->active,
            'trial' => (int) $row->trial,
            'cancelled' => (int) $row->cancelled,
        ])->toArray();
    }

    /**
     * Monthly certification application trend.
     *
     * @return array<int, array{month: string, pending: int, approved: int, rejected: int}>
     */
    public function platformCertificationTrend(): array
    {
        $rows = DB::table('certification_applications')
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected"),
            )
            ->groupBy(DB::raw("TO_CHAR(created_at, 'YYYY-MM')"))
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($row) => [
            'month' => $row->month,
            'pending' => (int) $row->pending,
            'approved' => (int) $row->approved,
            'rejected' => (int) $row->rejected,
        ])->toArray();
    }

    public function platformSubscriptions(): array
    {
        $counts = DB::table('subscriptions')
            ->select('subscriptions.status', DB::raw('COUNT(*) as count'))
            ->groupBy('subscriptions.status')
            ->get();

        $trialToPaid = DB::table('subscriptions')
            ->whereNotNull('trial_ends_at')
            ->where('status', 'active')
            ->count();

        $totalTrials = DB::table('subscriptions')
            ->whereNotNull('trial_ends_at')
            ->count();

        $conversionRate = $totalTrials > 0 ? round(($trialToPaid / $totalTrials) * 100, 2) : 0.0;

        return [
            'counts' => $counts->toArray(),
            'trial_to_paid_conversion' => $conversionRate,
        ];
    }

    public function platformCertifications(): array
    {
        return DB::table('certification_applications')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
    }

    public function platformRfqs(CarbonInterface $from, CarbonInterface $to): array
    {
        $volume = DB::table('rfqs')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $avgResolutionTimeSeconds = DB::table('rfqs')
            ->where('status', 'closed')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('closed_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (closed_at - created_at))) as avg_time')
            ->value('avg_time');

        return [
            'volume' => $volume,
            'avg_resolution_time_seconds' => (float) $avgResolutionTimeSeconds,
        ];
    }

    public function platformSponsoredContent(): array
    {
        return DB::table('sponsored_content_events')
            ->join('sponsored_content', 'sponsored_content.id', '=', 'sponsored_content_events.sponsored_content_id')
            ->join('sponsored_content_tiers', 'sponsored_content_tiers.id', '=', 'sponsored_content.sponsored_content_tier_id')
            ->select('sponsored_content_tiers.name as tier_name', 'sponsored_content_events.event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('sponsored_content_tiers.name', 'sponsored_content_events.event_type')
            ->get()
            ->toArray();
    }

    public function shopSubscription(Shop $shop): ?array
    {
        $subscription = DB::table('subscriptions')
            ->join('subscription_plans', 'subscription_plans.id', '=', 'subscriptions.subscription_plan_id')
            ->where('subscriptions.shop_id', $shop->id)
            ->select('subscriptions.status', 'subscriptions.current_period_end', 'subscription_plans.name as plan_name')
            ->first();

        if (! $subscription) {
            return null;
        }

        return [
            'status' => $subscription->status,
            'plan_name' => $subscription->plan_name,
            'renewal_date' => $subscription->current_period_end,
        ];
    }

    public function shopCertification(Shop $shop): array
    {
        return [
            'is_certified' => $shop->is_halal_certified,
            'expiry_date' => $shop->certified_expiry_date,
        ];
    }

    public function shopListings(Shop $shop): array
    {
        return [
            'published' => DB::table('products')->where('shop_id', $shop->id)->where('publish_status', 'published')->where('moderation_status', 'active')->count(),
            'draft' => DB::table('products')->where('shop_id', $shop->id)->where('publish_status', 'draft')->count(),
            'flagged' => DB::table('products')->where('shop_id', $shop->id)->where('moderation_status', 'flagged')->count(),
            'unpublished' => DB::table('products')->where('shop_id', $shop->id)->where('moderation_status', 'admin_unpublished')->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topProducts(Closure $orderCondition, string $rankBy, int $limit): array
    {
        $orderColumn = match ($rankBy) {
            'order_count' => DB::raw('COUNT(DISTINCT orders.id)'),
            'quantity' => DB::raw('SUM(order_items.quantity)'),
            default => DB::raw('SUM(order_items.subtotal)'),
        };

        $results = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where($orderCondition)
            ->groupBy('order_items.description')
            ->select([
                'order_items.description as product_name',
                DB::raw('SUM(order_items.subtotal) as revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
            ])
            ->orderByDesc($orderColumn)
            ->limit($limit)
            ->get();

        $rank = 0;

        return $results->map(fn ($row) => [
            'rank' => ++$rank,
            'product_name' => $row->product_name,
            'revenue' => (float) $row->revenue,
            'order_count' => (int) $row->order_count,
            'quantity_sold' => (float) $row->quantity_sold,
        ])->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topCustomers(Closure $orderCondition, string $rankBy, int $limit): array
    {
        $orderColumn = match ($rankBy) {
            'order_count' => DB::raw('COUNT(orders.id)'),
            'quantity' => DB::raw('SUM(oi.quantity)'),
            default => DB::raw('SUM(orders.order_total)'),
        };

        $results = DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.buyer_user_id')
            ->leftJoin('order_items as oi', 'oi.order_id', '=', 'orders.id')
            ->where($orderCondition)
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->select([
                'users.id',
                DB::raw("users.first_name || ' ' || users.last_name as customer_name"),
                DB::raw('SUM(orders.order_total) as revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('COALESCE(SUM(oi.quantity), 0) as quantity_purchased'),
            ])
            ->orderByDesc($orderColumn)
            ->limit($limit)
            ->get();

        $rank = 0;

        return $results->map(fn ($row) => [
            'rank' => ++$rank,
            'customer_name' => $row->customer_name,
            'revenue' => (float) $row->revenue,
            'order_count' => (int) $row->order_count,
            'quantity_purchased' => (float) $row->quantity_purchased,
        ])->toArray();
    }
}
