<div>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
            <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ $rfq->title }}</h1>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                            {{ __('Sent') }} {{ $rfq->created_at->diffForHumans() }}
                            @if ($rfq->recipients->first())
                                · {{ __('To') }} <span class="font-medium">{{ $rfq->recipients->first()->shop->storefront_name }}</span>
                            @endif
                        </p>
                    </div>
                    <x-hs-status-pill :status="$rfq->status" />
                </div>
            </div>

            {{-- Close RFQ Action --}}
            @if ($rfq->isOpen())
                <div class="px-6 py-3 border-b border-[var(--hs-neutral-100)] flex items-center gap-3">
                    <flux:button variant="ghost" size="sm" wire:click="$set('showCloseConfirmation', true)" class="text-red-600 hover:text-red-700">
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
            <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)]">
                <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Quotations') }}</h2>
            </div>
            <div class="p-6">
                @php
                    $visibleQuotations = $rfq->quotations->where('status', '!=', 'draft');
                @endphp

                @if ($visibleQuotations->isEmpty())
                    <p class="text-sm text-[var(--hs-neutral-500)] italic">{{ __('No quotations received yet.') }}</p>
                @else
                    <div class="space-y-4">
                        @foreach ($visibleQuotations as $quotation)
                            <div class="rounded-lg border border-[var(--hs-neutral-200)] p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-[var(--hs-neutral-900)]">
                                            {{ $quotation->currency }} {{ number_format((float) $quotation->total_amount, 2) }}
                                        </span>
                                        <x-hs-status-pill :status="$quotation->status" />
                                    </div>
                                    @if ($quotation->valid_until)
                                        <span class="text-xs text-[var(--hs-neutral-500)]">
                                            {{ __('Valid until') }} {{ $quotation->valid_until->format('M d, Y') }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Line items --}}
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-left text-[var(--hs-neutral-500)]">
                                                <th class="pb-1">{{ __('Item') }}</th>
                                                <th class="pb-1 text-right">{{ __('Qty') }}</th>
                                                <th class="pb-1">{{ __('Unit') }}</th>
                                                <th class="pb-1 text-right">{{ __('Price') }}</th>
                                                <th class="pb-1 text-right">{{ __('Subtotal') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-[var(--hs-neutral-700)]">
                                            @foreach ($quotation->items as $item)
                                                <tr>
                                                    <td class="py-0.5">{{ $item->description }}</td>
                                                    <td class="py-0.5 text-right">{{ $item->quantity }}</td>
                                                    <td class="py-0.5">{{ $item->unit }}</td>
                                                    <td class="py-0.5 text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                                                    <td class="py-0.5 text-right font-medium">{{ number_format((float) $item->subtotal, 2) }}</td>
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
                                        <flux:button variant="primary" size="xs" wire:click="awardQuotation({{ $quotation->id }})">
                                            {{ __('Award') }}
                                        </flux:button>
                                        <flux:button variant="ghost" size="xs" wire:click="rejectQuotation({{ $quotation->id }})" class="text-red-600">
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
                <div class="p-6">
                    <ul class="space-y-2">
                        @foreach ($rfq->attachments as $attachment)
                            <li class="flex items-center gap-2 text-sm text-[var(--hs-neutral-700)]">
                                <svg class="size-4 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                {{ $attachment->original_filename }}
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
</div>
