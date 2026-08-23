<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Fulfilment;

use App\Events\OrderDelivered;
use App\Events\OrderShipped;
use App\Models\Fulfilment;
use App\Models\Order;
use Livewire\Component;

final class FulfilmentPanel extends Component
{
    public Order $order;

    public ?Fulfilment $fulfilment = null;

    public string $carrier = '';

    public string $trackingCode = '';

    public string $trackingUrl = '';

    public function mount(Order $order): void
    {
        $this->order = $order;
        $this->fulfilment = $order->fulfilments()->first();

        if ($this->fulfilment) {
            $this->carrier = $this->fulfilment->carrier ?? '';
            $this->trackingCode = $this->fulfilment->tracking_code ?? '';
            $this->trackingUrl = $this->fulfilment->tracking_url ?? '';
        }
    }

    public function createFulfilment(): void
    {
        if ($this->order->status !== 'paid' || $this->fulfilment) {
            return;
        }

        $this->fulfilment = Fulfilment::create([
            'order_id' => $this->order->id,
            'status' => 'pending',
            'carrier' => $this->carrier ?: null,
            'tracking_code' => $this->trackingCode ?: null,
            'tracking_url' => $this->trackingUrl ?: null,
        ]);

        session()->flash('success', 'Fulfilment created.');
    }

    public function markShipped(): void
    {
        if (! $this->fulfilment || $this->fulfilment->status !== 'pending') {
            return;
        }

        $this->fulfilment->update([
            'status' => 'shipped',
            'shipped_at' => now(),
            'carrier' => $this->carrier ?: null,
            'tracking_code' => $this->trackingCode ?: null,
            'tracking_url' => $this->trackingUrl ?: null,
        ]);

        OrderShipped::dispatch($this->order->fresh());
        session()->flash('success', 'Order marked as shipped.');
    }

    public function markDelivered(): void
    {
        if (! $this->fulfilment || $this->fulfilment->status !== 'shipped') {
            return;
        }

        $this->fulfilment->update(['status' => 'delivered', 'delivered_at' => now()]);
        OrderDelivered::dispatch($this->order->fresh());
        session()->flash('success', 'Order marked as delivered.');
    }

    public function markReturned(): void
    {
        if (! $this->fulfilment || $this->fulfilment->status !== 'shipped') {
            return;
        }

        $this->fulfilment->update(['status' => 'returned_to_seller', 'returned_at' => now()]);
        session()->flash('success', 'Marked as returned to seller.');
    }

    public function render()
    {
        return view('livewire.company-hub.fulfilment.fulfilment-panel');
    }
}
