<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DltDisbursementServiceInterface;
use App\Models\Payout;
use App\Models\SellerPayoutAccount;
use App\ValueObjects\DisbursementResult;
use App\ValueObjects\DisbursementStatus;
use Illuminate\Support\Str;

final class MockDltDisbursementService implements DltDisbursementServiceInterface
{
    public function disburse(Payout $payout, SellerPayoutAccount $account): DisbursementResult
    {
        return new DisbursementResult(
            success: true,
            dltReferenceId: 'MOCK-REF-'.Str::upper(Str::random(12)),
            dltTransactionId: 'MOCK-TXN-'.Str::upper(Str::random(8)),
            rawResponse: ['status' => 'SUCCESS', 'mock' => true],
        );
    }

    public function checkStatus(string $dltTransactionId): DisbursementStatus
    {
        return new DisbursementStatus(
            status: 'succeeded',
            rawResponse: ['status' => 'SUCCESS', 'mock' => true],
        );
    }
}
