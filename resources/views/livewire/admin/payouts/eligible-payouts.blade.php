<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Eligible Payouts</h1>
            <flux:button size="sm" href="{{ route('admin.payouts.index') }}" wire:navigate>← Back to Queue</flux:button>
        </div>

        <div class="p-6">
            @session('error')
                <div class="p-3 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-sm text-[var(--hs-danger-700)] mb-4">{{ $value }}</div>
            @endsession

            @if ($this->eligibleSellers->isEmpty())
                <p class="text-center text-[var(--hs-neutral-500)] py-8">No sellers currently eligible for payout.</p>
            @else
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-[var(--hs-neutral-100)]">
                        <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)]">Shop</th>
                        <th class="text-center py-3 px-2 text-[var(--hs-neutral-600)]">Eligible Orders</th>
                        <th class="text-right py-3 px-2 text-[var(--hs-neutral-600)]">Releasable Amount</th>
                        <th class="py-3 px-2"></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($this->eligibleSellers as $seller)
                            <tr class="border-b border-[var(--hs-neutral-50)]">
                                <td class="py-3 px-2 font-medium text-[var(--hs-neutral-900)]">{{ $seller['shop_name'] }}</td>
                                <td class="py-3 px-2 text-center text-[var(--hs-neutral-700)]">{{ $seller['eligible_orders_count'] }}</td>
                                <td class="py-3 px-2 text-right font-mono text-[var(--hs-neutral-900)]">₱{{ number_format($seller['releasable_amount'], 2) }}</td>
                                <td class="py-3 px-2 text-right">
                                    <flux:modal.trigger name="confirm-create-batch-{{ $seller['shop_id'] }}">
                                        <flux:button size="sm">Create Batch</flux:button>
                                    </flux:modal.trigger>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Confirm Modals --}}
    @foreach ($this->eligibleSellers as $seller)
        <x-hs-confirm
            name="confirm-create-batch-{{ $seller['shop_id'] }}"
            title="Create Payout Batch"
            message="Create a payout batch for {{ $seller['shop_name'] }}? This will bundle {{ $seller['eligible_orders_count'] }} eligible order(s) totalling ₱{{ number_format($seller['releasable_amount'], 2) }} (before fees)."
            variant="primary"
            confirmText="Create Batch"
        >
            <x-slot name="action">
                <flux:button variant="primary" wire:click="createBatch({{ $seller['shop_id'] }})" wire:loading.attr="disabled">
                    Create Batch
                </flux:button>
            </x-slot>
        </x-hs-confirm>
    @endforeach
</div>
