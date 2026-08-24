<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SellerPayoutAccount;
use App\Models\User;

final class SellerPayoutAccountPolicy
{
    /**
     * Admins may list all payout accounts (admin queue).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Owner of the shop or an admin may view a payout account.
     */
    public function view(User $user, SellerPayoutAccount $account): bool
    {
        return $user->id === $account->shop->user_id
            || $user->hasRole('admin');
    }

    /**
     * A user may create a payout account if they own an active shop.
     */
    public function create(User $user): bool
    {
        return $user->shop !== null && $user->shop->status === 'active';
    }

    /**
     * A user may update their payout account if they own the shop and it is active.
     */
    public function update(User $user, SellerPayoutAccount $account): bool
    {
        return $user->id === $account->shop->user_id
            && $account->shop->status === 'active';
    }

    /**
     * Admins may verify a payout account.
     */
    public function verify(User $user, SellerPayoutAccount $account): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Admins may reject a payout account.
     */
    public function reject(User $user, SellerPayoutAccount $account): bool
    {
        return $user->hasRole('admin');
    }
}
