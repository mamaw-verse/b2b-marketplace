<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EarningsResource;
use App\Models\LedgerEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class HubEarningsController extends Controller
{
    /**
     * Seller earnings summary + transaction list.
     */
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('accessHub');

        $shop = $request->user()->shop;
        $shopId = $shop->id;

        // Summary figures
        $totalEarned = $this->getPayoutItemsTotal($shopId);
        $totalReleased = $this->getCompletedPayoutsTotal($shopId);
        $pendingBalance = $this->getPendingBalance($shopId);
        $totalFeesPaid = $this->getTotalFeesPaid($shopId);

        // Ledger entries for the seller's shop
        $query = LedgerEntry::query()
            ->join('orders', 'orders.id', '=', 'ledger_entries.order_id')
            ->where('orders.shop_id', $shopId)
            ->select('ledger_entries.*');

        if ($request->filled('from')) {
            $query->where('ledger_entries.created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->where('ledger_entries.created_at', '<=', $request->query('to').' 23:59:59');
        }

        $query->orderByDesc('ledger_entries.created_at');

        $entries = $query->cursorPaginate($request->integer('limit', 20));

        return response()->json([
            'data' => [
                'summary' => [
                    'total_earned' => number_format((float) $totalEarned, 2, '.', ''),
                    'total_released' => number_format((float) $totalReleased, 2, '.', ''),
                    'pending_balance' => number_format((float) $pendingBalance, 2, '.', ''),
                    'total_fees_paid' => number_format((float) $totalFeesPaid, 2, '.', ''),
                ],
                'transactions' => EarningsResource::collection($entries),
            ],
            'meta' => [
                'next_cursor' => $entries->nextCursor()?->encode(),
                'prev_cursor' => $entries->previousCursor()?->encode(),
                'per_page' => $entries->perPage(),
            ],
        ]);
    }

    /**
     * Sum of payout_items.net_amount for completed payouts of this shop.
     */
    private function getPayoutItemsTotal(int $shopId): float
    {
        if (! config('marketplace.features.payouts_enabled')) {
            return 0.00;
        }

        try {
            return (float) DB::table('payout_items')
                ->join('payouts', 'payouts.id', '=', 'payout_items.payout_id')
                ->where('payouts.shop_id', $shopId)
                ->where('payouts.status', 'completed')
                ->sum('payout_items.net_amount');
        } catch (Throwable) {
            return 0.00;
        }
    }

    /**
     * Sum of payouts.net_amount WHERE status = 'completed' for this shop.
     */
    private function getCompletedPayoutsTotal(int $shopId): float
    {
        if (! config('marketplace.features.payouts_enabled')) {
            return 0.00;
        }

        try {
            return (float) DB::table('payouts')
                ->where('shop_id', $shopId)
                ->where('status', 'completed')
                ->sum('net_amount');
        } catch (Throwable) {
            return 0.00;
        }
    }

    /**
     * Sum of ledger_entries WHERE type='escrow_hold' for completed orders
     * that don't yet have a payout_items row.
     */
    private function getPendingBalance(int $shopId): float
    {
        $query = DB::table('ledger_entries')
            ->join('orders', 'orders.id', '=', 'ledger_entries.order_id')
            ->where('orders.shop_id', $shopId)
            ->where('orders.status', 'completed')
            ->where('ledger_entries.type', 'escrow_hold');

        if (config('marketplace.features.payouts_enabled')) {
            try {
                $query->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('payout_items')
                        ->whereColumn('payout_items.order_id', 'ledger_entries.order_id');
                });
            } catch (Throwable) {
                // If payout_items table doesn't exist yet, just skip the exclusion
            }
        }

        return (float) $query->sum('ledger_entries.amount');
    }

    /**
     * Sum of payout_items fees for completed payouts.
     */
    private function getTotalFeesPaid(int $shopId): float
    {
        if (! config('marketplace.features.payouts_enabled')) {
            return 0.00;
        }

        try {
            return (float) DB::table('payout_items')
                ->join('payouts', 'payouts.id', '=', 'payout_items.payout_id')
                ->where('payouts.shop_id', $shopId)
                ->where('payouts.status', 'completed')
                ->sum(DB::raw('payout_items.transaction_fee + payout_items.commission_fee'));
        } catch (Throwable) {
            return 0.00;
        }
    }
}
