<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Rfq;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RfqReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Rfq $rfq,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New RFQ Received: '.$this->rfq->title)
            ->line('You have received a new Request for Quotation.')
            ->line('Title: '.$this->rfq->title)
            ->line('From: '.($this->rfq->buyer_snapshot['business_name'] ?? 'A buyer'))
            ->action('View RFQ', url('/hub/rfqs'))
            ->line('Please review and respond to this inquiry.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rfq_received',
            'rfq_id' => $this->rfq->id,
            'title' => $this->rfq->title,
            'message' => 'New RFQ received: '.$this->rfq->title,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
