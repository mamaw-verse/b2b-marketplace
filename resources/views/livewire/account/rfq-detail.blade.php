<div>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
            <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ $rfq->title }}</h1>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                            {{ __('Sent') }} {{ $rfq->created_at->diffForHumans() }}
                            @if ($rfq->recipients->first())
                                <span class="text-[var(--hs-neutral-300)]">·</span>
                                {{ __('To') }} <span class="font-medium text-[var(--hs-neutral-700)]">{{ $rfq->recipients->first()->shop->storefront_name }}</span>
                            @endif
                        </p>
                    </div>
                    <x-hs-status-pill :status="$rfq->status" class="shrink-0" />
                </div>
            </div>

            {{-- Seller closed the RFQ: show notice whenever a recipient has a
                 terminal status (closed or no_bid). The buyer-close path sets the
                 parent RFQ to 'closed' too, so we distinguish the two cases:
                 - rfq open + terminal recipient  → seller self-closed
                 - rfq closed + terminal recipient with decline_reason → seller closed first, buyer may have followed
                 Either way, show the seller's reason when one was given. --}}
            @php $closedRecipient = $rfq->recipients->first(fn ($r) => in_array($r->status, ['closed', 'no_bid'])); @endphp
            @if ($closedRecipient && ($rfq->isOpen() || $closedRecipient->decline_reason))
                <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)]">
                    <div class="flex items-start gap-3 rounded-[var(--hs-radius-card)] bg-[var(--hs-warning-50)] border border-[var(--hs-warning-200)] p-3.5">
                        <flux:icon name="x-circle" variant="mini" class="mt-0.5 shrink-0 text-[var(--hs-warning-500)]" />
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-[var(--hs-warning-700)]">
                                {{ __('The seller closed this RFQ.') }}
                            </p>
                            @if ($closedRecipient->decline_reason)
                                <p class="mt-1 text-sm text-[var(--hs-warning-700)]">
                                    <span class="font-medium">{{ __('Reason:') }}</span>
                                    {{ $closedRecipient->decline_reason }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Close RFQ Action: only visible when the RFQ is open and the seller
                 has not already self-closed (any terminal recipient hides the button). --}}
            @if ($rfq->isOpen() && ! $rfq->recipients->contains(fn ($r) => $r->isTerminal()))
                <div class="px-6 py-3 border-b border-[var(--hs-neutral-100)]">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        icon="x-circle"
                        wire:click="$set('showCloseConfirmation', true)"
                        class="text-[var(--hs-danger-600)] hover:bg-[var(--hs-danger-50)] hover:text-[var(--hs-danger-700)]"
                    >
                        {{ __('Close RFQ') }}
                    </flux:button>
                </div>
            @endif

            {{-- Description --}}
            <div class="p-6">
                @if ($rfq->description)
                    <div class="prose prose-sm max-w-none text-[var(--hs-neutral-700)]">
                        {!! nl2br(e($rfq->description)) !!}
                    </div>
                @else
                    <p class="text-sm text-[var(--hs-neutral-500)] italic">{{ __('No description provided.') }}</p>
                @endif
            </div>
        </div>

        {{-- Quotations --}}
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
            <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)] flex items-center gap-2">
                <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Quotations') }}</h2>
                @php $visibleQuotations = $rfq->quotations->where('status', '!=', 'draft'); @endphp
                @if ($visibleQuotations->isNotEmpty())
                    <flux:badge size="sm" color="zinc">{{ $visibleQuotations->count() }}</flux:badge>
                @endif
            </div>
            <div class="p-6">
                @if ($visibleQuotations->isEmpty())
                    <div class="text-center py-8">
                        <flux:icon name="document-text" class="mx-auto size-8 text-[var(--hs-neutral-300)]" />
                        <p class="mt-2 text-sm text-[var(--hs-neutral-500)]">{{ __('No quotations received yet.') }}</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($visibleQuotations as $quotation)
                            <div @class([
                                'flex items-center gap-3 rounded-[var(--hs-radius-card)] border px-4 py-3',
                                'border-[var(--hs-green-200)] bg-[var(--hs-green-50)]' => $quotation->status === 'awarded',
                                'border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)]' => $quotation->status === 'rejected',
                                'border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)]' => $quotation->status === 'withdrawn',
                                'border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]' => in_array($quotation->status, ['submitted', 'not_awarded', 'expired']),
                            ])>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-base font-bold text-[var(--hs-neutral-900)]">
                                            {{ $quotation->currency }} {{ number_format((float) $quotation->total_amount, 2) }}
                                        </span>
                                        <x-hs-status-pill :status="$quotation->status" />
                                    </div>
                                    <p class="mt-0.5 flex items-center gap-1 text-xs text-[var(--hs-neutral-500)]">
                                        {{ $quotation->items->count() }} {{ Str::plural('item', $quotation->items->count()) }}
                                        @if ($quotation->valid_until)
                                            <span class="text-[var(--hs-neutral-300)]">·</span>
                                            {{ __('Valid until') }} {{ $quotation->valid_until->format('M d, Y') }}
                                        @endif
                                    </p>
                                </div>

                                <flux:button
                                    variant="ghost"
                                    size="xs"
                                    icon="eye"
                                    wire:click="viewQuotation({{ $quotation->id }})"
                                    class="shrink-0"
                                >
                                    {{ __('View') }}
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Attachments --}}
        @if ($rfq->attachments->count() > 0)
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Attachments') }}</h2>
                </div>
                <div class="p-3">
                    <ul class="divide-y divide-[var(--hs-neutral-100)]">
                        @foreach ($rfq->attachments as $attachment)
                            <li>
                                <a
                                    href="{{ route('media.view', $attachment) }}"
                                    target="_blank"
                                    class="flex items-center gap-3 rounded-[var(--hs-radius-card)] px-3 py-2.5 text-sm text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-50)] transition"
                                >
                                    <flux:icon name="paper-clip" variant="mini" class="shrink-0 text-[var(--hs-neutral-400)]" />
                                    <span class="truncate">{{ $attachment->original_filename }}</span>
                                    <flux:icon name="arrow-top-right-on-square" variant="mini" class="ml-auto shrink-0 text-[var(--hs-neutral-300)]" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

    {{-- Quotation Detail Modal --}}
    <x-hs-modal
        name="quotation-detail"
        model="showQuotationDetail"
        :title="__('Quotation Detail')"
        size="lg"
    >
        @php $viewingQuotation = $this->viewingQuotation; @endphp
        @if ($viewingQuotation)
            <div class="space-y-5">
                {{-- Summary --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-lg font-bold text-[var(--hs-neutral-900)]">
                            {{ $viewingQuotation->currency }} {{ number_format((float) $viewingQuotation->total_amount, 2) }}
                        </span>
                        <x-hs-status-pill :status="$viewingQuotation->status" />
                    </div>
                    @if ($viewingQuotation->valid_until)
                        <span class="text-xs text-[var(--hs-neutral-500)] shrink-0 whitespace-nowrap">
                            {{ __('Valid until') }} {{ $viewingQuotation->valid_until->format('M d, Y') }}
                        </span>
                    @endif
                </div>

                <p class="text-xs text-[var(--hs-neutral-500)]">
                    {{ $viewingQuotation->items->count() }} {{ Str::plural('item', $viewingQuotation->items->count()) }}
                </p>

                {{-- Line items --}}
                <div class="overflow-hidden rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)]">
                    <table class="w-full text-xs">
                        <thead class="bg-[var(--hs-neutral-50)] text-[var(--hs-neutral-500)]">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium">{{ __('Item') }}</th>
                                <th class="px-3 py-2 text-right font-medium">{{ __('Qty') }}</th>
                                <th class="px-3 py-2 text-left font-medium">{{ __('Unit') }}</th>
                                <th class="px-3 py-2 text-right font-medium">{{ __('Price') }}</th>
                                <th class="px-3 py-2 text-right font-medium">{{ __('Subtotal') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--hs-neutral-100)]">
                            @foreach ($viewingQuotation->items as $item)
                                <tr class="text-[var(--hs-neutral-700)]">
                                    <td class="px-3 py-2">{{ $item->description }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $item->quantity + 0 }}</td>
                                    <td class="px-3 py-2">{{ $item->unit }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="px-3 py-2 text-right font-medium tabular-nums text-[var(--hs-neutral-900)]">{{ number_format((float) $item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)]">
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-right font-medium text-[var(--hs-neutral-600)]">{{ __('Total') }}</td>
                                <td class="px-3 py-2 text-right font-bold tabular-nums text-[var(--hs-neutral-900)]">
                                    {{ $viewingQuotation->currency }} {{ number_format((float) $viewingQuotation->total_amount, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Notes --}}
                @if ($viewingQuotation->notes)
                    <div class="text-sm text-[var(--hs-neutral-700)]">
                        <strong>{{ __('Notes:') }}</strong>
                        <p class="mt-1 whitespace-pre-wrap">{{ $viewingQuotation->notes }}</p>
                    </div>
                @endif
            </div>
        @endif

        <x-slot name="footer">
            <div class="flex items-center justify-between gap-3">
                <flux:button variant="ghost" wire:click="$set('showQuotationDetail', false)">
                    {{ __('Close') }}
                </flux:button>

                @if ($viewingQuotation && $viewingQuotation->isSubmitted() && $rfq->isOpen())
                    <div class="flex items-center gap-2">
                        <flux:button
                            variant="ghost"
                            icon="x-mark"
                            wire:click="confirmRejectQuotation({{ $viewingQuotation->id }})"
                            class="text-[var(--hs-danger-600)] hover:bg-[var(--hs-danger-50)]"
                        >
                            {{ __('Reject') }}
                        </flux:button>
                        <flux:button variant="primary" icon="check" wire:click="confirmAwardQuotation({{ $viewingQuotation->id }})">
                            {{ __('Award') }}
                        </flux:button>
                    </div>
                @endif
            </div>
        </x-slot>
    </x-hs-modal>

    {{-- Close RFQ Confirmation Modal --}}
    <flux:modal name="close-rfq" wire:model="showCloseConfirmation">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Close RFQ') }}</flux:heading>
            <p class="text-sm text-[var(--hs-neutral-600)]">
                {{ __('Closing this RFQ will mark any remaining submitted quotations as "not awarded". Awarded and rejected quotations will not be affected.') }}
            </p>
            <div class="flex items-center gap-3">
                <flux:button variant="danger" wire:click="closeRfq">
                    {{ __('Close RFQ') }}
                </flux:button>
                <flux:button variant="ghost" wire:click="$set('showCloseConfirmation', false)">
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Reject Quotation Confirmation --}}
    <x-hs-confirm
        name="confirm-reject-quotation"
        title="Reject Quotation"
        message="Reject this quotation? This cannot be undone."
        variant="danger"
        confirmText="Reject"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="rejectQuotation" wire:loading.attr="disabled">
                {{ __('Reject') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    {{-- Award Quotation Confirmation --}}
    <x-hs-confirm
        name="confirm-award-quotation"
        title="Award Quotation"
        message="Award this quotation? The seller will be notified and an order will be created."
        variant="primary"
        confirmText="Award"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="awardQuotation" wire:loading.attr="disabled">
                {{ __('Award') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>