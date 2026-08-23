<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Orders;

use App\Actions\CreateOrderAction;
use App\Actions\SubmitOrderAction;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Order Builder')]
final class OrderBuilder extends Component
{
    public ?int $orderId = null;

    public ?int $quotationId = null;

    public ?int $buyerUserId = null;

    public ?Order $order = null;

    // Snapshot fields
    public string $customerBusinessName = '';

    public string $customerContactName = '';

    public string $customerContactPhone = '';

    public string $customerContactEmail = '';

    public string $shippingAddress1 = '';

    public string $shippingCity = '';

    public string $shippingProvince = '';

    public string $shippingZipCode = '';

    // Items and fees managed via Alpine.js (sent on save)
    public array $items = [];

    public array $fees = [];

    public function mount(?int $orderId = null, ?int $quotationId = null, ?int $buyerUserId = null, ?Order $order = null): void
    {
        $shop = Auth::user()->shop;

        // For edit route: /hub/orders/{order}/edit (route model binding)
        if ($order?->exists) {
            abort_unless($order->shop_id === $shop->id && $order->status === 'draft', 403);
            $this->order = $order;
        } elseif ($orderId) {
            $this->order = Order::where('shop_id', $shop->id)->where('status', 'draft')->findOrFail($orderId);
        } else {
            // Read from query params: /hub/orders/create?quotationId=X or ?buyerUserId=X
            $quotationId = $quotationId ?? request()->integer('quotationId') ?: null;
            $buyerUserId = $buyerUserId ?? request()->integer('buyerUserId') ?: null;

            if ($quotationId || $buyerUserId) {
                $result = (new CreateOrderAction)->execute($shop, $quotationId, $buyerUserId);
                if ($result['success']) {
                    $this->order = $result['order']->load(['items', 'fees']);
                } else {
                    session()->flash('error', $result['error']);
                    $this->redirectRoute('hub.orders.index');

                    return;
                }
            }
        }

        if ($this->order) {
            $this->orderId = $this->order->id;
            $this->order->load(['items', 'fees']);
            $this->fillFromOrder();
        }
    }

    public function save(): void
    {
        if (! $this->order || $this->order->status !== 'draft') {
            return;
        }

        $this->order->update([
            'customer_business_name' => $this->customerBusinessName,
            'customer_contact_name' => $this->customerContactName,
            'customer_contact_phone' => $this->customerContactPhone,
            'customer_contact_email' => $this->customerContactEmail,
            'shipping_address_1' => $this->shippingAddress1,
            'shipping_city' => $this->shippingCity,
            'shipping_province' => $this->shippingProvince,
            'shipping_zip_code' => $this->shippingZipCode,
        ]);

        // Replace items
        $this->order->items()->delete();
        foreach ($this->items as $index => $item) {
            if (empty($item['description'])) {
                continue;
            }
            $subtotal = round(((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_price'] ?? 0)), 2);
            $this->order->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'] ?? 1,
                'unit' => $item['unit'] ?? 'pcs',
                'unit_price' => $item['unit_price'] ?? 0,
                'subtotal' => $subtotal,
                'sort_order' => $index,
            ]);
        }

        // Replace fees
        $this->order->fees()->delete();
        foreach ($this->fees as $fee) {
            if (empty($fee['label'])) {
                continue;
            }
            $this->order->fees()->create([
                'label' => $fee['label'],
                'amount' => $fee['amount'] ?? 0,
            ]);
        }

        // Recalculate
        $itemsTotal = $this->order->items()->sum('subtotal');
        $feesTotal = $this->order->fees()->sum('amount');
        $this->order->update([
            'items_total' => $itemsTotal,
            'fees_total' => $feesTotal,
            'order_total' => $itemsTotal + $feesTotal,
        ]);

        $this->order->refresh();
        $this->fillFromOrder();

        session()->flash('success', 'Order saved.');
    }

    public function confirmSubmit(): void
    {
        \Flux\Flux::modal('submit-order-confirm')->show();
    }

    public function submit(): void
    {
        $this->save();

        $result = (new SubmitOrderAction)->execute($this->order);

        if (! $result['success']) {
            session()->flash('error', $result['error']);

            return;
        }

        \App\Events\OrderSubmittedForPayment::dispatch($result['order']);

        session()->flash('success', 'Order submitted for payment.');
        $this->redirectRoute('hub.orders.show', ['order' => $this->order->id]);
    }

    public function render()
    {
        return view('livewire.company-hub.orders.order-builder');
    }

    private function fillFromOrder(): void
    {
        $this->customerBusinessName = $this->order->customer_business_name ?? '';
        $this->customerContactName = $this->order->customer_contact_name ?? '';
        $this->customerContactPhone = $this->order->customer_contact_phone ?? '';
        $this->customerContactEmail = $this->order->customer_contact_email ?? '';
        $this->shippingAddress1 = $this->order->shipping_address_1 ?? '';
        $this->shippingCity = $this->order->shipping_city ?? '';
        $this->shippingProvince = $this->order->shipping_province ?? '';
        $this->shippingZipCode = $this->order->shipping_zip_code ?? '';

        $this->items = $this->order->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'unit_price' => $item->unit_price,
        ])->toArray();

        $this->fees = $this->order->fees->map(fn ($fee) => [
            'label' => $fee->label,
            'amount' => $fee->amount,
        ])->toArray();
    }
}
