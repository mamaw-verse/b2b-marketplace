<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Earnings') }}</h1>
        </div>

        {{-- Summary Cards --}}
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ([
                ['label' => __('Total Earned'), 'value' => $this->summary['total_earned'], 'color' => 'green'],
                ['label' => __('Pending Balance'), 'value' => $this->summary['pending_balance'], 'color' => 'warning'],
                ['label' => __('Total Released'), 'value' => $this->summary['total_released'], 'color' => 'blue'],
                ['label' => __('Total Fees Paid'), 'value' => $this->summary['total_fees_paid'], 'color' => 'neutral'],
            ] as $card)
                <div class="p-4 rounded-lg border border-[var(--hs-neutral-200)]">
                    <p class="text-sm text-[var(--hs-neutral-600)]">{{ $card['label'] }}</p>
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)] mt-1">₱{{ number_format((float) $card['value'], 2) }}</p>
                </div>
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="px-6 flex flex-wrap items-end gap-4 border-t border-[var(--hs-neutral-100)] pt-5">
            <div>
                <flux:input type="date" wire:model.live="dateFrom" label="{{ __('From') }}" size="sm" />
            </div>
            <div>
                <flux:input type="date" wire:model.live="dateTo" label="{{ __('To') }}" size="sm" />
            </div>
        </div>

        {{-- Transaction List --}}
        <div class="p-6">
            @if ($this->transactions->isEmpty())
                <p class="text-center text-[var(--hs-neutral-500)] py-8">{{ __('No transactions yet.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--hs-neutral-100)]">
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Date') }}</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Type') }}</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Order') }}</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Note') }}</th>
                                <th class="text-right py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->transactions as $entry)
                                <tr class="border-b border-[var(--hs-neutral-50)] hover:bg-[var(--hs-neutral-50)]">
                                    <td class="py-3 px-2 text-[var(--hs-neutral-700)]">
                                        {{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('M d, Y') }}
                                    </td>
                                    <td class="py-3 px-2">
                                        @if ($entry->type === 'escrow_hold')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--hs-info-50)] text-[var(--hs-info-700)]">{{ __('Escrow') }}</span>
                                        @elseif ($entry->type === 'payout')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--hs-success-50)] text-[var(--hs-success-700)]">{{ __('Payout') }}</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--hs-warning-50)] text-[var(--hs-warning-700)]">{{ __('Refund') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-700)]">#{{ $entry->order_id }}</td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-500)]">{{ $entry->note ?? '—' }}</td>
                                    <td class="py-3 px-2 text-right font-medium text-[var(--hs-neutral-900)]">
                                        ₱{{ number_format((float) $entry->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $this->transactions->links() }}</div>
            @endif
        </div>
    </div>
</div>
