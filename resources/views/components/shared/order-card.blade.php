@props(['order'])

<div class="w-full max-w-sm rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-3 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
        <div class="flex size-7 items-center justify-center rounded-lg bg-[var(--hs-blue-50)]">
            <svg class="size-3.5 text-[var(--hs-blue-700)]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--hs-blue-700)]">{{ $order->order_number }}</p>
        </div>
        <x-hs-status-pill :status="$order->status" />
    </div>
    <div class="space-y-1">
        <div class="flex items-center justify-between">
            <span class="text-[11px] text-[var(--hs-neutral-600)]">{{ __('Items') }}</span>
            <span class="text-[11px] font-medium text-[var(--hs-neutral-900)]">{{ $order->items->count() }} {{ __('items') }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-[11px] text-[var(--hs-neutral-600)]">{{ __('Total') }}</span>
            <span class="text-xs font-semibold text-[var(--hs-neutral-900)]">{{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</span>
        </div>
    </div>
    <p class="mt-2 text-[9px] text-[var(--hs-neutral-400)]">{{ $order->created_at->format('M d, Y · g:i A') }}</p>
</div>
