<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\RfqRecipient;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('RFQ Detail')]
final class RfqDetail extends Component
{
    public RfqRecipient $rfqRecipient;

    public string $declineReason = '';

    public bool $showDeclineModal = false;

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
     * E13.T4: Decline (no_bid) with optional reason.
     */
    public function decline(): void
    {
        Gate::authorize('decline', $this->rfqRecipient);

        $this->rfqRecipient->update([
            'status' => 'no_bid',
            'decline_reason' => $this->declineReason ?: null,
            'last_activity_at' => now(),
        ]);

        $this->rfqRecipient->rfq->touchActivity();
        $this->rfqRecipient->refresh();

        $this->showDeclineModal = false;

        \Flux\Flux::toast('RFQ declined.', variant: 'warning');
    }

    public function openDeclineModal(): void
    {
        $this->showDeclineModal = true;
    }

    public function render()
    {
        $this->rfqRecipient->load(['quotations.items', 'quotations.order', 'rfq.attachments', 'rfq.user.buyerProfile']);

        return view('livewire.company-hub.rfq-detail');
    }
}
