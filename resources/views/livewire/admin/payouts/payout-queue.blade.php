<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Payout Queue') }}</h1>
            <flux:button size="sm" href="{{ route('admin.payouts.eligible') }}" wire:navigate>{{ __('View Eligible') }}</flux:button>
        </div>

        <div class="px-6 py-3 border-b border-[var(--hs-neutral-100)]">
            <flux:select wire:model.live="statusFilter" size="sm" class="w-44">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                <flux:select.option value="processing">{{ __('Processing') }}</flux:select.option>
                <flux:select.option value="completed">{{ __('Completed') }}</flux:select.option>
                <flux:select.option value="failed">{{ __('Failed') }}</flux:select.option>
            </flux:select>
        </div>

        <div class="p-6">
            @if ($this->payouts->isEmpty())
                <p class="text-center text-[var(--hs-neutral-500)] py-8">{{ __('No payouts found.') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-[var(--hs-neutral-100)]">
                        <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)]">{{ __('ID') }}</th>
                        <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)]">{{ __('Shop') }}</th>
                        <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)]">{{ __('Status') }}</th>
                        <th class="text-right py-3 px-2 text-[var(--hs-neutral-600)]">{{ __('Net Amount') }}</th>
                        <th class="text-center py-3 px-2 text-[var(--hs-neutral-600)]">{{ __('Orders') }}</th>
                        <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)]">{{ __('Created') }}</th>
                        <th class="py-3 px-2"></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($this->payouts as $payout)
                            <tr class="border-b border-[var(--hs-neutral-50)]">
                                <td class="py-3 px-2 font-medium text-[var(--hs-neutral-900)]">#{{ $payout->id }}</td>
                                <td class="py-3 px-2 text-[var(--hs-neutral-700)]">{{ $payout->shop->storefront_name }}</td>
                                <td class="py-3 px-2">
                                    <x-hs-status-pill :status="$payout->status" />
                                </td>
                                <td class="py-3 px-2 text-right font-mono text-[var(--hs-neutral-900)]">₱{{ number_format((float) $payout->net_amount, 2) }}</td>
                                <td class="py-3 px-2 text-center text-[var(--hs-neutral-700)]">{{ $payout->orders_count }}</td>
                                <td class="py-3 px-2 text-[var(--hs-neutral-500)]">{{ $payout->created_at->diffForHumans() }}</td>
                                <td class="py-3 px-2 text-right">
                                    <flux:button size="sm" href="{{ route('admin.payouts.show', $payout) }}" wire:navigate>{{ __('View') }}</flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-4">{{ $this->payouts->links() }}</div>
            @endif
        </div>
    </div>
</div>
