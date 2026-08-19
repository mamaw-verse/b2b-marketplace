<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Rfq;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RfqClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Rfq $rfq,
        public string $closedBy = 'buyer',
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reason = $this->closedBy === 'system'
            ? 'The RFQ was automatically closed due to 7 days of inactivity.'
            : 'The buyer has closed this RFQ.';

        return (new MailMessage)
            ->subject('RFQ Closed: '.$this->rfq->title)
            ->line($reason)
            ->line('RFQ: '.$this->rfq->title)
            ->action('View Details', url('/hub/rfqs'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rfq_closed',
            'rfq_id' => $this->rfq->id,
            'closed_by' => $this->closedBy,
            'message' => 'RFQ closed: '.$this->rfq->title,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
