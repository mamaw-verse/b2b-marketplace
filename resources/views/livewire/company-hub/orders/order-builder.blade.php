<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ $orderId ? 'Edit Order #' . $orderId : 'New Order' }}</h1>
            <div class="flex gap-2">
                <flux:button variant="ghost" href="{{ route('hub.orders.index') }}" wire:navigate>Cancel</flux:button>
                <flux:button wire:click="save">Save Draft</flux:button>
                <flux:button variant="primary" wire:click="confirmSubmit">Submit for Payment</flux:button>
            </div>
        </div>

        @if (session('error'))
            <div class="mx-6 mt-4 p-3 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-sm text-[var(--hs-danger-700)]">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="mx-6 mt-4 p-3 bg-[var(--hs-success-50)] border border-[var(--hs-success-200)] rounded-lg text-sm text-[var(--hs-success-700)]">{{ session('success') }}</div>
        @endif

        <div class="p-6 space-y-6">
            {{-- Customer Details --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="customerBusinessName" label="Business Name" />
                <flux:input wire:model="customerContactName" label="Contact Name" />
                <flux:input wire:model="customerContactPhone" label="Phone" />
                <flux:input wire:model="customerContactEmail" label="Email" type="email" />
            </div>

            {{-- Shipping Address --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input wire:model="shippingAddress1" label="Shipping Address" class="md:col-span-3" />
                <flux:input wire:model="shippingCity" label="City" />
                <flux:input wire:model="shippingProvince" label="Province" />
                <flux:input wire:model="shippingZipCode" label="Zip Code" />
            </div>

            {{-- Order Items --}}
            <div x-data="{ items: @entangle('items') }">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-medium text-[var(--hs-neutral-900)]">Items</h2>
                    <button type="button" @click="items.push({description:'', quantity:1, unit:'pcs', unit_price:0})" class="text-sm text-[var(--hs-green-700)] hover:underline">+ Add Item</button>
                </div>
                <template x-for="(item, index) in items" :key="index">
                    <div class="grid grid-cols-12 gap-2 mb-2 items-end">
                        <div class="col-span-4"><input x-model="item.description" placeholder="Description" class="w-full rounded-md border border-[var(--hs-neutral-200)] px-3 py-2 text-sm bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)]" /></div>
                        <div class="col-span-2"><input x-model="item.quantity" type="number" min="0" step="0.01" placeholder="Qty" class="w-full rounded-md border border-[var(--hs-neutral-200)] px-3 py-2 text-sm bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)]" /></div>
                        <div class="col-span-2"><input x-model="item.unit" placeholder="Unit" class="w-full rounded-md border border-[var(--hs-neutral-200)] px-3 py-2 text-sm bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)]" /></div>
                        <div class="col-span-2"><input x-model="item.unit_price" type="number" min="0" step="0.01" placeholder="Unit Price" class="w-full rounded-md border border-[var(--hs-neutral-200)] px-3 py-2 text-sm bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)]" /></div>
                        <div class="col-span-1 text-right text-sm text-[var(--hs-neutral-700)] py-2" x-text="(parseFloat(item.quantity||0) * parseFloat(item.unit_price||0)).toFixed(2)"></div>
                        <div class="col-span-1"><button type="button" @click="items.splice(index, 1)" class="text-[var(--hs-danger-700)] text-sm hover:underline">Remove</button></div>
                    </div>
                </template>
            </div>

            {{-- Fees --}}
            <div x-data="{ fees: @entangle('fees') }">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-medium text-[var(--hs-neutral-900)]">Fees</h2>
                    <button type="button" @click="fees.push({label:'', amount:0})" class="text-sm text-[var(--hs-green-700)] hover:underline">+ Add Fee</button>
                </div>
                <template x-for="(fee, index) in fees" :key="index">
                    <div class="grid grid-cols-12 gap-2 mb-2 items-end">
                        <div class="col-span-6"><input x-model="fee.label" placeholder="Fee Label" class="w-full rounded-md border border-[var(--hs-neutral-200)] px-3 py-2 text-sm bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)]" /></div>
                        <div class="col-span-4"><input x-model="fee.amount" type="number" min="0" step="0.01" placeholder="Amount" class="w-full rounded-md border border-[var(--hs-neutral-200)] px-3 py-2 text-sm bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)]" /></div>
                        <div class="col-span-2"><button type="button" @click="fees.splice(index, 1)" class="text-[var(--hs-danger-700)] text-sm hover:underline">Remove</button></div>
                    </div>
                </template>
            </div>

            {{-- Totals --}}
            @if ($order)
                <div class="border-t border-[var(--hs-neutral-100)] pt-4 text-right space-y-1">
                    <p class="text-sm text-[var(--hs-neutral-600)]">Items: {{ $order->currency }} {{ number_format((float) $order->items_total, 2) }}</p>
                    <p class="text-sm text-[var(--hs-neutral-600)]">Fees: {{ $order->currency }} {{ number_format((float) $order->fees_total, 2) }}</p>
                    <p class="text-lg font-bold text-[var(--hs-neutral-900)]">Total: {{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Submit Confirmation --}}
    <x-hs-confirm
        name="submit-order-confirm"
        title="Submit Order for Payment?"
        message="The buyer will be notified and prompted to pay. You won't be able to edit the order after submission."
        variant="primary"
        confirmText="Submit"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="submit" wire:loading.attr="disabled">
                Submit
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
