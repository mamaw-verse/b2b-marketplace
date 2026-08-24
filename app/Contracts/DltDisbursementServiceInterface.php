<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Payout;
use App\Models\SellerPayoutAccount;
use App\ValueObjects\DisbursementResult;
use App\ValueObjects\DisbursementStatus;

interface DltDisbursementServiceInterface
{
    /**
     * Disburse a payout to the seller's bank account via DLT.
     */
    public function disburse(Payout $payout, SellerPayoutAccount $account): DisbursementResult;

    /**
     * Check the status of an existing disbursement transaction.
     */
    public function checkStatus(string $dltTransactionId): DisbursementStatus;
}
