<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Support\Facades\Notification;

final class NotifySellerOrderCompleted
{
    public function handle(OrderCompleted $event): void
    {
        $shop = $event->order->shop;
        Notification::send($shop->operationalRecipients(), new OrderCompletedNotification($event->order));
    }
}
