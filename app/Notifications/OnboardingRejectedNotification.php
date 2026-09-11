<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ShopOnboarding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OnboardingRejectedNotification extends Notification implements ShouldQueue
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
            ->subject(__('Your Onboarding Application Has Been Rejected'))
            ->line(__('Unfortunately, your onboarding application for **:storefrontName** has been rejected.', ['storefrontName' => $storefrontName]))
            ->when($this->onboarding->admin_notes, fn (MailMessage $mail) => $mail->line(__('**Reason:** :notes', ['notes' => $this->onboarding->admin_notes])))
            ->line(__('If you believe this is in error, please contact support.'))
            ->action(__('View Application'), route('account.onboarding'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $storefrontName = $this->onboarding->storefront_name ?? 'your business';

        return [
            'type' => 'onboarding_rejected',
            'onboarding_id' => $this->onboarding->id,
            'storefront_name' => $storefrontName,
            'admin_notes' => $this->onboarding->admin_notes,
            'message' => "Your onboarding application for {$storefrontName} has been rejected.",
            'url' => route('account.onboarding'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
