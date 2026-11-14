<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\QuotationAwarded;
use App\Notifications\QuotationAwardedNotification;
use Illuminate\Support\Facades\Notification;

final class NotifySellerQuotationAwarded
{
    public function handle(QuotationAwarded $event): void
    {
        $shop = $event->quotation->rfqRecipient->shop;
        Notification::send($shop->operationalRecipients(), new QuotationAwardedNotification($event->quotation));
    }
}
