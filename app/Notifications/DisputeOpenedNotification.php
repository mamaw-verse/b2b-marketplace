<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class DisputeOpenedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Dispute $dispute,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Dispute Opened on Order #:orderId', ['orderId' => $this->dispute->order_id]))
            ->line(__('A buyer has opened a :type dispute.', ['type' => $this->dispute->type]))
            ->line(__('Reason: :reason', ['reason' => str($this->dispute->reason)->limit(100)]))
            ->action(__('View Dispute'), url('/hub/orders/'.$this->dispute->order_id))
            ->line(__('Please review and respond to this dispute.'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'dispute_opened',
            'dispute_id' => $this->dispute->id,
            'order_id' => $this->dispute->order_id,
            'message' => 'A buyer has opened a '.$this->dispute->type.' dispute.',
            'url' => url('/hub/orders/'.$this->dispute->order_id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
