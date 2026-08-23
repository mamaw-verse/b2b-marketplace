<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class DisputeResolvedNotification extends Notification implements ShouldQueue
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
            ->subject('Dispute Resolved - Order #'.$this->dispute->order_id)
            ->line('The dispute has been resolved: '.$this->dispute->resolution_type)
            ->action('View Details', $this->urlForRecipient($notifiable))
            ->line('Thank you for your patience.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'dispute_resolved',
            'dispute_id' => $this->dispute->id,
            'order_id' => $this->dispute->order_id,
            'resolution_type' => $this->dispute->resolution_type,
            'message' => 'The dispute has been resolved: '.$this->dispute->resolution_type,
            'url' => $this->urlForRecipient($notifiable),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    private function urlForRecipient(object $notifiable): string
    {
        $order = $this->dispute->order;

        // If the recipient is the buyer, link to buyer's order page
        if ($notifiable->id === $order->buyer_user_id) {
            return url('/account/orders/'.$order->id);
        }

        // Otherwise (seller), link to hub order page
        return url('/hub/orders/'.$order->id);
    }
}
