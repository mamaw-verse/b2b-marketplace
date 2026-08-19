<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

final class AwardQuotationAction
{
    /**
     * Award a quotation with race condition protection.
     *
     * Uses SELECT ... FOR UPDATE to prevent concurrent award/reject/expire
     * from creating an inconsistent state.
     *
     * @return array{success: bool, quotation: Quotation, error?: string}
     */
    public function execute(Quotation $quotation): array
    {
        return DB::transaction(function () use ($quotation) {
            // Lock the row to prevent concurrent modifications
            $locked = Quotation::where('id', $quotation->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== 'submitted') {
                return [
                    'success' => false,
                    'quotation' => $locked ?? $quotation,
                    'error' => 'This quotation is no longer in a state that can be awarded. It may have expired or been withdrawn.',
                ];
            }

            $locked->update(['status' => 'awarded']);

            return [
                'success' => true,
                'quotation' => $locked->fresh(),
            ];
        });

        // Dispatch event after transaction commits (handled by caller or afterCommit)
    }
}
