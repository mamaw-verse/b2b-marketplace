<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Actions\AwardQuotationAction;
use App\Actions\CloseRfqAction;
use App\Actions\RejectQuotationAction;
use App\Events\QuotationAwarded;
use App\Events\QuotationRejected;
use App\Models\Quotation;
use App\Models\Rfq;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('RFQ Detail')]
final class RfqDetail extends Component
{
    public Rfq $rfq;

    public bool $showCloseConfirmation = false;

    public ?int $confirmingRejectQuotationId = null;

    public ?int $confirmingAwardQuotationId = null;

    /** @var array<int, bool> */
    public array $expandedQuotations = [];

    public function mount(Rfq $rfq): void
    {
        Gate::authorize('view', $rfq);
        $this->rfq = $rfq;
    }

    public function toggleQuotation(int $quotationId): void
    {
        $this->expandedQuotations[$quotationId] = ! ($this->expandedQuotations[$quotationId] ?? false);
    }

    public function confirmAwardQuotation(int $quotationId): void
    {
        $this->confirmingAwardQuotationId = $quotationId;
        \Flux\Flux::modal('confirm-award-quotation')->show();
    }

    public function awardQuotation(?int $quotationId = null): void
    {
        $quotationId = $quotationId ?? $this->confirmingAwardQuotationId;

        if (! $quotationId) {
            return;
        }

        $quotation = Quotation::findOrFail($quotationId);
        Gate::authorize('award', $quotation);

        $result = app(AwardQuotationAction::class)->execute($quotation);

        if (! $result['success']) {
            \Flux\Flux::toast($result['error'], variant: 'danger');

            return;
        }

        $result['quotation']->rfq->touchActivity();
        QuotationAwarded::dispatch($result['quotation']);

        \Flux\Flux::toast('Quotation awarded.', variant: 'success');
        $this->rfq->refresh();
    }

    public function confirmRejectQuotation(int $quotationId): void
    {
        $this->confirmingRejectQuotationId = $quotationId;
        \Flux\Flux::modal('confirm-reject-quotation')->show();
    }

    public function rejectQuotation(?int $quotationId = null): void
    {
        $quotationId = $quotationId ?? $this->confirmingRejectQuotationId;

        if (! $quotationId) {
            return;
        }

        $quotation = Quotation::findOrFail($quotationId);
        Gate::authorize('reject', $quotation);

        $result = app(RejectQuotationAction::class)->execute($quotation);

        if (! $result['success']) {
            \Flux\Flux::toast($result['error'], variant: 'danger');

            return;
        }

        $result['quotation']->rfq->touchActivity();
        QuotationRejected::dispatch($result['quotation']);

        \Flux\Flux::toast('Quotation rejected.', variant: 'warning');
        $this->confirmingRejectQuotationId = null;
        $this->rfq->refresh();
    }

    public function closeRfq(): void
    {
        Gate::authorize('close', $this->rfq);

        $result = app(CloseRfqAction::class)->execute($this->rfq);

        if (! $result['success']) {
            \Flux\Flux::toast($result['error'], variant: 'danger');

            return;
        }

        // S4.T9: cascade — close every recipient seller that is not already in a
        // terminal state (no_bid) and has not had a quotation awarded, so they no
        // longer see this as an open RFQ. NOTE: the rfq_recipient_status enum has
        // no 'awarded' value (awarding lives on the quotation), so we express the
        // "not awarded" exclusion via the quotations relation rather than a status
        // literal, which Postgres would reject.
        $this->rfq->recipients()
            ->where('status', '!=', 'no_bid')
            ->whereDoesntHave('quotations', fn ($q) => $q->where('status', 'awarded'))
            ->update(['status' => 'closed']);

        $this->showCloseConfirmation = false;
        $this->rfq->refresh();

        \Flux\Flux::toast('RFQ closed.', variant: 'success');
    }

    public function render()
    {
        $this->rfq->load(['recipients.shop', 'attachments', 'quotations.items']);

        return view('livewire.account.rfq-detail');
    }
}
