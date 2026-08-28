<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Order Detail')]
final class OrderDetail extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);

        $this->order = $order->load(['items', 'fees', 'fulfilments', 'disputes.openedBy', 'disputes.resolvedBy', 'ledgerEntries', 'buyer']);
    }

    public function recall(): void
    {
        if ($this->order->status !== 'pending_payment') {
            return;
        }

        $this->order->update(['status' => 'draft', 'submitted_at' => null]);
        $this->order->refresh();
        session()->flash('success', 'Order recalled to draft.');
    }

    public function render()
    {
        return view('livewire.company-hub.orders.order-detail');
    }
}
