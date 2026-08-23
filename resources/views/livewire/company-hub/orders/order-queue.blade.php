<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Orders</h1>
            <flux:button variant="primary" icon="plus" href="{{ route('hub.orders.create') }}" wire:navigate>
                New Order
            </flux:button>
        </div>

        <div class="px-6 pt-5">
            <div class="flex gap-1.5 bg-[var(--hs-neutral-100)] rounded-[var(--hs-radius-pill)] p-1 w-fit">
                @foreach (['all' => 'All', 'draft' => 'Draft', 'pending_payment' => 'Pending Payment', 'to_fulfil' => 'To Fulfil', 'disputed' => 'Disputed'] as $key => $label)
                    <button
                        wire:click="$set('tab', '{{ $key }}')"
                        @class([
                            'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                            'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm' => $tab === $key,
                            'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)]' => $tab !== $key,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="p-6">
            @if ($this->orders->isEmpty())
                <p class="text-center text-[var(--hs-neutral-500)] py-8">No orders found.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--hs-neutral-100)]">
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">Order</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">Customer</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">Total</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">Status</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">Date</th>
                                <th class="py-3 px-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->orders as $order)
                                <tr class="border-b border-[var(--hs-neutral-50)] hover:bg-[var(--hs-neutral-50)]">
                                    <td class="py-3 px-2 font-medium text-[var(--hs-neutral-900)]">#{{ $order->id }}</td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-700)]">{{ $order->customer_business_name ?? $order->buyer?->first_name }}</td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-900)] font-medium">{{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</td>
                                    <td class="py-3 px-2"><x-hs-status-pill :status="$order->status" /></td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-500)]">{{ $order->created_at->format('M d, Y') }}</td>
                                    <td class="py-3 px-2 text-right">
                                        <flux:button size="sm" variant="ghost" href="{{ route('hub.orders.show', $order) }}" wire:navigate>
                                            View
                                        </flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $this->orders->links() }}</div>
            @endif
        </div>
    </div>
</div>
