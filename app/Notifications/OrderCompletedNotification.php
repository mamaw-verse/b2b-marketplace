<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order Completed - Payout Processed')
            ->line('Order #'.$this->order->id.' has been completed and payout processed.')
            ->action('View Order', url('/hub/orders/'.$this->order->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_completed',
            'order_id' => $this->order->id,
            'message' => 'Order #'.$this->order->id.' has been completed and payout processed.',
            'url' => url('/hub/orders/'.$this->order->id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
