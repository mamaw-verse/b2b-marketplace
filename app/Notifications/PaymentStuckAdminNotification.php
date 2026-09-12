<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PaymentAttempt;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the admin when a DLT payment attempt remains PENDING after all
 * sync retries are exhausted (Gap 1 give-up) or when the user returns to
 * the payment page and the existing attempt is unresolvable (Gap 2).
 */
final class PaymentStuckAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PaymentAttempt $attempt,
        public readonly Subscription $subscription,
        public readonly string $trigger,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[ACTION REQUIRED] Payment stuck PENDING — Subscription #'.$this->subscription->id)
            ->error()
            ->line('A subscription payment attempt remains **PENDING** and could not be resolved automatically.')
            ->line('**Trigger:** '.$this->trigger)
            ->line('---')
            ->line('**Technical Details**')
            ->line('Subscription ID: `'.$this->subscription->id.'`')
            ->line('Subscription Status: `'.$this->subscription->status.'`')
            ->line('Plan: '.$this->subscription->plan->name)
            ->line('Amount: '.$this->subscription->plan->currency.' '.number_format((float) $this->subscription->plan->price, 2))
            ->line('Payment Attempt ID: `'.$this->attempt->id.'`')
            ->line('Merchant Transaction ID: `'.$this->attempt->dlt_merchant_transaction_id.'`')
            ->line('DLT Payment Status: `'.($this->attempt->dlt_payment_status ?? 'null').'`')
            ->line('Sync Attempts: `'.$this->attempt->sync_attempts.'`')
            ->line('Attempt Created At: `'.$this->attempt->created_at?->toIso8601String().'`')
            ->line('Last Updated At: `'.$this->attempt->updated_at?->toIso8601String().'`')
            ->line('---')
            ->line('**Action required:** Log in to DLT Web Integration to check the transaction status, or call `/sync` manually. Once resolved, update the subscription and payment attempt accordingly.')
            ->line('Do **not** re-submit the same `merchant_transaction_id` — use `/sync` first to avoid a 409 TRANSACTION_REFERENCE_CONFLICT.');
    }
}
