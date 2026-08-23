<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DisputeResolved;
use App\Notifications\DisputeResolvedNotification;

final class NotifyPartiesDisputeResolved
{
    public function handle(DisputeResolved $event): void
    {
        $buyer = $event->dispute->order->buyer;
        $seller = $event->dispute->order->shop->user ?? null;

        if ($buyer) {
            $buyer->notify(new DisputeResolvedNotification($event->dispute));
        }

        if ($seller) {
            $seller->notify(new DisputeResolvedNotification($event->dispute));
        }
    }
}
