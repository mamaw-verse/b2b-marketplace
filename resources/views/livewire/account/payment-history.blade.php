<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Payment History') }}</h1>
        </div>

        {{-- Filters --}}
        <div class="px-6 pt-5 flex flex-wrap items-end gap-4">
            <div>
                <flux:select wire:model.live="status" label="{{ __('Status') }}" size="sm" class="w-32">
                    <flux:select.option value="">{{ __('All') }}</flux:select.option>
                    <flux:select.option value="paid">{{ __('Paid') }}</flux:select.option>
                    <flux:select.option value="refunded">{{ __('Refunded') }}</flux:select.option>
                </flux:select>
            </div>
            <div>
                <flux:input type="date" wire:model.live="dateFrom" label="{{ __('From') }}" size="sm" />
            </div>
            <div>
                <flux:input type="date" wire:model.live="dateTo" label="{{ __('To') }}" size="sm" />
            </div>
        </div>

        <div class="p-6">
            @if ($this->transactions->isEmpty())
                <p class="text-center text-[var(--hs-neutral-500)] py-8">{{ __('No transactions found.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--hs-neutral-100)]">
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Date') }}</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Type') }}</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Order') }}</th>
                                <th class="text-right py-3 px-2 text-[var(--hs-neutral-600)] font-medium">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->transactions as $txn)
                                <tr class="border-b border-[var(--hs-neutral-50)] hover:bg-[var(--hs-neutral-50)]">
                                    <td class="py-3 px-2 text-[var(--hs-neutral-700)]">
                                        {{ \Illuminate\Support\Carbon::parse($txn->date)->format('M d, Y') }}
                                    </td>
                                    <td class="py-3 px-2">
                                        @if ($txn->resource_type === 'payment')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--hs-success-50)] text-[var(--hs-success-700)]">
                                                {{ __('Paid') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--hs-warning-50)] text-[var(--hs-warning-700)]">
                                                {{ __('Refund') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-700)]">#{{ $txn->order_id }}</td>
                                    <td class="py-3 px-2 text-right font-medium text-[var(--hs-neutral-900)]">
                                        ₱{{ number_format((float) $txn->amount, 2) }}
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
