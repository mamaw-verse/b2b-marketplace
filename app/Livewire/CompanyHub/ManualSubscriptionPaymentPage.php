<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\PaymentAttempt;
use App\Models\Subscription;
use App\Notifications\ManualPaymentSubmittedAdminNotification;
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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Manual Subscription Payment')]
final class ManualSubscriptionPaymentPage extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $subscriptionId;

    /**
     * @var string form|submitted
     *
     * - form       : reference number input + receipt upload (initial state)
     * - submitted  : confirmation screen while admin reviews
     */
    public string $step = 'form';

    public string $referenceNumber = '';

    /** @var TemporaryUploadedFile|null */
    public $receipt = null;

    public ?string $errorMessage = null;

    #[Computed]
    public function subscription(): Subscription
    {
        return Subscription::with('plan')->findOrFail($this->subscriptionId);
    }

    public function mount(Subscription $subscription): void
    {
        // Only available when the instance is configured for manual payments.
        abort_unless(config('marketplace.payment_method') === 'manual', 403);

        $shop = Auth::user()?->shop;
        abort_unless($shop && $shop->id === $subscription->shop_id, 403);
        abort_unless($subscription->status === 'pending_payment', 403);

        $this->subscriptionId = $subscription->id;

        // If there is already a pending manual attempt, jump straight to the
        // submitted screen — the merchant already submitted; prevent double-submit.
        $existingAttempt = PaymentAttempt::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'pending')
            ->where('payment_method', 'manual')
            ->latest()
            ->first();

        if ($existingAttempt !== null) {
            $this->step = 'submitted';
        }
    }

    public function submit(): void
    {
        $this->validate([
            'referenceNumber' => 'required|string|min:3|max:100',
            'receipt' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ], [
            'receipt.required' => 'Please upload your payment receipt.',
            'receipt.mimes' => 'Receipt must be a JPEG, PNG, or PDF file.',
            'receipt.max' => 'Receipt file must be under 5 MB.',
        ]);

        $subscription = $this->subscription;

        if ($subscription->status !== 'pending_payment') {
            $this->errorMessage = 'This subscription is no longer awaiting payment.';

            return;
        }

        // Guard against double-submit.
        if (PaymentAttempt::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'pending')
            ->where('payment_method', 'manual')
            ->exists()) {
            $this->step = 'submitted';

            return;
        }

        $receiptPath = $this->receipt->storePubliclyAs(
            path: 'subscription-receipts/'.$subscription->id,
            name: Str::ulid().'.'.$this->receipt->getClientOriginalExtension(),
            options: 's3',
        );

        $attempt = DB::transaction(function () use ($subscription, $receiptPath): PaymentAttempt {
            return PaymentAttempt::create([
                'subscription_id' => $subscription->id,
                'payment_method' => 'manual',
                'status' => 'pending',
                'manual_reference_number' => $this->referenceNumber,
                'manual_receipt_path' => $receiptPath,
            ]);
        });

        Notification::route('mail', config('mail.admin_address', config('mail.from.address')))
            ->notify(new ManualPaymentSubmittedAdminNotification(
                attempt: $attempt,
                subscription: $subscription,
            ));

        $this->step = 'submitted';
    }

    public function render(): View
    {
        return view('livewire.company-hub.manual-subscription-payment-page', [
            'subscription' => $this->subscription,
        ]);
    }
}
