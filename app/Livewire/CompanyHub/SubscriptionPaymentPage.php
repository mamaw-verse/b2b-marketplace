<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Events\SubscriptionStatusChanged;
use App\Jobs\SyncPendingPaymentJob;
use App\Models\PaymentAttempt;
use App\Models\Subscription;
use App\Notifications\PaymentStuckAdminNotification;
use App\Services\DltPaymentService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Pay Subscription')]
final class SubscriptionPaymentPage extends Component
{
    #[Locked]
    public int $subscriptionId;

    /**
     * @var string brands|payment_form|processing|confirming|sync_pending|success|pending|failed
     *
     * - brands        : brand picker (initial state)
     * - payment_form  : billing details form
     * - processing    : spinner while /submit is in-flight
     * - confirming    : payor returned from DLT hosted checkout; background job is polling /sync
     * - sync_pending  : pre-payment mount check found an unresolvable pending attempt
     * - success       : payment confirmed PAID
     * - pending       : (legacy, kept for redirect compatibility) payment not yet settled
     * - failed        : terminal failure
     */
    public string $step = 'brands';

    public bool $loadingBrands = true;

    public ?string $errorMessage = null;

    /**
     * ID of the PaymentAttempt being confirmed (used for Livewire polling on
     * the 'confirming' step to detect when the background job settles it).
     */
    #[Locked]
    public ?int $confirmingAttemptId = null;

    /** @var array<int, array{value: string, code: string, image: string|null, is_card: bool}> */
    public array $brands = [];

    /** @var array<int, array{value: string, code: string, image: string|null, is_card: bool}> */
    public array $cardBrands = [];

    /** @var array<int, array{value: string, code: string, image: string|null, is_card: bool}> */
    public array $ewalletBrands = [];

    /** @var array{value: string, code: string, image: string|null, is_card: bool}|null */
    public ?array $selectedBrand = null;

    // Payor / billing fields
    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public string $addressLine1 = '';

    public string $addressLine2 = '';

    public string $city = '';

    public string $province = '';

    public string $postalCode = '';

    public string $countryCode = 'PH';

    #[Computed]
    public function subscription(): Subscription
    {
        return Subscription::with('plan')->findOrFail($this->subscriptionId);
    }

    public function mount(Subscription $subscription): void
    {
        $shop = Auth::user()?->shop;
        abort_unless($shop && $shop->id === $subscription->shop_id, 403);
        abort_unless($subscription->status === 'pending_payment', 403);
        abort_unless(config('marketplace.payment_method') === 'dlt', 403);

        $this->subscriptionId = $subscription->id;

        $user = Auth::user();
        $this->firstName = $user->first_name ?? '';
        $this->lastName = $user->last_name ?? '';
        $this->email = $user->email ?? '';

        // Gap 1: if the callback controller detected PENDING and redirected back here,
        // it flashes 'confirming_attempt_id' so we can immediately show the polling UI.
        $confirmingId = session('confirming_attempt_id');
        if ($confirmingId !== null) {
            $this->confirmingAttemptId = (int) $confirmingId;
            $this->step = 'confirming';

            return;
        }

        // Gap 2: check for an existing pending attempt before showing the brand picker.
        // If the user closed the browser mid-flow, we may already have a payment in-flight.
        $this->checkExistingPendingAttempt($subscription);
    }

