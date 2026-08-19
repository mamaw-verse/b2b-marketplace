<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\RfqRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RfqNoBidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RfqRecipient $rfqRecipient,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Seller did not respond to your RFQ')
            ->line('The seller ('.$this->rfqRecipient->shop->storefront_name.') did not respond to your request for quotation within 7 days.')
            ->line('RFQ: '.$this->rfqRecipient->rfq->title)
            ->action('View RFQ', url('/account/rfqs/'.$this->rfqRecipient->rfq_id))
            ->line('You may close this RFQ or wait for it to auto-close.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rfq_no_bid',
            'rfq_id' => $this->rfqRecipient->rfq_id,
            'message' => 'Seller did not respond to your RFQ.',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
