<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RfqClosed;
use App\Notifications\RfqClosedNotification;

final class NotifySellerRfqClosed
{
    public function handle(RfqClosed $event): void
    {
        $rfq = $event->rfq;

        foreach ($rfq->recipients()->with('shop.user')->get() as $recipient) {
            $shopOwner = $recipient->shop->user ?? null;
            if ($shopOwner) {
                $shopOwner->notify(new RfqClosedNotification($rfq, 'buyer'));
            }
        }
    }
}
