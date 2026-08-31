<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayoutResource;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Payout;
use App\Models\PayoutSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class HubPayoutController extends Controller
{
    /**
     * GET /api/v1/hub/payouts — Seller's payout history.
     */
    public function index(Request $request): JsonResponse
    {
        $shop = $request->user()->shop;

        if (! $shop) {
            return response()->json(['data' => []], 200);
        }

        $payouts = Payout::where('shop_id', $shop->id)
            ->latest()
            ->cursorPaginate($request->integer('limit', 20));

        return PayoutResource::collection($payouts)->response();
    }

    /**
     * GET /api/v1/hub/payouts/{payout} — Seller's payout detail.
     */
    public function show(Request $request, Payout $payout): JsonResponse
    {
        Gate::authorize('view', $payout);

        $payout->load(['items.order', 'disbursements']);

        return (new PayoutResource($payout))->response();
    }

    /**
     * GET /api/v1/hub/payouts/balance — Seller's releasable balance + eligible orders.
     */
    public function balance(Request $request): JsonResponse
    {
        $shop = $request->user()->shop;

        if (! $shop) {
            return response()->json([
                'data' => [
                    'releasable_amount' => '0.00',
                    'eligible_orders_count' => 0,
                    'minimum_threshold' => number_format(PayoutSetting::getMinimumPayoutThreshold(), 2, '.', ''),
                ],
            ]);
        }

        $disputeHoldDays = PayoutSetting::getDisputeHoldDays();
        $transactionFeeAmount = PayoutSetting::getTransactionFeeAmount();
        $commissionFeeRate = PayoutSetting::getCommissionFeeRate();

        $eligibleOrders = Order::where('shop_id', $shop->id)
            ->where('status', 'completed')
            ->whereDoesntHave('payoutItem')
            ->where('completed_at', '<=', now()->subDays($disputeHoldDays))
            ->whereDoesntHave('disputes', function ($q) {
                $q->whereIn('status', ['open', 'awaiting_return', 'return_received', 'escalated']);
            })
            ->pluck('id');

        // Net releasable = sum over each order's escrow_hold (gross) entry minus
        // the flat transaction fee and percentage commission — the same basis as
        // CreatePayoutBatchAction. Do NOT sum ledger_entries.type = 'payout'
        // (notional, already net of reporting commission) per requirements §3.4.
        $netReleasable = 0.0;
        $releasableOrderCount = 0;

        if ($eligibleOrders->isNotEmpty()) {
            $escrowEntries = LedgerEntry::whereIn('order_id', $eligibleOrders)
                ->where('type', 'escrow_hold')
                ->get();

            foreach ($escrowEntries as $entry) {
                $escrowed = (float) $entry->amount;
                $commissionFee = round($escrowed * $commissionFeeRate, 2, PHP_ROUND_HALF_UP);
                $net = round($escrowed - $transactionFeeAmount - $commissionFee, 2);

                if ($net <= 0) {
                    continue;
                }

                $netReleasable = round($netReleasable + $net, 2);
                $releasableOrderCount++;
            }
        }

        return response()->json([
            'data' => [
                'releasable_amount' => number_format($netReleasable, 2, '.', ''),
                'eligible_orders_count' => $releasableOrderCount,
                'minimum_threshold' => number_format(PayoutSetting::getMinimumPayoutThreshold(), 2, '.', ''),
            ],
        ]);
    }
}
