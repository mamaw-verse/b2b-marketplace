<?php

declare(strict_types=1);

namespace App\Livewire\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('Order Detail')]
final class OrderDetail extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);
        $this->order = $order->load(['items', 'fees', 'fulfilments', 'disputes', 'shop']);
    }

    #[On('dispute-opened')]
    #[On('dispute-updated')]
    public function refreshOrder(): void
    {
        $this->order = $this->order->fresh(['items', 'fees', 'fulfilments', 'disputes', 'shop']);
    }

    public function cancel(): void
    {
        if ($this->order->status !== 'pending_payment') {
            return;
        }

        $this->order->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $this->order->refresh();

        \App\Events\OrderCancelled::dispatch($this->order);

        session()->flash('success', 'Order cancelled.');
    }

    public function confirmReceipt(): void
    {
        $action = app(\App\Actions\ConfirmReceiptAction::class);
        $result = $action->execute($this->order);

        if ($result['success']) {
            $this->order = $result['order'];
            session()->flash('success', 'Receipt confirmed. Order completed!');
        } else {
            session()->flash('error', $result['error']);
        }
    }

    public function render()
    {
        return view('livewire.account.orders.order-detail');
    }
}
