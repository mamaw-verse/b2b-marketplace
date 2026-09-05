<div class="p-4 rounded-lg border border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)]">
    <div class="flex items-center justify-between gap-2 mb-3">
        <h3 class="text-sm font-semibold text-[var(--hs-danger-700)]">{{ __('Active Dispute') }}</h3>
        @if ($dispute)
            <x-hs-status-pill :status="$dispute->status" />
        @endif
    </div>

    @if ($dispute)
        <div class="space-y-4">
            {{-- Type + who opened it --}}
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                <span class="inline-flex items-center gap-1.5 rounded-[var(--hs-radius-pill)] bg-[var(--hs-neutral-0)] border border-[var(--hs-neutral-200)] px-2.5 py-1 text-xs font-medium text-[var(--hs-neutral-700)]">
                    <flux:icon name="{{ $dispute->type === 'return_refund' ? 'arrow-uturn-left' : 'banknotes' }}" variant="mini" class="size-3.5 text-[var(--hs-neutral-500)]" />
                    {{ $dispute->type === 'return_refund' ? __('Return & Refund') : __('Refund Only') }}
                </span>
                @if ($dispute->openedBy)
                    <span class="text-xs text-[var(--hs-neutral-500)]">
                        {{ __('Opened by') }} {{ $dispute->openedBy->first_name }} {{ $dispute->openedBy->last_name }}
                        @if ($dispute->created_at) · {{ $dispute->created_at->format('M d, Y') }} @endif
                    </span>
                @endif
            </div>

            {{-- Reason --}}
            <div>
                <h4 class="text-[11px] uppercase tracking-[.05em] font-semibold text-[var(--hs-neutral-500)] mb-1">{{ __('Reason') }}</h4>
                <p class="text-sm text-[var(--hs-neutral-800)] leading-relaxed whitespace-pre-line">{{ $dispute->reason }}</p>
            </div>

            {{-- Disputed items — what the buyer wants to return/refund and how many --}}
            @if ($dispute->items->isNotEmpty())
                <div>
                    <h4 class="text-[11px] uppercase tracking-[.05em] font-semibold text-[var(--hs-neutral-500)] mb-1.5">
                        {{ $dispute->type === 'return_refund' ? __('Items to Return') : __('Items Disputed') }}
                    </h4>
                    <div class="rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] divide-y divide-[var(--hs-neutral-100)]">
                        @foreach ($dispute->items as $line)
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
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

            {{-- Supporting photos from the buyer --}}
            @if (count($this->photos))
                <div>
                    <h4 class="text-[11px] uppercase tracking-[.05em] font-semibold text-[var(--hs-neutral-500)] mb-1.5">{{ __('Buyer Photos') }}</h4>
                    <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                        @foreach ($this->photos as $photo)
                            <a
                                href="{{ $photo['url'] }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="group relative block aspect-square overflow-hidden rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]"
                                title="{{ $photo['filename'] }}"
                            >
                                @if ($photo['is_image'])
                                    <img src="{{ $photo['url'] }}" alt="{{ $photo['filename'] }}" class="h-full w-full object-cover transition group-hover:scale-105" />
                                @else
                                    <span class="flex h-full w-full flex-col items-center justify-center gap-1 p-1.5 text-center">
                                        <flux:icon name="document" variant="mini" class="size-5 text-[var(--hs-neutral-400)]" />
                                        <span class="text-[9px] text-[var(--hs-neutral-500)] truncate w-full">{{ $photo['filename'] }}</span>
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Return handling: confirm the goods came back (return_refund only) --}}
            @if ($dispute->type === 'return_refund' && in_array($dispute->status, ['open', 'awaiting_return']))
                <div class="rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-3">
                    <p class="text-xs text-[var(--hs-neutral-600)] mb-2">{{ __('Once the returned goods arrive, confirm receipt to unlock refund options. If the claim is invalid you can deny it below without accepting a return.') }}</p>
                    <flux:modal.trigger name="confirm-return">
                        <flux:button size="sm">{{ __('Confirm Return Received') }}</flux:button>
                    </flux:modal.trigger>
                </div>
            @endif

            {{-- Resolution controls.
                 - refund_only (open): full/partial/deny all available.
                 - return_refund (return_received): full/partial/deny all available.
                 - return_refund (open/awaiting_return): DENY ONLY — a refund needs the
                   return confirmed first, but a bogus claim can be denied immediately. --}}
            @php
                $canFullyResolve = ($dispute->type === 'refund_only' && $dispute->status === 'open')
                    || ($dispute->type === 'return_refund' && $dispute->status === 'return_received');
                $canDenyOnly = $dispute->type === 'return_refund' && in_array($dispute->status, ['open', 'awaiting_return']);
            @endphp

            @if ($canFullyResolve || $canDenyOnly)
                <div class="border-t border-[var(--hs-neutral-200)] pt-3 space-y-2">
                    <h4 class="text-[11px] uppercase tracking-[.05em] font-semibold text-[var(--hs-neutral-500)]">{{ __('Resolve Dispute') }}</h4>

                    @if ($canFullyResolve)
                        <flux:select wire:model.live="resolutionType" :label="__('Resolution')">
                            <flux:select.option value="full_refund">{{ __('Full Refund') }}</flux:select.option>
                            <flux:select.option value="partial_refund">{{ __('Partial Refund') }}</flux:select.option>
                            <flux:select.option value="denied">{{ __('Deny') }}</flux:select.option>
                        </flux:select>

                        @if ($resolutionType === 'partial_refund')
                            <div>
                                <flux:input
                                    wire:model="resolutionAmount"
                                    :label="__('Refund Amount').' *'"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max="{{ (float) $order->order_total }}"
                                    required
                                />
                                <p class="mt-1 text-xs text-[var(--hs-neutral-500)]">{{ __('Max:') }} {{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</p>
                                @error('resolutionAmount') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    @else
                        {{-- Deny-only path: resolutionType is forced to 'denied' in mount(). --}}
                        <p class="text-xs text-[var(--hs-neutral-600)]">{{ __('You can deny this claim now, or confirm the return above to enable a refund.') }}</p>
                    @endif

                    <flux:input wire:model="resolutionNotes" :label="__('Notes (optional)')" :placeholder="__('Explain your decision to the buyer')" />

                    <flux:modal.trigger name="confirm-resolve-dispute">
                        <flux:button size="sm" variant="primary">
                            {{ $canDenyOnly ? __('Deny Dispute') : __('Resolve') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            @endif

            {{-- Escalate to admin (available for both parties on non-terminal disputes) --}}
            @if (! in_array($dispute->status, ['escalated', 'resolved_refunded', 'resolved_denied', 'withdrawn']))
                <div class="border-t border-[var(--hs-neutral-200)] pt-3">
                    <flux:modal.trigger name="confirm-escalate-dispute">
                        <flux:button size="sm" variant="ghost">{{ __('Escalate to Admin') }}</flux:button>
                    </flux:modal.trigger>
                </div>
            @endif
        </div>
    @else
        <p class="text-sm text-[var(--hs-neutral-500)]">{{ __('No active dispute.') }}</p>
    @endif

    <x-hs-confirm
        name="confirm-return"
        title="{{ __('Confirm Return') }}"
        message="{{ __('Confirm you received the returned goods? This unlocks refund options and cannot be undone.') }}"
        variant="primary"
        confirmText="{{ __('Confirm Receipt') }}"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="confirmReturn" wire:loading.attr="disabled">
                {{ __('Confirm Receipt') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    <x-hs-confirm
        name="confirm-resolve-dispute"
        title="{{ __('Resolve Dispute') }}"
        message="{{ __('A full or partial refund moves money back to the buyer and marks the order refunded. Denying keeps the order completed with no refund. This cannot be undone.') }}"
        variant="warning"
        confirmText="{{ __('Confirm Resolution') }}"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="resolve" wire:loading.attr="disabled">
                {{ __('Confirm Resolution') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    <x-hs-confirm
        name="confirm-escalate-dispute"
        title="{{ __('Escalate to Admin') }}"
        message="{{ __('Escalate this dispute to admin? This cannot be undone.') }}"
        variant="warning"
        confirmText="{{ __('Escalate') }}"
    >
        <x-slot name="action">
            <flux:button variant="filled" wire:click="escalate" wire:loading.attr="disabled">
                {{ __('Escalate') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
