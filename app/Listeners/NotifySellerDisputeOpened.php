<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DisputeOpened;
use App\Notifications\DisputeOpenedNotification;
use Illuminate\Support\Facades\Notification;

final class NotifySellerDisputeOpened
{
    public function handle(DisputeOpened $event): void
    {
        $shop = $event->dispute->order->shop;
        Notification::send($shop->operationalRecipients(), new DisputeOpenedNotification($event->dispute));
    }
}
