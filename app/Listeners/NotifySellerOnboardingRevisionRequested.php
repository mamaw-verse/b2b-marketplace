<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OnboardingStatusChanged;
use App\Notifications\OnboardingRevisionRequestedNotification;

final class NotifySellerOnboardingRevisionRequested
{
    public function handle(OnboardingStatusChanged $event): void
    {
        if ($event->onboarding->status !== 'requires_revision') {
            return;
        }

        $seller = $event->onboarding->user;

        if ($seller) {
            $seller->notify(new OnboardingRevisionRequestedNotification($event->onboarding));
        }
    }
}
