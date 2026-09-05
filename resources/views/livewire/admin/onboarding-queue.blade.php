<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Onboarding Queue') }}</h1>
        </div>

        {{-- Status filter tabs (pill style with counts) --}}
        <div class="px-4 sm:px-6 pt-5 pb-4 flex flex-wrap gap-1.5 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            @php
                $tabs = [
                    'all' => __('All'),
                    'submitted' => __('Submitted'),
                    'pending' => __('Pending'),
                    'requires_revision' => __('Requires Revision'),
                    'approved' => __('Approved'),
                    'rejected' => __('Rejected'),
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

        {{-- Onboarding submissions table --}}
        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Business Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Applicant') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Submitted') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->onboardings as $onboarding)
                        <flux:table.row
                            :key="$onboarding->id"
                            x-on:click="Livewire.navigate('{{ route('admin.onboardings.show', $onboarding) }}')"
                            class="cursor-pointer hover:bg-[var(--hs-neutral-50)] transition"
                        >
                            <flux:table.cell variant="strong">
                                {{ $onboarding->registered_business_name }}
                            </flux:table.cell>

                            <flux:table.cell>
                                {{ $onboarding->user?->first_name }} {{ $onboarding->user?->last_name }}
                            </flux:table.cell>

                            <flux:table.cell class="py-0">
                                <x-hs-status-pill :status="$onboarding->status" />
                            </flux:table.cell>

                            <flux:table.cell>
                                {{ $onboarding->submitted_at?->format('M d, Y') ?? '—' }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="eye"
                                    href="{{ route('admin.onboardings.show', $onboarding) }}"
                                    wire:navigate
                                    x-on:click.stop
                                >
                                    {{ $onboarding->status === 'submitted' ? 'Review' : 'View' }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center">
                                <x-hs-empty-state
                                    icon="folder"
                                    title="{{ __('No submissions') }}"
                                    subtitle="{{ __('No onboarding submissions found for this status.') }}"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            {{-- Only show pagination when there is more than one page --}}
            @if ($this->onboardings->hasPages())
                <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">
                    {{ $this->onboardings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
