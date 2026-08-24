<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Payout;
use App\Models\PayoutItem;
use App\Models\PayoutSetting;
use App\Models\SellerPayoutAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreatePayoutBatchAction
{
    /**
     * Create a payout batch for a given shop.
     *
     * @param  array<int>|null  $orderIds  Specific order IDs, or null for all eligible.
     *
     * @throws ValidationException
     */
    public function execute(int $shopId, ?array $orderIds = null): Payout
    {
        // Validate payout account is verified
        $payoutAccount = SellerPayoutAccount::where('shop_id', $shopId)->first();

        if (! $payoutAccount || $payoutAccount->verification_status !== 'verified') {
            throw ValidationException::withMessages([
                'shop_id' => ['Seller payout account is not verified.'],
            ]);
        }

        $disputeHoldDays = PayoutSetting::getDisputeHoldDays();
        $minimumThreshold = PayoutSetting::getMinimumPayoutThreshold();
        $transactionFeeAmount = PayoutSetting::getTransactionFeeAmount();
        $commissionFeeRate = PayoutSetting::getCommissionFeeRate();

        // Build eligible orders query
        $query = Order::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereDoesntHave('payoutItem')
            ->where('completed_at', '<=', now()->subDays($disputeHoldDays))
            ->whereDoesntHave('disputes', function ($q) {
                $q->whereIn('status', ['open', 'awaiting_return', 'return_received', 'escalated']);
            });

        if (! empty($orderIds)) {
            $query->whereIn('id', $orderIds);
        }

        $eligibleOrders = $query->get();

        if ($eligibleOrders->isEmpty()) {
            throw ValidationException::withMessages([
                'orders' => ['No eligible orders found for payout.'],
            ]);
        }

        // Calculate totals and validate threshold
        $grossAmount = '0.00';
        $totalTransactionFees = '0.00';
        $totalCommissionFees = '0.00';
        $totalNetAmount = '0.00';

        $itemsData = [];

        foreach ($eligibleOrders as $order) {
            // Get the payout ledger entry for this order (the escrowed amount)
            $ledgerEntry = LedgerEntry::where('order_id', $order->id)
                ->where('type', 'payout')
                ->first();

            if (! $ledgerEntry) {
                continue;
            }

            $escrowed = (float) $ledgerEntry->amount;
            $txFee = $transactionFeeAmount;
            $commFee = round($escrowed * $commissionFeeRate, 2, PHP_ROUND_HALF_UP);
            $netAmount = round($escrowed - $txFee - $commFee, 2);

            // Validate net amount doesn't go negative
            if ($netAmount <= 0) {
                throw ValidationException::withMessages([
                    'orders' => ["Order #{$order->id}: fees exceed escrowed amount. Cannot create payout."],
                ]);
            }

            $grossAmount = bcadd($grossAmount, number_format($escrowed, 2, '.', ''), 2);
            $totalTransactionFees = bcadd($totalTransactionFees, number_format($txFee, 2, '.', ''), 2);
            $totalCommissionFees = bcadd($totalCommissionFees, number_format($commFee, 2, '.', ''), 2);
            $totalNetAmount = bcadd($totalNetAmount, number_format($netAmount, 2, '.', ''), 2);

            $itemsData[] = [
                'order_id' => $order->id,
                'ledger_entry_id' => $ledgerEntry->id,
                'escrowed_amount' => $escrowed,
                'transaction_fee' => $txFee,
                'commission_fee' => $commFee,
                'net_amount' => $netAmount,
            ];
        }

        if (empty($itemsData)) {
            throw ValidationException::withMessages([
                'orders' => ['No eligible orders with payout ledger entries found.'],
            ]);
        }

        // Check minimum threshold
        if ((float) $totalNetAmount < $minimumThreshold) {
            throw ValidationException::withMessages([
                'threshold' => ["Releasable balance ({$totalNetAmount}) is below the minimum payout threshold ({$minimumThreshold})."],
            ]);
        }

        // Create payout batch in a transaction
        return DB::transaction(function () use ($shopId, $grossAmount, $totalTransactionFees, $totalCommissionFees, $totalNetAmount, $itemsData): Payout {
            $payout = Payout::create([
                'shop_id' => $shopId,
                'status' => 'pending',
                'currency' => 'PHP',
                'gross_amount' => $grossAmount,
                'total_transaction_fees' => $totalTransactionFees,
                'total_commission_fees' => $totalCommissionFees,
                'net_amount' => $totalNetAmount,
                'orders_count' => count($itemsData),
            ]);

            foreach ($itemsData as $item) {
                PayoutItem::create([
                    'payout_id' => $payout->id,
                    ...$item,
                ]);
            }

            return $payout;
        });
    }
}
