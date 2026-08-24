<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SellerPayoutAccount;
use App\Notifications\PayoutAccountRejectedNotification;
use Illuminate\Support\Facades\DB;

final class RejectPayoutAccountAction
{
    /**
     * Mark a payout account as rejected with the given reason.
     */
    public function execute(SellerPayoutAccount $account, string $rejectionReason): SellerPayoutAccount
    {
        $account = DB::transaction(function () use ($account, $rejectionReason): SellerPayoutAccount {
            $account->update([
                'verification_status' => 'rejected',
                'rejection_reason' => $rejectionReason,
                'verified_at' => null,
                'verified_by_id' => null,
            ]);

            return $account->fresh();
        });

        $account->shop->user->notify(new PayoutAccountRejectedNotification($account));

        return $account;
    }
}
