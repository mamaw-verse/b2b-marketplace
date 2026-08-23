<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Order #{{ $order->id }}</h1>
                <p class="text-sm text-[var(--hs-neutral-500)] mt-1">{{ $order->customer_business_name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-hs-status-pill :status="$order->status" />
                @if ($order->status === 'draft')
                    <flux:button size="sm" href="{{ route('hub.orders.edit', $order) }}" wire:navigate>Edit</flux:button>
                @endif
                @if ($order->status === 'pending_payment')
                    <flux:modal.trigger name="confirm-recall-order">
                        <flux:button size="sm">Recall</flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="mx-6 mt-4 p-3 bg-[var(--hs-success-50)] border border-[var(--hs-success-200)] rounded-lg text-sm text-[var(--hs-success-700)]">{{ session('success') }}</div>
        @endif

        <div class="p-6 space-y-6">
            {{-- Customer Info --}}
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-[var(--hs-neutral-500)]">Contact</p>
                    <p class="text-[var(--hs-neutral-900)] font-medium">{{ $order->customer_contact_name }}</p>
                    <p class="text-[var(--hs-neutral-700)]">{{ $order->customer_contact_email }}</p>
                    <p class="text-[var(--hs-neutral-700)]">{{ $order->customer_contact_phone }}</p>
                </div>
                <div>
                    <p class="text-[var(--hs-neutral-500)]">Shipping</p>
                    <p class="text-[var(--hs-neutral-900)]">{{ $order->shipping_address_1 }}</p>
                    <p class="text-[var(--hs-neutral-700)]">{{ $order->shipping_city }}, {{ $order->shipping_province }} {{ $order->shipping_zip_code }}</p>
                </div>
            </div>

            {{-- Items Table --}}
            <div>
                <h2 class="text-sm font-medium text-[var(--hs-neutral-500)] mb-2">Items</h2>
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-[var(--hs-neutral-100)]">
                        <th class="text-left py-2 text-[var(--hs-neutral-600)]">Description</th>
                        <th class="text-right py-2 text-[var(--hs-neutral-600)]">Qty</th>
                        <th class="text-right py-2 text-[var(--hs-neutral-600)]">Unit Price</th>
                        <th class="text-right py-2 text-[var(--hs-neutral-600)]">Subtotal</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr class="border-b border-[var(--hs-neutral-50)]">
                                <td class="py-2 text-[var(--hs-neutral-900)]">{{ $item->description }}</td>
                                <td class="py-2 text-right text-[var(--hs-neutral-700)]">{{ $item->quantity }} {{ $item->unit }}</td>
                                <td class="py-2 text-right text-[var(--hs-neutral-700)]">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="py-2 text-right text-[var(--hs-neutral-900)] font-medium">{{ number_format((float) $item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Fees --}}
            @if ($order->fees->isNotEmpty())
                <div>
                    <h2 class="text-sm font-medium text-[var(--hs-neutral-500)] mb-2">Fees</h2>
                    @foreach ($order->fees as $fee)
                        <div class="flex justify-between text-sm py-1">
                            <span class="text-[var(--hs-neutral-700)]">{{ $fee->label }}</span>
                            <span class="text-[var(--hs-neutral-900)]">{{ number_format((float) $fee->amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Total --}}
            <div class="border-t border-[var(--hs-neutral-100)] pt-4 flex justify-end">
                <div class="text-right">
                    <p class="text-sm text-[var(--hs-neutral-600)]">Order Total</p>
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)]">{{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</p>
                </div>
            </div>

            {{-- Fulfilment Panel placeholder --}}
            @if ($order->status === 'paid' || $order->fulfilments->isNotEmpty())
                <livewire:company-hub.fulfilment.fulfilment-panel :order="$order" />
            @endif

            {{-- Dispute Panel placeholder --}}
            @if ($order->disputes->isNotEmpty())
                <livewire:company-hub.disputes.dispute-panel :order="$order" />
            @endif
        </div>
    </div>

    <x-hs-confirm
        name="confirm-recall-order"
        title="Recall Order"
        message="Recall this order to draft?"
        variant="warning"
        confirmText="Recall"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="recall" wire:loading.attr="disabled">
                Recall
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
