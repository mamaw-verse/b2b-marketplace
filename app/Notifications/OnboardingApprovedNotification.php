<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ShopOnboarding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OnboardingApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ShopOnboarding $onboarding,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $storefrontName = $this->onboarding->storefront_name ?? 'your business';

        return (new MailMessage)
            ->subject(__('Your Onboarding Application Has Been Approved!'))
            ->line(__('Congratulations! Your onboarding application for **:storefrontName** has been approved.', ['storefrontName' => $storefrontName]))
            ->line(__('Your shop is now active on the marketplace.'))
            ->action(__('Go to Your Dashboard'), route('hub.hub-dashboard'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $storefrontName = $this->onboarding->storefront_name ?? 'your business';

        return [
            'type' => 'onboarding_approved',
            'onboarding_id' => $this->onboarding->id,
            'storefront_name' => $storefrontName,
            'message' => "Your onboarding application for {$storefrontName} has been approved.",
            'url' => route('hub.hub-dashboard'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
