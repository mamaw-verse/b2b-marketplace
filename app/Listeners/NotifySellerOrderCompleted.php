<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Notifications\OrderCompletedNotification;

final class NotifySellerOrderCompleted
{
    public function handle(OrderCompleted $event): void
    {
        $seller = $event->order->shop->user ?? null;

        if ($seller) {
            $seller->notify(new OrderCompletedNotification($event->order));
        }
    }
}
