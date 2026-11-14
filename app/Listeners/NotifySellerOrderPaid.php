<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Notifications\OrderPaidNotification;
use Illuminate\Support\Facades\Notification;

final class NotifySellerOrderPaid
{
    public function handle(OrderPaid $event): void
    {
        $shop = $event->order->shop;
        Notification::send($shop->operationalRecipients(), new OrderPaidNotification($event->order));
    }
}
