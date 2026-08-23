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

            {{-- Close RFQ Action --}}
            @if ($rfq->isOpen())
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
                                'rounded-[var(--hs-radius-card)] border p-4',
                                'border-[var(--hs-success-200)] bg-[var(--hs-success-50)]' => $quotation->status === 'awarded',
                                'border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)]' => $quotation->status === 'rejected',
                                'border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)]' => $quotation->status === 'withdrawn',
                                'border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]' => in_array($quotation->status, ['submitted', 'not_awarded', 'expired']),
                            ])>
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-base font-bold text-[var(--hs-neutral-900)]">
                                            {{ $quotation->currency }} {{ number_format((float) $quotation->total_amount, 2) }}
                                        </span>
                                        <x-hs-status-pill :status="$quotation->status" />
                                    </div>
                                    @if ($quotation->valid_until)
                                        <span class="text-xs text-[var(--hs-neutral-500)] shrink-0 whitespace-nowrap">
                                            {{ __('Valid until') }} {{ $quotation->valid_until->format('M d, Y') }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Line items --}}
                                <div class="overflow-x-auto rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)]">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-left text-[var(--hs-neutral-500)] bg-[var(--hs-neutral-50)]">
                                                <th class="px-3 py-2 font-medium">{{ __('Item') }}</th>
                                                <th class="px-3 py-2 font-medium text-right">{{ __('Qty') }}</th>
                                                <th class="px-3 py-2 font-medium">{{ __('Unit') }}</th>
                                                <th class="px-3 py-2 font-medium text-right">{{ __('Price') }}</th>
                                                <th class="px-3 py-2 font-medium text-right">{{ __('Subtotal') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-[var(--hs-neutral-700)] divide-y divide-[var(--hs-neutral-100)]">
                                            @foreach ($quotation->items as $item)
                                                <tr>
                                                    <td class="px-3 py-1.5">{{ $item->description }}</td>
                                                    <td class="px-3 py-1.5 text-right">{{ $item->quantity }}</td>
                                                    <td class="px-3 py-1.5">{{ $item->unit }}</td>
                                                    <td class="px-3 py-1.5 text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                                                    <td class="px-3 py-1.5 text-right font-medium">{{ number_format((float) $item->subtotal, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if ($quotation->notes)
                                    <p class="mt-2 text-xs text-[var(--hs-neutral-500)]">{{ $quotation->notes }}</p>
                                @endif

                                {{-- Actions for submitted quotations --}}
                                @if ($quotation->isSubmitted() && $rfq->isOpen())
                                    <div class="mt-3 pt-3 border-t border-[var(--hs-neutral-100)] flex items-center gap-2">
                                        <flux:button variant="primary" size="xs" icon="check" wire:click="awardQuotation({{ $quotation->id }})">
                                            {{ __('Award') }}
                                        </flux:button>
                                        <flux:button
                                            variant="ghost"
                                            size="xs"
                                            icon="x-mark"
                                            wire:click="confirmRejectQuotation({{ $quotation->id }})"
                                            class="text-[var(--hs-danger-600)] hover:bg-[var(--hs-danger-50)]"
                                        >
                                            {{ __('Reject') }}
                                        </flux:button>
                                    </div>
                                @endif
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
</div>