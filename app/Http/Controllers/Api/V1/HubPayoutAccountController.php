<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertPayoutAccountRequest;
use App\Http\Resources\SellerPayoutAccountResource;
use App\Models\User;
use App\Notifications\PayoutAccountPendingNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

final class HubPayoutAccountController extends Controller
{
    /**
     * Show the seller's payout account.
     */
    public function show(Request $request): JsonResponse
    {
        Gate::authorize('manageShop');
        $shop = $request->user()->shop;

        if (! $shop) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No shop found for this user.',
                ],
            ], 404);
        }

        $account = $shop->payoutAccount;

        if (! $account) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Payout account not found.',
                ],
            ], 404);
        }

        return response()->json(['data' => new SellerPayoutAccountResource($account)]);
    }

    /**
     * Create or update the seller's payout account.
     * Resets verification_status to 'pending' on update.
     */
    public function upsert(UpsertPayoutAccountRequest $request): JsonResponse
    {
        Gate::authorize('manageShop');
        $shop = $request->user()->shop;

        $account = $shop->payoutAccount()->updateOrCreate(
            ['shop_id' => $shop->id],
            [
                ...$request->validated(),
                'verification_status' => 'pending',
                'verified_at' => null,
                'verified_by_id' => null,
                'rejection_reason' => null,
            ],
        );

        $account->refresh();

        // Notify all admins about the pending payout account
        $admins = User::role('admin')->get();
        Notification::send($admins, new PayoutAccountPendingNotification($account));

        return response()->json(['data' => new SellerPayoutAccountResource($account)]);
    }
}
