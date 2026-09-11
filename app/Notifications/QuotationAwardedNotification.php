<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class QuotationAwardedNotification extends Notification implements ShouldQueue
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
            ->subject(__('Your Quotation Was Awarded!'))
            ->line(__('Great news! The buyer has awarded your quotation.'))
            ->line(__('RFQ: :title', ['title' => $this->quotation->rfq->title]))
            ->line(__('Total: :total', ['total' => $this->quotation->currency.' '.number_format((float) $this->quotation->total_amount, 2)]))
            ->action(__('View Details'), url('/hub/rfqs/'.$this->quotation->rfqRecipient->id))
            ->line(__('The buyer has accepted your offer.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quotation_awarded',
            'quotation_id' => $this->quotation->id,
            'rfq_id' => $this->quotation->rfq_id,
            'rfq_recipient_id' => $this->quotation->rfqRecipient->id,
            'message' => 'Your quotation was awarded!',
            'url' => url('/hub/rfqs/'.$this->quotation->rfqRecipient->id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
