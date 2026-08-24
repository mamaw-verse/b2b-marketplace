<div>
    {{-- Single outer page card — the ONLY white/bordered/shadowed container --}}
    <div class="bg-[var(--hs-neutral-0)] rounded-[16px] border border-[var(--hs-neutral-200)] shadow-[0_1px_2px_rgba(20,22,30,.04)] overflow-hidden">

        {{-- Header --}}
        <div class="px-8 pt-7 pb-6">
            <div class="flex items-start justify-between gap-6 flex-wrap">
                <div>
                    <h1 class="text-[22px] font-[650] tracking-[-0.01em] text-[var(--hs-neutral-900)]">Order #{{ $order->id }}</h1>
                    <p class="text-sm text-[var(--hs-neutral-500)] mt-1">{{ $order->shop->storefront_name }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @if (($order->fulfilments->first()?->status === 'delivered' || $order->status === 'completed') && ! in_array($order->status, ['refunded', 'cancelled']))
                        @if ($order->disputes->isEmpty() || $order->disputes->every(fn ($d) => in_array($d->status, ['resolved_refunded', 'resolved_denied', 'withdrawn'])))
                            <flux:button size="sm" variant="ghost" href="{{ route('account.orders.dispute.create', $order) }}" wire:navigate>Open Dispute</flux:button>
                        @endif
                    @endif
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
                    <x-hs-status-pill :status="$order->status" />
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="mx-8 mb-4 p-3 bg-[var(--hs-success-50)] border border-[var(--hs-success-200)] rounded-lg text-sm text-[var(--hs-success-700)]">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mx-8 mb-4 p-3 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-sm text-[var(--hs-danger-700)]">{{ session('error') }}</div>
        @endif

        {{-- Body: Two-column grid --}}
        <div class="px-8 pb-8">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-8 items-start">

                {{-- MAIN COLUMN — flat sections separated by hairline dividers --}}
                <div>

                    {{-- Fulfilment Section --}}
                    @if ($order->fulfilments->isNotEmpty() || in_array($order->status, ['paid', 'completed', 'refunded']))
                        <section class="pb-6 border-b border-[var(--hs-neutral-100)]">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)]">{{ __('Fulfilment') }}</h2>
                                @php $fulfilment = $order->fulfilments->first(); @endphp
                                @if ($fulfilment?->carrier)
                                    <span class="text-[12px] text-[var(--hs-neutral-500)]">{{ $fulfilment->carrier }} · {{ $fulfilment->tracking_code }}</span>
                                @endif
                            </div>

                            @if ($fulfilment)
                                {{-- Timeline: horizontal stepper --}}
                                <div class="flex items-start py-2">
                                    @php
                                        $steps = [
                                            ['label' => __('Paid'), 'date' => $order->paid_at, 'done' => (bool) $order->paid_at],
                                            ['label' => __('Shipped'), 'date' => $fulfilment->shipped_at, 'done' => (bool) $fulfilment->shipped_at],
                                            ['label' => __('Delivered'), 'date' => $fulfilment->delivered_at, 'done' => (bool) $fulfilment->delivered_at],
                                        ];
                                    @endphp
                                    @foreach ($steps as $i => $step)
                                        <div class="flex-1 flex flex-col items-center text-center">
                                            {{-- Node + connector row --}}
                                            <div class="flex items-center w-full">
                                                {{-- Left connector --}}
                                                @if ($i > 0)
                                                    <div class="flex-1 h-[3px] rounded-full {{ $step['done'] ? 'bg-[var(--hs-green-600)]' : 'bg-[var(--hs-neutral-200)]' }}"></div>
                                                @else
                                                    <div class="flex-1"></div>
                                                @endif
                                                {{-- Node --}}
                                                @if ($step['done'])
                                                    <div class="w-7 h-7 rounded-full bg-[var(--hs-green-600)] text-white flex items-center justify-center shrink-0">
                                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                    </div>
                                                @else
                                                    <div class="w-7 h-7 rounded-full border-2 border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] flex items-center justify-center shrink-0">
                                                        <div class="w-2 h-2 rounded-full bg-[var(--hs-neutral-400)]"></div>
                                                    </div>
                                                @endif
                                                {{-- Right connector --}}
                                                @if ($i < count($steps) - 1)
                                                    <div class="flex-1 h-[3px] rounded-full {{ $steps[$i + 1]['done'] ? 'bg-[var(--hs-green-600)]' : 'bg-[var(--hs-neutral-200)]' }}"></div>
                                                @else
                                                    <div class="flex-1"></div>
                                                @endif
                                            </div>
                                            {{-- Label + date --}}
                                            <p class="text-[13px] font-semibold text-[var(--hs-neutral-900)] mt-2.5">{{ $step['label'] }}</p>
                                            <p class="text-[12px] text-[var(--hs-neutral-500)] mt-0.5">{{ $step['date']?->format('M d, H:i') ?? '—' }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Courier info --}}
                                @if ($fulfilment->carrier)
                                    <div class="mt-[18px] pt-[14px] border-t border-dashed border-[var(--hs-neutral-100)] text-[13px] text-[var(--hs-neutral-600)]">
                                        {{ __('Courier:') }} <span class="font-bold text-[var(--hs-neutral-900)]">{{ $fulfilment->carrier }}</span>
                                        @if ($fulfilment->tracking_code)
                                            — <span class="font-bold text-[var(--hs-neutral-900)]">{{ $fulfilment->tracking_code }}</span>
                                        @endif
                                        @if ($fulfilment->tracking_url)
                                            <a href="{{ $fulfilment->tracking_url }}" target="_blank" rel="noopener" class="ml-2 font-semibold text-[#157a46] hover:underline">{{ __('Track →') }}</a>
                                        @endif
                                    </div>
                                @endif
                            @elseif ($order->status === 'paid')
                                {{-- Paid but no fulfilment yet — show partial timeline --}}
                                <div class="flex items-start py-2">
                                    @php
                                        $steps = [
                                            ['label' => __('Paid'), 'date' => $order->paid_at, 'done' => true],
                                            ['label' => __('Shipped'), 'date' => null, 'done' => false],
                                            ['label' => __('Delivered'), 'date' => null, 'done' => false],
                                        ];
                                    @endphp
                                    @foreach ($steps as $i => $step)
                                        <div class="flex-1 flex flex-col items-center text-center">
                                            <div class="flex items-center w-full">
                                                @if ($i > 0)
                                                    <div class="flex-1 h-[3px] rounded-full {{ $step['done'] ? 'bg-[var(--hs-green-600)]' : 'bg-[var(--hs-neutral-200)]' }}"></div>
                                                @else
                                                    <div class="flex-1"></div>
                                                @endif
                                                @if ($step['done'])
                                                    <div class="w-7 h-7 rounded-full bg-[var(--hs-green-600)] text-white flex items-center justify-center shrink-0">
                                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                    </div>
                                                @else
                                                    <div class="w-7 h-7 rounded-full border-2 border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] flex items-center justify-center shrink-0">
                                                        <div class="w-2 h-2 rounded-full bg-[var(--hs-neutral-400)]"></div>
                                                    </div>
                                                @endif
                                                @if ($i < count($steps) - 1)
                                                    <div class="flex-1 h-[3px] rounded-full {{ $steps[$i + 1]['done'] ? 'bg-[var(--hs-green-600)]' : 'bg-[var(--hs-neutral-200)]' }}"></div>
                                                @else
                                                    <div class="flex-1"></div>
                                                @endif
                                            </div>
                                            <p class="text-[13px] font-semibold text-[var(--hs-neutral-900)] mt-2.5">{{ $step['label'] }}</p>
                                            <p class="text-[12px] text-[var(--hs-neutral-500)] mt-0.5">{{ $step['date']?->format('M d, H:i') ?? '—' }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Resolved dispute note — muted gray, never alarming --}}
                            @php $resolvedDispute = $order->disputes->first(fn ($d) => in_array($d->status, ['resolved_refunded', 'resolved_denied'])); @endphp
                            @if ($resolvedDispute)
                                <div class="mt-[18px] flex gap-2.5 items-start bg-[var(--hs-neutral-50)] border border-[var(--hs-neutral-100)] rounded-lg p-3 text-[13px] text-[var(--hs-neutral-600)]">
                                    <svg class="size-4 shrink-0 mt-0.5 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><circle cx="12" cy="16" r="0.6" fill="currentColor"/></svg>
                                    <div>
                                        <span class="font-bold text-[var(--hs-neutral-900)]">{{ __('Dispute resolved') }}</span>
                                        {{ __('on') }} {{ $resolvedDispute->resolved_at?->format('M d') }}
                                        — {{ $resolvedDispute->resolution_type === 'denied' ? __('no refund issued.') : __('buyer refunded.') }}
                                    </div>
                                </div>
                            @endif
                        </section>
                    @endif

                    {{-- Active Dispute — its own distinct urgent block, separate from fulfilment --}}
                    @php
                        $activeDispute = $order->disputes->first(fn ($d) => ! in_array($d->status, ['resolved_refunded', 'resolved_denied', 'withdrawn']));
                    @endphp
                    @if ($activeDispute)
                        <section class="py-6 border-b border-[var(--hs-neutral-100)]">
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
                        </section>
                    @endif

                    {{-- Items Section --}}
                    <section class="pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)]">{{ __('Items') }}</h2>
                        </div>
                        <table class="w-full border-collapse text-[13.5px]">
                            <thead>
                                <tr>
                                    <th class="text-left text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold pb-2.5 border-b border-[var(--hs-neutral-100)]">{{ __('Description') }}</th>
                                    <th class="text-right text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold pb-2.5 border-b border-[var(--hs-neutral-100)]">{{ __('Qty') }}</th>
                                    <th class="text-right text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold pb-2.5 border-b border-[var(--hs-neutral-100)]">{{ __('Unit price') }}</th>
                                    <th class="text-right text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold pb-2.5 border-b border-[var(--hs-neutral-100)]">{{ __('Subtotal') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td class="py-3 text-[var(--hs-neutral-900)] font-medium">{{ $item->description }}</td>
                                        <td class="py-3 text-right text-[var(--hs-neutral-700)] tabular-nums">{{ $item->quantity }} {{ $item->unit }}</td>
                                        <td class="py-3 text-right text-[var(--hs-neutral-700)]">{{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td class="py-3 text-right text-[var(--hs-neutral-900)] font-semibold tabular-nums">{{ number_format((float) $item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if ($order->fees->isNotEmpty())
                            <div class="mt-4 pt-3 border-t border-[var(--hs-neutral-100)]">
                                @foreach ($order->fees as $fee)
                                    <div class="flex justify-between text-[13.5px] py-1.5">
                                        <span class="text-[var(--hs-neutral-600)]">{{ $fee->label }}</span>
                                        <span class="text-[var(--hs-neutral-900)] font-semibold tabular-nums">{{ number_format((float) $fee->amount, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>

                {{-- SIDEBAR — single tinted panel, NOT white/bordered like the outer card --}}
                <div class="bg-[var(--hs-neutral-50)] border border-[var(--hs-neutral-100)] rounded-[var(--hs-radius-card)] overflow-hidden">
                    {{-- Order Total block --}}
                    <div class="p-5">
                        <div class="text-right">
                            <p class="text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold">{{ __('Order total') }}</p>
                            <p class="text-[26px] font-bold tracking-[-0.01em] text-[var(--hs-neutral-900)] tabular-nums mt-1">
                                <span class="text-sm font-semibold text-[var(--hs-neutral-400)] mr-1">{{ $order->currency }}</span>{{ number_format((float) $order->order_total, 2) }}
                            </p>
                        </div>
                        <div class="mt-3.5 pt-3 border-t border-[var(--hs-neutral-200)] space-y-1">
                            <div class="flex justify-between text-[13px]">
                                <span class="text-[var(--hs-neutral-500)]">{{ __('Subtotal') }}</span>
                                <span class="text-[var(--hs-neutral-900)] font-medium tabular-nums">{{ number_format((float) $order->items_total, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-[13px]">
                                <span class="text-[var(--hs-neutral-500)]">{{ __('Fees') }}</span>
                                <span class="text-[var(--hs-neutral-900)] font-medium tabular-nums">{{ number_format((float) ($order->fees_total ?? 0), 2) }}</span>
                            </div>
                            @php $refundedAmount = $order->ledgerEntries->where('type', 'refund')->sum('amount'); @endphp
                            @if ($refundedAmount > 0)
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-[var(--hs-neutral-500)]">{{ __('Refunded') }}</span>
                                    <span class="text-[var(--hs-danger-700)] font-medium tabular-nums">−{{ number_format((float) $refundedAmount, 2) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Customer & Shipping block — same panel, separated by internal divider --}}
                    <div class="p-5 border-t border-[var(--hs-neutral-200)]">
                        <p class="text-[13.5px] font-semibold text-[var(--hs-neutral-900)]">{{ $order->customer_contact_name }}</p>
                        <p class="text-[13px] text-[var(--hs-neutral-600)] leading-relaxed">{{ $order->customer_contact_email }}</p>
                        @if ($order->customer_contact_phone)
                            <p class="text-[13px] text-[var(--hs-neutral-600)]">{{ $order->customer_contact_phone }}</p>
                        @endif

                        <div class="mt-3.5 pt-3.5 border-t border-[var(--hs-neutral-200)]">
                            <p class="text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold mb-1.5">{{ __('Shipping') }}</p>
                            <p class="text-[13.5px] text-[var(--hs-neutral-800)] leading-relaxed">{{ $order->shipping_address_1 }}</p>
                            @if ($order->shipping_address_2)
                                <p class="text-[13.5px] text-[var(--hs-neutral-600)] leading-relaxed">{{ $order->shipping_address_2 }}</p>
                            @endif
                            <p class="text-[13.5px] text-[var(--hs-neutral-600)]">{{ $order->shipping_city }}, {{ $order->shipping_province }} {{ $order->shipping_zip_code }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reviews Section (completed orders only) — full width below the grid --}}
            @if ($order->status === 'completed')
                <section class="pt-6 mt-6 border-t border-[var(--hs-neutral-100)]">
                    <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)] mb-4">{{ __('Reviews') }}</h2>
                    <livewire:account.orders.review-form :order="$order" />
                </section>
            @endif
        </div>
    </div>

    {{-- Cancel Confirmation Modal --}}
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

    {{-- Confirm Receipt Modal --}}
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
