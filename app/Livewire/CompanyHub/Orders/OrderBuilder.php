<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Orders;

use App\Actions\CreateOrderAction;
use App\Actions\SubmitOrderAction;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Order Builder')]
final class OrderBuilder extends Component
{
    #[Locked]
    public ?int $orderId = null;

    public ?int $quotationId = null;

    public ?int $buyerUserId = null;

    // Snapshot fields
    public string $customerBusinessName = '';

    public string $customerContactName = '';

    public string $customerContactPhone = '';

    public string $customerContactEmail = '';

    // Billing address
    public string $billingAddress1 = '';

    public string $billingCity = '';

    public string $billingProvince = '';

    public string $billingZipCode = '';

    public string $shippingAddress1 = '';

    public string $shippingCity = '';

    public string $shippingProvince = '';

    public string $shippingZipCode = '';

    // Product/variant search (S4.T17)
    public string $productSearch = '';

    // Items and fees managed via Alpine.js (sent on save)
    public array $items = [];

    public array $fees = [];

    #[Computed]
    public function order(): ?Order
    {
        return $this->orderId ? Order::with(['items', 'fees'])->find($this->orderId) : null;
    }

    /**
     * Search this shop's published product variants by product name or SKU.
     *
     * @return Collection<int, ProductVariant>
     */
    #[Computed]
    public function searchResults(): Collection
    {
        $term = mb_trim($this->productSearch);

        if (mb_strlen($term) < 2) {
            return collect();
        }

        $shop = Auth::user()->shop;

        if (! $shop) {
            return collect();
        }

        return ProductVariant::query()
            ->with('product:id,name,shop_id')
            ->whereHas('product', fn ($query) => $query->where('shop_id', $shop->id))
            ->where(function ($query) use ($term): void {
                $query->where('sku', 'ilike', "%{$term}%")
                    ->orWhereHas('product', fn ($q) => $q->where('name', 'ilike', "%{$term}%"));
            })
            ->limit(10)
            ->get();
    }

    public function mount(?int $orderId = null, ?int $quotationId = null, ?int $buyerUserId = null, ?Order $order = null): void
    {
        $shop = Auth::user()->shop;

        // For edit route: /hub/orders/{order}/edit (route model binding)
        if ($order?->exists) {
            Gate::authorize('update', $order);
            $this->orderId = $order->id;
        } elseif ($orderId) {
            $order = Order::where('shop_id', $shop->id)->where('status', 'draft')->findOrFail($orderId);
            $this->orderId = $order->id;
        } else {
            // Read from query params: /hub/orders/create?quotationId=X
            $quotationId = $quotationId ?? request()->integer('quotationId') ?: null;
            $buyerUserId = $buyerUserId ?? request()->integer('buyerUserId') ?: null;

            if (! $quotationId && ! $buyerUserId) {
                session()->flash('error', 'An awarded quotation is required to create an order.');
                $this->redirectRoute('hub.orders.index');

                return;
            }

            $result = (new CreateOrderAction)->execute($shop, $quotationId, $buyerUserId);
            if ($result['success']) {
                $this->orderId = $result['order']->id;
            } else {
                session()->flash('error', $result['error']);
                $this->redirectRoute('hub.orders.index');

                return;
            }
        }

        if ($this->orderId) {
            $this->fillFromOrder();
            $this->fillBillingFromBuyerProfile();
        }
    }

    /**
     * Auto-fill billing/contact fields from the buyer's saved profile when the
     * order does not yet carry those details. Existing order values win so a
     * saved draft is never overwritten.
     */
    public function fillBillingFromBuyerProfile(): void
    {
        $order = $this->order();

        if (! $order) {
            return;
        }

        $profile = $order->buyer?->buyerProfile;

        if (! $profile) {
            return;
        }

        $this->customerBusinessName = $this->customerBusinessName ?: (string) ($profile->business_name ?? '');
        $this->customerContactPhone = $this->customerContactPhone ?: (string) ($profile->contact_phone ?? '');
        $this->customerContactEmail = $this->customerContactEmail ?: (string) ($profile->contact_email ?? '');

        $this->billingAddress1 = $this->billingAddress1 ?: (string) ($profile->billing_address_1 ?? '');
        $this->billingCity = $this->billingCity ?: (string) ($profile->billing_city ?? '');
        $this->billingProvince = $this->billingProvince ?: (string) ($profile->billing_province ?? '');
        $this->billingZipCode = $this->billingZipCode ?: (string) ($profile->billing_zip_code ?? '');

        $this->shippingAddress1 = $this->shippingAddress1 ?: (string) ($profile->shipping_address_1 ?? '');
        $this->shippingCity = $this->shippingCity ?: (string) ($profile->shipping_city ?? '');
        $this->shippingProvince = $this->shippingProvince ?: (string) ($profile->shipping_province ?? '');
        $this->shippingZipCode = $this->shippingZipCode ?: (string) ($profile->shipping_zip_code ?? '');
    }

