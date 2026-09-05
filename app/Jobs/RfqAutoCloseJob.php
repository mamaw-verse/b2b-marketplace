<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Rfq;
use App\Notifications\RfqClosedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Scan open RFQs with no activity for 7+ days and auto-close them.
 * Cascades submitted quotations to not_awarded within a DB transaction.
 *
 * Idempotent: only transitions open RFQs; already-closed are no-ops.
 */
final class RfqAutoCloseJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $threshold = now()->subDays(7);

        $rfqs = Rfq::where('status', 'open')
            ->where('last_activity_at', '<', $threshold)
            ->with(['recipients.shop.user'])
            ->get();

        $count = 0;

        foreach ($rfqs as $rfq) {
            DB::transaction(function () use ($rfq) {
                $rfq->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);

                if (! config('marketplace.features.admin_rfq_management_enabled')) {
                    // Cascade submitted quotations to not_awarded
                    $rfq->quotations()
                        ->where('status', 'submitted')
                        ->update(['status' => 'not_awarded']);
                } else {
                    // Update admin_status on recipients
                    $rfq->recipients()
                        ->whereNotIn('admin_status', ['resolved', 'closed'])
                        ->update(['admin_status' => 'closed']);
                }
            });

            if (! config('marketplace.features.admin_rfq_management_enabled')) {
                // Notify recipient sellers
                foreach ($rfq->recipients as $recipient) {
                    $shopOwner = $recipient->shop->user ?? null;
                    if ($shopOwner) {
                        $shopOwner->notify(new RfqClosedNotification($recipient, 'system'));
                    }
                }
            }

            $count++;
        }

        if ($count > 0) {
            Log::info("RfqAutoCloseJob: auto-closed {$count} RFQs due to inactivity.");
        }
    }
}
