<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\QuotationAwarded;
use App\Notifications\QuotationAwardedNotification;

final class NotifySellerQuotationAwarded
{
    public function handle(QuotationAwarded $event): void
    {
        $seller = $event->quotation->rfqRecipient->shop->user ?? null;

        if ($seller) {
            $seller->notify(new QuotationAwardedNotification($event->quotation));
        }
    }
}
