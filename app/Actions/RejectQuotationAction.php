<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

final class RejectQuotationAction
{
    /**
     * Reject a quotation with race condition protection.
     *
     * @return array{success: bool, quotation: Quotation, error?: string}
     */
    public function execute(Quotation $quotation): array
    {
        return DB::transaction(function () use ($quotation) {
            $locked = Quotation::where('id', $quotation->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== 'submitted') {
                return [
                    'success' => false,
                    'quotation' => $locked ?? $quotation,
                    'error' => 'This quotation is no longer in a state that can be rejected.',
                ];
            }

            $locked->update(['status' => 'rejected']);

            return [
                'success' => true,
                'quotation' => $locked->fresh(),
            ];
        });
    }
}
