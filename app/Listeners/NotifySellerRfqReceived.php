<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RfqCreated;
use App\Notifications\RfqReceivedNotification;

final class NotifySellerRfqReceived
{
    public function handle(RfqCreated $event): void
    {
        $rfq = $event->rfq;

        foreach ($rfq->recipients()->with('shop.user')->get() as $recipient) {
            $shopOwner = $recipient->shop->user ?? null;
            if ($shopOwner) {
                $shopOwner->notify(new RfqReceivedNotification($recipient));
            }
        }
    }
}
