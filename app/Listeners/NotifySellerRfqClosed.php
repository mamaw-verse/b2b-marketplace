<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RfqClosed;
use App\Notifications\RfqClosedNotification;
use Illuminate\Support\Facades\Notification;

final class NotifySellerRfqClosed
{
    public function handle(RfqClosed $event): void
    {
        $rfq = $event->rfq;

        foreach ($rfq->recipients()->with('shop.user')->get() as $recipient) {
            Notification::send($recipient->shop->operationalRecipients(), new RfqClosedNotification($recipient, 'buyer'));
        }
    }
}
