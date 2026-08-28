<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
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
    public function reports(Shop $shop, int $windowDays, \Carbon\CarbonInterface $from, \Carbon\CarbonInterface $to): array
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
