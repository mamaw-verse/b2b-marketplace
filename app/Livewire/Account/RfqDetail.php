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

    public function mount(Rfq $rfq): void
    {
        Gate::authorize('view', $rfq);
        $this->rfq = $rfq;
    }

    public function awardQuotation(int $quotationId): void
    {
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
