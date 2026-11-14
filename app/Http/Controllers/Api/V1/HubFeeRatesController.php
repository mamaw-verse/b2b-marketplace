<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PayoutSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class HubFeeRatesController extends Controller
{
    /**
     * GET /api/v1/hub/fee-rates — Returns transaction fee and commission rate for sellers.
     */
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('manageShop');
        $shop = $request->user()->shop;

        if (! $shop || $shop->status !== 'active') {
            abort(403, 'You must have an active shop to view fee rates.');
        }

        return response()->json([
            'data' => [
                'transaction_fee_amount' => PayoutSetting::get('transaction_fee_amount'),
                'commission_fee_rate' => PayoutSetting::get('commission_fee_rate'),
            ],
        ]);
    }
}
