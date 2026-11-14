<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DisputeResolved;
use App\Notifications\DisputeResolvedNotification;
use Illuminate\Support\Facades\Notification;

final class NotifyPartiesDisputeResolved
{
    public function handle(DisputeResolved $event): void
    {
        $buyer = $event->dispute->order->buyer;
        $shop = $event->dispute->order->shop;

        if ($buyer) {
            $buyer->notify(new DisputeResolvedNotification($event->dispute));
        }

        Notification::send($shop->operationalRecipients(), new DisputeResolvedNotification($event->dispute));
    }
}
