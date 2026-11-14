<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Ownership-based policy for products.
 * A user may only view/update/delete products belonging to their own shop.
 */
final class ProductPolicy
{
    /**
     * Determine whether the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        return $product->shop?->isManagedBy($user) ?? false;
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        return $product->shop?->isManagedBy($user) ?? false;
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $product->shop?->isManagedBy($user) ?? false;
    }

    /**
     * Determine whether the user can publish the product.
     */
    public function publish(User $user, Product $product): bool
    {
        if (! ($product->shop?->isManagedBy($user) ?? false)) {
            return false;
        }

        $shop = $product->shop;

        if ($shop->status !== 'active') {
            return false;
        }

        if (! config('marketplace.features.subscriptions_enabled')) {
            return true;
        }

        return $shop->hasActiveSubscription();
    }
}
