<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class QuotationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Quotation $quotation,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Quotation Rejected'))
            ->line(__('The buyer has rejected your quotation.'))
            ->line(__('RFQ: :title', ['title' => $this->quotation->rfq->title]))
            ->action(__('View Details'), url('/hub/rfqs/'.$this->quotation->rfqRecipient->id))
            ->line(__('You can submit a revised quotation with different terms.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quotation_rejected',
            'quotation_id' => $this->quotation->id,
            'rfq_id' => $this->quotation->rfq_id,
            'rfq_recipient_id' => $this->quotation->rfqRecipient->id,
            'message' => 'Your quotation was rejected.',
            'url' => url('/hub/rfqs/'.$this->quotation->rfqRecipient->id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
