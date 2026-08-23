<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Disputes;

use App\Actions\ResolveDisputeAction;
use App\Models\Dispute;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class DisputePanel extends Component
{
    public Order $order;

    public ?Dispute $dispute = null;

    public string $resolutionType = 'full_refund';

    public ?string $resolutionAmount = null;

    public string $resolutionNotes = '';

    public function mount(Order $order): void
    {
        $this->order = $order;
        $this->dispute = $order->disputes()
            ->whereNotIn('status', ['resolved_refunded', 'resolved_denied', 'withdrawn'])
            ->latest()
            ->first();
    }

    public function confirmReturn(): void
    {
        if (! $this->dispute || ! in_array($this->dispute->status, ['open', 'awaiting_return'])) {
            return;
        }

        $this->dispute->update(['status' => 'return_received']);
        $this->dispute->refresh();
        session()->flash('success', 'Return confirmed.');
    }

    public function resolve(): void
    {
        $result = (new ResolveDisputeAction)->execute($this->dispute, Auth::user(), [
            'resolution_type' => $this->resolutionType,
            'resolution_amount' => $this->resolutionAmount ? (float) $this->resolutionAmount : null,
            'resolution_notes' => $this->resolutionNotes,
        ]);

        if ($result['success']) {
            $this->dispute = $result['dispute'];
            $this->order->refresh();
            session()->flash('success', 'Dispute resolved.');
        } else {
            session()->flash('error', $result['error']);
        }
    }

    public function escalate(): void
    {
        if (! $this->dispute || in_array($this->dispute->status, ['escalated', 'resolved_refunded', 'resolved_denied', 'withdrawn'])) {
            return;
        }

        $this->dispute->update(['status' => 'escalated', 'escalated_at' => now()]);
        \App\Events\DisputeEscalated::dispatch($this->dispute->fresh());
        $this->dispute->refresh();
        session()->flash('success', 'Dispute escalated to admin.');
    }

    public function render()
    {
        return view('livewire.company-hub.disputes.dispute-panel');
    }
}
