<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the seller (shop owner) when their subscription is activated by
 * an admin — used for the manual payment flow where admin verifies the
 * bank transfer out-of-band before activating.
 */
final class SubscriptionActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Subscription $subscription,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $shopName = $this->subscription->shop->storefront_name
            ?? $this->subscription->shop->registered_business_name;

        return (new MailMessage)
            ->subject(__('Your subscription is now active — :shop', ['shop' => $shopName]))
            ->line(__('Great news! Your subscription for **:shop** has been verified and activated.', ['shop' => $shopName]))
            ->line(__('Plan: :plan', ['plan' => $this->subscription->plan->name]))
            ->line(__('Your subscription is active until :date.', [
                'date' => $this->subscription->current_period_end?->format('F j, Y') ?? 'N/A',
            ]))
            ->action(__('View Subscription'), route('hub.subscription'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $shopName = $this->subscription->shop->storefront_name
            ?? $this->subscription->shop->registered_business_name;

        return [
            'type' => 'subscription_activated',
            'subscription_id' => $this->subscription->id,
            'shop_id' => $this->subscription->shop_id,
            'shop_name' => $shopName,
            'plan_name' => $this->subscription->plan->name,
            'period_end' => $this->subscription->current_period_end?->toIso8601String(),
            'message' => 'Your subscription ('.$this->subscription->plan->name.') is now active.',
            'url' => route('hub.subscription'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
