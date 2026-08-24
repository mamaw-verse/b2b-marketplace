<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DltDisbursementServiceInterface;
use App\Models\Payout;
use App\Models\SellerPayoutAccount;
use App\ValueObjects\DisbursementResult;
use App\ValueObjects\DisbursementStatus;

final class DltDisbursementService implements DltDisbursementServiceInterface
{
    public function disburse(Payout $payout, SellerPayoutAccount $account): DisbursementResult
    {
        // Stub: real DLT API integration not yet configured
        return new DisbursementResult(
            success: false,
            errorMessage: 'DLT Disbursement API not yet configured',
            rawResponse: ['error' => 'NOT_CONFIGURED'],
        );
    }

    public function checkStatus(string $dltTransactionId): DisbursementStatus
    {
        return new DisbursementStatus(
            status: 'failed',
            rawResponse: ['error' => 'NOT_CONFIGURED'],
        );
    }
}
