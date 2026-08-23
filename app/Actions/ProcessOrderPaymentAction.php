<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\OrderPaid;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\DB;

final class ProcessOrderPaymentAction
{
    /**
     * Process a successful payment callback.
     *
     * Uses SELECT FOR UPDATE on the payment attempt for idempotency.
     * Creates escrow_hold ledger entry and transitions order to paid.
     */
    public function execute(PaymentAttempt $attempt, Order $order): void
    {
        DB::transaction(function () use ($attempt, $order) {
            // Lock the payment attempt row for idempotency
            $locked = PaymentAttempt::where('id', $attempt->id)
                ->lockForUpdate()
                ->first();

            // Idempotency guard: if already succeeded, skip
            if (! $locked || $locked->status === 'succeeded') {
                return;
            }

            // Update payment attempt
            $locked->update([
                'status' => 'succeeded',
                'succeeded_at' => now(),
                'dlt_payment_status' => 'PAID',
            ]);

            // Transition order to paid
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Create escrow hold ledger entry
            LedgerEntry::create([
                'order_id' => $order->id,
                'type' => 'escrow_hold',
                'amount' => $order->order_total,
                'note' => 'Escrow hold on payment',
            ]);
        });

        // Dispatch event AFTER transaction commits
        OrderPaid::dispatch($order->fresh());
    }
}
