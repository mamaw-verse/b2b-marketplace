<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Models\Rfq;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('RFQ Detail')]
final class RfqDetail extends Component
{
    public Rfq $rfq;

    public function mount(Rfq $rfq): void
    {
        Gate::authorize('view', $rfq);
        $this->rfq = $rfq;
    }

    /**
     * The admin-managed status for this RFQ's recipient.
     *
     * Buyer-admin negotiation means the buyer sees the status driven by admin
     * actions (pending / in_progress / resolved / closed), not the RFQ lifecycle.
     */
    #[Computed]
    public function adminStatus(): string
    {
        return $this->rfq->recipients->first()?->admin_status ?? 'pending';
    }

    public function render()
    {
        $this->rfq->load(['recipients', 'attachments', 'product.variants']);

        return view('livewire.account.rfq-detail');
    }
}
