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
 * Sent to the admin when a merchant submits a manual subscription payment
 * (reference number + receipt upload). The admin must verify the payment
 * in their bank portal and then activate or cancel the subscription in the
 * admin panel.
 */
final class ManualPaymentSubmittedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PaymentAttempt $attempt,
        public readonly Subscription $subscription,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[ACTION REQUIRED] Manual payment submitted — Subscription #'.$this->subscription->id)
            ->line('A merchant has submitted a manual payment for their subscription.')
            ->line('Please verify the payment in your bank portal and then activate or cancel the subscription.')
            ->line('---')
            ->line('**Payment Details**')
            ->line('Subscription ID: `'.$this->subscription->id.'`')
            ->line('Plan: '.$this->subscription->plan->name)
            ->line('Amount: '.$this->subscription->plan->currency.' '.number_format((float) $this->subscription->plan->price, 2))
            ->line('Payment Attempt ID: `'.$this->attempt->id.'`')
            ->line('Reference Number: `'.($this->attempt->manual_reference_number ?? 'N/A').'`')
            ->line('Submitted At: `'.$this->attempt->created_at?->toIso8601String().'`')
            ->line('---')
            ->line('Log in to the admin panel to view the receipt and take action.');
    }
}
