<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Quotation;
use App\Notifications\QuotationExpiredNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Scan submitted quotations past their valid_until date and expire them.
 *
 * Idempotent: only transitions submitted quotations past valid_until; already-expired are no-ops.
 */
final class QuotationExpiryJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $quotations = Quotation::where('status', 'submitted')
            ->where('valid_until', '<', now())
            ->with(['rfq.user', 'rfqRecipient.shop.user'])
            ->get();

        $count = 0;

        foreach ($quotations as $quotation) {
            $quotation->update(['status' => 'expired']);

            // Notify both parties
            $buyer = $quotation->rfq->user;
            $seller = $quotation->rfqRecipient->shop->user ?? null;

            if ($buyer) {
                $buyer->notify(new QuotationExpiredNotification($quotation));
            }
            if ($seller) {
                $seller->notify(new QuotationExpiredNotification($quotation));
            }

            $count++;
        }

        if ($count > 0) {
            Log::info("QuotationExpiryJob: expired {$count} quotations past valid_until.");
        }
    }
}
