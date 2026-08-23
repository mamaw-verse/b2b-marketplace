<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Notifications\OrderDeliveredNotification;

final class NotifyBuyerOrderDelivered
{
    public function handle(OrderDelivered $event): void
    {
        $buyer = $event->order->buyer;

        if ($buyer) {
            $buyer->notify(new OrderDeliveredNotification($event->order));
        }
    }
}
