<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\SubscriptionStatusChanged;
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
     * Both success_url and failure_url land here — we call /sync for the
     * authoritative status rather than trusting which URL was hit.
     */
    public function success(Request $request, Subscription $subscription): RedirectResponse
    {
        return $this->handleCallback($request, $subscription);
    }

    public function failure(Request $request, Subscription $subscription): RedirectResponse
    {
        return $this->handleCallback($request, $subscription);
    }

    private function handleCallback(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->authorizeOwner($request, $subscription);

        $attempt = PaymentAttempt::findOrFail($request->query('attempt'));

        if ($attempt->subscription_id !== $subscription->id) {
            abort(403);
        }

        // Already settled — idempotent return
        if (in_array($attempt->status, ['succeeded', 'failed'], true)) {
            return $attempt->status === 'succeeded'
                ? redirect()->route('hub.subscription')->with('message', __('Your subscription is now active.'))
                : redirect()->route('hub.subscription')->with('message', __('The subscription payment was not completed.'));
        }

        // Call /sync to get the authoritative payment status from DLT
        $paymentStatus = $this->syncPaymentStatus($attempt->dlt_merchant_transaction_id);

        if ($paymentStatus === 'PAID') {
            return $this->activateSubscription($attempt, $subscription);
        }

        if ($paymentStatus === 'REJECTED') {
            return $this->rejectSubscription($attempt, $subscription);
        }

        // PENDING — payment not yet settled, send back to the payment page to wait
        Log::info('DLT: Subscription payment still PENDING after sync', [
            'subscription_id' => $subscription->id,
            'attempt_id' => $attempt->id,
            'merchant_transaction_id' => $attempt->dlt_merchant_transaction_id,
        ]);

        return redirect()
            ->route('hub.subscription.pay', ['subscription' => $subscription->id])
            ->with('message', __('Your payment is still being processed. Please wait and check back shortly.'));
    }

    private function syncPaymentStatus(string $merchantTransactionId): string
    {
        try {
            $dlt = app(DltPaymentService::class);
            $result = $dlt->syncPaymentStatus($merchantTransactionId);

            return $result['payment_status'] ?? 'PENDING';
        } catch (RuntimeException $e) {
            Log::error('DLT: /sync call failed on subscription callback', [
                'merchant_transaction_id' => $merchantTransactionId,
                'error' => $e->getMessage(),
            ]);

            // Cannot determine status — treat as pending rather than falsely activating
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

            // If there's a future trial end, start the paid period from there
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

        return redirect()->route('hub.subscription')->with('message', __('Your subscription is now active.'));
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

        return redirect()->route('hub.subscription')->with('message', __('The subscription payment was not completed.'));
    }

    private function authorizeOwner(Request $request, Subscription $subscription): void
    {
        if ($request->user()?->shop?->id !== $subscription->shop_id) {
            abort(403);
        }
    }
}
