<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\RejectPayoutAccountAction;
use App\Actions\VerifyPayoutAccountAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectPayoutAccountRequest;
use App\Http\Resources\SellerPayoutAccountResource;
use App\Models\SellerPayoutAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PayoutAccountController extends Controller
{
    /**
     * List all payout accounts, filterable by verification_status.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', SellerPayoutAccount::class);

        $query = SellerPayoutAccount::with('shop')
            ->when(
                $request->query('verification_status'),
                fn ($q, $status) => $q->where('verification_status', $status),
            )
            ->orderByDesc('created_at');

        $accounts = $query->paginate(20);

        return response()->json([
            'data' => SellerPayoutAccountResource::collection($accounts->items()),
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
        ]);
    }

    /**
     * Verify a payout account.
     */
    public function verify(Request $request, SellerPayoutAccount $sellerPayoutAccount): JsonResponse
    {
        Gate::authorize('verify', $sellerPayoutAccount);

        $account = app(VerifyPayoutAccountAction::class)->execute($sellerPayoutAccount, $request->user());

        return response()->json(['data' => new SellerPayoutAccountResource($account)]);
    }

    /**
     * Reject a payout account with reason.
     */
    public function reject(RejectPayoutAccountRequest $request, SellerPayoutAccount $sellerPayoutAccount): JsonResponse
    {
        Gate::authorize('reject', $sellerPayoutAccount);

        $account = app(RejectPayoutAccountAction::class)->execute(
            $sellerPayoutAccount,
            $request->validated('rejection_reason'),
        );

        return response()->json(['data' => new SellerPayoutAccountResource($account)]);
    }
}
