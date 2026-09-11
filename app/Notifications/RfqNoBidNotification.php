<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\RfqRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RfqNoBidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  'timeout'|'seller_closed'  $reason  Why the recipient moved to no_bid.
     */
    public function __construct(
        public RfqRecipient $rfqRecipient,
        public string $reason = 'timeout',
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sellerName = $this->rfqRecipient->shop->storefront_name;

        $mail = (new MailMessage)
            ->subject($this->reason === 'seller_closed'
                ? __('Seller closed your RFQ')
                : __('Seller did not respond to your RFQ'));

        if ($this->reason === 'seller_closed') {
            $mail->line(__('The seller (:sellerName) has closed your request for quotation and will not be submitting a quote.', ['sellerName' => $sellerName]));

            if ($this->rfqRecipient->decline_reason) {
                $mail->line(__('Reason: :reason', ['reason' => $this->rfqRecipient->decline_reason]));
            }
        } else {
            $mail->line(__('The seller (:sellerName) did not respond to your request for quotation within 7 days.', ['sellerName' => $sellerName]));
        }

        return $mail
            ->line(__('RFQ: :title', ['title' => $this->rfqRecipient->rfq->title]))
            ->action(__('View RFQ'), url('/account/rfqs/'.$this->rfqRecipient->rfq_id))
            ->line(__('You may close this RFQ or wait for it to auto-close.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rfq_no_bid',
            'rfq_id' => $this->rfqRecipient->rfq_id,
            'rfq_recipient_id' => $this->rfqRecipient->id,
            'reason' => $this->reason,
            'message' => $this->reason === 'seller_closed'
                ? 'Seller closed your RFQ.'
                : 'Seller did not respond to your RFQ.',
            'url' => url('/account/rfqs/'.$this->rfqRecipient->rfq_id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
