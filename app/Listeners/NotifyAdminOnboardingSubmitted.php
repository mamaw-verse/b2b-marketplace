<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OnboardingSubmitted;
use App\Models\User;
use App\Notifications\OnboardingSubmittedNotification;

final class NotifyAdminOnboardingSubmitted
{
    public function handle(OnboardingSubmitted $event): void
    {
        User::role('admin')->get()->each(fn (User $admin) => $admin->notify(
            new OnboardingSubmittedNotification($event->onboarding),
        ));
    }
}