    /**
     * Gap 2 — pre-payment sync check.
     * Looks for the most recent pending attempt for this subscription and
     * calls /sync to determine its authoritative status before proceeding.
     */
    private function checkExistingPendingAttempt(Subscription $subscription): void
    {
        $existingAttempt = PaymentAttempt::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($existingAttempt === null) {
            // No prior pending attempt — load brands normally.
            $this->loadBrands();

            return;
        }

        // No transaction ID means the attempt was created but /submit was never called
        // (e.g. the user closed the browser before the payment wall redirect).
        // There is nothing to /sync — treat as a fresh start.
        if (empty($existingAttempt->dlt_merchant_transaction_id)) {
            $this->loadBrands();

            return;
        }

        // There's a pending attempt that reached DLT. Call /sync for authoritative status.
        try {
            $dlt = app(DltPaymentService::class);
            $result = $dlt->syncPaymentStatus($existingAttempt->dlt_merchant_transaction_id);
            $paymentStatus = $result['payment_status'] ?? 'PENDING';

            $existingAttempt->update(['dlt_payment_status' => $paymentStatus]);
        } catch (RuntimeException $e) {
            // 503 or connection error — cannot determine status. Treat as PENDING.
            report($e);
            $paymentStatus = 'PENDING';
        }

        if ($paymentStatus === 'PAID') {
            // Already paid — activate and redirect.
            $this->activateSubscription($existingAttempt, $subscription);
            $this->step = 'success';

            return;
        }

        if ($paymentStatus === 'REJECTED') {
            // Previous attempt was rejected — mark it, allow fresh payment.
            DB::transaction(function () use ($existingAttempt): void {
                $existingAttempt->update([
                    'status' => 'failed',
                    'dlt_payment_status' => 'REJECTED',
                    'failed_at' => now(),
                ]);
            });

            $this->loadBrands();

            return;
        }

        // PENDING or unresolvable — show the sync_pending UI and alert admin.
        $this->step = 'sync_pending';
        $this->alertAdmin($existingAttempt, $subscription, 'User returned to payment page; existing attempt still PENDING after /sync check on mount()');
    }

    public function loadBrands(): void
    {
        $this->loadingBrands = true;
        $this->errorMessage = null;

        try {
            $dlt = app(DltPaymentService::class);
            $this->brands = $dlt->getBrands();
            $this->cardBrands = array_values(array_filter($this->brands, fn ($b) => $b['is_card'] === true));
            $this->ewalletBrands = array_values(array_filter($this->brands, fn ($b) => $b['is_card'] === false));
        } catch (RuntimeException $e) {
            $this->errorMessage = 'Failed to load payment options. Please try again.';
            report($e);
        } finally {
            $this->loadingBrands = false;
        }
    }

    public function selectBrand(int $index, bool $isCard): void
    {
        $this->selectedBrand = $isCard
            ? ($this->cardBrands[$index] ?? null)
            : ($this->ewalletBrands[$index] ?? null);

        $this->errorMessage = null;

        if ($this->selectedBrand) {
            $this->step = 'payment_form';
        }
    }

    public function goBack(): void
    {
        $this->step = 'brands';
        $this->selectedBrand = null;
        $this->errorMessage = null;
    }

