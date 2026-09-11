<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderPaidNotification extends Notification implements ShouldQueue
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
            ->subject(__('Order Paid - Ready to Fulfil'))
            ->line(__('A buyer has paid for order #:id.', ['id' => $this->order->id]))
            ->line(__('Total: PHP :total', ['total' => number_format((float) $this->order->order_total, 2)]))
            ->action(__('View Order'), url('/hub/orders/'.$this->order->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_paid',
            'order_id' => $this->order->id,
            'message' => 'A buyer has paid for order #'.$this->order->id.'.',
            'url' => url('/hub/orders/'.$this->order->id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
