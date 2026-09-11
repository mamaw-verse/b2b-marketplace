<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ShopCertificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CertificateApprovedNotification extends Notification implements ShouldQueue
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
        $shopName = $this->certificate->shop?->storefront_name ?? 'your shop';

        return (new MailMessage)
            ->subject(__('Your Halal Certificate Has Been Approved'))
            ->line(__('Your halal certificate for **:shopName** has been reviewed and approved.', ['shopName' => $shopName]))
            ->line(__('Your certificate is now active on the marketplace.'))
            ->action(__('View Certificates'), route('hub.certifications.index'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $shopName = $this->certificate->shop?->storefront_name ?? 'your shop';

        return [
            'type' => 'certificate_approved',
            'certificate_id' => $this->certificate->id,
            'shop_id' => $this->certificate->shop_id,
            'shop_name' => $shopName,
            'message' => "Your halal certificate for {$shopName} has been approved.",
            'url' => route('hub.certifications.index'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
