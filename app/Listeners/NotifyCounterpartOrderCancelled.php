<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Notifications\OrderCancelledNotification;

final class NotifyCounterpartOrderCancelled
{
    public function handle(OrderCancelled $event): void
    {
        $buyer = $event->order->buyer;
        $seller = $event->order->shop->user ?? null;

        if ($buyer) {
            $buyer->notify(new OrderCancelledNotification($event->order));
        }

        if ($seller) {
            $seller->notify(new OrderCancelledNotification($event->order));
        }
    }
}
