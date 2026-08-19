<div>
    <div class="space-y-6">
        {{-- Header with status and actions --}}
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
            <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ $rfqRecipient->rfq->title }}</h1>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                            {{ __('From') }}
                            <span class="font-medium text-[var(--hs-neutral-700)]">
                                {{ $rfqRecipient->rfq->buyer_snapshot['business_name'] ?? 'Unknown' }}
                            </span>
                            · {{ $rfqRecipient->rfq->created_at->format('M d, Y') }}
                        </p>
                    </div>
                    <x-hs-status-pill :status="$rfqRecipient->status" />
                </div>
            </div>

            {{-- Actions --}}
            @unless ($rfqRecipient->isTerminal())
                <div class="px-4 sm:px-6 py-4 flex flex-wrap items-center gap-2 border-b border-[var(--hs-neutral-100)]">
                    @if ($rfqRecipient->status === 'reviewed')
                        <flux:button variant="primary" wire:click="markInProgress" size="sm">
                            {{ __('Start Working') }}
                        </flux:button>
                    @endif

                    @if (in_array($rfqRecipient->status, ['reviewed', 'in_progress']))
                        <flux:button variant="primary" href="{{ route('hub.quotations.create', $rfqRecipient) }}" size="sm" wire:navigate>
                            {{ __('Create Quotation') }}
                        </flux:button>
                        <flux:button variant="ghost" wire:click="openDeclineModal" size="sm" class="text-red-600 hover:text-red-700">
                            {{ __('Decline') }}
                        </flux:button>
                    @endif
                </div>
            @endunless
        </div>

        {{-- RFQ Content --}}
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
            <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)]">
                <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Requirements') }}</h2>
            </div>
            <div class="p-6">
                @if ($rfqRecipient->rfq->description)
                    <div class="prose prose-sm max-w-none text-[var(--hs-neutral-700)]">
                        {!! nl2br(e($rfqRecipient->rfq->description)) !!}
                    </div>
                @else
                    <p class="text-sm text-[var(--hs-neutral-500)] italic">{{ __('No description provided.') }}</p>
                @endif
            </div>
        </div>

        {{-- Your Quotations --}}
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
            <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
                <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Your Quotations') }}</h2>
                @if (! $rfqRecipient->isTerminal() && $rfqRecipient->rfq->isOpen())
                    <flux:button variant="primary" href="{{ route('hub.quotations.create', $rfqRecipient) }}" size="sm" wire:navigate>
                        + {{ __('New Quotation') }}
                    </flux:button>
                @endif
            </div>
            <div class="p-6">
                @if ($rfqRecipient->quotations->isEmpty())
                    <p class="text-sm text-[var(--hs-neutral-500)] italic">{{ __('No quotations submitted yet.') }}</p>
                @else
                    <div class="space-y-4">
                        @foreach ($rfqRecipient->quotations as $quotation)
                            <div @class([
                                'rounded-lg border p-4',
                                'border-[var(--hs-success-200)] bg-[var(--hs-success-50)]' => $quotation->status === 'awarded',
                                'border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)]' => $quotation->status === 'rejected',
                                'border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)]' => $quotation->status === 'withdrawn',
                                'border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]' => in_array($quotation->status, ['draft', 'submitted', 'not_awarded', 'expired']),
                            ])>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-[var(--hs-neutral-900)]">
                                            {{ $quotation->currency }} {{ number_format((float) $quotation->total_amount, 2) }}
                                        </span>
                                        <x-hs-status-pill :status="$quotation->status" />
                                    </div>
                                    <span class="text-xs text-[var(--hs-neutral-500)]">
                                        {{ $quotation->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                {{-- Status-specific feedback messages --}}
                                @if ($quotation->status === 'awarded')
                                    <div class="flex items-center gap-2 mb-2 text-sm text-[var(--hs-success-700)]">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        {{ __('Buyer awarded this quotation. Proceed to next steps.') }}
                                    </div>
                                @elseif ($quotation->status === 'rejected')
                                    <div class="flex items-center gap-2 mb-2 text-sm text-[var(--hs-danger-700)]">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        {{ __('Buyer rejected this quotation. You can submit a revised quotation with different pricing or terms.') }}
                                    </div>
                                @elseif ($quotation->status === 'not_awarded')
                                    <div class="flex items-center gap-2 mb-2 text-sm text-[var(--hs-neutral-600)]">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                                        {{ __('RFQ was closed before this quotation was awarded.') }}
                                    </div>
                                @elseif ($quotation->status === 'expired')
                                    <div class="flex items-center gap-2 mb-2 text-sm text-[var(--hs-warning-700)]">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        {{ __('This quotation expired without a response from the buyer.') }}
                                    </div>
                                @elseif ($quotation->status === 'submitted')
                                    <div class="flex items-center gap-2 mb-2 text-sm text-[var(--hs-neutral-600)]">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        {{ __('Waiting for buyer response.') }}
                                        @if ($quotation->valid_until)
                                            {{ __('Valid until') }} {{ $quotation->valid_until->format('M d, Y') }}.
                                        @endif
                                    </div>
                                @endif

                                {{-- Line items summary --}}
                                <div class="text-xs text-[var(--hs-neutral-600)]">
                                    {{ $quotation->items->count() }} {{ Str::plural('item', $quotation->items->count()) }}
                                    @if ($quotation->notes)
                                        · {{ Str::limit($quotation->notes, 60) }}
                                    @endif
                                </div>

                                {{-- Actions for draft quotations --}}
                                @if ($quotation->isDraft())
                                    <div class="mt-3 pt-2 border-t border-[var(--hs-neutral-100)] flex items-center gap-2">
                                        <flux:button variant="ghost" href="{{ route('hub.quotations.edit', [$rfqRecipient, $quotation]) }}" size="xs" wire:navigate>
                                            {{ __('Edit Draft') }}
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Prompt to submit another after rejection --}}
                    @if ($rfqRecipient->quotations->where('status', 'rejected')->isNotEmpty() && $rfqRecipient->rfq->isOpen() && ! $rfqRecipient->isTerminal())
                        <div class="mt-4 rounded-lg bg-[var(--hs-info-50)] border border-[var(--hs-info-200)] p-3">
                            <p class="text-sm text-[var(--hs-info-700)]">
                                <strong>{{ __('Tip:') }}</strong> {{ __('A quotation was rejected. You can submit a revised quotation with updated pricing, terms, or packaging options.') }}
                            </p>
                            <div class="mt-2">
                                <flux:button variant="primary" href="{{ route('hub.quotations.create', $rfqRecipient) }}" size="sm" wire:navigate>
                                    {{ __('Submit Revised Quotation') }}
                                </flux:button>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Attachments --}}
        @if ($rfqRecipient->rfq->attachments->count() > 0)
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Attachments') }}</h2>
                </div>
                <div class="p-6">
                    <ul class="space-y-2">
                        @foreach ($rfqRecipient->rfq->attachments as $attachment)
                            <li class="flex items-center gap-2 text-sm text-[var(--hs-neutral-700)]">
                                <svg class="size-4 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                <a href="{{ route('media.view', $attachment) }}" target="_blank" class="text-[var(--hs-green-700)] hover:underline">
                                    {{ $attachment->original_filename }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Buyer Info (from snapshot) --}}
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
            <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)]">
                <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Buyer Information') }}</h2>
            </div>
            <div class="p-6">
                @php $snapshot = $rfqRecipient->rfq->buyer_snapshot; @endphp
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-medium text-[var(--hs-neutral-500)]">{{ __('Business Name') }}</dt>
                        <dd class="mt-1 text-[var(--hs-neutral-900)]">{{ $snapshot['business_name'] ?? '—' }}</dd>
                    </div>
                    @if (isset($snapshot['shipping_address']))
                        <div>
                            <dt class="font-medium text-[var(--hs-neutral-500)]">{{ __('Shipping Address') }}</dt>
                            <dd class="mt-1 text-[var(--hs-neutral-900)]">
                                {{ $snapshot['shipping_address']['address_1'] ?? '' }}
                                @if ($snapshot['shipping_address']['city'] ?? null)
                                    <br>{{ $snapshot['shipping_address']['city'] }}, {{ $snapshot['shipping_address']['province'] ?? '' }} {{ $snapshot['shipping_address']['zip_code'] ?? '' }}
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Decline Modal --}}
    <flux:modal name="decline-rfq" wire:model="showDeclineModal">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Decline RFQ') }}</flux:heading>
            <p class="text-sm text-[var(--hs-neutral-600)]">
                {{ __('Are you sure you want to decline this request? This action cannot be undone.') }}
            </p>

            <flux:textarea
                wire:model="declineReason"
                label="Reason (optional)"
                placeholder="e.g., We cannot fulfil this volume within the requested timeline."
                rows="3"
            />

            <div class="flex items-center gap-3">
                <flux:button variant="danger" wire:click="decline">
                    {{ __('Confirm Decline') }}
                </flux:button>
                <flux:button variant="ghost" wire:click="$set('showDeclineModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
