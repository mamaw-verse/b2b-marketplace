<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class SellerHubController extends Controller
{
    public function __construct()
    {
        // All methods require the accessHub gate
    }

    /**
     * Dashboard to-do widget counts.
     */
    public function dashboard(Request $request): JsonResponse
    {
        Gate::authorize('accessHub');

        $shop = $request->user()->shop;

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

        return response()->json([
            'data' => [
                'new_rfqs' => $newRfqs,
                'orders_in_progress' => $ordersInProgress,
                'unfulfilled_orders' => $unfulfilledOrders,
                'draft_orders' => $draftOrders,
                'open_disputes' => $openDisputes,
                'pending_quotations' => $pendingQuotations,
            ],
        ]);
    }

    /**
     * Customers list (buyers who have transacted with this shop).
     */
    public function customers(Request $request): JsonResponse
    {
        Gate::authorize('accessHub');

        $shop = $request->user()->shop;

        // Buyers with orders or awarded quotations for this shop
        $customers = User::where(function ($query) use ($shop) {
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
            ->cursorPaginate($request->integer('limit', 20));

        return response()->json([
            'data' => $customers->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->first_name.' '.$user->last_name,
                'email' => $user->email,
                'order_count' => $user->orders_count,
                'last_order_date' => $user->orders_max_created_at,
            ]),
            'meta' => [
                'next_cursor' => $customers->nextCursor()?->encode(),
                'prev_cursor' => $customers->previousCursor()?->encode(),
                'per_page' => $customers->perPage(),
            ],
        ]);
    }

    /**
     * Sales & fulfilment reports over a time window.
     */
    public function reports(Request $request): JsonResponse
    {
        Gate::authorize('accessHub');

        $shop = $request->user()->shop;
        $windowDays = $request->integer('window', 30);
        $from = $request->date('from') ?? now()->subDays($windowDays);
        $to = $from->copy()->addDays($windowDays);

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

        // Customer counts
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

        // Items purchased
        $itemsPurchased = (int) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.shop_id', $shop->id)
            ->whereIn('orders.status', ['completed', 'refunded'])
            ->whereBetween('orders.paid_at', [$from, $to])
            ->sum('order_items.quantity');

        return response()->json([
            'data' => [
                'window_days' => $windowDays,
                'new_customers' => $newCustomers,
                'returning_customers' => $returningCustomers,
                'orders_placed' => $ordersPlaced,
                'items_purchased' => $itemsPurchased,
                'gross_sales' => $grossSales,
                'total_refunds' => $totalRefunds,
                'platform_commission' => $platformCommission,
                'net_sales' => $netSales,
            ],
        ]);
    }

    /**
     * Leaderboards — top products / customers / categories.
     */
    public function leaderboards(Request $request): JsonResponse
    {
        Gate::authorize('accessHub');

        $shop = $request->user()->shop;
        $type = $request->query('type', 'products');
        $rankBy = $request->query('rank_by', 'revenue');
        $limit = $request->integer('limit', 10);
        $windowDays = $request->integer('window', 0); // 0 = all time

        $orderCondition = function ($query) use ($shop, $windowDays) {
            $query->where('orders.shop_id', $shop->id)
                ->whereIn('orders.status', ['completed', 'refunded']);
            if ($windowDays > 0) {
                $query->where('orders.paid_at', '>=', now()->subDays($windowDays));
            }
        };

        $data = match ($type) {
            'products' => $this->topProducts($orderCondition, $rankBy, $limit),
            'customers' => $this->topCustomers($orderCondition, $rankBy, $limit),
            default => [],
        };

        return response()->json(['data' => $data]);
    }

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
