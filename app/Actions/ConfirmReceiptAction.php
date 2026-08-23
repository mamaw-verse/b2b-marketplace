<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\OrderCompleted;
use App\Models\LedgerEntry;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class ConfirmReceiptAction
{
    /**
     * Confirm receipt of a delivered order.
     *
     * Transitions order to completed, creates payout ledger entry
     * (deducting platform commission).
     *
     * @return array{success: bool, order: Order, error?: string}
     */
    public function execute(Order $order): array
    {
        $result = DB::transaction(function () use ($order): array {
            // Lock the order row
            $locked = Order::where('id', $order->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status === 'completed') {
                return [
                    'success' => false,
                    'order' => $locked ?? $order,
                    'error' => 'Order is already completed.',
                ];
            }

            // Validate fulfilment is delivered
            $fulfilment = $locked->fulfilments()->first();
            if (! $fulfilment || $fulfilment->status !== 'delivered') {
                return [
                    'success' => false,
                    'order' => $locked,
                    'error' => 'Order fulfilment has not been marked as delivered.',
                ];
            }

            // Check no active dispute
            $hasActiveDispute = $locked->disputes()
                ->whereNotIn('status', ['resolved_refunded', 'resolved_denied', 'withdrawn'])
                ->exists();

            if ($hasActiveDispute) {
                return [
                    'success' => false,
                    'order' => $locked,
                    'error' => 'Cannot confirm receipt while a dispute is active.',
                ];
            }

            // Transition to completed
            $locked->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Calculate payout (order total minus platform commission)
            $commissionRate = (float) config('marketplace.commission_rate', 0.01);
            $orderTotal = (float) $locked->order_total;
            $commission = round($orderTotal * $commissionRate, 2);
            $payoutAmount = round($orderTotal - $commission, 2);

            // Create payout ledger entry
            LedgerEntry::create([
                'order_id' => $locked->id,
                'type' => 'payout',
                'amount' => $payoutAmount,
                'note' => 'Payout on completion (commission: '.number_format($commission, 2).')',
            ]);

            return [
                'success' => true,
                'order' => $locked->fresh(),
            ];
        });

        // Dispatch event AFTER transaction commits
        if ($result['success']) {
            OrderCompleted::dispatch($result['order']);
        }

        return $result;
    }
}
