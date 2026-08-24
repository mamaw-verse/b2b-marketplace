<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payout;
use App\Models\User;

final class PayoutPolicy
{
    /**
     * Admins may list all payout batches.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Admin or the seller who owns the shop may view a payout.
     */
    public function view(User $user, Payout $payout): bool
    {
        return $user->hasRole('admin')
            || ($user->shop && $user->shop->id === $payout->shop_id);
    }

    /**
     * Only admins may create payout batches.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Admin with payout.release permission may release a payout.
     */
    public function release(User $user, Payout $payout): bool
    {
        return $user->hasRole('admin') && $user->hasPermissionTo('payout.release');
    }

    /**
     * Admin with payout.release permission may retry a failed payout.
     */
    public function retry(User $user, Payout $payout): bool
    {
        return $user->hasRole('admin') && $user->hasPermissionTo('payout.release');
    }

    /**
     * Admins may view eligible sellers/orders.
     */
    public function viewEligible(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
