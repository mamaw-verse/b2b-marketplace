<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Request for Quotations') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Manage and assign Request for Quotations.') }}</p>
        </div>

        {{-- Status filter tabs (pill style with counts) --}}
        <div class="px-4 sm:px-6 pt-5 pb-4 flex flex-wrap gap-1.5 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            @php
                $tabs = [
                    'all' => __('All'),
                    'pending' => __('Pending'),
                    'in_progress' => __('In Progress'),
                    'resolved' => __('Resolved'),
                    'closed' => __('Closed'),
                ];
            @endphp

            @foreach ($tabs as $tabKey => $tabLabel)
                <button
                    wire:click="filterByStatus('{{ $tabKey }}')"
                    @class([
                        'inline-flex items-center gap-2 px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                        'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-[var(--hs-neutral-200)]' => $statusFilter === $tabKey,
                        'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-0)]' => $statusFilter !== $tabKey,
                    ])
                >
                    <span>{{ $tabLabel }}</span>
                    @if (($this->tabCounts[$tabKey] ?? 0) > 0)
                        <span @class([
                            'inline-flex items-center justify-center min-w-5 h-5 px-1.5 text-xs font-semibold rounded-[var(--hs-radius-pill)]',
                            'bg-[var(--hs-green-600)] text-white' => $statusFilter === $tabKey,
                            'bg-[var(--hs-neutral-200)] text-[var(--hs-neutral-700)]' => $statusFilter !== $tabKey,
                        ])>
                            {{ $this->tabCounts[$tabKey] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="px-4 sm:px-6 py-4 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by RFQ title, buyer email, or shop name...') }}" />
        </div>

        {{-- Table --}}
        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('RFQ / Buyer') }}</flux:table.column>
                    <flux:table.column>{{ __('Target Business') }}</flux:table.column>
                    <flux:table.column>{{ __('Admin Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->rfqs as $recipient)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="font-medium text-zinc-900 dark:text-white line-clamp-1" title="{{ $recipient->rfq->title }}">{{ $recipient->rfq->title }}</div>
                                <div class="text-xs text-zinc-500">{{ $recipient->rfq->buyer->email ?? 'Unknown Buyer' }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $recipient->shop->registered_business_name }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $adminStatus = $recipient->admin_status ?? 'pending';
                                    $color = match($adminStatus) {
                                        'pending' => 'yellow',
                                        'in_progress' => 'blue',
                                        'resolved' => 'green',
                                        'closed' => 'zinc',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge :color="$color">
                                    {{ str_replace('_', ' ', ucfirst($adminStatus)) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $recipient->created_at->format('M d, Y g:i A') }}</flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:button variant="ghost" size="sm" href="{{ route('admin.rfqs.show', $recipient->rfq_id) }}" wire:navigate>
                                    {{ __('View') }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-zinc-500 py-8">
                                {{ __('No RFQs found matching your criteria.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($this->rfqs->hasPages())
                <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">
                    {{ $this->rfqs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
