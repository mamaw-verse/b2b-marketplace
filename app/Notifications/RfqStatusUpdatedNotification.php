<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\RfqRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RfqStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RfqRecipient $recipient,
        public string $previousStatus,
        public string $currentStatus,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('RFQ Status Updated: '.$this->recipient->rfq->title)
            ->line('The status of your Request for Quotation has been updated.')
            ->line('RFQ: '.$this->recipient->rfq->title)
            ->line('Status: '.$this->statusLabel($this->currentStatus))
            ->action('View RFQ', route('account.rfqs.show', $this->recipient->rfq_id))
            ->line('You can view the latest details of your RFQ in your account.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rfq_status_updated',
            'rfq_id' => $this->recipient->rfq_id,
            'previous_status' => $this->previousStatus,
            'current_status' => $this->currentStatus,
            'message' => 'Your RFQ status has been updated to '.$this->statusLabel($this->currentStatus).'.',
            'url' => route('account.rfqs.show', $this->recipient->rfq_id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Human-readable label for an admin status value.
     */
    public function statusLabel(string $status): string
    {
        return match ($status) {
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            default => 'Pending',
        };
    }
}