    public function submitPayment(): void
    {
        $this->validate([
            'firstName' => 'required|string|min:2|max:50',
            'lastName' => 'required|string|min:2|max:50',
            'email' => 'required|email|max:100',
            'phone' => 'nullable|string|max:16',
            'addressLine1' => 'required|string|max:80',
            'addressLine2' => 'nullable|string|max:80',
            'city' => 'required|string|max:60',
            'province' => 'required|string|max:40',
            'postalCode' => 'required|string|max:10',
        ]);

        $subscription = $this->subscription;

        if ($subscription->status !== 'pending_payment') {
            $this->step = 'failed';
            $this->errorMessage = 'This subscription is no longer awaiting payment.';

            return;
        }

        $this->step = 'processing';
        $this->errorMessage = null;

        $merchantTxnId = mb_substr(
            'sub'.mb_str_pad((string) $subscription->id, 5, '0', STR_PAD_LEFT).mb_strtoupper((string) Str::ulid()),
            0,
            45
        );

        $attempt = PaymentAttempt::create([
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'dlt_merchant_transaction_id' => $merchantTxnId,
        ]);

        try {
            $dlt = app(DltPaymentService::class);
            $result = $dlt->submitPayment([
                'merchant_transaction_id' => $merchantTxnId,
                'time_offset' => '+08:00',
                'channel' => 4,
                'amount' => number_format((float) $subscription->plan->price, 2, '.', ''),
                'currency' => 'PHP',
                'payment_brand' => $this->selectedBrand['value'],
                'payment_brand_code' => $this->selectedBrand['code'],
                'product_name' => Str::limit($subscription->plan->name, 80, ''),
                'product_description' => Str::limit($subscription->plan->name.' annual subscription', 120, ''),
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
                'phone' => $this->phone,
                'address_line_one' => $this->addressLine1,
                'address_line_two' => $this->addressLine2,
                'city_municipality' => $this->city,
                'state_province_region' => $this->province,
                'country_code' => $this->countryCode,
                'postal_code' => $this->postalCode,
                'success_url' => route('hub.subscriptions.payment.success', ['subscription' => $subscription->id, 'attempt' => $attempt->id]),
                'failure_url' => route('hub.subscriptions.payment.failure', ['subscription' => $subscription->id, 'attempt' => $attempt->id]),
            ]);

            $paymentData = $result['data'] ?? [];
            $paymentUrl = $paymentData['payment_url'] ?? null;

            $attempt->update([
                'dlt_payment_status' => $paymentData['payment_status'] ?? 'PENDING',
                'dlt_payment_url' => $paymentUrl,
                'dlt_amount' => $paymentData['amount'] ?? null,
                'gateway_response' => $paymentData,
            ]);

            if ($paymentUrl) {
                $this->dispatch('redirect-to-payment', url: $paymentUrl);
            } else {
                $attempt->update(['status' => 'failed', 'failed_at' => now()]);
                $subscription->update(['status' => 'cancelled']);
                event(new SubscriptionStatusChanged($subscription->fresh()));
                Flux::toast(__('Payment could not be initiated. Please try again.'), variant: 'danger');
                $this->step = 'failed';
                $this->errorMessage = 'Payment could not be initiated. Please try again.';
            }
        } catch (RuntimeException $e) {
            $attempt->update([
                'status' => 'failed',
                'failed_at' => now(),
                'gateway_response' => ['error' => $e->getMessage()],
            ]);
            $subscription->update(['status' => 'cancelled']);
            event(new SubscriptionStatusChanged($subscription->fresh()));
            Flux::toast(__('Payment failed. Please try again.'), variant: 'danger');
            $this->step = 'failed';
            $this->errorMessage = 'Payment failed. Please try again.';
            report($e);
        }
    }

    /**
     * Polled by the 'confirming' step UI (wire:poll) to detect when the
     * background SyncPendingPaymentJob has settled the attempt.
     */
    public function checkConfirmingStatus(): void
    {
        if ($this->step !== 'confirming' || $this->confirmingAttemptId === null) {
            return;
        }

        $attempt = PaymentAttempt::find($this->confirmingAttemptId);

        if ($attempt === null) {
            return;
        }

        if ($attempt->status === 'succeeded') {
            $this->step = 'success';

            return;
        }

        if ($attempt->status === 'failed') {
            // Check whether the job gave up (still pending on DLT) vs. was actually rejected.
            if ($attempt->dlt_payment_status === 'REJECTED') {
                $this->step = 'failed';
                $this->errorMessage = __('Your payment was declined. Please try a different payment method.');
            } else {
                // Job gave up after max retries — show sync_pending advisory.
                $this->step = 'sync_pending';
            }
        }
    }

    public function retry(): void
    {
        $this->step = 'brands';
        $this->errorMessage = null;
        $this->selectedBrand = null;
        $this->confirmingAttemptId = null;
        $this->loadBrands();
    }

    /**
     * Activate subscription inside a DB transaction.
     * Mirrors the logic in SubscriptionPaymentCallbackController and SyncPendingPaymentJob.
     */
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
    }

    private function alertAdmin(PaymentAttempt $attempt, Subscription $subscription, string $trigger): void
    {
        Notification::route('mail', config('mail.admin_address', config('mail.from.address')))
            ->notify(new PaymentStuckAdminNotification(
                attempt: $attempt,
                subscription: $subscription,
                trigger: $trigger,
            ));
    }

    public function render(): View
    {
        return view('livewire.company-hub.subscription-payment-page', [
            'subscription' => $this->subscription,
        ]);
    }
}
