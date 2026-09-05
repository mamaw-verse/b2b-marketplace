<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderReturned;
use App\Notifications\OrderReturnedNotification;

final class NotifyBuyerOrderReturned
{
    public function handle(OrderReturned $event): void
    {
        $buyer = $event->order->buyer;

        if ($buyer) {
            $buyer->notify(new OrderReturnedNotification($event->order));
        }
    }
}
