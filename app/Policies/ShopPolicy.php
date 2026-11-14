<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

/**
 * Ownership-based policy for shops.
 * A user may only view/update/delete their own shop.
 */
final class ShopPolicy
{
    /**
     * Determine whether the user can view the shop.
     */
    public function view(User $user, Shop $shop): bool
    {
        return $user->id === $shop->user_id;
    }

    /**
     * Determine whether the user can update the shop.
     */
    public function update(User $user, Shop $shop): bool
    {
        return $user->id === $shop->user_id;
    }

    /**
     * Determine whether the user can delete the shop.
     */
    public function delete(User $user, Shop $shop): bool
    {
        return $user->id === $shop->user_id;
    }

    /**
     * Determine whether the user can create products in this shop.
     */
    public function createProduct(User $user, Shop $shop): bool
    {
        return $shop->isManagedBy($user) && $shop->status === 'active';
    }

    public function manageStaff(User $user, Shop $shop): bool
    {
        return $user->id === $shop->user_id;
    }
}
