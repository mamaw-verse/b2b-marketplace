<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\RfqRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RfqClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RfqRecipient $recipient,
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
            ? __('The RFQ was automatically closed due to 7 days of inactivity.')
            : __('The buyer has closed this RFQ.');

        return (new MailMessage)
            ->subject(__('RFQ Closed: :title', ['title' => $this->recipient->rfq->title]))
            ->line($reason)
            ->line(__('RFQ: :title', ['title' => $this->recipient->rfq->title]))
            ->action(__('View Details'), url('/hub/rfqs/'.$this->recipient->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rfq_closed',
            'rfq_id' => $this->recipient->rfq_id,
            'rfq_recipient_id' => $this->recipient->id,
            'closed_by' => $this->closedBy,
            'message' => 'RFQ closed: '.$this->recipient->rfq->title,
            'url' => url('/hub/rfqs/'.$this->recipient->id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
