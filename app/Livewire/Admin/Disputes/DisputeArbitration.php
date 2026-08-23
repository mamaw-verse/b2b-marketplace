<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Disputes;

use App\Actions\ResolveDisputeAction;
use App\Models\Dispute;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Dispute Arbitration')]
final class DisputeArbitration extends Component
{
    public Dispute $dispute;

    public string $resolutionType = 'full_refund';

    public ?string $resolutionAmount = null;

    public string $resolutionNotes = '';

    public function mount(Dispute $dispute): void
    {
        abort_unless($dispute->status === 'escalated', 404);
        $this->dispute = $dispute->load(['order.items', 'order.fees', 'order.shop', 'openedBy']);
    }

    public function arbitrate(): void
    {
        $this->validate([
            'resolutionType' => 'required|in:full_refund,partial_refund,denied',
            'resolutionNotes' => 'required|string|max:2000',
        ]);

        $result = (new ResolveDisputeAction)->execute($this->dispute, Auth::user(), [
            'resolution_type' => $this->resolutionType,
            'resolution_amount' => $this->resolutionAmount ? (float) $this->resolutionAmount : null,
            'resolution_notes' => $this->resolutionNotes,
        ]);

        if ($result['success']) {
            session()->flash('success', 'Dispute arbitrated successfully.');
            $this->redirectRoute('admin.disputes.index');
        } else {
            session()->flash('error', $result['error']);
        }
    }

    public function render()
    {
        return view('livewire.admin.disputes.dispute-arbitration');
    }
}
