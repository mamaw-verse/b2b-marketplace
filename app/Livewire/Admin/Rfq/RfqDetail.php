<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Rfq;

use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Notifications\RfqStatusUpdatedNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('RFQ Details')]
final class RfqDetail extends Component
{
    public Rfq $rfq;

    public RfqRecipient $recipient;

    public string $adminStatus = 'pending';

    public string $adminNotes = '';

    public function mount(Rfq $rfq): void
    {
        $this->rfq = $rfq->load(['buyer.shop', 'buyer.buyerProfile', 'attachments', 'product.variants', 'product.shop']);
        // For Phase 1, each RFQ targets exactly one shop (recipient)
        $this->recipient = $rfq->recipients()->with(['shop', 'managedBy'])->firstOrFail();

        $this->adminStatus = $this->recipient->admin_status ?? 'pending';
        $this->adminNotes = $this->recipient->admin_notes ?? '';
    }

    public function saveAdminDetails(): void
    {
        $this->validate([
            'adminStatus' => ['required', 'string', 'in:pending,in_progress,resolved,closed'],
            'adminNotes' => ['nullable', 'string', 'max:5000'],
        ]);

        $previousStatus = $this->recipient->admin_status ?? 'pending';

        $updates = [
            'admin_status' => $this->adminStatus,
            'admin_notes' => $this->adminNotes,
        ];

        // Auto-assign to current admin if status changes from pending and it's unassigned
        if ($this->adminStatus !== 'pending' && ! $this->recipient->managed_by) {
            $updates['managed_by'] = Auth::id();
            $this->recipient->managedBy()->associate(Auth::user());
        }

        $this->recipient->update($updates);

        // Notify the buyer when the admin status actually changes so they can
        // track the negotiation (in-app database notification + email).
        if ($previousStatus !== $this->adminStatus && $this->rfq->buyer) {
            $this->rfq->buyer->notify(
                new RfqStatusUpdatedNotification($this->recipient, $previousStatus, $this->adminStatus)
            );
        }

        $this->dispatch('toast', message: 'RFQ admin details updated successfully.');
    }

    public function render()
    {
        return view('livewire.admin.rfq.rfq-detail');
    }
}
