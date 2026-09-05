<div>
    <x-hs-modal
        name="open-dispute-detail-modal"
        model="show"
        :title="__('Dispute') . ' — ' . $order->order_number"
        size="lg"
    >
        @if ($dispute)
            <div class="space-y-5">
                {{-- Summary row: status + type + opened date --}}
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <x-hs-status-pill :status="$dispute->status" />
                    <span class="inline-flex items-center gap-1.5 text-[13px] text-[var(--hs-neutral-600)]">
                        <flux:icon name="tag" variant="mini" class="size-3.5 text-[var(--hs-neutral-400)]" />
                        {{ str_replace('_', ' ', ucfirst($dispute->type)) }}
                    </span>
                    @if ($dispute->created_at)
                        <span class="inline-flex items-center gap-1.5 text-[13px] text-[var(--hs-neutral-500)]">
                            <flux:icon name="clock" variant="mini" class="size-3.5 text-[var(--hs-neutral-400)]" />
                            {{ __('Opened') }} {{ $dispute->created_at->format('M d, Y') }}
                        </span>
                    @endif
                </div>

                {{-- Reason --}}
                <div>
                    <h4 class="text-[11px] uppercase tracking-[.05em] font-semibold text-[var(--hs-neutral-500)] mb-1.5">{{ __('Reason') }}</h4>
                    <p class="text-sm text-[var(--hs-neutral-800)] leading-relaxed whitespace-pre-line">{{ $dispute->reason }}</p>
                </div>

                {{-- Disputed items --}}
                @if ($dispute->items->isNotEmpty())
                    <div>
                        <h4 class="text-[11px] uppercase tracking-[.05em] font-semibold text-[var(--hs-neutral-500)] mb-2">{{ __('Disputed Items') }}</h4>
                        <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] divide-y divide-[var(--hs-neutral-100)]">
                            @foreach ($dispute->items as $line)
                                <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                                    <p class="min-w-0 truncate text-sm text-[var(--hs-neutral-900)]">
                                        {{ $line->orderItem?->description ?? __('Item removed') }}
                                    </p>
                                    <span class="shrink-0 text-sm tabular-nums text-[var(--hs-neutral-600)]">
                                        {{ (int) $line->quantity }}@if ($line->orderItem) {{ $line->orderItem->unit }}@endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Supporting photos --}}
                @if (count($this->photos))
                    <div>
                        <h4 class="text-[11px] uppercase tracking-[.05em] font-semibold text-[var(--hs-neutral-500)] mb-2">{{ __('Supporting Photos') }}</h4>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                            @foreach ($this->photos as $photo)
                                <a
                                    href="{{ $photo['url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="group relative block aspect-square overflow-hidden rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)]"
                                    title="{{ $photo['filename'] }}"
                                >
                                    @if ($photo['is_image'])
                                        <img src="{{ $photo['url'] }}" alt="{{ $photo['filename'] }}" class="h-full w-full object-cover transition group-hover:scale-105" />
                                    @else
                                        <span class="flex h-full w-full flex-col items-center justify-center gap-1 p-2 text-center">
                                            <flux:icon name="document" variant="mini" class="size-6 text-[var(--hs-neutral-400)]" />
                                            <span class="text-[10px] text-[var(--hs-neutral-500)] truncate w-full">{{ $photo['filename'] }}</span>
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Resolution (if resolved) --}}
                @if ($dispute->resolution_type)
                    <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] p-4">
                        <h4 class="text-[11px] uppercase tracking-[.05em] font-semibold text-[var(--hs-neutral-500)] mb-2">{{ __('Resolution') }}</h4>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[var(--hs-neutral-700)]">{{ str_replace('_', ' ', ucfirst($dispute->resolution_type)) }}</span>
                            @if ($dispute->resolution_amount)
                                <span class="font-semibold text-[var(--hs-neutral-900)] tabular-nums">PHP {{ number_format((float) $dispute->resolution_amount, 2) }}</span>
                            @endif
                        </div>
                        @if ($dispute->resolution_notes)
                            <p class="mt-1.5 text-sm text-[var(--hs-neutral-600)] leading-relaxed">{{ $dispute->resolution_notes }}</p>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        <x-slot name="footer">
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Close') }}</flux:button>
                </flux:modal.close>

                @if ($dispute && ! in_array($dispute->status, ['resolved_refunded', 'resolved_denied', 'withdrawn']))
                    @if ($dispute->status !== 'escalated')
                        <flux:modal.trigger name="confirm-escalate-dispute">
                            <flux:button size="sm">{{ __('Escalate to Admin') }}</flux:button>
                        </flux:modal.trigger>
                    @endif
                    <flux:modal.trigger name="confirm-withdraw-dispute">
                        <flux:button size="sm" variant="danger">{{ __('Withdraw') }}</flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        </x-slot>
    </x-hs-modal>

    <x-hs-confirm
        name="confirm-escalate-dispute"
        title="{{ __('Escalate to Admin') }}"
        message="{{ __('Escalate to admin? This cannot be undone.') }}"
        variant="warning"
        confirmText="{{ __('Escalate') }}"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="escalate" wire:loading.attr="disabled">
                {{ __('Escalate') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    <x-hs-confirm
        name="confirm-withdraw-dispute"
        title="{{ __('Withdraw Dispute') }}"
        message="{{ __('Withdraw this dispute?') }}"
        variant="danger"
        confirmText="{{ __('Withdraw') }}"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="withdraw" wire:loading.attr="disabled">
                {{ __('Withdraw') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
