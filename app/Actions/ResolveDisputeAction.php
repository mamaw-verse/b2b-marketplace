<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\DisputeResolved;
use App\Models\Dispute;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\PayoutSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ResolveDisputeAction
{
    /**
     * Resolve a dispute with a refund (full/partial) or denial.
     *
     * @param  array{resolution_type: string, resolution_amount?: float|null, resolution_notes?: string|null}  $data
     * @return array{success: bool, dispute: Dispute, error?: string}
     */
    public function execute(Dispute $dispute, User $resolver, array $data): array
    {
        $resolutionType = $data['resolution_type'];
        $resolutionAmount = $data['resolution_amount'] ?? null;
        $resolutionNotes = $data['resolution_notes'] ?? null;

        // Validate resolution type
        if (! in_array($resolutionType, ['full_refund', 'partial_refund', 'denied'])) {
            return [
                'success' => false,
                'dispute' => $dispute,
                'error' => 'Invalid resolution type.',
            ];
        }

        // For a return_refund dispute, a refund (full/partial) may only be issued
        // once the returned goods have been confirmed received. A denial, however,
        // can be issued at any point — a seller must be able to reject a bogus
        // return claim without first accepting a return that may never arrive.
        if ($dispute->type === 'return_refund'
            && $resolutionType !== 'denied'
            && $dispute->status !== 'return_received'
        ) {
            return [
                'success' => false,
                'dispute' => $dispute,
                'error' => 'Confirm the return has been received before issuing a refund.',
            ];
        }

        $order = $dispute->order;

        // Validate amount for partial refund
        if ($resolutionType === 'partial_refund') {
            if ($resolutionAmount === null || $resolutionAmount <= 0) {
                return [
                    'success' => false,
                    'dispute' => $dispute,
                    'error' => 'Resolution amount is required for partial refund.',
                ];
            }

            if ($resolutionAmount > (float) $order->order_total) {
                return [
                    'success' => false,
                    'dispute' => $dispute,
                    'error' => 'Resolution amount cannot exceed the order total.',
                ];
            }
        }

        $result = DB::transaction(function () use ($dispute, $order, $resolver, $resolutionType, $resolutionAmount, $resolutionNotes): array {
            // Lock dispute and order
            $lockedDispute = Dispute::where('id', $dispute->id)->lockForUpdate()->first();
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            if (! $lockedDispute || in_array($lockedDispute->status, ['resolved_refunded', 'resolved_denied', 'withdrawn'])) {
                return [
                    'success' => false,
                    'dispute' => $lockedDispute ?? $dispute,
                    'error' => 'Dispute is already in a terminal state.',
                ];
            }

            // Determine final amount for full refund
            $actualAmount = match ($resolutionType) {
                'full_refund' => (float) $lockedOrder->order_total,
                'partial_refund' => (float) $resolutionAmount,
                'denied' => null,
            };

            // Update dispute
            $newStatus = $resolutionType === 'denied' ? 'resolved_denied' : 'resolved_refunded';
            $lockedDispute->update([
                'status' => $newStatus,
                'resolution_type' => $resolutionType,
                'resolution_amount' => $actualAmount,
                'resolution_notes' => $resolutionNotes,
                'resolved_by_user_id' => $resolver->id,
                'resolved_at' => now(),
            ]);

            // Handle fund movement
            if ($resolutionType !== 'denied') {
                // Create refund ledger entry
                LedgerEntry::create([
                    'order_id' => $lockedOrder->id,
                    'type' => 'refund',
                    'amount' => $actualAmount,
                    'note' => "Dispute resolution: {$resolutionType}",
                ]);

                // If partial refund, create payout for remainder. Use the same
                // commission source as escrow/payout and the quotation/order payout
                // estimates (PayoutSetting) so the basis is consistent app-wide.
                if ($resolutionType === 'partial_refund') {
                    $commissionRate = PayoutSetting::getCommissionFeeRate();
                    $remainder = (float) $lockedOrder->order_total - $actualAmount;
                    $payout = round($remainder * (1 - $commissionRate), 2);

                    if ($payout > 0) {
                        LedgerEntry::create([
                            'order_id' => $lockedOrder->id,
                            'type' => 'payout',
                            'amount' => $payout,
                            'note' => 'Partial payout after dispute resolution',
                        ]);
                    }
                }

                // Set order status to refunded
                $lockedOrder->update(['status' => 'refunded']);
            } else {
                // Denied: order stands as completed
                $lockedOrder->update(['status' => 'completed']);
            }

            return [
                'success' => true,
                'dispute' => $lockedDispute->fresh(),
            ];
        });

        // Dispatch event after transaction
        if ($result['success']) {
            DisputeResolved::dispatch($result['dispute']);
        }

        return $result;
    }
}
