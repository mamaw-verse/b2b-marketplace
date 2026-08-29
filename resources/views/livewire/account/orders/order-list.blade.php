<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">My Orders</h1>
        </div>

        <div class="px-6 pt-5">
            <div class="flex gap-1.5 bg-[var(--hs-neutral-100)] rounded-[var(--hs-radius-pill)] p-1 w-fit flex-wrap">
                @foreach (['to_pay' => 'To Pay', 'to_ship' => 'To Ship', 'to_receive' => 'To Receive', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'disputed' => 'Disputes'] as $key => $label)
                    <button wire:click="$set('tab', '{{ $key }}')" @class([
                        'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition flex items-center gap-2',
                        'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm' => $tab === $key,
                        'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)]' => $tab !== $key
                    ])>
                        {{ $label }}
                        @if ($this->tabCounts[$key] > 0)
                            <flux:badge size="sm" color="zinc">{{ $this->tabCounts[$key] }}</flux:badge>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <div class="p-6">
            @if ($this->orders->isEmpty())
                <p class="text-center text-[var(--hs-neutral-500)] py-8">No orders in this tab.</p>
            @else
                <div class="space-y-3">
                    @foreach ($this->orders as $order)
                        {{-- The action buttons below (Track Order / Confirm Receive) must stay --}}
                        {{-- outside this link: Flux's href-button renders as its own <a>, and --}}
                        {{-- nesting an <a> inside an <a> is invalid HTML — browsers auto-close --}}
                        {{-- the outer anchor early, which broke this row's layout and made the --}}
                        {{-- buttons render detached/unstyled. --}}
                        <div class="rounded-lg border border-[var(--hs-neutral-200)] hover:border-[var(--hs-green-300)] transition">
                            <a href="{{ route('account.orders.show', $order) }}" wire:navigate class="block p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-medium text-[var(--hs-neutral-900)]">{{ $order->shop->storefront_name }}</p>
                                        <p class="text-sm text-[var(--hs-neutral-500)] mt-0.5">Order #{{ $order->id }} &middot; {{ $order->created_at->format('M d, Y') }}</p>

                                        @php
                                            // On the To Receive tab, show the quantity that was actually
                                            // fulfilled (shipped) per line, keyed by order_item_id. Falls
                                            // back to the ordered quantity when no fulfilment item exists.
                                            $fulfilledByItem = $tab === 'to_receive'
                                                ? $order->fulfilments->flatMap->fulfilmentItems->keyBy('order_item_id')
                                                : collect();
                                        @endphp
                                        <div class="mt-3 space-y-1">
                                            @foreach ($order->items->take(3) as $item)
                                                @php
                                                    $fulfilledQty = $fulfilledByItem->get($item->id)?->quantity;
                                                    $displayQty = $fulfilledQty ?? $item->quantity;
                                                @endphp
                                                <p class="text-sm text-[var(--hs-neutral-700)] flex gap-2">
                                                    <span class="text-[var(--hs-neutral-500)]">{{ rtrim(rtrim(number_format((float) $displayQty, 2), '0'), '.') }}x</span>
                                                    <span>{{ $item->description }}</span>
                                                    @if ($tab === 'to_receive' && $fulfilledQty !== null)
                                                        <span class="text-xs text-[var(--hs-neutral-400)]">of {{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} shipped</span>
                                                    @endif
                                                </p>
                                            @endforeach
                                            @if ($order->items->count() > 3)
                                                <p class="text-xs text-[var(--hs-neutral-400)] italic mt-1">
                                                    + {{ $order->items->count() - 3 }} more item(s)
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="font-semibold text-[var(--hs-neutral-900)]">{{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</p>
                                        <x-hs-status-pill :status="$order->status" class="mt-1" />
                                    </div>
                                </div>
                            </a>

                            @if ($tab === 'to_receive')
                                <div class="flex items-center justify-end gap-2 px-4 pb-4 pt-3 border-t border-[var(--hs-neutral-100)]">
                                    <flux:button variant="ghost" size="sm" href="{{ route('account.orders.show', $order) }}" wire:navigate>{{ __('Track Order') }}</flux:button>
                                    <flux:button variant="primary" size="sm" wire:click="confirmReceived({{ $order->id }})">{{ __('Confirm Receive') }}</flux:button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">{{ $this->orders->links() }}</div>
            @endif
        </div>
    </div>
</div>
