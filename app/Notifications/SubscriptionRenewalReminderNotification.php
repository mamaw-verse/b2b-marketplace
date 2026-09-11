<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SubscriptionRenewalReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public int $daysUntilRenewal,
        public int $thresholdDays,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $shopName = $this->subscription->shop->storefront_name;

        return (new MailMessage)
            ->subject(__(':shopName subscription renewal reminder', ['shopName' => $shopName]))
            ->line(__('Your subscription for :shopName is approaching renewal.', ['shopName' => $shopName]))
            ->line(__('Renewal is due in :days day(s).', ['days' => $this->daysUntilRenewal]))
            ->action(__('Review Subscription'), route('hub.subscription'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_renewal_reminder',
            'subscription_id' => $this->subscription->id,
            'shop_id' => $this->subscription->shop_id,
            'shop_name' => $this->subscription->shop->storefront_name,
            'days_until_renewal' => $this->daysUntilRenewal,
            'threshold_days' => $this->thresholdDays,
            'message' => $this->subscription->shop->storefront_name.' subscription renews in '.$this->daysUntilRenewal.' day(s).',
            'url' => route('hub.subscription'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
