<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\AwardQuotationAction;
use App\Actions\RejectQuotationAction;
use App\Events\QuotationAwarded;
use App\Events\QuotationRejected;
use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class QuotationActionController extends Controller
{
    /**
     * Award a submitted quotation.
     */
    public function award(Request $request, Quotation $quotation, AwardQuotationAction $action): JsonResponse
    {
        Gate::authorize('award', $quotation);

        $result = $action->execute($quotation);

        if (! $result['success']) {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => $result['error'],
                ],
            ], 409);
        }

        // Update activity on the parent RFQ
        $result['quotation']->rfq->touchActivity();

        // Dispatch event after successful award
        QuotationAwarded::dispatch($result['quotation']);

        return response()->json([
            'data' => [
                'id' => $result['quotation']->id,
                'status' => 'awarded',
                'rfq_id' => $result['quotation']->rfq_id,
            ],
        ]);
    }

    /**
     * Reject a submitted quotation.
     */
    public function reject(Request $request, Quotation $quotation, RejectQuotationAction $action): JsonResponse
    {
        Gate::authorize('reject', $quotation);

        $result = $action->execute($quotation);

        if (! $result['success']) {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => $result['error'],
                ],
            ], 409);
        }

        // Update activity on the parent RFQ
        $result['quotation']->rfq->touchActivity();

        // Dispatch event after successful rejection
        QuotationRejected::dispatch($result['quotation']);

        return response()->json([
            'data' => [
                'id' => $result['quotation']->id,
                'status' => 'rejected',
                'rfq_id' => $result['quotation']->rfq_id,
            ],
        ]);
    }
}
