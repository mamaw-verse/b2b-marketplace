<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Fulfilment;

use App\Events\OrderDelivered;
use App\Events\OrderReturned;
use App\Events\OrderShipped;
use App\Models\Fulfilment;
use App\Models\FulfilmentItem;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\PayoutSetting;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

final class FulfilmentPanel extends Component
{
    public Order $order;

    public ?Fulfilment $fulfilment = null;

    /**
     * True when a return_refund dispute is already managing the return — hides the
     * "Mark Returned" button to avoid the seller using two parallel return paths.
     * Also true when the dispute's return has already been confirmed received via
     * the DisputePanel (status = return_received), preventing a duplicate action.
     */
    public bool $hasReturnDisputeInProgress = false;

    public string $carrier = '';

    public string $trackingCode = '';

    public string $trackingUrl = '';

    // ── Mark Returned modal state ─────────────────────────────────────────────

    /** @var string 'no_refund'|'full_refund'|'partial_refund' */
    public string $returnRefundType = 'no_refund';

    public ?string $returnRefundAmount = null;

    public function mount(Order $order): void
    {
        $this->order = $order;
        $this->fulfilment = $order->fulfilments()->first();

        // Hide "Mark Returned" if a return_refund dispute is already handling
        // the return process (open / awaiting_return / return_received).
        $this->hasReturnDisputeInProgress = $order->disputes
            ->contains(fn ($d) => $d->type === 'return_refund'
                && in_array($d->status, ['open', 'awaiting_return', 'return_received'], true));

        if ($this->fulfilment) {
            $this->carrier = $this->fulfilment->carrier ?? '';
            $this->trackingCode = $this->fulfilment->tracking_code ?? '';
            $this->trackingUrl = $this->fulfilment->tracking_url ?? '';
        }
    }

    /**
     * Clear the partial amount whenever the type changes away from partial_refund
     * so stale data cannot linger in component state.
     */
    public function updatedReturnRefundType(string $value): void
    {
        if ($value !== 'partial_refund') {
            $this->returnRefundAmount = null;
            $this->resetErrorBag('returnRefundAmount');
        }
    }

    public function createFulfilment(): void
    {
        if ($this->order->status !== 'paid' || $this->fulfilment) {
            return;
        }

        $this->validate([
            'carrier' => ['required', 'string', 'max:100'],
            'trackingCode' => ['required', 'string', 'max:100'],
            'trackingUrl' => ['required', 'url', 'max:500'],
        ], [
            'carrier.required' => 'Please enter the carrier name.',
            'trackingCode.required' => 'Please enter the tracking code.',
            'trackingUrl.required' => 'Please enter the tracking URL.',
            'trackingUrl.url' => 'Please enter a valid URL (e.g. https://...).',
        ]);

        $this->fulfilment = DB::transaction(function (): Fulfilment {
            $fulfilment = Fulfilment::create([
                'order_id' => $this->order->id,
                'status' => 'pending',
                'carrier' => $this->carrier,
                'tracking_code' => $this->trackingCode,
                'tracking_url' => $this->trackingUrl,
            ]);

            // Default fulfilment to the full order — one fulfilment item per order
            // line, each shipping the order item's full quantity.
            foreach ($this->order->items as $orderItem) {
                FulfilmentItem::create([
                    'fulfilment_id' => $fulfilment->id,
                    'order_item_id' => $orderItem->id,
                    'quantity' => $orderItem->quantity,
                ]);
            }

            return $fulfilment;
        });

        session()->flash('success', 'Fulfilment created.');
    }

    public function markShipped(): void
    {
        if (! $this->fulfilment || $this->fulfilment->status !== 'pending') {
            return;
        }

        $this->validate([
            'carrier' => ['required', 'string', 'max:100'],
            'trackingCode' => ['required', 'string', 'max:100'],
            'trackingUrl' => ['required', 'url', 'max:500'],
        ], [
            'carrier.required' => 'Please enter the carrier name.',
            'trackingCode.required' => 'Please enter the tracking code.',
            'trackingUrl.required' => 'Please enter the tracking URL.',
            'trackingUrl.url' => 'Please enter a valid URL (e.g. https://...).',
        ]);

        $this->fulfilment->update([
            'status' => 'shipped',
            'shipped_at' => now(),
            'carrier' => $this->carrier,
            'tracking_code' => $this->trackingCode,
            'tracking_url' => $this->trackingUrl,
        ]);

        OrderShipped::dispatch($this->order->fresh());
        session()->flash('success', 'Order marked as shipped.');
    }

    public function markDelivered(): void
    {
        if (! $this->fulfilment || $this->fulfilment->status !== 'shipped') {
            return;
        }

        $this->fulfilment->update(['status' => 'delivered', 'delivered_at' => now()]);
        OrderDelivered::dispatch($this->order->fresh());
        session()->flash('success', 'Order marked as delivered.');
    }

    /**
     * Mark the fulfilment as returned to seller, optionally issuing a refund.
     *
     * Refund type options:
     *   - no_refund:      Just mark returned; order stays in its current status.
     *   - full_refund:    Refund the full order total; order → refunded.
     *   - partial_refund: Refund the specified amount; order → refunded.
     */
    public function markReturned(): void
    {
        if (! $this->fulfilment || $this->fulfilment->status !== 'shipped') {
            return;
        }

        if ($this->hasReturnDisputeInProgress) {
            return;
        }

        $this->validate([
            'returnRefundType' => ['required', 'in:no_refund,full_refund,partial_refund'],
        ]);

        if ($this->returnRefundType === 'partial_refund') {
            $this->validate([
                'returnRefundAmount' => ['required', 'numeric', 'gt:0', 'lte:'.(float) $this->order->order_total],
            ], [
                'returnRefundAmount.required' => 'Enter the amount to refund.',
                'returnRefundAmount.gt' => 'The refund amount must be greater than zero.',
                'returnRefundAmount.lte' => 'The refund amount cannot exceed the order total.',
            ]);
        }

        DB::transaction(function (): void {
            $this->fulfilment->update(['status' => 'returned_to_seller', 'returned_at' => now()]);

            if ($this->returnRefundType === 'no_refund') {
                return;
            }

            $actualAmount = $this->returnRefundType === 'full_refund'
                ? (float) $this->order->order_total
                : (float) $this->returnRefundAmount;

            LedgerEntry::create([
                'order_id' => $this->order->id,
                'type' => 'refund',
                'amount' => $actualAmount,
                'note' => 'Seller return: '.$this->returnRefundType,
            ]);

            // For a partial refund, payout the remainder to the seller (net of commission).
            if ($this->returnRefundType === 'partial_refund') {
                $commissionRate = PayoutSetting::getCommissionFeeRate();
                $remainder = (float) $this->order->order_total - $actualAmount;
                $payout = round($remainder * (1 - $commissionRate), 2);

                if ($payout > 0) {
                    LedgerEntry::create([
                        'order_id' => $this->order->id,
                        'type' => 'payout',
                        'amount' => $payout,
                        'note' => 'Partial payout after seller return',
                    ]);
                }
            }

            $this->order->update(['status' => 'refunded']);
        });

        OrderReturned::dispatch($this->order->fresh());

        \Flux\Flux::modal('confirm-mark-returned')->close();
        \Flux\Flux::toast('Marked as returned to seller.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.company-hub.fulfilment.fulfilment-panel');
    }
}
