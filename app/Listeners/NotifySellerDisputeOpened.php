<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DisputeOpened;
use App\Notifications\DisputeOpenedNotification;

final class NotifySellerDisputeOpened
{
    public function handle(DisputeOpened $event): void
    {
        $seller = $event->dispute->order->shop->user ?? null;

        if ($seller) {
            $seller->notify(new DisputeOpenedNotification($event->dispute));
        }
    }
}
