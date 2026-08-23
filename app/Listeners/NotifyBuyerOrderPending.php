<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderSubmittedForPayment;
use App\Notifications\OrderPendingPaymentNotification;

final class NotifyBuyerOrderPending
{
    public function handle(OrderSubmittedForPayment $event): void
    {
        $buyer = $event->order->buyer;

        if ($buyer) {
            $buyer->notify(new OrderPendingPaymentNotification($event->order));
        }
    }
}
