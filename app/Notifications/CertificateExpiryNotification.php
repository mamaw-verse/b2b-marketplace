<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ShopCertificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CertificateExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ShopCertificate $certificate,
        public int $daysUntilExpiry,
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
        $shopName = $this->certificate->shop->storefront_name;
        $registrationNumber = $this->certificate->registration_number;

        if ($this->daysUntilExpiry <= 0) {
            $subject = "Certificate {$registrationNumber} has expired";
            $line = "The halal certificate ({$registrationNumber}) for **{$shopName}** has expired.";
        } else {
            $subject = "Certificate {$registrationNumber} expires in {$this->daysUntilExpiry} days";
            $line = "The halal certificate ({$registrationNumber}) for **{$shopName}** will expire in {$this->daysUntilExpiry} days.";
        }

        return (new MailMessage)
            ->subject($subject)
            ->line($line)
            ->line('Please renew your certificate to maintain your shop listing.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $registrationNumber = $this->certificate->registration_number;
        $message = $this->daysUntilExpiry <= 0
            ? "Halal certificate {$registrationNumber} has expired."
            : "Halal certificate {$registrationNumber} expires in {$this->daysUntilExpiry} days.";

        $url = url('/account/onboarding');

        return [
            'type' => $this->daysUntilExpiry <= 0 ? 'certificate_expired' : 'certificate_expiring',
            'certificate_id' => $this->certificate->id,
            'registration_number' => $this->certificate->registration_number,
            'days_until_expiry' => $this->daysUntilExpiry,
            'shop_name' => $this->certificate->shop->storefront_name,
            'message' => $message,
            'url' => $url,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
