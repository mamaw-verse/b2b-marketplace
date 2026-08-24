<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\DltDisbursementServiceInterface;
use App\Models\Payout;
use App\Models\PayoutDisbursement;
use App\Models\SellerPayoutAccount;
use App\Models\User;
use App\Notifications\PayoutCompletedNotification;
use App\Notifications\PayoutFailedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class ReleasePayoutAction
{
    public function __construct(
        private readonly DltDisbursementServiceInterface $disbursementService,
    ) {}

    /**
     * Release a payout — triggers disbursement via DLT.
     *
     * @return array{success: bool, payout: Payout, error?: string}
     *
     * @throws ValidationException
     */
    public function execute(Payout $payout, User $releasedBy): array
    {
        if (! in_array($payout->status, ['pending', 'failed'], true)) {
            throw ValidationException::withMessages([
                'payout' => ['Payout must be in pending or failed status to release.'],
            ]);
        }

        // Get seller's payout account
        $account = SellerPayoutAccount::where('shop_id', $payout->shop_id)->first();

        if (! $account || $account->verification_status !== 'verified') {
            throw ValidationException::withMessages([
                'payout' => ['Seller payout account is not verified.'],
            ]);
        }

        // Transition to processing
        $payout->update(['status' => 'processing']);

        // Set released_by on first release
        if (! $payout->released_by_id) {
            $payout->update([
                'released_by_id' => $releasedBy->id,
                'released_at' => now(),
            ]);
        }

        // Call DLT disbursement
        $result = $this->disbursementService->disburse($payout, $account);

        if ($result->success) {
            // Create successful disbursement record
            PayoutDisbursement::create([
                'payout_id' => $payout->id,
                'status' => 'succeeded',
                'dlt_reference_id' => $result->dltReferenceId,
                'dlt_transaction_id' => $result->dltTransactionId,
                'amount' => $payout->net_amount,
                'gateway_response' => $result->rawResponse,
                'attempted_at' => now(),
                'succeeded_at' => now(),
            ]);

            // Transition payout to completed
            $payout->update([
                'status' => 'completed',
                'completed_at' => now(),
                'failed_at' => null,
                'failure_reason' => null,
            ]);

            $payout->refresh();

            $payout->shop->user->notify(new PayoutCompletedNotification($payout));

            return ['success' => true, 'payout' => $payout];
        }

        // Create failed disbursement record
        PayoutDisbursement::create([
            'payout_id' => $payout->id,
            'status' => 'failed',
            'amount' => $payout->net_amount,
            'gateway_response' => $result->rawResponse,
            'attempted_at' => now(),
            'failed_at' => now(),
        ]);

        // Transition payout to failed
        $payout->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $result->errorMessage ?? 'Disbursement failed',
        ]);

        $payout->refresh();

        $admins = User::role('admin')->get();
        Notification::send($admins, new PayoutFailedNotification($payout));

        return [
            'success' => false,
            'payout' => $payout,
            'error' => $result->errorMessage ?? 'Disbursement failed',
        ];
    }
}
