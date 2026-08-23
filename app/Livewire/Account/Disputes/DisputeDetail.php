<?php

declare(strict_types=1);

namespace App\Livewire\Account\Disputes;

use App\Events\DisputeEscalated;
use App\Models\Dispute;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('Dispute Detail')]
final class DisputeDetail extends Component
{
    public Order $order;

    public Dispute $dispute;

    public function mount(Order $order, Dispute $dispute): void
    {
        abort_unless($order->buyer_user_id === Auth::id(), 403);
        $this->order = $order;
        $this->dispute = $dispute;
    }

    public function escalate(): void
    {
        if (in_array($this->dispute->status, ['escalated', 'resolved_refunded', 'resolved_denied', 'withdrawn'])) {
            return;
        }

        $this->dispute->update(['status' => 'escalated', 'escalated_at' => now()]);
        DisputeEscalated::dispatch($this->dispute->fresh());
        $this->dispute->refresh();
        session()->flash('success', 'Dispute escalated to admin.');
    }

    public function withdraw(): void
    {
        if (in_array($this->dispute->status, ['resolved_refunded', 'resolved_denied', 'withdrawn'])) {
            return;
        }

        $this->dispute->update(['status' => 'withdrawn']);
        $this->dispute->refresh();
        session()->flash('success', 'Dispute withdrawn.');
    }

    public function render()
    {
        return view('livewire.account.disputes.dispute-detail');
    }
}
