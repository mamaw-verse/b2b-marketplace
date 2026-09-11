<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\RfqRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RfqReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RfqRecipient $recipient,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New RFQ Received: :title', ['title' => $this->recipient->rfq->title]))
            ->line(__('You have received a new Request for Quotation.'))
            ->line(__('Title: :title', ['title' => $this->recipient->rfq->title]))
            ->line(__('From: :name', ['name' => $this->recipient->rfq->buyer_snapshot['business_name'] ?? __('A buyer')]))
            ->action(__('View RFQ'), url('/hub/rfqs/'.$this->recipient->id))
            ->line(__('Please review and respond to this inquiry.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rfq_received',
            'rfq_id' => $this->recipient->rfq_id,
            'rfq_recipient_id' => $this->recipient->id,
            'title' => $this->recipient->rfq->title,
            'message' => 'New RFQ received: '.$this->recipient->rfq->title,
            'url' => url('/hub/rfqs/'.$this->recipient->id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
