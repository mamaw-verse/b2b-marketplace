<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('My Requests for Quotation') }}</h1>
        </div>

        <div class="p-6">
            @if ($rfqs->isEmpty())
                <x-hs-empty-state
                    icon="document-magnifying-glass"
                    title="{{ __('No requests for quotation yet') }}"
                    description="{{ __('Browse products and send a Request for Quotation to get started.') }}"
                />
            @else
                <div class="space-y-3">
                    @foreach ($rfqs as $rfq)
                        <a href="{{ route('account.rfqs.show', $rfq) }}" wire:navigate
                           class="group flex items-center gap-3 p-4 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] hover:border-[var(--hs-green-300)] hover:shadow-[var(--hs-shadow-sm)] transition">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <h3 class="truncate font-medium text-[var(--hs-neutral-900)]">{{ $rfq->title }}</h3>
                                        <span class="shrink-0 text-xs text-[var(--hs-neutral-400)]">{{ $rfq->created_at->diffForHumans() }}</span>
                                    </div>
                                    <x-hs-status-pill :status="$rfq->recipients->first()?->admin_status ?? 'pending'" class="shrink-0" />
                                </div>
                            </div>
                            <flux:icon name="chevron-right" variant="mini" class="shrink-0 text-[var(--hs-neutral-300)] group-hover:text-[var(--hs-neutral-400)] transition" />
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>