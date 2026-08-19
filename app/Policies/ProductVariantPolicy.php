<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductVariant;
use App\Models\User;

/**
 * Ownership-based policy for product variants.
 * A user may only view/update/delete variants belonging to their own shop's products.
 */
final class ProductVariantPolicy
{
    /**
     * Determine whether the user can view the variant.
     */
    public function view(User $user, ProductVariant $productVariant): bool
    {
        return $user->id === $productVariant->product->shop->user_id;
    }

    /**
     * Determine whether the user can update the variant.
     */
    public function update(User $user, ProductVariant $productVariant): bool
    {
        return $user->id === $productVariant->product->shop->user_id;
    }

    /**
     * Determine whether the user can delete the variant.
     */
    public function delete(User $user, ProductVariant $productVariant): bool
    {
        return $user->id === $productVariant->product->shop->user_id;
    }
}
