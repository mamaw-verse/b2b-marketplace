<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\RfqRecipient;
use App\Notifications\RfqNoBidNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Scan rfq_recipients that have been inactive for 7+ days and transition to no_bid.
 *
 * Idempotent: only transitions rows not already in no_bid; safe to run repeatedly.
 */
final class SellerNoBidTimeoutJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $threshold = now()->subDays(7);

        $recipients = RfqRecipient::whereIn('status', ['received', 'reviewed', 'in_progress'])
            ->where('last_activity_at', '<', $threshold)
            ->with(['rfq.user', 'shop'])
            ->get();

        $count = 0;

        foreach ($recipients as $recipient) {
            $recipient->update([
                'status' => 'no_bid',
                'last_activity_at' => now(),
            ]);

            // Notify the buyer that the seller did not respond
            $buyer = $recipient->rfq->user;
            if ($buyer) {
                $buyer->notify(new RfqNoBidNotification($recipient));
            }

            $count++;
        }

        if ($count > 0) {
            Log::info("SellerNoBidTimeoutJob: transitioned {$count} recipients to no_bid.");
        }
    }
}
