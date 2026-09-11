<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderPendingPaymentNotification extends Notification implements ShouldQueue
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
            ->subject(__('New Order Awaiting Payment'))
            ->line(__('An order from :storefront requires your payment.', ['storefront' => $this->order->shop->storefront_name]))
            ->line(__('Total: PHP :total', ['total' => number_format((float) $this->order->order_total, 2)]))
            ->action(__('Pay Now'), url('/account/orders/'.$this->order->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_pending_payment',
            'order_id' => $this->order->id,
            'message' => 'An order from '.$this->order->shop->storefront_name.' requires your payment.',
            'url' => url('/account/orders/'.$this->order->id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
