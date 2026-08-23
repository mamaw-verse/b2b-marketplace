<?php

declare(strict_types=1);

namespace App\Livewire\Account\Disputes;

use App\Events\DisputeOpened;
use App\Models\Dispute;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('Open Dispute')]
final class DisputeForm extends Component
{
    public Order $order;

    public string $type = 'refund_only';

    public string $reason = '';

    public function mount(Order $order): void
    {
        abort_unless($order->buyer_user_id === Auth::id(), 403);
        $this->order = $order;
    }

    public function submit(): void
    {
        $this->validate([
            'type' => 'required|in:return_refund,refund_only',
            'reason' => 'required|string|max:2000',
        ]);

        $status = $this->type === 'return_refund' ? 'awaiting_return' : 'open';

        $dispute = Dispute::create([
            'order_id' => $this->order->id,
            'opened_by_user_id' => Auth::id(),
            'type' => $this->type,
            'status' => $status,
            'reason' => $this->reason,
        ]);

        DisputeOpened::dispatch($dispute);

        session()->flash('success', 'Dispute opened.');
        $this->redirectRoute('account.orders.show', ['order' => $this->order->id]);
    }

    public function render()
    {
        return view('livewire.account.disputes.dispute-form');
    }
}
