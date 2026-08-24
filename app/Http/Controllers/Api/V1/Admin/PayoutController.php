<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\CreatePayoutBatchAction;
use App\Actions\ReleasePayoutAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\PayoutResource;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Payout;
use App\Models\PayoutSetting;
use App\Models\SellerPayoutAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PayoutController extends Controller
{
    /**
     * GET /api/v1/admin/payouts — List all payout batches.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Payout::class);

        $query = Payout::with(['shop', 'releasedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->integer('shop_id'));
        }

        $payouts = $query->latest()->cursorPaginate($request->integer('limit', 20));

        return PayoutResource::collection($payouts)->response();
    }

    /**
     * GET /api/v1/admin/payouts/eligible — List sellers with eligible orders.
     */
    public function eligible(Request $request): JsonResponse
    {
        Gate::authorize('viewEligible', Payout::class);

        $disputeHoldDays = PayoutSetting::getDisputeHoldDays();
        $minimumThreshold = PayoutSetting::getMinimumPayoutThreshold();

        // Find verified payout accounts with eligible orders
        $accounts = SellerPayoutAccount::where('verification_status', 'verified')
            ->with('shop')
            ->get();

        $eligible = [];

        foreach ($accounts as $account) {
            $eligibleOrders = Order::where('shop_id', $account->shop_id)
                ->where('status', 'completed')
                ->whereDoesntHave('payoutItem')
                ->where('completed_at', '<=', now()->subDays($disputeHoldDays))
                ->whereDoesntHave('disputes', function ($q) {
                    $q->whereIn('status', ['open', 'awaiting_return', 'return_received', 'escalated']);
                })
                ->pluck('id');

            if ($eligibleOrders->isEmpty()) {
                continue;
            }

            $totalReleasable = LedgerEntry::whereIn('order_id', $eligibleOrders)
                ->where('type', 'payout')
                ->sum('amount');

            if ((float) $totalReleasable < $minimumThreshold) {
                continue;
            }

            $eligible[] = [
                'shop_id' => $account->shop_id,
                'shop_name' => $account->shop->storefront_name,
                'eligible_orders_count' => $eligibleOrders->count(),
                'releasable_amount' => number_format((float) $totalReleasable, 2, '.', ''),
                'bank_name' => $account->bank_name,
            ];
        }

        return response()->json(['data' => $eligible]);
    }

    /**
     * POST /api/v1/admin/payouts — Create a payout batch.
     */
    public function store(Request $request, CreatePayoutBatchAction $action): JsonResponse
    {
        Gate::authorize('create', Payout::class);

        $request->validate([
            'shop_id' => 'required|integer|exists:shops,id',
            'order_ids' => 'sometimes|array',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $payout = $action->execute(
            $request->integer('shop_id'),
            $request->input('order_ids'),
        );

        return (new PayoutResource($payout->load(['shop', 'items.order'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/v1/admin/payouts/{payout} — Payout detail.
     */
    public function show(Request $request, Payout $payout): JsonResponse
    {
        Gate::authorize('view', $payout);

        $payout->load(['shop', 'releasedBy', 'items.order', 'disbursements']);

        return (new PayoutResource($payout))->response();
    }

    /**
     * POST /api/v1/admin/payouts/{payout}/release — Trigger disbursement.
     */
    public function release(Request $request, Payout $payout, ReleasePayoutAction $action): JsonResponse
    {
        Gate::authorize('release', $payout);

        $result = $action->execute($payout, $request->user());

        if ($result['success']) {
            return (new PayoutResource($result['payout']->load(['shop', 'disbursements'])))
                ->response();
        }

        return response()->json([
            'error' => [
                'code' => 'DISBURSEMENT_FAILED',
                'message' => $result['error'],
            ],
        ], 422);
    }

    /**
     * POST /api/v1/admin/payouts/{payout}/retry — Retry failed disbursement.
     */
    public function retry(Request $request, Payout $payout, ReleasePayoutAction $action): JsonResponse
    {
        Gate::authorize('retry', $payout);

        $result = $action->execute($payout, $request->user());

        if ($result['success']) {
            return (new PayoutResource($result['payout']->load(['shop', 'disbursements'])))
                ->response();
        }

        return response()->json([
            'error' => [
                'code' => 'DISBURSEMENT_FAILED',
                'message' => $result['error'],
            ],
        ], 422);
    }
}
