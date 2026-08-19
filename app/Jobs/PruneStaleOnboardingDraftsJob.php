<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ShopOnboarding;
use App\Notifications\StaleOnboardingReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

final class PruneStaleOnboardingDraftsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Find stale pending onboardings (never submitted, inactive >30 days) and send reminders.
     */
    public function handle(): void
    {
        $cutoff = Carbon::now()->subDays(30);

        ShopOnboarding::query()
            ->where('status', 'pending')
            ->whereNull('submitted_at')
            ->where('updated_at', '<', $cutoff)
            ->with('user')
            ->chunk(200, function ($onboardings) {
                foreach ($onboardings as $onboarding) {
                    $user = $onboarding->user;
                    if ($user) {
                        $user->notify(new StaleOnboardingReminderNotification($onboarding));
                    }
                }
            });
    }
}
