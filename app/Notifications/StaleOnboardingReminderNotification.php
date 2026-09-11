<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ShopOnboarding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class StaleOnboardingReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ShopOnboarding $onboarding,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $storefrontName = $this->onboarding->storefront_name ?? 'your business';

        return (new MailMessage)
            ->subject(__('Your shop onboarding is still pending'))
            ->line(__('Your onboarding application for **:storefrontName** has been inactive for over 30 days.', ['storefrontName' => $storefrontName]))
            ->line(__('Please complete and submit your application to get your shop listed on the marketplace.'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $storefrontName = $this->onboarding->storefront_name ?? 'your business';

        return [
            'type' => 'stale_onboarding_reminder',
            'onboarding_id' => $this->onboarding->id,
            'storefront_name' => $storefrontName,
            'message' => "Onboarding application for {$storefrontName} is still pending.",
            'url' => url('/account/onboarding'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
