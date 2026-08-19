@props(['rfq'])

<div class="w-full max-w-sm rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-3 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
        <div class="flex size-7 items-center justify-center rounded-lg bg-[var(--hs-green-50)]">
            <svg class="size-3.5 text-[var(--hs-green-700)]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--hs-green-700)]">{{ __('RFQ') }}</p>
        </div>
        <x-hs-status-pill :status="$rfq->status" />
    </div>
    <p class="text-xs font-semibold text-[var(--hs-neutral-900)] truncate">{{ $rfq->title }}</p>
    @if ($rfq->description)
        <p class="mt-1 text-[11px] text-[var(--hs-neutral-600)] line-clamp-2">{{ $rfq->description }}</p>
    @endif
    <p class="mt-2 text-[9px] text-[var(--hs-neutral-400)]">{{ $rfq->created_at->format('M d, Y · H:i') }}</p>
</div>
