@props(['quotation'])

<div class="w-full max-w-sm rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-3 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
        <div class="flex size-7 items-center justify-center rounded-lg bg-[var(--hs-green-50)]">
            <svg class="size-3.5 text-[var(--hs-green-700)]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--hs-green-700)]">{{ __('Quotation') }}</p>
        </div>
        <x-hs-status-pill :status="$quotation->status" />
    </div>
    <div class="flex items-baseline justify-between">
        <p class="text-sm font-bold text-[var(--hs-neutral-900)]">{{ $quotation->currency }} {{ number_format((float) $quotation->total_amount, 2) }}</p>
        <span class="text-[10px] text-[var(--hs-neutral-500)]">{{ $quotation->items()->count() }} {{ Str::plural('item', $quotation->items()->count()) }}</span>
    </div>
    @if ($quotation->notes)
        <p class="mt-1 text-[11px] text-[var(--hs-neutral-600)] line-clamp-2">{{ $quotation->notes }}</p>
    @endif
    @if ($quotation->valid_until)
        <p class="mt-2 text-[9px] text-[var(--hs-neutral-400)]">{{ __('Valid until') }} {{ $quotation->valid_until->format('M d, Y') }}</p>
    @endif
</div>
