<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\QuotationSubmitted;
use App\Notifications\QuotationSubmittedNotification;

final class NotifyBuyerQuotationSubmitted
{
    public function handle(QuotationSubmitted $event): void
    {
        $buyer = $event->quotation->rfq->user;

        if ($buyer) {
            $buyer->notify(new QuotationSubmittedNotification($event->quotation));
        }
    }
}
