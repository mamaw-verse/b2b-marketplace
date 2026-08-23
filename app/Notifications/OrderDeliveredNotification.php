<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderDeliveredNotification extends Notification implements ShouldQueue
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
            ->subject('Your Order Has Been Delivered')
            ->line('Your order #'.$this->order->id.' has been delivered.')
            ->line('Please confirm receipt within 7 days.')
            ->action('Confirm Receipt', url('/account/orders/'.$this->order->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_delivered',
            'order_id' => $this->order->id,
            'message' => 'Your order #'.$this->order->id.' has been delivered.',
            'url' => url('/account/orders/'.$this->order->id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
