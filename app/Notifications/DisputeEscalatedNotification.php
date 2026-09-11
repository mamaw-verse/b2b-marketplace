<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class DisputeEscalatedNotification extends Notification implements ShouldQueue
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
            ->subject(__('Dispute Escalated - Requires Admin Review'))
            ->line(__('A dispute on order #:orderId has been escalated for arbitration.', ['orderId' => $this->dispute->order_id]))
            ->action(__('Review Dispute'), url('/admin/disputes'))
            ->line(__('Please review and resolve this dispute.'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'dispute_escalated',
            'dispute_id' => $this->dispute->id,
            'order_id' => $this->dispute->order_id,
            'message' => 'A dispute on order #'.$this->dispute->order_id.' has been escalated for arbitration.',
            'url' => url('/admin/disputes'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
