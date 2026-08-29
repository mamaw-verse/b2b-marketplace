<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\Quotation;
use App\Models\RfqRecipient;
use App\Notifications\QuotationWithdrawnNotification;
use App\Notifications\RfqNoBidNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('RFQ Detail')]
final class RfqDetail extends Component
{
    public RfqRecipient $rfqRecipient;

    public string $closeReason = '';

    public bool $showCloseModal = false;

    /** @var array<int, bool> */
    public array $expandedQuotations = [];

    public ?int $confirmingWithdrawQuotationId = null;

    public function mount(RfqRecipient $rfqRecipient): void
    {
        Gate::authorize('view', $rfqRecipient);

        $this->rfqRecipient = $rfqRecipient;
        $this->rfqRecipient->load(['rfq.attachments', 'rfq.user.buyerProfile']);

        // E13.T3: Auto-transition received → reviewed on first view
        if ($this->rfqRecipient->status === 'received') {
            $this->rfqRecipient->update([
                'status' => 'reviewed',
                'reviewed_at' => now(),
                'last_activity_at' => now(),
            ]);

            // Also touch the parent RFQ's last_activity_at
            $this->rfqRecipient->rfq->touchActivity();

            $this->rfqRecipient->refresh();
        }
    }

    /**
     * E13.T4: Mark as in_progress.
     */
    public function markInProgress(): void
    {
        Gate::authorize('inProgress', $this->rfqRecipient);

        $this->rfqRecipient->update([
            'status' => 'in_progress',
            'last_activity_at' => now(),
        ]);

        $this->rfqRecipient->rfq->touchActivity();
        $this->rfqRecipient->refresh();

        \Flux\Flux::toast('RFQ marked as in progress.', variant: 'success');
    }

    /**
     * S4.T10: Close (no_bid) this RFQ with an optional reason.
     *
     * Mirrors the buyer-side close: withdraws any still-submitted quotations and
     * always notifies the buyer that the seller has closed the RFQ, regardless of
     * whether a quotation existed, since the seller is no longer pursuing it.
     */
    public function closeRfq(): void
    {
        Gate::authorize('decline', $this->rfqRecipient);

        $submitted = DB::transaction(function () {
            $this->rfqRecipient->update([
                'status' => 'no_bid',
                'decline_reason' => $this->closeReason ?: null,
                'last_activity_at' => now(),
            ]);

            // Withdraw any submitted quotations owned by this recipient.
            $submitted = $this->rfqRecipient->quotations()
                ->where('status', 'submitted')
                ->get();

            foreach ($submitted as $quotation) {
                $quotation->update(['status' => 'withdrawn']);
            }

            // Phase 1 has a single recipient per RFQ, so a seller close means no
            // seller is pursuing the request. Close the parent RFQ too (unless a
            // quotation was already awarded) so the buyer view reflects the same
            // terminal state as a buyer-initiated close and can no longer act on it.
            $rfq = $this->rfqRecipient->rfq;

            $hasAwarded = $rfq->quotations()->where('status', 'awarded')->exists();

            if (! $rfq->isClosed() && ! $hasAwarded) {
                $rfq->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                    'last_activity_at' => now(),
                ]);
            }

            return $submitted;
        });

        // Notify the buyer per withdrawn quotation (outside the transaction).
        foreach ($submitted as $quotation) {
            $this->notifyBuyerOfWithdrawal($quotation);
        }

        $this->rfqRecipient->refresh();

        // Always notify the buyer that the seller closed this RFQ, so the close
        // has the same visible effect as a buyer-initiated close even when the
        // seller never submitted a quotation.
        $buyer = $this->rfqRecipient->rfq->user;
        $buyer?->notify(new RfqNoBidNotification($this->rfqRecipient, reason: 'seller_closed'));

        $this->showCloseModal = false;

        \Flux\Flux::toast('RFQ closed.', variant: 'warning');
    }

    public function openCloseModal(): void
    {
        $this->showCloseModal = true;
    }

    /**
     * S4.T11: Toggle the expanded line-item view for a quotation.
     */
    public function toggleQuotation(int $quotationId): void
    {
        $this->expandedQuotations[$quotationId] = ! ($this->expandedQuotations[$quotationId] ?? false);
    }

    /**
     * S4.T11: Open the confirm dialog for withdrawing a quotation.
     */
    public function confirmWithdrawQuotation(int $quotationId): void
    {
        $this->confirmingWithdrawQuotationId = $quotationId;
        \Flux\Flux::modal('confirm-withdraw-quotation')->show();
    }

    /**
     * S4.T11: Withdraw a submitted quotation owned by this seller and notify the buyer.
     */
    public function withdrawQuotation(?int $quotationId = null): void
    {
        $quotationId = $quotationId ?? $this->confirmingWithdrawQuotationId;

        if (! $quotationId) {
            return;
        }

        $quotation = Quotation::findOrFail($quotationId);

        Gate::authorize('withdraw', $quotation);

        $quotation->update(['status' => 'withdrawn']);
        $quotation->rfq->touchActivity();

        $this->notifyBuyerOfWithdrawal($quotation);

        $this->confirmingWithdrawQuotationId = null;
        $this->rfqRecipient->refresh();

        \Flux\Flux::toast('Quotation withdrawn.', variant: 'warning');
    }

    public function render()
    {
        $this->rfqRecipient->load(['quotations.items', 'quotations.order', 'rfq.attachments', 'rfq.user.buyerProfile']);

        return view('livewire.company-hub.rfq-detail');
    }

    /**
     * Notify the buyer that a quotation was withdrawn.
     */
    private function notifyBuyerOfWithdrawal(Quotation $quotation): void
    {
        $buyer = $quotation->rfq->user;

        $buyer?->notify(new QuotationWithdrawnNotification($quotation));
    }
}
