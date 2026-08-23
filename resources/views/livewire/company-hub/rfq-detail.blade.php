<div>
    <div class="space-y-6">
        {{-- Header with status and actions --}}
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
            <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ $rfqRecipient->rfq->title }}</h1>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                            {{ __('From') }}
                            <span class="font-medium text-[var(--hs-neutral-700)]">
                                {{ $rfqRecipient->rfq->buyer_snapshot['business_name'] ?? 'Unknown' }}
                            </span>
                            <span class="text-[var(--hs-neutral-300)]">·</span>
                            {{ $rfqRecipient->rfq->created_at->format('M d, Y') }}
                        </p>
                    </div>
                    <x-hs-status-pill :status="$rfqRecipient->status" class="shrink-0" />
                </div>
            </div>

            {{-- Actions --}}
            @unless ($rfqRecipient->isTerminal())
                <div class="px-4 sm:px-6 py-3.5 flex flex-wrap items-center gap-2">
                    @if ($rfqRecipient->status === 'reviewed')
                        <flux:button variant="primary" wire:click="markInProgress" size="sm" icon="play">
                            {{ __('Start Working') }}
                        </flux:button>
                    @endif

                    @if (in_array($rfqRecipient->status, ['reviewed', 'in_progress']))
                        <flux:button variant="primary" href="{{ route('hub.quotations.create', $rfqRecipient) }}" size="sm" icon="document-plus" wire:navigate>
                            {{ __('Create Quotation') }}
                        </flux:button>

                        <flux:button variant="ghost" wire:click="openDeclineModal" size="sm" class="ml-auto text-[var(--hs-danger-600)] hover:bg-[var(--hs-danger-50)] hover:text-[var(--hs-danger-700)]">
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
            <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)] flex items-center gap-2">
                <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Your Quotations') }}</h2>
                @if ($rfqRecipient->quotations->isNotEmpty())
                    <flux:badge size="sm" color="zinc">{{ $rfqRecipient->quotations->count() }}</flux:badge>
                @endif
            </div>
            <div class="p-6">
                @if ($rfqRecipient->quotations->isEmpty())
                    <div class="text-center py-8">
                        <flux:icon name="document-text" class="mx-auto size-8 text-[var(--hs-neutral-300)]" />
                        <p class="mt-2 text-sm text-[var(--hs-neutral-500)]">{{ __('No quotations submitted yet.') }}</p>
                        @if (! $rfqRecipient->isTerminal() && $rfqRecipient->rfq->isOpen())
                            <flux:button variant="primary" href="{{ route('hub.quotations.create', $rfqRecipient) }}" size="sm" icon="document-plus" class="mt-4" wire:navigate>
                                {{ __('Create Quotation') }}
                            </flux:button>
                        @endif
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($rfqRecipient->quotations as $quotation)
                            <div @class([
                                'rounded-[var(--hs-radius-card)] border p-4',
                                'border-[var(--hs-success-200)] bg-[var(--hs-success-50)]' => $quotation->status === 'awarded',
                                'border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)]' => $quotation->status === 'rejected',
                                'border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)]' => $quotation->status === 'withdrawn',
                                'border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]' => in_array($quotation->status, ['draft', 'submitted', 'not_awarded', 'expired']),
                            ])>
                                <div class="flex items-start justify-between gap-3 mb-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-base font-bold text-[var(--hs-neutral-900)]">
                                            {{ $quotation->currency }} {{ number_format((float) $quotation->total_amount, 2) }}
                                        </span>
                                        <x-hs-status-pill :status="$quotation->status" />
                                    </div>
                                    <span class="text-xs text-[var(--hs-neutral-500)] shrink-0 whitespace-nowrap">
                                        {{ $quotation->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                {{-- Status-specific feedback messages --}}
                                @if ($quotation->status === 'awarded')
                                    <div class="flex items-center gap-1.5 mt-2 text-sm text-[var(--hs-success-700)]">
                                        <flux:icon name="check-circle" variant="mini" class="shrink-0" />
                                        {{ __('Buyer awarded this quotation. Proceed to next steps.') }}
                                    </div>
                                    {{-- Create Order action --}}
                                    <div class="mt-3 pt-3 border-t border-[var(--hs-success-200)]">
                                        @if ($quotation->order)
                                            <flux:button variant="ghost" size="xs" icon="eye" href="{{ route('hub.orders.show', $quotation->order) }}" wire:navigate>
                                                {{ __('View Order #') }}{{ $quotation->order->id }}
                                            </flux:button>
                                        @else
                                            <flux:button variant="primary" size="xs" icon="plus" href="{{ route('hub.orders.create', ['quotationId' => $quotation->id]) }}" wire:navigate>
                                                {{ __('Create Order') }}
                                            </flux:button>
                                        @endif
                                    </div>
                                @elseif ($quotation->status === 'rejected')
                                    <div class="flex items-center gap-1.5 mt-2 text-sm text-[var(--hs-danger-700)]">
                                        <flux:icon name="x-circle" variant="mini" class="shrink-0" />
                                        {{ __('Buyer rejected this quotation. You can submit a revised quotation with different pricing or terms.') }}
                                    </div>
                                @elseif ($quotation->status === 'not_awarded')
                                    <div class="flex items-center gap-1.5 mt-2 text-sm text-[var(--hs-neutral-600)]">
                                        <flux:icon name="minus-circle" variant="mini" class="shrink-0" />
                                        {{ __('RFQ was closed before this quotation was awarded.') }}
                                    </div>
                                @elseif ($quotation->status === 'expired')
                                    <div class="flex items-center gap-1.5 mt-2 text-sm text-[var(--hs-warning-700)]">
                                        <flux:icon name="exclamation-triangle" variant="mini" class="shrink-0" />
                                        {{ __('This quotation expired without a response from the buyer.') }}
                                    </div>
                                @elseif ($quotation->status === 'submitted')
                                    <div class="flex items-center gap-1.5 mt-2 text-sm text-[var(--hs-neutral-600)]">
                                        <flux:icon name="clock" variant="mini" class="shrink-0" />
                                        {{ __('Waiting for buyer response.') }}
                                        @if ($quotation->valid_until)
                                            {{ __('Valid until') }} {{ $quotation->valid_until->format('M d, Y') }}.
                                        @endif
                                    </div>
                                @endif

                                {{-- Line items summary --}}
                                <div class="mt-2 text-xs text-[var(--hs-neutral-500)]">
                                    {{ $quotation->items->count() }} {{ Str::plural('item', $quotation->items->count()) }}
                                    @if ($quotation->notes)
                                        <span class="text-[var(--hs-neutral-300)]">·</span> {{ Str::limit($quotation->notes, 60) }}
                                    @endif
                                </div>

                                {{-- Actions for draft quotations --}}
                                @if ($quotation->isDraft())
                                    <div class="mt-3 pt-3 border-t border-[var(--hs-neutral-100)]">
                                        <flux:button variant="ghost" href="{{ route('hub.quotations.edit', [$rfqRecipient, $quotation]) }}" size="xs" icon="pencil-square" wire:navigate>
                                            {{ __('Edit Draft') }}
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Prompt to submit another after rejection --}}
                    @if ($rfqRecipient->quotations->where('status', 'rejected')->isNotEmpty() && $rfqRecipient->rfq->isOpen() && ! $rfqRecipient->isTerminal())
                        <div class="mt-4 flex items-start gap-3 rounded-[var(--hs-radius-card)] bg-[var(--hs-info-50)] border border-[var(--hs-info-200)] p-3.5">
                            <flux:icon name="light-bulb" variant="mini" class="mt-0.5 shrink-0 text-[var(--hs-info-500)]" />
                            <div>
                                <p class="text-sm text-[var(--hs-info-700)]">
                                    {{ __('A quotation was rejected. You can submit a revised quotation with updated pricing, terms, or packaging options.') }}
                                </p>
                                <flux:button variant="primary" href="{{ route('hub.quotations.create', $rfqRecipient) }}" size="sm" class="mt-2.5" wire:navigate>
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
                <div class="p-3">
                    <ul class="divide-y divide-[var(--hs-neutral-100)]">
                        @foreach ($rfqRecipient->rfq->attachments as $attachment)
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

        {{-- Buyer Info (from snapshot) --}}
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
            <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)]">
                <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Buyer Information') }}</h2>
            </div>
            <div class="p-6">
                @php $snapshot = $rfqRecipient->rfq->buyer_snapshot; @endphp
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                    <div>
                        <dt class="flex items-center gap-1.5 font-medium text-[var(--hs-neutral-500)]">
                            <flux:icon name="building-storefront" variant="mini" class="text-[var(--hs-neutral-400)]" />
                            {{ __('Business Name') }}
                        </dt>
                        <dd class="mt-1 text-[var(--hs-neutral-900)] font-medium">{{ $snapshot['business_name'] ?? '—' }}</dd>
                    </div>
                    @if (isset($snapshot['shipping_address']))
                        <div>
                            <dt class="flex items-center gap-1.5 font-medium text-[var(--hs-neutral-500)]">
                                <flux:icon name="map-pin" variant="mini" class="text-[var(--hs-neutral-400)]" />
                                {{ __('Shipping Address') }}
                            </dt>
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