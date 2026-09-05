<div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
    {{-- Card Header --}}
    <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
        <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Certification Queue') }}</h1>
    </div>

    @if (session('message'))
        <div class="px-6 pt-5">
            <flux:callout variant="success" icon="check-circle">{{ session('message') }}</flux:callout>
        </div>
    @endif

    {{-- Status filter tabs (pill style with counts) --}}
    <div class="px-4 sm:px-6 pt-5 pb-4 flex flex-wrap gap-1.5 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
        @php
            $tabs = [
                'all' => __('All'),
                'interested' => __('Needs Contact'),
                'contacted' => __('Contacted'),
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

    {{-- Certification interest table --}}
    <div class="overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Seller') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Submitted') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->applications as $application)
                    <flux:table.row wire:key="certification-{{ $application->id }}">
                        <flux:table.cell variant="strong">
                            {{ $application->shop->storefront_name }}
                            <flux:text size="sm">{{ $application->shop->user?->email }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell><flux:badge :color="$application->status === 'contacted' ? 'green' : 'yellow'">{{ str($application->status)->headline() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $application->created_at?->format('M d, Y') }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($application->status === 'interested')
                                <flux:button size="sm" variant="primary" wire:click="markContacted({{ $application->id }})">{{ __('Mark as Contacted') }}</flux:button>
                            @else
                                <flux:text>{{ $application->addressed_at?->format('M d, Y') }}</flux:text>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="4" class="text-center">{{ __('No certification applications found.') }}</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        @if ($this->applications->hasPages())
            <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">{{ $this->applications->links() }}</div>
        @endif
    </div>
</div>
