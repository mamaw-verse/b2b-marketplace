<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                {{ __('RFQs Received') }}
            </h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                {{ __('Requests for quotation from buyers.') }}
            </p>
        </div>

        {{-- Status Filter Tabs --}}
        <div class="px-6 pt-4 flex flex-wrap gap-2">
            @php
                $statuses = [
                    '' => __('All'),
                    'received' => __('New'),
                    'reviewed' => __('Reviewed'),
                    'in_progress' => __('In Progress'),
                    'closed' => __('Closed'),
                ];
            @endphp
            @foreach ($statuses as $value => $label)
                <button
                    wire:click="filterByStatus('{{ $value }}')"
                    @class([
                        'px-3 py-1.5 rounded-[var(--hs-radius-pill)] text-sm font-medium border transition',
                        'bg-[var(--hs-green-600)] border-[var(--hs-green-600)] text-white' => $statusFilter === $value,
                        'bg-[var(--hs-neutral-0)] border-[var(--hs-neutral-200)] text-[var(--hs-neutral-600)] hover:border-[var(--hs-green-300)]' => $statusFilter !== $value,
                    ])
                >
                    {{ $label }}
                    @if ($value !== '' && ($this->statusCounts[$value] ?? 0) > 0)
                        <span class="ml-1 text-xs opacity-75">({{ $this->statusCounts[$value] }})</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- RFQ List --}}
        <div class="p-6">
            @if ($this->rfqs->isEmpty())
                <x-hs-empty-state
                    title="{{ __('No RFQs found') }}"
                    description="{{ __("You haven't received any requests for quotation yet.") }}"
                />
            @else
                <div class="space-y-3">
                    @foreach ($this->rfqs as $recipient)
                        <a href="{{ route('hub.rfqs.show', $recipient) }}" wire:navigate
                           class="block p-4 rounded-lg border border-[var(--hs-neutral-200)] hover:border-[var(--hs-green-300)] transition">
                            <div class="flex items-center justify-between">
                                <h3 class="font-medium text-[var(--hs-neutral-900)] truncate">{{ $recipient->rfq->title }}</h3>
                                <x-hs-status-pill :status="$recipient->status" />
                            </div>
                            <div class="mt-1 flex items-center gap-3 text-xs text-[var(--hs-neutral-500)]">
                                <span>{{ $recipient->rfq->user->buyerProfile?->business_name ?? $recipient->rfq->user->first_name . ' ' . $recipient->rfq->user->last_name }}</span>
                                <span>·</span>
                                <span>{{ $recipient->created_at->diffForHumans() }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $this->rfqs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
