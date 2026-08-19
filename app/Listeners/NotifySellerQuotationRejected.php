<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\QuotationRejected;
use App\Notifications\QuotationRejectedNotification;

final class NotifySellerQuotationRejected
{
    public function handle(QuotationRejected $event): void
    {
        $seller = $event->quotation->rfqRecipient->shop->user ?? null;

        if ($seller) {
            $seller->notify(new QuotationRejectedNotification($event->quotation));
        }
    }
}
