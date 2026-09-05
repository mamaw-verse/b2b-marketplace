<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-[var(--hs-neutral-900)]">Payout Settings</h1>
        <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">Configure platform-wide payout and fee parameters.</p>
    </div>

    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <form wire:submit="save" class="space-y-6 p-6">
            {{-- Dispute Hold Days --}}
            <div>
                <flux:input
                    wire:model.blur="dispute_hold_days"
                    label="Dispute Hold Days"
                    type="number"
                    min="1"
                    max="365"
                    description="Number of days to hold funds after order completion before releasing payout."
                    required
                />
                @error('dispute_hold_days') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Minimum Payout Threshold --}}
            <div>
                <flux:input
                    wire:model.blur="minimum_payout_threshold"
                    label="Minimum Payout Threshold (₱)"
                    type="number"
                    step="0.01"
                    min="0.01"
                    description="Minimum balance required before a payout can be initiated."
                    required
                />
                @error('minimum_payout_threshold') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Transaction Fee Amount --}}
            <div>
                <flux:input
                    wire:model.blur="transaction_fee_amount"
                    label="Transaction Fee Amount (₱)"
                    type="number"
                    step="0.01"
                    min="0"
                    description="Flat fee deducted per payout transaction."
                    required
                />
                @error('transaction_fee_amount') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Commission Fee Rate --}}
            <div>
                <flux:input
                    wire:model.blur="commission_fee_rate"
                    label="Commission Fee Rate"
                    type="number"
                    step="0.001"
                    min="0"
                    max="1"
                    description="Platform commission as a decimal (e.g. 0.03 = 3%)."
                    required
                />
                @error('commission_fee_rate') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Last Updated Info --}}
            @if ($lastUpdatedBy)
                <div class="border-t border-[var(--hs-neutral-100)] pt-4">
                    <p class="text-xs text-[var(--hs-neutral-500)]">
                        Last updated by <span class="font-medium text-[var(--hs-neutral-700)]">{{ $lastUpdatedBy }}</span>
                        {{ $lastUpdatedAt }}
                    </p>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex justify-end gap-3 border-t border-[var(--hs-neutral-100)] pt-4">
                <flux:button variant="primary" type="submit">
                    Save Settings
                </flux:button>
            </div>
        </form>
    </div>
</div>
