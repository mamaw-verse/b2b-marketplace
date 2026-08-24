<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SellerPayoutAccount;
use App\Models\User;
use App\Notifications\PayoutAccountVerifiedNotification;
use Illuminate\Support\Facades\DB;

final class VerifyPayoutAccountAction
{
    /**
     * Mark a payout account as verified by the given admin.
     */
    public function execute(SellerPayoutAccount $account, User $admin): SellerPayoutAccount
    {
        $account = DB::transaction(function () use ($account, $admin): SellerPayoutAccount {
            $account->update([
                'verification_status' => 'verified',
                'verified_at' => now(),
                'verified_by_id' => $admin->id,
                'rejection_reason' => null,
            ]);

            return $account->fresh();
        });

        $account->shop->user->notify(new PayoutAccountVerifiedNotification($account));

        return $account;
    }
}
