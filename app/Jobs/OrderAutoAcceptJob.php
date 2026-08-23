<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ConfirmReceiptAction;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class OrderAutoAcceptJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     *
     * Finds all orders that are paid, have a delivered fulfilment older than
     * the auto-accept window, and have no active disputes — then auto-confirms
     * receipt via ConfirmReceiptAction.
     */
    public function handle(ConfirmReceiptAction $action): void
    {
        $autoAcceptDays = (int) config('marketplace.auto_accept_days', 7);
        $cutoff = now()->subDays($autoAcceptDays);

        $orders = Order::where('status', 'paid')
            ->whereHas('fulfilments', function ($query) use ($cutoff) {
                $query->where('status', 'delivered')
                    ->where('delivered_at', '<=', $cutoff);
            })
            ->whereDoesntHave('disputes', function ($query) {
                $query->whereNotIn('status', [
                    'resolved_refunded',
                    'resolved_denied',
                    'withdrawn',
                ]);
            })
            ->get();

        foreach ($orders as $order) {
            $result = $action->execute($order);

            if ($result['success']) {
                Log::info("OrderAutoAcceptJob: auto-completed order #{$order->id}");
            }
        }
    }
}
