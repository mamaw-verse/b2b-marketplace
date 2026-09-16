<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Events\SubscriptionStatusChanged;
use App\Livewire\Concerns\HasDocumentViewer;
use App\Models\PaymentAttempt;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Notifications\SubscriptionActivatedNotification;
use App\Services\DltPaymentService;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Subscriptions')]
final class SubscriptionQueue extends Component
{
    use HasDocumentViewer;
    use WithPagination;

    /**
     * Active tab: 'subscriptions' | 'pending_payments'
     */
    public string $tab = 'subscriptions';

    public string $statusFilter = 'all';

    public string $planFilter = 'all';

    // Settings modal state
    public bool $showSettingsModal = false;

    /** @var array<int, array{id: int, name: string, currency: string, billing_period: string, price: string, trial_days: string, is_active: bool}> */
    public array $settingsPlans = [];

    // Reconciliation confirmation state
    public ?int $confirmingActivateId = null;

    public ?int $confirmingCancelId = null;

    /**
     * Whether a /sync check is currently running (shows spinner on Activate button).
     */
    public bool $syncChecking = false;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPlanFilter(): void
    {
        $this->resetPage();
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function openSettings(): void
    {
        $this->loadSettingsPlans();
        $this->showSettingsModal = true;
    }

    public function saveSettings(): void
    {
        $rules = [];

        foreach ($this->settingsPlans as $index => $plan) {
            $rules["settingsPlans.{$index}.price"] = 'required|numeric|min:0|max:999999999999';
            $rules["settingsPlans.{$index}.trial_days"] = 'nullable|integer|min:0|max:9999';
            $rules["settingsPlans.{$index}.is_active"] = 'boolean';
        }

        $this->validate($rules);

        foreach ($this->settingsPlans as $planData) {
            SubscriptionPlan::where('id', $planData['id'])->update([
                'price' => $planData['price'],
                'trial_days' => $planData['trial_days'] !== '' ? (int) $planData['trial_days'] : null,
                'is_active' => (bool) $planData['is_active'],
            ]);
        }

        $this->showSettingsModal = false;
        $this->loadSettingsPlans();

        Flux::toast('Subscription settings saved.');
    }

    // ──────────────────────────────────────────────────────────────
    // Reconciliation actions
    // ──────────────────────────────────────────────────────────────

    /**
     * Called when the admin clicks Activate on a pending_payment subscription.
     *
     * For DLT attempts: calls /sync first to get an authoritative status, then:
     *   - PAID     → activate immediately, no confirmation modal needed.
     *   - PENDING  → show confirm-activate-pending modal so admin can override
     *                knowing the payment is still unresolved on DLT's side.
     *   - REJECTED → cancel the subscription immediately; no manual activation needed.
     *   - error    → treat as REJECTED (safe default; admin can check DLT portal).
     *
     * For manual attempts: /sync does not apply — go straight to the standard
     * confirm-activate modal for admin to confirm they verified out-of-band.
     */
    public function confirmActivate(int $subscriptionId): void
    {
        $this->confirmingActivateId = $subscriptionId;
        $this->syncChecking = true;

        $subscription = Subscription::with('plan')
            ->where('status', 'pending_payment')
            ->findOrFail($subscriptionId);

        $attempt = PaymentAttempt::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        // Manual payment — skip /sync, show receipt review modal.
        if ($attempt === null || $attempt->isManual()) {
            $this->syncChecking = false;
            Flux::modal('confirm-activate-manual')->show();

            return;
        }

        // DLT payment — call /sync and branch on the result.
        $paymentStatus = $this->callSync($attempt);

        $this->syncChecking = false;

        if ($paymentStatus === 'PAID') {
            // Already paid — activate immediately without a second confirmation.
            $this->doActivate($subscription, $attempt);
            $this->confirmingActivateId = null;
            Flux::toast(__('Payment confirmed PAID via DLT. Subscription activated.'), variant: 'success');

            return;
        }

        if ($paymentStatus === 'PENDING') {
            // Still pending on DLT — show warning modal so admin can decide.
            Flux::modal('confirm-activate-pending')->show();

            return;
        }

        // REJECTED or any other status (error, unknown) — cancel immediately.
        $this->doCancel($subscription, $attempt, $paymentStatus);
        $this->confirmingActivateId = null;
        Flux::toast(__('DLT returned :status. Subscription cancelled.', ['status' => $paymentStatus]), variant: 'warning');
    }

    public function confirmCancel(int $subscriptionId): void
    {
        $this->confirmingCancelId = $subscriptionId;
        Flux::modal('confirm-cancel')->show();
    }

    /**
     * Admin confirms activation after being warned the DLT status is still PENDING.
     * This is an explicit override — admin has verified payment out-of-band.
     */
    public function forceActivateOverride(): void
    {
        $subscription = Subscription::with('plan')
            ->where('status', 'pending_payment')
            ->findOrFail($this->confirmingActivateId);

        $attempt = PaymentAttempt::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        $this->doActivate($subscription, $attempt);
        $this->confirmingActivateId = null;

        Flux::toast(__('Subscription activated manually (payment still PENDING on DLT).'), variant: 'success');
    }

    /**
     * Admin force-activates a stuck pending_payment subscription.
     * Used for manual payment attempts (no /sync check needed).
     * For DLT attempts, activation flows through confirmActivate() instead.
     */
    public function forceActivate(): void
    {
        $subscription = Subscription::with('plan')
            ->where('status', 'pending_payment')
            ->findOrFail($this->confirmingActivateId);

        $attempt = PaymentAttempt::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        $this->doActivate($subscription, $attempt);
        $this->confirmingActivateId = null;

        Flux::toast(__('Subscription activated manually.'), variant: 'success');
    }

    /**
     * Admin force-cancels a stuck pending_payment subscription.
     * Marks subscription cancelled, marks the latest pending attempt failed.
     */
    public function forceCancel(): void
    {
        $subscription = Subscription::query()
            ->where('status', 'pending_payment')
            ->findOrFail($this->confirmingCancelId);

        $attempt = PaymentAttempt::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        $this->doCancel($subscription, $attempt, 'REJECTED');
        $this->confirmingCancelId = null;

        Flux::toast(__('Subscription cancelled manually.'), variant: 'success');
    }

    // ──────────────────────────────────────────────────────────────
    // Computed queries
    // ──────────────────────────────────────────────────────────────

    /**
     * One subscription per company — the latest by id.
     *
     * Uses a subquery to pick MAX(id) per shop_id so that only the most
     * recent subscription row is returned for each company, regardless of
     * status. Filters and pagination then apply on top of that set.
     *
     * @return LengthAwarePaginator<Subscription>
     */
    #[Computed]
    public function subscriptions(): LengthAwarePaginator
    {
        // Subquery: the highest subscription id per shop (= the latest one).
        $latestIds = Subscription::query()
            ->select(DB::raw('MAX(id) as id'))
            ->whereHas('shop', fn ($q) => $q->where('country_code', config('marketplace.instance_country')))
            ->groupBy('shop_id');

        return Subscription::query()
            ->with(['shop.user', 'plan', 'paymentAttempts' => fn ($q) => $q->latest()->limit(1)])
            ->whereIn('id', $latestIds)
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->planFilter !== 'all', fn ($query) => $query->where('subscription_plan_id', (int) $this->planFilter))
            ->latest('id')
            ->paginate(15);
    }

