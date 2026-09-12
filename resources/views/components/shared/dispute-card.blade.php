@props(['dispute'])

<div class="w-full max-w-sm rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-3 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
        <div class="flex size-7 items-center justify-center rounded-lg bg-[var(--hs-red-50)]">
            <svg class="size-3.5 text-[var(--hs-red-700)]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--hs-red-700)]">{{ __('Dispute') }}</p>
        </div>
        <x-hs-status-pill :status="$dispute->status" />
    </div>
    <p class="text-[11px] text-[var(--hs-neutral-600)]">
        <span class="font-medium">{{ str_replace('_', ' ', ucfirst($dispute->type)) }}</span>
    </p>
    <p class="mt-1 text-[11px] text-[var(--hs-neutral-700)] line-clamp-2">{{ $dispute->reason }}</p>
    @if ($dispute->resolution_type)
        <div class="mt-2 rounded-md bg-[var(--hs-neutral-50)] px-2 py-1">
            <p class="text-[10px] text-[var(--hs-neutral-600)]">
                {{ __('Resolution') }}: <span class="font-medium">{{ str_replace('_', ' ', $dispute->resolution_type) }}</span>
                @if ($dispute->resolution_amount)
                    — PHP {{ number_format((float) $dispute->resolution_amount, 2) }}
                @endif
            </p>
        </div>
    @endif
    <p class="mt-2 text-[9px] text-[var(--hs-neutral-400)]">{{ $dispute->created_at->format('M d, Y · g:i A') }}</p>
</div>
