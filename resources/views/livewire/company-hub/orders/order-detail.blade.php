<div>
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
                    <p class="text-sm text-[var(--hs-neutral-500)] mt-1">
                        {{ $order->customer_business_name }}
                        @if ($order->created_at)
                            <span class="text-[var(--hs-neutral-300)] mx-1.5">·</span>
                            {{ __('Placed') }} {{ $order->created_at->format('M d, Y') }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    @if ($order->status === 'draft')
                        <flux:button size="sm" href="{{ route('hub.orders.edit', $order) }}" wire:navigate>{{ __('Edit') }}</flux:button>
                    @endif
                    @if ($order->status === 'pending_payment')
                        <flux:modal.trigger name="confirm-recall-order">
                            <flux:button size="sm">{{ __('Recall') }}</flux:button>
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

                @php
                    $activeDispute = $order->disputes->first(fn ($d) => ! in_array($d->status, ['resolved_refunded', 'resolved_denied', 'withdrawn']));
                    $fulfilment = $order->fulfilments->first();
                    $showFulfilment = $order->status === 'paid' || $order->fulfilments->isNotEmpty() || in_array($order->status, ['completed', 'refunded']);

                    if ($fulfilment) {
                        $steps = [
                            ['label' => __('Paid'), 'date' => $order->paid_at, 'done' => (bool) $order->paid_at],
                            ['label' => __('Shipped'), 'date' => $fulfilment->shipped_at, 'done' => (bool) $fulfilment->shipped_at],
                            ['label' => __('Delivered'), 'date' => $fulfilment->delivered_at, 'done' => (bool) $fulfilment->delivered_at],
                        ];
                    } elseif ($order->status === 'paid') {
                        $steps = [
                            ['label' => __('Paid'), 'date' => $order->paid_at, 'done' => true],
                            ['label' => __('Shipped'), 'date' => null, 'done' => false],
                            ['label' => __('Delivered'), 'date' => null, 'done' => false],
                        ];
                    } else {
                        $steps = [];
                    }
                @endphp

                {{-- Active Dispute — most urgent, always leads the column --}}
                @if ($activeDispute)
                    <section class="pb-6 border-b border-[var(--hs-neutral-100)]">
                        <livewire:company-hub.disputes.dispute-panel :order="$order" />
                    </section>
                @endif

                {{-- Fulfilment Section --}}
                @if ($showFulfilment)
                    <section class="py-6 border-b border-[var(--hs-neutral-100)] first:pt-0">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)]">{{ __('Fulfilment') }}</h2>
                            @if ($fulfilment?->carrier)
                                <span class="text-[12px] text-[var(--hs-neutral-500)]">{{ $fulfilment->carrier }} · {{ $fulfilment->tracking_code }}</span>
                            @endif
                        </div>

                        @if (! empty($steps))
                            {{-- Timeline: horizontal stepper (single implementation, used whether or not fulfilment has started) --}}
                            <div class="flex items-start py-2">
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
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
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

                        {{-- Courier info --}}
                        @if ($fulfilment?->carrier)
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

                        {{-- Fulfilment actions (ship/deliver/return) --}}
                        @if (! $fulfilment || in_array($fulfilment?->status, ['pending', 'shipped']))
                            <div class="{{ $fulfilment ? 'mt-5 pt-4 border-t border-[var(--hs-neutral-100)]' : '' }}">
                                <livewire:company-hub.fulfilment.fulfilment-panel :order="$order" />
                            </div>
                        @endif
                    </section>
                @endif

                {{-- Items Section --}}
                <section class="pt-6 {{ $showFulfilment || $activeDispute ? '' : 'pt-0' }}">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)]">
                            {{ __('Items') }}
                            <span class="text-[var(--hs-neutral-400)] font-normal normal-case tracking-normal">· {{ $order->items->count() }}</span>
                        </h2>
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

                {{-- Disputes Section — full history: status, reason, resolution, timestamps --}}
                @if ($order->disputes->isNotEmpty())
                    <section class="pt-6 mt-6 border-t border-[var(--hs-neutral-100)]">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)]">
                                {{ __('Disputes') }}
                                <span class="text-[var(--hs-neutral-400)] font-normal normal-case tracking-normal">· {{ $order->disputes->count() }}</span>
                            </h2>
                        </div>

                        <div class="space-y-3">
                            @foreach ($order->disputes as $dispute)
                                <div class="rounded-lg border border-[var(--hs-neutral-200)] p-4">
                                    <div class="flex items-center justify-between gap-3 flex-wrap">
                                        <div class="flex items-center gap-2">
                                            <x-hs-status-pill :status="$dispute->status" />
                                            <span class="text-[13px] text-[var(--hs-neutral-700)] font-medium">{{ str_replace('_', ' ', ucfirst($dispute->type)) }}</span>
                                        </div>
                                        <span class="text-[12px] text-[var(--hs-neutral-500)]">
                                            {{ __('Opened') }} {{ $dispute->created_at?->format('M d, Y H:i') }}
                                            @if ($dispute->openedBy)
                                                · {{ $dispute->openedBy->first_name }} {{ $dispute->openedBy->last_name }}
                                            @endif
                                        </span>
                                    </div>

                                    <p class="mt-2.5 text-[13.5px] text-[var(--hs-neutral-700)] leading-relaxed">{{ $dispute->reason }}</p>

                                    @if ($dispute->resolution_type)
                                        <div class="mt-3 pt-3 border-t border-[var(--hs-neutral-100)] text-[13px] text-[var(--hs-neutral-600)] space-y-1">
                                            <div class="flex justify-between">
                                                <span class="text-[var(--hs-neutral-500)]">{{ __('Resolution') }}</span>
                                                <span class="text-[var(--hs-neutral-900)] font-medium">{{ str_replace('_', ' ', ucfirst($dispute->resolution_type)) }}</span>
                                            </div>
                                            @if ($dispute->resolution_amount !== null)
                                                <div class="flex justify-between">
                                                    <span class="text-[var(--hs-neutral-500)]">{{ __('Refund amount') }}</span>
                                                    <span class="text-[var(--hs-neutral-900)] font-medium tabular-nums">{{ $order->currency }} {{ number_format((float) $dispute->resolution_amount, 2) }}</span>
                                                </div>
                                            @endif
                                            @if ($dispute->resolution_notes)
                                                <p class="text-[var(--hs-neutral-600)]">{{ $dispute->resolution_notes }}</p>
                                            @endif
                                            @if ($dispute->resolved_at)
                                                <p class="text-[12px] text-[var(--hs-neutral-500)]">
                                                    {{ __('Resolved') }} {{ $dispute->resolved_at->format('M d, Y H:i') }}
                                                    @if ($dispute->resolvedBy)
                                                        · {{ $dispute->resolvedBy->first_name }} {{ $dispute->resolvedBy->last_name }}
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            {{-- SIDEBAR — tinted panel bleeding to the card's edges, separated from the
                 main column by its own left border (matches the header hairline). --}}
            <div class="bg-[var(--hs-neutral-50)] border-t md:border-t-0 md:border-l border-[var(--hs-neutral-100)] px-6 py-6 flex flex-col gap-6">

                {{-- Summary breakdown — Subtotal/Fees up top, Total due below a dashed rule --}}
                @php $refundedAmount = $order->ledgerEntries->where('type', 'refund')->sum('amount'); @endphp
                <div class="flex flex-col gap-1.5">
                    <div class="flex justify-between text-[13px]">
                        <span class="text-[var(--hs-neutral-500)]">{{ __('Subtotal') }}</span>
                        <span class="text-[var(--hs-neutral-900)] font-semibold tabular-nums">{{ number_format((float) $order->items_total, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-[13px]">
                        <span class="text-[var(--hs-neutral-500)]">{{ __('Fees') }}</span>
                        <span class="text-[var(--hs-neutral-900)] font-semibold tabular-nums">{{ number_format((float) ($order->fees_total ?? 0), 2) }}</span>
                    </div>
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

                {{-- Customer block — separated by a solid hairline --}}
                <div class="pt-5 border-t border-[var(--hs-neutral-200)]">
                    <p class="text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold mb-2">{{ __('Customer') }}</p>
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

    {{-- Recall Confirmation Modal --}}
    <x-hs-confirm
        name="confirm-recall-order"
        title="{{ __('Recall Order') }}"
        message="{{ __('Recall this order to draft?') }}"
        variant="warning"
        confirmText="{{ __('Recall') }}"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="recall" wire:loading.attr="disabled">
                {{ __('Recall') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
