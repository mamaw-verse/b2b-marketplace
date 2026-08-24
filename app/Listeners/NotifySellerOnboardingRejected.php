<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OnboardingStatusChanged;
use App\Notifications\OnboardingRejectedNotification;

final class NotifySellerOnboardingRejected
{
    public function handle(OnboardingStatusChanged $event): void
    {
        if ($event->onboarding->status !== 'rejected') {
            return;
        }

        $seller = $event->onboarding->user;

        if ($seller) {
            $seller->notify(new OnboardingRejectedNotification($event->onboarding));
        }
    }
}
