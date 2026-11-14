<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;

final class ReviewPolicy
{
    /**
     * Anyone can view non-hidden reviews.
     */
    public function viewAny(?User $user): bool
    {
        if (! config('marketplace.features.reviews_enabled')) {
            return false;
        }

        return true;
    }

    /**
     * User can create a review if they are the order's buyer and order is completed.
     */
    public function create(User $user, Order $order): bool
    {
        if (! config('marketplace.features.reviews_enabled')) {
            return false;
        }

        return $user->id === $order->buyer_user_id
            && $order->status === 'completed';
    }

    /**
     * Only the reviewer can update their own review.
     */
    public function update(User $user, Review $review): bool
    {
        if (! config('marketplace.features.reviews_enabled')) {
            return false;
        }

        return $user->id === $review->reviewer_user_id;
    }

    /**
     * The shop owner of the reviewed entity can respond.
     */
    public function respond(User $user, Review $review): bool
    {
        if (! config('marketplace.features.reviews_enabled')) {
            return false;
        }

        $reviewable = $review->reviewable;

        if ($reviewable instanceof Shop) {
            return $reviewable->isManagedBy($user);
        }

        if ($reviewable instanceof Product) {
            return $reviewable->shop?->isManagedBy($user) ?? false;
        }

        return false;
    }

    /**
     * Any authenticated user can report a review (except the reviewer themselves).
     */
    public function report(User $user, Review $review): bool
    {
        if (! config('marketplace.features.reviews_enabled')) {
            return false;
        }

        return $user->id !== $review->reviewer_user_id;
    }

    /**
     * Only admins can hide a review.
     */
    public function hide(User $user): bool
    {
        if (! config('marketplace.features.reviews_enabled')) {
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * Only admins can dismiss a report.
     */
    public function dismiss(User $user): bool
    {
        if (! config('marketplace.features.reviews_enabled')) {
            return false;
        }

        return $user->hasRole('admin');
    }
}
