<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class QuotationAwardedNotification extends Notification implements ShouldQueue
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
            ->subject('Your Quotation Was Awarded!')
            ->line('Great news! The buyer has awarded your quotation.')
            ->line('RFQ: '.$this->quotation->rfq->title)
            ->line('Total: '.$this->quotation->currency.' '.number_format((float) $this->quotation->total_amount, 2))
            ->action('View Details', url('/hub/rfqs'))
            ->line('The buyer has accepted your offer.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quotation_awarded',
            'quotation_id' => $this->quotation->id,
            'rfq_id' => $this->quotation->rfq_id,
            'message' => 'Your quotation was awarded!',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
