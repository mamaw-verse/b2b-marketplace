<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CertificationApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CertificationInterestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CertificationApplication $application,
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
        $shopName = $this->application->shop->storefront_name;

        return (new MailMessage)
            ->subject("{$shopName} is interested in halal certification")
            ->line("{$shopName} has expressed interest in applying for external halal certification.")
            ->action('View Certification Interests', route('admin.certifications.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'certification_interest',
            'application_id' => $this->application->id,
            'shop_id' => $this->application->shop_id,
            'shop_name' => $this->application->shop->storefront_name,
            'message' => $this->application->shop->storefront_name.' is interested in halal certification.',
            'url' => route('admin.certifications.index'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
