<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ShopOnboarding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OnboardingSubmittedNotification extends Notification implements ShouldQueue
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
        $businessName = $this->onboarding->registered_business_name ?? 'Unknown';

        return (new MailMessage)
            ->subject('New Onboarding Application Submitted')
            ->line("A new onboarding application has been submitted by **{$businessName}**.")
            ->line('Please review the application and approve or reject it.')
            ->action('Review Application', url('/admin/onboardings/'.$this->onboarding->id));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $businessName = $this->onboarding->registered_business_name ?? 'Unknown';

        return [
            'type' => 'onboarding_submitted',
            'onboarding_id' => $this->onboarding->id,
            'storefront_name' => $businessName,
            'message' => "New onboarding application submitted by {$businessName}.",
            'url' => url('/admin/onboardings/'.$this->onboarding->id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
