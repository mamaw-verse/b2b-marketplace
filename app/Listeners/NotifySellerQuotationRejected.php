<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\QuotationRejected;
use App\Notifications\QuotationRejectedNotification;
use Illuminate\Support\Facades\Notification;

final class NotifySellerQuotationRejected
{
    public function handle(QuotationRejected $event): void
    {
        $shop = $event->quotation->rfqRecipient->shop;
        Notification::send($shop->operationalRecipients(), new QuotationRejectedNotification($event->quotation));
    }
}
