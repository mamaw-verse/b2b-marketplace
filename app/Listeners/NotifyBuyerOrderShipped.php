<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Notifications\OrderShippedNotification;

final class NotifyBuyerOrderShipped
{
    public function handle(OrderShipped $event): void
    {
        $buyer = $event->order->buyer;

        if ($buyer) {
            $buyer->notify(new OrderShippedNotification($event->order));
        }
    }
}
