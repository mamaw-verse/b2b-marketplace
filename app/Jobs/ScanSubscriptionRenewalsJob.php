<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Subscription;
use App\Notifications\SubscriptionRenewalReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

final class ScanSubscriptionRenewalsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Reminder thresholds in descending order.
     *
     * @var array<int, int>
     */
    private const array THRESHOLDS = [30, 7, 0];

    public function handle(): void
    {
        $today = Carbon::today();

        Subscription::query()
            ->whereIn('status', ['trialing', 'active'])
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<=', $today->copy()->addDays(self::THRESHOLDS[0]))
            ->with(['shop.user', 'plan'])
            ->chunk(200, function ($subscriptions) use ($today): void {
                foreach ($subscriptions as $subscription) {
                    $this->processSubscription($subscription, $today);
                }
            });
    }

    private function processSubscription(Subscription $subscription, Carbon $today): void
    {
        $daysUntilRenewal = (int) $today->diffInDays($subscription->current_period_end, false);
        $currentThreshold = $this->determineThreshold($daysUntilRenewal);

        if ($currentThreshold === null) {
            return;
        }

        if (
            $subscription->last_renewal_alert_threshold_days !== null
            && $subscription->last_renewal_alert_threshold_days <= $currentThreshold
        ) {
            return;
        }

        $this->transitionStatus($subscription, $daysUntilRenewal);

        $user = $subscription->shop->user;
        if ($user) {
            $user->notify(new SubscriptionRenewalReminderNotification(
                subscription: $subscription,
                daysUntilRenewal: $daysUntilRenewal,
                thresholdDays: $currentThreshold,
            ));
        }

        $subscription->update([
            'last_renewal_alert_threshold_days' => $currentThreshold,
        ]);
    }

    private function determineThreshold(int $daysUntilRenewal): ?int
    {
        foreach (array_reverse(self::THRESHOLDS) as $threshold) {
            if ($daysUntilRenewal <= $threshold) {
                return $threshold;
            }
        }

        return null;
    }

    private function transitionStatus(Subscription $subscription, int $daysUntilRenewal): void
    {
        if ($daysUntilRenewal <= 0 && $subscription->status !== 'lapsed') {
            $subscription->update(['status' => 'lapsed']);
        }
    }
}
