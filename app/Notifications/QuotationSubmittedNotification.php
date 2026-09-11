<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class QuotationSubmittedNotification extends Notification implements ShouldQueue
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
            ->subject(__('New Quotation Received'))
            ->line(__('A seller has submitted a quotation for your RFQ.'))
            ->line(__('RFQ: :title', ['title' => $this->quotation->rfq->title]))
            ->line(__('Total: :total', ['total' => $this->quotation->currency.' '.number_format((float) $this->quotation->total_amount, 2)]))
            ->action(__('View Quotation'), url('/account/rfqs/'.$this->quotation->rfq_id))
            ->line(__('Review and award or reject this quotation.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quotation_submitted',
            'quotation_id' => $this->quotation->id,
            'rfq_id' => $this->quotation->rfq_id,
            'message' => 'New quotation received for your RFQ.',
            'url' => url('/account/rfqs/'.$this->quotation->rfq_id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
