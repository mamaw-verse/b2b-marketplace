<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Notifications\OrderPaidNotification;

final class NotifySellerOrderPaid
{
    public function handle(OrderPaid $event): void
    {
        $seller = $event->order->shop->user ?? null;

        if ($seller) {
            $seller->notify(new OrderPaidNotification($event->order));
        }
    }
}
