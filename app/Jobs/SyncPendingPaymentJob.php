<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\SubscriptionStatusChanged;
use App\Models\PaymentAttempt;
use App\Models\Subscription;
use App\Notifications\PaymentStuckAdminNotification;
use App\Services\DltPaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as QueueableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

/**
 * Polls DLT /sync for a PENDING subscription payment attempt using
 * exponential backoff. Dispatched after the payor's browser returns to
 * success_url and the first /sync call still returns PENDING.
 *
 * Retry schedule (sync_attempts → delay before next dispatch):
 *   1 → 1 min
 *   2 → 5 min
 *   3 → 15 min
 *   4 → 30 min
 *   5 → 60 min (give-up after this attempt)
 *
 * On PAID  → activates the subscription.
 * On REJECTED → cancels the subscription.
 * On PENDING after 5 attempts → emails admin, leaves attempt in pending state
 *   for manual reconciliation.
 * On /sync throws (503/connection) → treat as PENDING, increment counter,
 *   re-schedule if retries remain, otherwise email admin.
 */
final class SyncPendingPaymentJob implements ShouldQueue
{
    use QueueableTrait;

    /** @var array<int, int> Delay in minutes indexed by attempt number (1-based) */
    private const array BACKOFF_MINUTES = [
        1 => 1,
        2 => 5,
        3 => 15,
        4 => 30,
        5 => 60,
    ];

    private const int MAX_SYNC_ATTEMPTS = 5;

    public function __construct(
        public readonly int $paymentAttemptId,
    ) {}

    public function handle(DltPaymentService $dlt): void
    {
        $attempt = PaymentAttempt::with('subscription.plan')->find($this->paymentAttemptId);

        if ($attempt === null) {
            Log::warning('SyncPendingPaymentJob: PaymentAttempt not found', [
                'payment_attempt_id' => $this->paymentAttemptId,
            ]);

            return;
        }

        // Already settled — nothing to do.
        if (in_array($attempt->status, ['succeeded', 'failed'], true)) {
            return;
        }

        $subscription = $attempt->subscription;

        if ($subscription === null) {
            Log::error('SyncPendingPaymentJob: Subscription not found for attempt', [
                'payment_attempt_id' => $attempt->id,
            ]);

            return;
        }

        $syncAttemptNumber = $attempt->sync_attempts + 1;

        // Call /sync
        $paymentStatus = $this->callSync($dlt, $attempt);

        // Update sync counter and next_sync_at regardless of outcome
        $attempt->increment('sync_attempts');

        if ($paymentStatus === 'PAID') {
            $this->activateSubscription($attempt, $subscription);

            return;
        }

        if ($paymentStatus === 'REJECTED') {
            $this->rejectSubscription($attempt, $subscription);

            return;
        }

        // Still PENDING (or unresolvable 503)
        if ($syncAttemptNumber >= self::MAX_SYNC_ATTEMPTS) {
            $this->giveUp($attempt, $subscription);

            return;
        }

        // Schedule next retry with exponential backoff
        $delayMinutes = self::BACKOFF_MINUTES[$syncAttemptNumber + 1] ?? 60;
        $nextSyncAt = now()->addMinutes($delayMinutes);

        $attempt->update(['next_sync_at' => $nextSyncAt]);

        Log::info('SyncPendingPaymentJob: Still PENDING, re-scheduling', [
            'payment_attempt_id' => $attempt->id,
            'sync_attempt_number' => $syncAttemptNumber,
            'next_sync_at' => $nextSyncAt->toIso8601String(),
            'delay_minutes' => $delayMinutes,
        ]);

        self::dispatch($attempt->id)->delay($nextSyncAt);
    }

    /**
     * Call DLT /sync and return the payment_status string.
     * Returns 'PENDING' on any exception (503, connection error) so callers
     * treat it the same as an unresolved status.
     */
    private function callSync(DltPaymentService $dlt, PaymentAttempt $attempt): string
    {
        try {
            $result = $dlt->syncPaymentStatus($attempt->dlt_merchant_transaction_id);
            $status = $result['payment_status'] ?? 'PENDING';

            $attempt->update(['dlt_payment_status' => $status]);

            return $status;
        } catch (RuntimeException $e) {
            Log::error('SyncPendingPaymentJob: /sync call failed', [
                'payment_attempt_id' => $attempt->id,
                'merchant_transaction_id' => $attempt->dlt_merchant_transaction_id,
                'error' => $e->getMessage(),
            ]);

            return 'PENDING';
        }
    }

    private function activateSubscription(PaymentAttempt $attempt, Subscription $subscription): void
    {
        $previousSubscriptions = collect();

        DB::transaction(function () use ($attempt, $subscription, &$previousSubscriptions): void {
            $previousSubscriptions = Subscription::query()
                ->where('shop_id', $subscription->shop_id)
                ->whereIn('status', ['active', 'trialing'])
                ->where('id', '<>', $subscription->id)
                ->get();

            $periodStart = now();
            $previousEnd = $previousSubscriptions->max('current_period_end');
            if ($previousEnd && $previousEnd->isFuture()) {
                $periodStart = $previousEnd;
            }

            $previousSubscriptions->each->update(['status' => 'lapsed']);

            $subscription->update([
                'status' => 'active',
                'trial_ends_at' => null,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodStart->copy()->addYear(),
                'last_renewal_alert_threshold_days' => null,
            ]);

            $attempt->update([
                'status' => 'succeeded',
                'dlt_payment_status' => 'PAID',
                'failed_at' => null,
                'succeeded_at' => now(),
                'next_sync_at' => null,
            ]);
        });

        foreach ($previousSubscriptions as $previousSubscription) {
            event(new SubscriptionStatusChanged($previousSubscription->fresh()));
        }
        event(new SubscriptionStatusChanged($subscription->fresh()));

        Log::info('SyncPendingPaymentJob: Subscription activated via /sync', [
            'subscription_id' => $subscription->id,
            'payment_attempt_id' => $attempt->id,
        ]);
    }

    private function rejectSubscription(PaymentAttempt $attempt, Subscription $subscription): void
    {
        DB::transaction(function () use ($attempt, $subscription): void {
            $subscription->update(['status' => 'cancelled']);

            $attempt->update([
                'status' => 'failed',
                'dlt_payment_status' => 'REJECTED',
                'failed_at' => now(),
                'next_sync_at' => null,
            ]);
        });

        event(new SubscriptionStatusChanged($subscription->fresh()));

        Log::info('SyncPendingPaymentJob: Subscription rejected via /sync', [
            'subscription_id' => $subscription->id,
            'payment_attempt_id' => $attempt->id,
        ]);
    }

    private function giveUp(PaymentAttempt $attempt, Subscription $subscription): void
    {
        $attempt->update(['next_sync_at' => null]);

        Log::error('SyncPendingPaymentJob: Giving up after max retries — emailing admin', [
            'payment_attempt_id' => $attempt->id,
            'subscription_id' => $subscription->id,
            'sync_attempts' => $attempt->sync_attempts,
        ]);

        Notification::route('mail', config('mail.admin_address', config('mail.from.address')))
            ->notify(new PaymentStuckAdminNotification(
                attempt: $attempt,
                subscription: $subscription,
                trigger: 'SyncPendingPaymentJob gave up after '.self::MAX_SYNC_ATTEMPTS.' sync attempts',
            ));
    }
}
