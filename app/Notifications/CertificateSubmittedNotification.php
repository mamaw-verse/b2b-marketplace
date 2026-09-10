<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ShopCertificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CertificateSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ShopCertificate $certificate,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $businessName = $this->certificate->shop?->registered_business_name ?? 'Unknown';

        return (new MailMessage)
            ->subject('New Halal Certificate Submitted for Review')
            ->line("**{$businessName}** has submitted a new halal certificate for review.")
            ->line('Please review the certificate and approve or reject it.')
            ->action('Review Certificate', route('admin.certifications.index'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $businessName = $this->certificate->shop?->registered_business_name ?? 'Unknown';

        return [
            'type' => 'certificate_submitted',
            'certificate_id' => $this->certificate->id,
            'shop_id' => $this->certificate->shop_id,
            'business_name' => $businessName,
            'message' => "{$businessName} submitted a new halal certificate for review.",
            'url' => route('admin.certifications.index'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
