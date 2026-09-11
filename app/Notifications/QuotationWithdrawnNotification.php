<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class QuotationWithdrawnNotification extends Notification implements ShouldQueue
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
            ->subject(__('Quotation Withdrawn'))
            ->line(__('A seller has withdrawn their quotation.'))
            ->line(__('RFQ: :title', ['title' => $this->quotation->rfq->title]))
            ->action(__('View RFQ'), url('/account/rfqs/'.$this->quotation->rfq_id))
            ->line(__('The seller may submit a revised quotation.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quotation_withdrawn',
            'quotation_id' => $this->quotation->id,
            'rfq_id' => $this->quotation->rfq_id,
            'message' => 'A quotation was withdrawn by the seller.',
            'url' => url('/account/rfqs/'.$this->quotation->rfq_id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
