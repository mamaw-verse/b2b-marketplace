<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OnboardingStatusChanged;
use App\Notifications\OnboardingApprovedNotification;

final class NotifySellerOnboardingApproved
{
    public function handle(OnboardingStatusChanged $event): void
    {
        if ($event->onboarding->status !== 'approved') {
            return;
        }

        $seller = $event->onboarding->user;

        if ($seller) {
            $seller->notify(new OnboardingApprovedNotification($event->onboarding));
        }
    }
}
