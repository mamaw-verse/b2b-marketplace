<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Notifications\OrderCancelledNotification;
use Illuminate\Support\Facades\Notification;

final class NotifyCounterpartOrderCancelled
{
    public function handle(OrderCancelled $event): void
    {
        $buyer = $event->order->buyer;
        $shop = $event->order->shop;

        if ($buyer) {
            $buyer->notify(new OrderCancelledNotification($event->order));
        }

        Notification::send($shop->operationalRecipients(), new OrderCancelledNotification($event->order));
    }
}
