<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Platform Analytics</h1>
            <div class="flex items-center gap-3">
                <flux:input type="date" wire:model.live="from" label="From" size="sm" />
                <flux:input type="date" wire:model.live="to" label="To" size="sm" />
            </div>
        </div>

        {{-- Revenue Metrics --}}
        <div class="p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)] mb-4">Revenue</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-4 rounded-lg bg-[var(--hs-neutral-50)]">
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)] tabular-nums">PHP {{ number_format((float) ($metrics['gmv'] ?? 0), 2) }}</p>
                    <p class="text-sm text-[var(--hs-neutral-600)] mt-1">GMV</p>
                </div>
                <div class="p-4 rounded-lg bg-[var(--hs-neutral-50)]">
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)] tabular-nums">{{ $metrics['total_orders'] ?? 0 }}</p>
                    <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Total Orders</p>
                </div>
                <div class="p-4 rounded-lg bg-[var(--hs-neutral-50)]">
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)] tabular-nums">PHP {{ number_format((float) ($metrics['total_payouts_disbursed'] ?? 0), 2) }}</p>
                    <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Payouts Disbursed</p>
                </div>
                <div class="p-4 rounded-lg bg-[var(--hs-neutral-50)]">
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)] tabular-nums">{{ $metrics['total_active_sellers'] ?? 0 }}</p>
                    <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Active Sellers</p>
                </div>
            </div>
        </div>

        {{-- Fees & Ratings --}}
        <div class="px-6 pb-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)] mb-4">Fees & Ratings</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-lg bg-[var(--hs-neutral-50)]">
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)] tabular-nums">PHP {{ number_format((float) ($metrics['total_transaction_fees_collected'] ?? 0), 2) }}</p>
                    <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Transaction Fees Collected</p>
                </div>
                <div class="p-4 rounded-lg bg-[var(--hs-neutral-50)]">
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)] tabular-nums">PHP {{ number_format((float) ($metrics['total_commission_fees_collected'] ?? 0), 2) }}</p>
                    <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Commission Fees Collected</p>
                </div>
                <div class="p-4 rounded-lg bg-[var(--hs-neutral-50)]">
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)] tabular-nums">
                        @if (($metrics['platform_average_rating'] ?? 0) > 0)
                            {{ number_format((float) $metrics['platform_average_rating'], 2) }} <span class="text-base font-normal text-[var(--hs-neutral-500)]">/ 5.00</span>
                        @else
                            —
                        @endif
                    </p>
                    <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Platform Average Rating</p>
                </div>
            </div>
        </div>
    </div>
</div>
