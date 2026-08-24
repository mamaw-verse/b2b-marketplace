<div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] overflow-hidden">
    <div class="px-5 py-4 border-b border-[var(--hs-neutral-100)]">
        <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)]">Fee Calculator</h3>
        <p class="text-xs text-[var(--hs-neutral-500)] mt-0.5">Estimate your net proceeds after platform fees.</p>
    </div>

    <div class="px-5 py-4 space-y-4">
        @if ($actualFees)
            {{-- Actual recorded fees from payout_items --}}
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-[var(--hs-neutral-600)]">Transaction Fee</span>
                    <span class="font-medium text-[var(--hs-neutral-900)] tabular-nums">PHP {{ number_format($actualFees['transaction_fee'], 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-[var(--hs-neutral-600)]">Commission Fee</span>
                    <span class="font-medium text-[var(--hs-neutral-900)] tabular-nums">PHP {{ number_format($actualFees['commission_fee'], 2) }}</span>
                </div>
                <div class="pt-2 border-t border-[var(--hs-neutral-100)] flex justify-between text-sm">
                    <span class="font-semibold text-[var(--hs-neutral-900)]">Net Amount</span>
                    <span class="font-bold text-[var(--hs-green-700)] tabular-nums">PHP {{ number_format($actualFees['net_amount'], 2) }}</span>
                </div>
                <p class="text-xs text-[var(--hs-neutral-500)] italic">Actual fees from payout record.</p>
            </div>
        @else
            {{-- Calculation input --}}
            <flux:input
                wire:model.live.debounce.300ms="sale_price"
                type="number"
                label="Sale Price (PHP)"
                placeholder="0.00"
                step="0.01"
                min="0"
            />

            {{-- Results --}}
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-[var(--hs-neutral-600)]">Transaction Fee (flat)</span>
                    <span class="font-medium text-[var(--hs-neutral-900)] tabular-nums">
                        @if ($this->calculatedNet !== null)
                            PHP {{ number_format($this->transactionFeeAmount, 2) }}
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-[var(--hs-neutral-600)]">Commission Fee ({{ number_format($this->commissionFeeRate * 100, 1) }}%)</span>
                    <span class="font-medium text-[var(--hs-neutral-900)] tabular-nums">
                        @if ($this->calculatedCommissionFee !== null)
                            PHP {{ number_format($this->calculatedCommissionFee, 2) }}
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="pt-2 border-t border-[var(--hs-neutral-100)] flex justify-between text-sm">
                    <span class="font-semibold text-[var(--hs-neutral-900)]">Net Amount</span>
                    <span class="font-bold text-[var(--hs-green-700)] tabular-nums">
                        @if ($this->calculatedNet !== null)
                            PHP {{ number_format($this->calculatedNet, 2) }}
                        @else
                            —
                        @endif
                    </span>
                </div>
            </div>

            @if ($prefillAmount && ! $actualFees)
                <p class="text-xs text-[var(--hs-neutral-500)] italic">Projection based on current fee rates.</p>
            @endif
        @endif

        {{-- Fee rates transparency --}}
        <div class="pt-3 border-t border-[var(--hs-neutral-100)]">
            <p class="text-xs text-[var(--hs-neutral-500)]">
                Current rates: PHP {{ number_format($this->transactionFeeAmount, 2) }} flat fee + {{ number_format($this->commissionFeeRate * 100, 1) }}% commission.
            </p>
        </div>
    </div>
</div>
