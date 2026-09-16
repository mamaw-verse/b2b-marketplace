<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\Media;
use App\Models\PaymentAttempt;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\ManualPaymentSubmittedAdminNotification;
use App\Services\MediaUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
     * @var string form|scanning|submitted
     *
     * - form      : reference number input + receipt upload (initial state)
     * - scanning  : receipt uploaded, waiting for antivirus scan to pass
     * - submitted : admin has been notified, awaiting verification
     */
    public string $step = 'form';

    public string $referenceNumber = '';

    /** @var TemporaryUploadedFile|null Livewire temp file before MediaUploadService stores it */
    public $receipt = null;

    /** @var int|null Media ID once the file has been stored and scan dispatched */
    public ?int $receiptMediaId = null;

    public ?string $errorMessage = null;

    #[Computed]
    public function subscription(): Subscription
    {
        return Subscription::with('plan')->findOrFail($this->subscriptionId);
    }

    /**
     * The Media record for the uploaded receipt (set after upload, cleared on submit).
     */
    #[Computed]
    public function receiptMedia(): ?Media
    {
        return $this->receiptMediaId ? Media::find($this->receiptMediaId) : null;
    }

    public function mount(Subscription $subscription): void
    {
        abort_unless(config('marketplace.payment_method') === 'manual', 403);

        $shop = Auth::user()?->shop;
        abort_unless($shop && $shop->id === $subscription->shop_id, 403);
        abort_unless($subscription->status === 'pending_payment', 403);

        $this->subscriptionId = $subscription->id;

        // If there is already a pending manual attempt, jump to the submitted screen.
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

    /**
     * Called when Livewire finishes uploading the temporary file.
     * Immediately stores it via MediaUploadService (dispatches scan job) so
     * we have a Media record and can show the scan status badge.
     */
    public function uploadReceipt(MediaUploadService $uploader): void
    {
        $this->validate([
            'receipt' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ], [
            'receipt.required' => 'Please upload your payment receipt.',
            'receipt.mimes' => 'Receipt must be a JPEG, PNG, or PDF file.',
            'receipt.max' => 'Receipt file must be under 5 MB.',
        ]);

        // Delete any previously uploaded receipt for this session.
        if ($this->receiptMediaId) {
            Media::find($this->receiptMediaId)?->delete();
        }

        $media = $uploader->store($this->receipt, Auth::id(), 'Subscription payment receipt');

        $this->receiptMediaId = $media->id;
        $this->receipt = null;
        $this->step = 'scanning';
        unset($this->receiptMedia);
    }

    /**
     * Polled every 3s while on the scanning step to refresh scan status.
     */
    public function checkScanStatus(): void
    {
        unset($this->receiptMedia);

        $media = $this->receiptMedia;

        if ($media && $media->scan_status === 'clean') {
            $this->step = 'form';
        } elseif ($media && $media->scan_status === 'infected') {
            // Infected — delete the media and reset so they can re-upload.
            $media->delete();
            $this->receiptMediaId = null;
            $this->step = 'form';
            $this->errorMessage = 'Your receipt file failed the security scan. Please upload a different file.';
        }
    }

    /**
     * Remove the uploaded receipt and go back to the form step.
     */
    public function removeReceipt(): void
    {
        if ($this->receiptMediaId) {
            Media::find($this->receiptMediaId)?->delete();
            $this->receiptMediaId = null;
        }

        $this->step = 'form';
        unset($this->receiptMedia);
    }

    public function submit(): void
    {
        $this->validate([
            'referenceNumber' => 'required|string|min:3|max:100',
        ]);

        if (! $this->receiptMediaId) {
            $this->errorMessage = 'Please upload your payment receipt before submitting.';

            return;
        }

        $media = $this->receiptMedia;

        if (! $media || $media->scan_status !== 'clean') {
            $this->errorMessage = 'Your receipt is still being scanned. Please wait a moment and try again.';

            return;
        }

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

        $attempt = DB::transaction(function () use ($subscription, $media): PaymentAttempt {
            return PaymentAttempt::create([
                'subscription_id' => $subscription->id,
                'payment_method' => 'manual',
                'status' => 'pending',
                'manual_reference_number' => $this->referenceNumber,
                'manual_receipt_path' => $media->storage_path,
                'receipt_media_id' => $media->id,
            ]);
        });

        // Notify all admin users — triggers mail + database (in-app) + broadcast channels.
        User::role('admin')->get()->each(
            fn (User $admin) => $admin->notify(new ManualPaymentSubmittedAdminNotification(
                attempt: $attempt,
                subscription: $subscription,
            ))
        );

        $this->step = 'submitted';
    }

    public function render(): View
    {
        return view('livewire.company-hub.manual-subscription-payment-page', [
            'subscription' => $this->subscription,
        ]);
    }
}
