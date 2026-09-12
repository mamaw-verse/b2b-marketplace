<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\SubscriptionStatusChanged;
use App\Jobs\SyncPendingPaymentJob;
use App\Models\PaymentAttempt;
use App\Models\Subscription;
use App\Services\DltPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class SubscriptionPaymentCallbackController extends Controller
{
    /**
     * DLT redirects here after the payor completes payment on the hosted checkout.
     *
     * success_url — DLT signals the payment went through; call /sync to confirm.
     * failure_url — DLT signals a definitive failure; reject immediately without /sync.
     */
    public function success(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->authorizeOwner($request, $subscription);

        $attempt = $this->resolveAttempt($request, $subscription);

        // Already settled — idempotent return.
        if ($attempt->status === 'succeeded') {
            return redirect()->route('hub.subscription')
                ->with('message', __('Your subscription is now active.'));
        }

        if ($attempt->status === 'failed') {
            return redirect()->route('hub.subscription')
                ->with('message', __('The subscription payment was not completed.'));
        }

        // Call /sync to get the authoritative status.
        $paymentStatus = $this->syncPaymentStatus($attempt);

        if ($paymentStatus === 'PAID') {
            return $this->activateSubscription($attempt, $subscription);
        }

        if ($paymentStatus === 'REJECTED') {
            return $this->rejectSubscription($attempt, $subscription);
        }

        // PENDING — dispatch background job to poll with exponential backoff.
        // The user is redirected to the payment page which shows a 'confirming' step
        // (the Livewire component sets this when it sees confirmingAttemptId is set
        // via the session flash below).
        Log::info('DLT: Subscription payment PENDING after success_url /sync — dispatching SyncPendingPaymentJob', [
            'subscription_id' => $subscription->id,
            'attempt_id' => $attempt->id,
            'merchant_transaction_id' => $attempt->dlt_merchant_transaction_id,
        ]);

        SyncPendingPaymentJob::dispatch($attempt->id)->delay(now()->addMinute());

        // Store the next_sync_at for the first retry.
        $attempt->update(['next_sync_at' => now()->addMinute()]);

        return redirect()
            ->route('hub.subscription.pay', ['subscription' => $subscription->id])
            ->with('confirming_attempt_id', $attempt->id);
    }

    /**
     * failure_url — DLT has already determined the payment failed.
     * Skip /sync entirely; reject immediately.
     */
    public function failure(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->authorizeOwner($request, $subscription);

        $attempt = $this->resolveAttempt($request, $subscription);

        // Already settled — idempotent return.
        if (in_array($attempt->status, ['succeeded', 'failed'], true)) {
            return redirect()->route('hub.subscription')
                ->with('message', __('The subscription payment was not completed.'));
        }

        return $this->rejectSubscription($attempt, $subscription);
    }

    private function resolveAttempt(Request $request, Subscription $subscription): PaymentAttempt
    {
        $attempt = PaymentAttempt::findOrFail($request->query('attempt'));

        if ($attempt->subscription_id !== $subscription->id) {
            abort(403);
        }

        return $attempt;
    }

    private function syncPaymentStatus(PaymentAttempt $attempt): string
    {
        try {
            $dlt = app(DltPaymentService::class);
            $result = $dlt->syncPaymentStatus($attempt->dlt_merchant_transaction_id);
            $status = $result['payment_status'] ?? 'PENDING';

            $attempt->update(['dlt_payment_status' => $status]);

            return $status;
        } catch (RuntimeException $e) {
            Log::error('DLT: /sync call failed on subscription success_url callback', [
                'attempt_id' => $attempt->id,
                'merchant_transaction_id' => $attempt->dlt_merchant_transaction_id,
                'error' => $e->getMessage(),
            ]);

            // Cannot determine status — treat as PENDING; background job will retry.
            return 'PENDING';
        }
    }

    private function activateSubscription(PaymentAttempt $attempt, Subscription $subscription): RedirectResponse
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
            ]);
        });

        foreach ($previousSubscriptions as $previousSubscription) {
            event(new SubscriptionStatusChanged($previousSubscription->fresh()));
        }
        event(new SubscriptionStatusChanged($subscription->fresh()));

        return redirect()->route('hub.subscription')
            ->with('message', __('Your subscription is now active.'));
    }

    private function rejectSubscription(PaymentAttempt $attempt, Subscription $subscription): RedirectResponse
    {
        DB::transaction(function () use ($attempt, $subscription): void {
            $subscription->update(['status' => 'cancelled']);

            $attempt->update([
                'status' => 'failed',
                'dlt_payment_status' => 'REJECTED',
                'failed_at' => now(),
            ]);
        });

        event(new SubscriptionStatusChanged($subscription->fresh()));

        return redirect()->route('hub.subscription')
            ->with('message', __('The subscription payment was not completed.'));
    }

    private function authorizeOwner(Request $request, Subscription $subscription): void
    {
        if ($request->user()?->shop?->id !== $subscription->shop_id) {
            abort(403);
        }
    }
}
