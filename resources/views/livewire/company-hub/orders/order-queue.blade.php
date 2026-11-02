<div>
    {{-- Orders list --}}
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] mb-6">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Orders') }}</h1>
        </div>

        <div class="px-4 sm:px-6 pt-5">
            <div class="flex gap-1.5 bg-[var(--hs-neutral-100)] rounded-[var(--hs-radius-pill)] p-1 w-full sm:w-fit overflow-x-auto scrollbar-hide">
                @foreach (['all' => __('All'), 'draft' => __('Draft'), 'pending_payment' => __('Pending Payment'), 'to_fulfil' => __('To Fulfil'), 'disputed' => __('Disputed')] as $key => $label)
                    <button
                        wire:click="$set('tab', '{{ $key }}')"
                        @class([
                            'shrink-0 px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition flex items-center gap-2',
                            'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm' => $tab === $key,
                            'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)]' => $tab !== $key,
                        ])
                    >
                        {{ $label }}
                        @if ($this->tabCounts[$key] > 0)
                            <flux:badge size="sm" color="zinc">{{ $this->tabCounts[$key] }}</flux:badge>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <div class="p-4 sm:p-6">
            @if ($this->orders->isEmpty())
                <p class="text-center text-[var(--hs-neutral-500)] py-8">{{ __('No orders found.') }}</p>
            @else
                {{-- Mobile card list (hidden on sm+) --}}
                <div class="sm:hidden space-y-3">
                    @foreach ($this->orders as $order)
                        <div class="rounded-lg border border-[var(--hs-neutral-200)] overflow-hidden">
                            <a href="{{ route('hub.orders.show', $order) }}" wire:navigate class="flex items-start justify-between gap-3 p-4 hover:bg-[var(--hs-neutral-50)] transition">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-[var(--hs-neutral-900)]">{{ $order->order_number }}</p>
                                    <p class="text-sm text-[var(--hs-neutral-500)] mt-0.5">{{ $order->customer_business_name ?? $order->buyer?->first_name }}</p>
                                    <p class="text-xs text-[var(--hs-neutral-400)] mt-1">{{ $order->created_at->format('M d, Y') }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="font-semibold text-[var(--hs-neutral-900)]">{{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</p>
                                    <div class="mt-1"><x-hs-status-pill :status="$order->status" /></div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop table (hidden on mobile) --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--hs-neutral-100)]">
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Order') }}</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Customer') }}</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Total') }}</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Status') }}</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Date') }}</th>
                                <th class="py-3 px-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->orders as $order)
                                <tr class="border-b border-[var(--hs-neutral-50)] hover:bg-[var(--hs-neutral-50)]">
                                    <td class="py-3 px-2 font-medium text-[var(--hs-neutral-900)]">{{ $order->order_number }}</td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-700)]">{{ $order->customer_business_name ?? $order->buyer?->first_name }}</td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-900)] font-medium">{{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</td>
                                    <td class="py-3 px-2"><x-hs-status-pill :status="$order->status" /></td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-500)]">{{ $order->created_at->format('M d, Y') }}</td>
                                    <td class="py-3 px-2 text-right">
                                        <flux:button size="sm" variant="ghost" href="{{ route('hub.orders.show', $order) }}" wire:navigate>
                                            {{ __('View') }}
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
    {{-- Awarded Quotations awaiting order creation --}}
    @if ($this->awardedQuotations->isNotEmpty())
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
            <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)] flex items-center gap-2">
                <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Awarded Quotations') }}</h2>
                <flux:badge size="sm" color="lime">{{ $this->awardedQuotations->count() }}</flux:badge>
            </div>
            <div class="p-4 sm:p-4">
                <p class="text-sm text-[var(--hs-neutral-500)] mb-3">{{ __('These quotations have been awarded by the buyer. Create an order to proceed.') }}</p>

                {{-- Mobile card list (hidden on sm+) --}}
                <div class="sm:hidden space-y-3">
                    @foreach ($this->awardedQuotations as $quotation)
                        <div class="flex items-start justify-between gap-3 rounded-lg border border-[var(--hs-neutral-200)] p-3.5">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-[var(--hs-neutral-900)] truncate">{{ Str::limit($quotation->rfq->title ?? '—', 40) }}</p>
                                <p class="text-xs text-[var(--hs-neutral-500)] mt-0.5">{{ $quotation->updated_at->diffForHumans() }}</p>
                                <p class="text-sm font-semibold text-[var(--hs-neutral-900)] mt-1">{{ $quotation->currency }} {{ number_format((float) $quotation->total_amount, 2) }}</p>
                            </div>
                            <flux:button
                                size="sm"
                                variant="primary"
                                icon="plus"
                                href="{{ route('hub.orders.create', ['quotationId' => $quotation->id]) }}"
                                wire:navigate
                            >
                                {{ __('Create Order') }}
                            </flux:button>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop table (hidden on mobile) --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--hs-neutral-100)]">
                                <th class="text-left py-2.5 px-3 text-[var(--hs-neutral-600)] font-medium">{{ __('RFQ') }}</th>
                                <th class="text-right py-2.5 px-3 text-[var(--hs-neutral-600)] font-medium">{{ __('Amount') }}</th>
                                <th class="text-left py-2.5 px-3 text-[var(--hs-neutral-600)] font-medium">{{ __('Awarded') }}</th>
                                <th class="py-2.5 px-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->awardedQuotations as $quotation)
                                <tr class="border-b border-[var(--hs-neutral-50)] hover:bg-[var(--hs-neutral-50)]">
                                    <td class="py-2.5 px-3 text-[var(--hs-neutral-900)]">
                                        {{ Str::limit($quotation->rfq->title ?? '—', 40) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-medium text-[var(--hs-neutral-900)]">
                                        {{ $quotation->currency }} {{ number_format((float) $quotation->total_amount, 2) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-[var(--hs-neutral-500)]">
                                        {{ $quotation->updated_at->diffForHumans() }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        <flux:button
                                            size="sm"
                                            variant="primary"
                                            icon="plus"
                                            href="{{ route('hub.orders.create', ['quotationId' => $quotation->id]) }}"
                                            wire:navigate
                                        >
                                            {{ __('Create Order') }}
                                        </flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
