<div>
    {{-- Back navigation --}}
    <div class="mb-4">
        <a href="{{ route('account.orders.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)] transition">
            <flux:icon name="arrow-left" variant="mini" class="size-4" />
            {{ __('Back to Orders') }}
        </a>
    </div>

    {{-- Single outer page card — the ONLY white/bordered/shadowed container --}}
    <div class="bg-[var(--hs-neutral-0)] rounded-[20px] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-md)] overflow-hidden">

        {{-- Header --}}
        <div class="px-8 pt-7 pb-6 border-b border-[var(--hs-neutral-100)]">
            <div class="flex items-start justify-between gap-6 flex-wrap">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-[22px] font-[650] tracking-[-0.01em] text-[var(--hs-neutral-900)]">{{ $order->order_number }}</h1>
                        <x-hs-status-pill :status="$order->status" />
                    </div>
                    <p class="text-sm text-[var(--hs-neutral-500)] mt-1">{{ $order->shop->storefront_name }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @if (($order->fulfilments->first()?->status === 'delivered' || $order->status === 'completed') && ! in_array($order->status, ['refunded', 'cancelled']))
                        @if ($order->disputes->isEmpty() || $order->disputes->every(fn ($d) => in_array($d->status, ['resolved_refunded', 'resolved_denied', 'withdrawn'])))
                            <flux:button size="sm" variant="ghost" x-on:click="$dispatch('open-dispute-modal')">Open Dispute</flux:button>
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
                </div>
            </div>
        </div>

        {{-- Body: Two-column grid — tinted summary panel bleeds full-height on the right --}}
        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_300px] items-stretch">

            {{-- MAIN COLUMN — flat sections separated by hairline dividers --}}
            <div class="min-w-0 px-8 pb-8 pt-6">

                    @if (session('success'))
                        <div class="mb-6 p-3 bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] rounded-lg text-sm text-[var(--hs-green-700)]">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="mb-6 p-3 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-sm text-[var(--hs-danger-700)]">{{ session('error') }}</div>
                    @endif

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
                                            <a href="{{ $fulfilment->tracking_url }}" target="_blank" rel="noopener" class="ml-2 font-semibold text-[var(--hs-green-700)] hover:underline">{{ __('Track →') }}</a>
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
                                    <flux:button size="sm" x-on:click="$dispatch('open-dispute-detail-modal', { disputeId: {{ $activeDispute->id }} })">
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
                        <div class="rounded-lg border border-[var(--hs-neutral-200)] overflow-hidden">
                            <table class="w-full text-[13.5px]">
                                <thead>
                                    <tr class="bg-[var(--hs-neutral-50)]">
                                        <th class="text-left text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold px-4 py-2.5 w-8">#</th>
                                        <th class="text-left text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold px-4 py-2.5">{{ __('Description') }}</th>
                                        <th class="text-left text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold px-4 py-2.5 whitespace-nowrap">{{ __('Qty') }}</th>
                                        <th class="text-left text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold px-4 py-2.5 whitespace-nowrap">{{ __('Unit') }}</th>
                                        <th class="text-left text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold px-4 py-2.5 whitespace-nowrap">{{ __('Unit price') }}</th>
                                        <th class="text-right text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold px-4 py-2.5 whitespace-nowrap">{{ __('Subtotal') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[var(--hs-neutral-100)]">
                                    @foreach ($order->items as $item)
                                        <tr>
                                            <td class="px-4 py-3 text-[var(--hs-neutral-400)] text-[12px] tabular-nums">{{ $loop->iteration }}</td>
                                            <td class="px-4 py-3 text-[var(--hs-neutral-900)] font-medium">{{ $item->description }}</td>
                                            <td class="px-4 py-3 text-[var(--hs-neutral-600)] tabular-nums whitespace-nowrap">{{ (int) $item->quantity }}</td>
                                            <td class="px-4 py-3 text-[var(--hs-neutral-600)] whitespace-nowrap">{{ $item->unit }}</td>
                                            <td class="px-4 py-3 text-[var(--hs-neutral-600)] tabular-nums whitespace-nowrap">{{ $order->currency }}&nbsp;{{ number_format((float) $item->unit_price, 2) }}</td>
                                            <td class="px-4 py-3 text-right text-[var(--hs-neutral-900)] font-semibold tabular-nums whitespace-nowrap">{{ $order->currency }}&nbsp;{{ number_format((float) $item->subtotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                @if ($order->fees->isNotEmpty())
                                    <tfoot class="border-t border-[var(--hs-neutral-200)] divide-y divide-[var(--hs-neutral-100)]">
                                        @foreach ($order->fees as $fee)
                                            <tr class="bg-[var(--hs-neutral-50)]">
                                                <td colspan="5" class="px-4 py-2.5 text-[var(--hs-neutral-500)] italic">
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <flux:icon name="receipt-percent" variant="mini" class="size-3.5 text-[var(--hs-neutral-400)]" />
                                                        {{ $fee->label }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2.5 text-right text-[var(--hs-neutral-600)] font-medium tabular-nums italic whitespace-nowrap">{{ $order->currency }}&nbsp;{{ number_format((float) $fee->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </section>

                    {{-- Reviews Section (completed orders only) --}}
                    @if ($order->status === 'completed')
                        <section class="pt-6 mt-6 border-t border-[var(--hs-neutral-100)]">
                            <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)] mb-4">{{ __('Reviews') }}</h2>
                            <livewire:account.orders.review-form :order="$order" />
                        </section>
                    @endif
            </div>

            {{-- SIDEBAR — tinted panel bleeding to the card's edges, separated from the
                 main column by its own left border (matches the header hairline). --}}
            <div class="bg-[var(--hs-neutral-50)] border-t md:border-t-0 md:border-l border-[var(--hs-neutral-100)] px-6 py-6 flex flex-col gap-6">
                    {{-- Summary breakdown — Subtotal/Fees up top, Total due below a dashed rule --}}
                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between text-[13px]">
                            <span class="text-[var(--hs-neutral-500)]">{{ __('Subtotal') }}</span>
                            <span class="text-[var(--hs-neutral-900)] font-semibold tabular-nums">{{ number_format((float) $order->items_total, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-[13px]">
                            <span class="text-[var(--hs-neutral-500)]">{{ __('Fees') }}</span>
                            <span class="text-[var(--hs-neutral-900)] font-semibold tabular-nums">{{ number_format((float) ($order->fees_total ?? 0), 2) }}</span>
                        </div>
                        @php $refundedAmount = $order->ledgerEntries->where('type', 'refund')->sum('amount'); @endphp
                        @if ($refundedAmount > 0)
                            <div class="flex justify-between text-[13px]">
                                <span class="text-[var(--hs-neutral-500)]">{{ __('Refunded') }}</span>
                                <span class="text-[var(--hs-danger-700)] font-semibold tabular-nums">−{{ number_format((float) $refundedAmount, 2) }}</span>
                            </div>
                        @endif

                        <div class="mt-3 pt-4 border-t border-dashed border-[var(--hs-neutral-200)] flex items-baseline justify-between">
                            <span class="text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold">{{ __('Total due') }}</span>
                            <p class="text-[26px] font-bold tracking-[-0.01em] text-[var(--hs-neutral-900)] tabular-nums leading-none">
                                <span class="text-sm font-semibold text-[var(--hs-neutral-400)] mr-1 align-[3px]">{{ $order->currency }}</span>{{ number_format((float) $order->order_total, 2) }}
                            </p>
                        </div>
                    </div>

                    {{-- Contact block — separated by a solid hairline --}}
                    <div class="pt-5 border-t border-[var(--hs-neutral-200)]">
                        <p class="text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold mb-2">{{ __('Contact') }}</p>
                        <p class="text-[13.5px] font-bold text-[var(--hs-neutral-900)]">{{ $order->customer_contact_name }}</p>
                        <p class="mt-1.5 flex items-start gap-2 text-[13px] text-[var(--hs-neutral-600)] leading-relaxed">
                            <flux:icon name="envelope" variant="mini" class="size-3.5 mt-0.5 text-[var(--hs-neutral-400)] shrink-0" />
                            {{ $order->customer_contact_email }}
                        </p>
                        @if ($order->customer_contact_phone)
                            <p class="mt-1.5 flex items-start gap-2 text-[13px] text-[var(--hs-neutral-600)]">
                                <flux:icon name="phone" variant="mini" class="size-3.5 mt-0.5 text-[var(--hs-neutral-400)] shrink-0" />
                                {{ $order->customer_contact_phone }}
                            </p>
                        @endif
                    </div>

                    {{-- Shipping block --}}
                    <div class="pt-5 border-t border-[var(--hs-neutral-200)]">
                        <p class="text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold mb-2">{{ __('Shipping') }}</p>
                        <p class="flex items-start gap-2 text-[13.5px] text-[var(--hs-neutral-800)] leading-relaxed">
                            <flux:icon name="map-pin" variant="mini" class="size-3.5 mt-0.5 text-[var(--hs-neutral-400)] shrink-0" />
                            <span>
                                {{ $order->shipping_address_1 }}
                                @if ($order->shipping_address_2)
                                    <br>{{ $order->shipping_address_2 }}
                                @endif
                                <br>{{ $order->shipping_city }}, {{ $order->shipping_province }} {{ $order->shipping_zip_code }}
                            </span>
                        </p>
                    </div>
            </div>
        </div>
    </div>

    {{-- Open Dispute Modal (opened via the 'open-dispute-modal' browser event) --}}
    @if (($order->fulfilments->first()?->status === 'delivered' || $order->status === 'completed') && ! in_array($order->status, ['refunded', 'cancelled']))
        @if ($order->disputes->isEmpty() || $order->disputes->every(fn ($d) => in_array($d->status, ['resolved_refunded', 'resolved_denied', 'withdrawn'])))
            <livewire:account.disputes.dispute-form :order="$order" :key="'dispute-form-'.$order->id" />
        @endif
    @endif

    {{-- View Dispute Modal (opened via the 'open-dispute-detail-modal' browser event) --}}
    @if ($order->disputes->isNotEmpty())
        <livewire:account.disputes.dispute-detail :order="$order" :key="'dispute-detail-'.$order->id" />
    @endif

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