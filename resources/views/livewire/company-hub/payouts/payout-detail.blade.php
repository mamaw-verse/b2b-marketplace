<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Payout #:id', ['id' => $payout->id]) }}</h1>
                <p class="text-sm text-[var(--hs-neutral-500)]">{{ $payout->created_at->format('M d, Y h:i A') }}</p>
            </div>
            <x-hs-status-pill :status="$payout->status" />
        </div>

        <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 border-b border-[var(--hs-neutral-100)]">
            <div>
                <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Gross Amount') }}</p>
                <p class="text-base sm:text-lg font-semibold text-[var(--hs-neutral-900)]">₱{{ number_format((float) $payout->gross_amount, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Transaction Fees') }}</p>
                <p class="text-base sm:text-lg font-semibold text-red-600">-₱{{ number_format((float) $payout->total_transaction_fees, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Commission Fees') }}</p>
                <p class="text-base sm:text-lg font-semibold text-red-600">-₱{{ number_format((float) $payout->total_commission_fees, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Net Amount') }}</p>
                <p class="text-base sm:text-lg font-semibold text-green-700">₱{{ number_format((float) $payout->net_amount, 2) }}</p>
            </div>
        </div>

        {{-- Order Breakdown --}}
        <div class="p-6">
            <h2 class="text-sm font-semibold text-[var(--hs-neutral-700)] mb-3">{{ __('Order Breakdown') }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[400px]">
                <thead><tr class="border-b border-[var(--hs-neutral-100)]">
                    <th class="text-left py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Order') }}</th>
                    <th class="text-right py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Escrowed') }}</th>
                    <th class="text-right py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Fees') }}</th>
                    <th class="text-right py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Net') }}</th>
                </tr></thead>
                <tbody>
                    @foreach ($payout->items as $item)
                        <tr class="border-b border-[var(--hs-neutral-50)]">
                            <td class="py-2 px-2 font-medium text-[var(--hs-neutral-900)]">#{{ $item->order_id }}</td>
                            <td class="py-2 px-2 text-right font-mono">₱{{ number_format((float) $item->escrowed_amount, 2) }}</td>
                            <td class="py-2 px-2 text-right font-mono text-red-600">-₱{{ number_format((float) $item->transaction_fee + (float) $item->commission_fee, 2) }}</td>
                            <td class="py-2 px-2 text-right font-mono text-green-700">₱{{ number_format((float) $item->net_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($payout->failure_reason)
            <div class="px-6 pb-6">
                <div class="p-3 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-sm text-[var(--hs-danger-700)]">{{ $payout->failure_reason }}</div>
            </div>
        @endif
    </div>
</div>
