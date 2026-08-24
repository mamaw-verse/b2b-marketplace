<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Financial Ledger</h1>
        </div>

        {{-- Summary Totals --}}
        <div class="p-6 grid grid-cols-1 sm:grid-cols-3 gap-4 border-b border-[var(--hs-neutral-100)]">
            <div class="p-4 rounded-lg border border-[var(--hs-neutral-200)]">
                <p class="text-sm text-[var(--hs-neutral-600)]">Total Escrow Held</p>
                <p class="text-2xl font-bold text-[var(--hs-neutral-900)] mt-1">₱{{ number_format((float) $this->totals['escrow_hold'], 2) }}</p>
            </div>
            <div class="p-4 rounded-lg border border-[var(--hs-neutral-200)]">
                <p class="text-sm text-[var(--hs-neutral-600)]">Total Payouts</p>
                <p class="text-2xl font-bold text-[var(--hs-neutral-900)] mt-1">₱{{ number_format((float) $this->totals['payout'], 2) }}</p>
            </div>
            <div class="p-4 rounded-lg border border-[var(--hs-neutral-200)]">
                <p class="text-sm text-[var(--hs-neutral-600)]">Total Refunds</p>
                <p class="text-2xl font-bold text-[var(--hs-neutral-900)] mt-1">₱{{ number_format((float) $this->totals['refund'], 2) }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="px-6 pt-5 flex flex-wrap items-end gap-4">
            <div>
                <flux:select wire:model.live="type" label="Type" size="sm" class="w-40">
                    <flux:select.option value="">All Types</flux:select.option>
                    <flux:select.option value="escrow_hold">Escrow Hold</flux:select.option>
                    <flux:select.option value="payout">Payout</flux:select.option>
                    <flux:select.option value="refund">Refund</flux:select.option>
                </flux:select>
            </div>
            <div>
                <flux:input wire:model.live.debounce.500ms="shopId" label="Shop ID" placeholder="Filter by shop" size="sm" class="w-32" />
            </div>
            <div>
                <flux:input type="date" wire:model.live="dateFrom" label="From" size="sm" />
            </div>
            <div>
                <flux:input type="date" wire:model.live="dateTo" label="To" size="sm" />
            </div>
        </div>

        {{-- Table --}}
        <div class="p-6">
            @if ($this->entries->isEmpty())
                <p class="text-center text-[var(--hs-neutral-500)] py-8">No ledger entries found.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--hs-neutral-100)]">
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">Date</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">Type</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">Order</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">Shop</th>
                                <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)] font-medium">Note</th>
                                <th class="text-right py-3 px-2 text-[var(--hs-neutral-600)] font-medium">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->entries as $entry)
                                <tr class="border-b border-[var(--hs-neutral-50)] hover:bg-[var(--hs-neutral-50)]">
                                    <td class="py-3 px-2 text-[var(--hs-neutral-700)]">
                                        {{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('M d, Y') }}
                                    </td>
                                    <td class="py-3 px-2">
                                        @if ($entry->type === 'escrow_hold')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--hs-info-50)] text-[var(--hs-info-700)]">Escrow</span>
                                        @elseif ($entry->type === 'payout')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--hs-success-50)] text-[var(--hs-success-700)]">Payout</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--hs-warning-50)] text-[var(--hs-warning-700)]">Refund</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-700)]">#{{ $entry->order_id }}</td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-700)]">{{ $entry->shop_name ?? '—' }}</td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-500)]">{{ $entry->note ?? '—' }}</td>
                                    <td class="py-3 px-2 text-right font-medium text-[var(--hs-neutral-900)]">
                                        ₱{{ number_format((float) $entry->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $this->entries->links() }}</div>
            @endif
        </div>
    </div>
</div>
