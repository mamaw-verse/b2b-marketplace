<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\RfqClosed;
use App\Models\Rfq;
use Illuminate\Support\Facades\DB;

final class CloseRfqAction
{
    /**
     * Close an RFQ and cascade submitted quotations to not_awarded.
     *
     * Atomically within a single DB transaction per design.md §6.2.
     *
     * @return array{success: bool, rfq: Rfq, quotations_not_awarded_count: int, error?: string}
     */
    public function execute(Rfq $rfq): array
    {
        if ($rfq->isClosed()) {
            return [
                'success' => false,
                'rfq' => $rfq,
                'quotations_not_awarded_count' => 0,
                'error' => 'This RFQ is already closed.',
            ];
        }

        $quotationsNotAwardedCount = 0;

        DB::transaction(function () use ($rfq, &$quotationsNotAwardedCount) {
            // Close the RFQ
            $rfq->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            // Cascade: transition all submitted quotations to not_awarded
            $quotationsNotAwardedCount = $rfq->quotations()
                ->where('status', 'submitted')
                ->update(['status' => 'not_awarded']);
        });

        // Dispatch event after commit
        RfqClosed::dispatch($rfq->fresh(), $quotationsNotAwardedCount);

        return [
            'success' => true,
            'rfq' => $rfq->fresh(),
            'quotations_not_awarded_count' => $quotationsNotAwardedCount,
        ];
    }
}
