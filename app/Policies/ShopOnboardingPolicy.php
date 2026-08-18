<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ShopOnboarding;
use App\Models\User;

/**
 * Ownership-based policy for shop onboardings.
 * A user may only view/update their own onboarding record.
 */
final class ShopOnboardingPolicy
{
    /**
     * Determine whether the user can view the onboarding.
     */
    public function view(User $user, ShopOnboarding $shopOnboarding): bool
    {
        return $user->id === $shopOnboarding->user_id;
    }

    /**
     * Determine whether the user can update the onboarding.
     */
    public function update(User $user, ShopOnboarding $shopOnboarding): bool
    {
        return $user->id === $shopOnboarding->user_id;
    }

    /**
     * Determine whether the user can delete the onboarding.
     */
    public function delete(User $user, ShopOnboarding $shopOnboarding): bool
    {
        return $user->id === $shopOnboarding->user_id;
    }
}