    /**
     * Pending payment subscriptions with their latest payment attempt details.
     * Used in the reconciliation tab.
     *
     * Includes both DLT-stuck attempts (have a dlt_merchant_transaction_id) and
     * manual payment attempts awaiting admin verification.
     *
     * @return LengthAwarePaginator<Subscription>
     */
    #[Computed]
    public function pendingPaymentSubscriptions(): LengthAwarePaginator
    {
        return Subscription::query()
            ->with([
                'shop.user',
                'plan',
                'paymentAttempts' => fn ($q) => $q->with('receiptMedia')->latest()->limit(1),
            ])
            ->whereHas('paymentAttempts', fn ($q) => $q
                ->where('status', 'pending')
                ->where(fn ($inner) => $inner
                    ->where('payment_method', 'manual')
                    ->orWhere(fn ($dlt) => $dlt
                        ->where('payment_method', 'dlt')
                        ->whereNotNull('dlt_merchant_transaction_id')
                        ->where('dlt_merchant_transaction_id', '<>', '')
                    )
                )
            )
            ->latest('id')
            ->paginate(20);
    }

    /**
     * @return Collection<int, SubscriptionPlan>
     */
    #[Computed]
    public function plans(): Collection
    {
        return SubscriptionPlan::query()
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.admin.subscription-queue');
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Call DLT /sync for the given attempt.
     * Returns the payment_status string ('PAID', 'PENDING', 'REJECTED') or
     * 'ERROR' on any exception so callers can treat it uniformly.
     */
    private function callSync(PaymentAttempt $attempt): string
    {
        try {
            $dlt = app(DltPaymentService::class);
            $result = $dlt->syncPaymentStatus($attempt->dlt_merchant_transaction_id);
            $status = $result['payment_status'] ?? 'PENDING';

            $attempt->update(['dlt_payment_status' => $status]);

            return $status;
        } catch (RuntimeException $e) {
            Log::error('SubscriptionQueue: /sync call failed on admin Activate', [
                'subscription_id' => $attempt->subscription_id,
                'attempt_id' => $attempt->id,
                'merchant_transaction_id' => $attempt->dlt_merchant_transaction_id,
                'error' => $e->getMessage(),
            ]);

            return 'ERROR';
        }
    }

    /**
     * Activate a subscription and its associated attempt inside a transaction.
     * Works for both DLT and manual attempts.
     */
    private function doActivate(Subscription $subscription, ?PaymentAttempt $attempt): void
    {
        DB::transaction(function () use ($subscription, $attempt): void {
            Subscription::query()
                ->where('shop_id', $subscription->shop_id)
                ->whereIn('status', ['active', 'trialing'])
                ->where('id', '<>', $subscription->id)
                ->update(['status' => 'lapsed']);

            $periodStart = now();

            $subscription->update([
                'status' => 'active',
                'trial_ends_at' => null,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodStart->copy()->addYear(),
                'last_renewal_alert_threshold_days' => null,
            ]);

            if ($attempt !== null) {
                $update = [
                    'status' => 'succeeded',
                    'succeeded_at' => now(),
                    'next_sync_at' => null,
                ];

                // DLT attempts get the canonical PAID status; manual attempts are
                // verified out-of-band so we leave dlt_payment_status as-is.
                if (! $attempt->isManual()) {
                    $update['dlt_payment_status'] = 'PAID';
                }

                $attempt->update($update);
            }
        });

        event(new SubscriptionStatusChanged($subscription->fresh()));

        // Notify the seller that their subscription is now active.
        $fresh = $subscription->fresh()->load('plan', 'shop.user');
        $fresh->shop->user?->notify(new SubscriptionActivatedNotification($fresh));
    }

    /**
     * Cancel a subscription and mark its pending attempt as failed.
     */
    private function doCancel(Subscription $subscription, ?PaymentAttempt $attempt, string $dltStatus): void
    {
        DB::transaction(function () use ($subscription, $attempt, $dltStatus): void {
            $subscription->update(['status' => 'cancelled']);

            $attempt?->update([
                'status' => 'failed',
                'dlt_payment_status' => $dltStatus,
                'failed_at' => now(),
                'next_sync_at' => null,
            ]);
        });

        event(new SubscriptionStatusChanged($subscription->fresh()));
    }

    private function loadSettingsPlans(): void
    {
        $this->settingsPlans = SubscriptionPlan::orderBy('id')
            ->get()
            ->map(fn (SubscriptionPlan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'currency' => $plan->currency,
                'billing_period' => $plan->billing_period,
                'price' => (string) $plan->price,
                'trial_days' => $plan->trial_days !== null ? (string) $plan->trial_days : '',
                'is_active' => $plan->is_active,
            ])
            ->all();
    }
}
