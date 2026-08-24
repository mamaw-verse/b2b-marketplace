<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;

final class ReviewPolicy
{
    /**
     * Anyone can view non-hidden reviews.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * User can create a review if they are the order's buyer and order is completed.
     */
    public function create(User $user, Order $order): bool
    {
        return $user->id === $order->buyer_user_id
            && $order->status === 'completed';
    }

    /**
     * Only the reviewer can update their own review.
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->reviewer_user_id;
    }

    /**
     * The shop owner of the reviewed entity can respond.
     */
    public function respond(User $user, Review $review): bool
    {
        $reviewable = $review->reviewable;

        if ($reviewable instanceof \App\Models\Shop) {
            return $user->id === $reviewable->user_id;
        }

        if ($reviewable instanceof \App\Models\Product) {
            return $user->id === $reviewable->shop->user_id;
        }

        return false;
    }

    /**
     * Any authenticated user can report a review (except the reviewer themselves).
     */
    public function report(User $user, Review $review): bool
    {
        return $user->id !== $review->reviewer_user_id;
    }

    /**
     * Only admins can hide a review.
     */
    public function hide(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Only admins can dismiss a report.
     */
    public function dismiss(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
