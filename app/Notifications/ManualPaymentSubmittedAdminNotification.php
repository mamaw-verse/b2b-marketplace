<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PaymentAttempt;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to all admin users when a merchant submits a manual subscription
 * payment (reference number + receipt upload).
 *
 * Channels: mail + database (in-app bell) + broadcast (real-time push).
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
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $businessName = $this->subscription->shop->registered_business_name ?? 'Unknown';

        return (new MailMessage)
            ->subject('[ACTION REQUIRED] Manual payment submitted — '.$businessName.' (Subscription #'.$this->subscription->id.')')
            ->line('**'.$businessName.'** has submitted a manual payment for their subscription.')
            ->line('Please verify the payment in your bank portal and then activate or cancel the subscription.')
            ->line('---')
            ->line('**Payment Details**')
            ->line('Subscription ID: `'.$this->subscription->id.'`')
            ->line('Plan: '.$this->subscription->plan->name)
            ->line('Amount: '.$this->subscription->plan->currency.' '.number_format((float) $this->subscription->plan->price, 2))
            ->line('Reference Number: `'.($this->attempt->manual_reference_number ?? 'N/A').'`')
            ->line('Submitted At: `'.$this->attempt->created_at?->toIso8601String().'`')
            ->action('Review in Admin Panel', url('/admin/subscriptions'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $businessName = $this->subscription->shop->registered_business_name ?? 'Unknown';

        return [
            'type' => 'manual_payment_submitted',
            'subscription_id' => $this->subscription->id,
            'attempt_id' => $this->attempt->id,
            'shop_id' => $this->subscription->shop_id,
            'business_name' => $businessName,
            'reference_number' => $this->attempt->manual_reference_number,
            'amount' => $this->subscription->plan->currency.' '.number_format((float) $this->subscription->plan->price, 2),
            'message' => $businessName.' has submitted a manual payment — reference: '.($this->attempt->manual_reference_number ?? 'N/A').'.',
            'url' => url('/admin/subscriptions'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
