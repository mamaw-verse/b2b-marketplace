<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class QuotationExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Quotation $quotation,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Quotation Expired'))
            ->line(__('A quotation has expired without a response.'))
            ->line(__('RFQ: :title', ['title' => $this->quotation->rfq->title]))
            ->line(__('Total: :total', ['total' => $this->quotation->currency.' '.number_format((float) $this->quotation->total_amount, 2)]))
            ->action(__('View RFQ'), url('/account/rfqs/'.$this->quotation->rfq_id));
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'quotation_expired',
            'quotation_id' => $this->quotation->id,
            'rfq_id' => $this->quotation->rfq_id,
            'message' => 'A quotation has expired.',
        ]);
    }
}
