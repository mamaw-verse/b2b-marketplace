<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Rfq;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RfqSubmittedAdminNotification extends Notification implements ShouldQueue
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
        $buyerName = $this->rfq->buyer_snapshot['business_name']
            ?? $this->rfq->buyer?->email
            ?? 'A buyer';

        return (new MailMessage)
            ->subject('New RFQ Submitted: '.$this->rfq->title)
            ->line('A new Request for Quotation has been submitted and requires your attention.')
            ->line('Title: '.$this->rfq->title)
            ->line('From: '.$buyerName)
            ->action('Review RFQ', route('admin.rfqs.show', $this->rfq->id))
            ->line('Please review and assign this RFQ in the admin panel.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rfq_submitted_admin',
            'rfq_id' => $this->rfq->id,
            'title' => $this->rfq->title,
            'message' => 'New RFQ submitted: '.$this->rfq->title,
            'url' => route('admin.rfqs.show', $this->rfq->id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
