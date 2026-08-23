<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Order #{{ $order->id }}</h1>
                <p class="text-sm text-[var(--hs-neutral-500)] mt-1">{{ $order->shop->storefront_name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-hs-status-pill :status="$order->status" />
                @if ($order->status === 'pending_payment')
                    <flux:button size="sm" variant="primary" href="{{ route('account.orders.pay', $order) }}" wire:navigate>Pay Now</flux:button>
                    <flux:modal.trigger name="confirm-cancel-order">
                        <flux:button size="sm" variant="ghost">Cancel</flux:button>
                    </flux:modal.trigger>
                @endif
                @if ($order->fulfilments->first()?->status === 'delivered' && $order->status === 'paid')
                    <flux:modal.trigger name="confirm-receipt-order">
                        <flux:button size="sm" variant="primary">Confirm Receipt</flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="mx-6 mt-4 p-3 bg-[var(--hs-success-50)] border border-[var(--hs-success-200)] rounded-lg text-sm text-[var(--hs-success-700)]">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mx-6 mt-4 p-3 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-sm text-[var(--hs-danger-700)]">{{ session('error') }}</div>
        @endif

        <div class="p-6 space-y-6">
            {{-- Items --}}
            <table class="w-full text-sm">
                <thead><tr class="border-b border-[var(--hs-neutral-100)]">
                    <th class="text-left py-2 text-[var(--hs-neutral-600)]">Item</th>
                    <th class="text-right py-2 text-[var(--hs-neutral-600)]">Qty</th>
                    <th class="text-right py-2 text-[var(--hs-neutral-600)]">Price</th>
                    <th class="text-right py-2 text-[var(--hs-neutral-600)]">Subtotal</th>
                </tr></thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr class="border-b border-[var(--hs-neutral-50)]">
                            <td class="py-2 text-[var(--hs-neutral-900)]">{{ $item->description }}</td>
                            <td class="py-2 text-right text-[var(--hs-neutral-700)]">{{ $item->quantity }} {{ $item->unit }}</td>
                            <td class="py-2 text-right text-[var(--hs-neutral-700)]">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="py-2 text-right font-medium text-[var(--hs-neutral-900)]">{{ number_format((float) $item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totals --}}
            <div class="border-t border-[var(--hs-neutral-100)] pt-4 text-right">
                <p class="text-2xl font-bold text-[var(--hs-neutral-900)]">{{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</p>
            </div>

            {{-- Fulfilment tracking --}}
            @if ($order->fulfilments->isNotEmpty())
                @php $fulfilment = $order->fulfilments->first(); @endphp
                <div class="p-4 rounded-lg bg-[var(--hs-neutral-50)] border border-[var(--hs-neutral-200)]">
                    <h3 class="text-sm font-medium text-[var(--hs-neutral-900)] mb-2">Fulfilment</h3>
                    <div class="flex items-center gap-2">
                        <x-hs-status-pill :status="$fulfilment->status" />
                        @if ($fulfilment->tracking_code)
                            <span class="text-sm text-[var(--hs-neutral-700)]">{{ $fulfilment->carrier }} — {{ $fulfilment->tracking_code }}</span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Dispute link --}}
            {{-- Active Dispute --}}
            @php
                $activeDispute = $order->disputes->first(fn ($d) => ! in_array($d->status, ['resolved_refunded', 'resolved_denied', 'withdrawn']));
            @endphp
            @if ($activeDispute)
                <div class="p-4 rounded-lg border border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)]">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-[var(--hs-danger-700)]">Active Dispute</h3>
                            <p class="text-sm text-[var(--hs-neutral-700)] mt-1">{{ str_replace('_', ' ', ucfirst($activeDispute->type)) }} — <x-hs-status-pill :status="$activeDispute->status" /></p>
                            <p class="text-xs text-[var(--hs-neutral-500)] mt-1 line-clamp-1">{{ $activeDispute->reason }}</p>
                        </div>
                        <flux:button size="sm" href="{{ route('account.orders.dispute.show', [$order, $activeDispute]) }}" wire:navigate>
                            View Dispute
                        </flux:button>
                    </div>
                </div>
            @endif

            {{-- Open Dispute button --}}
            @if (($order->fulfilments->first()?->status === 'delivered' || $order->status === 'completed') && ! in_array($order->status, ['refunded', 'cancelled']))
                @if ($order->disputes->isEmpty() || $order->disputes->every(fn ($d) => in_array($d->status, ['resolved_refunded', 'resolved_denied', 'withdrawn'])))
                    <div class="text-right">
                        <flux:button size="sm" variant="ghost" href="{{ route('account.orders.dispute.create', $order) }}" wire:navigate>Open Dispute</flux:button>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <x-hs-confirm
        name="confirm-cancel-order"
        title="Cancel Order"
        message="Cancel this order?"
        variant="danger"
        confirmText="Cancel Order"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="cancel" wire:loading.attr="disabled">
                Cancel Order
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    <x-hs-confirm
        name="confirm-receipt-order"
        title="Confirm Receipt"
        message="Confirm you received this order?"
        variant="primary"
        confirmText="Confirm Receipt"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="confirmReceipt" wire:loading.attr="disabled">
                Confirm Receipt
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
