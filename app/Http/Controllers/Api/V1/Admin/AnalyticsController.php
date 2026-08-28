<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AnalyticsController extends Controller
{
    /**
     * GET /api/v1/admin/analytics — Platform-wide analytics metrics.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $from = $request->date('from') ?? now()->subDays(30);
        $to = $request->date('to') ?? now();

        // GMV: sum of order_total for completed/refunded orders in window
        $gmv = (float) Order::whereIn('status', ['completed', 'refunded'])
            ->whereBetween('paid_at', [$from, $to])
            ->sum('order_total');

        // Total orders in window
        $totalOrders = Order::whereIn('status', ['completed', 'refunded'])
            ->whereBetween('paid_at', [$from, $to])
            ->count();

        // Payout metrics (graceful fallback)
        $totalPayoutsDisbursed = 0.0;
        $totalTransactionFeesCollected = 0.0;
        $totalCommissionFeesCollected = 0.0;

        if (config('marketplace.features.payouts_enabled')) {
            $totalPayoutsDisbursed = (float) DB::table('payouts')
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$from, $to])
                ->sum('net_amount');

            $totalTransactionFeesCollected = (float) DB::table('payouts')
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$from, $to])
                ->sum('total_transaction_fees');

            $totalCommissionFeesCollected = (float) DB::table('payouts')
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$from, $to])
                ->sum('total_commission_fees');
        }

        // Platform average rating (only shops with reviews)
        $platformAverageRating = (float) Shop::where('total_reviews', '>', 0)
            ->avg('average_rating');

        // Total active sellers
        $totalActiveSellers = Shop::where('status', 'active')->count();

        return response()->json([
            'data' => [
                'gmv' => $gmv,
                'total_orders' => $totalOrders,
                'total_payouts_disbursed' => $totalPayoutsDisbursed,
                'total_transaction_fees_collected' => $totalTransactionFeesCollected,
                'total_commission_fees_collected' => $totalCommissionFeesCollected,
                'platform_average_rating' => round($platformAverageRating, 2),
                'total_active_sellers' => $totalActiveSellers,
            ],
        ]);
    }
}