    public function save(): void
    {
        $order = $this->order();

        if (! $order || $order->status !== 'draft') {
            return;
        }

        $order->update([
            'customer_business_name' => $this->customerBusinessName,
            'customer_contact_name' => $this->customerContactName,
            'customer_contact_phone' => $this->customerContactPhone,
            'customer_contact_email' => $this->customerContactEmail,
            'billing_address_1' => $this->billingAddress1,
            'billing_city' => $this->billingCity,
            'billing_province' => $this->billingProvince,
            'billing_zip_code' => $this->billingZipCode,
            'shipping_address_1' => $this->shippingAddress1,
            'shipping_city' => $this->shippingCity,
            'shipping_province' => $this->shippingProvince,
            'shipping_zip_code' => $this->shippingZipCode,
        ]);

        // Replace items
        $order->items()->delete();
        $itemRows = [];
        foreach ($this->items as $index => $item) {
            if (empty($item['description'])) {
                continue;
            }
            $itemRows[] = [
                'description' => $item['description'],
                'quantity' => $item['quantity'] ?? 1,
                'unit' => $item['unit'] ?? 'pcs',
                'unit_price' => $item['unit_price'] ?? 0,
                'subtotal' => round(((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_price'] ?? 0)), 2),
                'sort_order' => $index,
                'variant_id' => $item['variant_id'] ?? null,
                'variant_snapshot' => $item['variant_snapshot'] ?? null,
            ];
        }
        if ($itemRows !== []) {
            $order->items()->createMany($itemRows);
        }

        // Replace fees
        $order->fees()->delete();
        $feeRows = [];
        foreach ($this->fees as $fee) {
            if (empty($fee['label'])) {
                continue;
            }
            $feeRows[] = [
                'label' => $fee['label'],
                'amount' => $fee['amount'] ?? 0,
            ];
        }
        if ($feeRows !== []) {
            $order->fees()->createMany($feeRows);
        }

        // Recalculate
        $itemsTotal = $order->items()->sum('subtotal');
        $feesTotal = $order->fees()->sum('amount');
        $order->update([
            'items_total' => $itemsTotal,
            'fees_total' => $feesTotal,
            'order_total' => $itemsTotal + $feesTotal,
        ]);

        $this->fillFromOrder();

        session()->flash('success', 'Order saved.');
    }

    public function confirmSubmit(): void
    {
        if (! $this->orderId) {
            session()->flash('error', 'No order to submit. Please create an order from an awarded quotation.');

            return;
        }

        \Flux\Flux::modal('submit-order-confirm')->show();
    }

    public function submit(): void
    {
        $order = $this->order();

        if (! $order) {
            session()->flash('error', 'No order to submit.');

            return;
        }

        $this->save();

        $result = (new SubmitOrderAction)->execute($order);

        if (! $result['success']) {
            session()->flash('error', $result['error']);

            return;
        }

        \App\Events\OrderSubmittedForPayment::dispatch($result['order']);

        session()->flash('success', 'Order submitted for payment.');
        $this->redirectRoute('hub.orders.show', ['order' => $order->id]);
    }

    public function render()
    {
        return view('livewire.company-hub.orders.order-builder');
    }

    private function fillFromOrder(): void
    {
        $order = $this->order();

        if (! $order) {
            return;
        }

        $this->customerBusinessName = $order->customer_business_name ?? '';
        $this->customerContactName = $order->customer_contact_name ?? '';
        $this->customerContactPhone = $order->customer_contact_phone ?? '';
        $this->customerContactEmail = $order->customer_contact_email ?? '';
        $this->billingAddress1 = $order->billing_address_1 ?? '';
        $this->billingCity = $order->billing_city ?? '';
        $this->billingProvince = $order->billing_province ?? '';
        $this->billingZipCode = $order->billing_zip_code ?? '';
        $this->shippingAddress1 = $order->shipping_address_1 ?? '';
        $this->shippingCity = $order->shipping_city ?? '';
        $this->shippingProvince = $order->shipping_province ?? '';
        $this->shippingZipCode = $order->shipping_zip_code ?? '';

        $this->items = $order->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'unit_price' => $item->unit_price,
            'variant_id' => $item->variant_id,
            'variant_snapshot' => $item->variant_snapshot,
        ])->toArray();

        $this->fees = $order->fees->map(fn ($fee) => [
            'label' => $fee->label,
            'amount' => $fee->amount,
        ])->toArray();
    }
}
