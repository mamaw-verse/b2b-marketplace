<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class SubmitOrderAction
{
    /**
     * Submit a draft order for payment.
     *
     * Validates the order has items and a positive total, recalculates
     * financial totals, and transitions status to pending_payment.
     *
     * @return array{success: bool, order: Order, error?: string}
     */
    public function execute(Order $order): array
    {
        if ($order->items()->count() === 0) {
            return [
                'success' => false,
                'order' => $order,
                'error' => 'An order must have at least one item before submission.',
            ];
        }

        return DB::transaction(function () use ($order) {
            $itemsTotal = (float) $order->items()->sum('subtotal');
            $feesTotal = (float) $order->fees()->sum('amount');
            $orderTotal = round($itemsTotal + $feesTotal, 2);

            if ($orderTotal <= 0) {
                return [
                    'success' => false,
                    'order' => $order,
                    'error' => 'Order total must be greater than zero.',
                ];
            }

            $order->update([
                'items_total' => $itemsTotal,
                'fees_total' => $feesTotal,
                'order_total' => $orderTotal,
                'status' => 'pending_payment',
                'submitted_at' => now(),
            ]);

            // Update the payment term amount to match final total
            $order->paymentTerms()->update(['amount' => $orderTotal]);

            return [
                'success' => true,
                'order' => $order->fresh(),
            ];
        });
    }
}
