<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Onboarding Queue</h1>
        </div>

        {{-- Status filter tabs (pill style with counts) --}}
        <div class="px-4 sm:px-6 pt-5 pb-4 flex flex-wrap gap-1.5 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            <button
                wire:click="filterByStatus('submitted')"
                @class([
                    'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                    'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-[var(--hs-neutral-200)]' => $statusFilter === 'submitted',
                    'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-0)]' => $statusFilter !== 'submitted',
                ])
            >
                Submitted
            </button>
            <button
                wire:click="filterByStatus('pending')"
                @class([
                    'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                    'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-[var(--hs-neutral-200)]' => $statusFilter === 'pending',
                    'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-0)]' => $statusFilter !== 'pending',
                ])
            >
                Pending
            </button>
            <button
                wire:click="filterByStatus('requires_revision')"
                @class([
                    'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                    'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-[var(--hs-neutral-200)]' => $statusFilter === 'requires_revision',
                    'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-0)]' => $statusFilter !== 'requires_revision',
                ])
            >
                Requires Revision
            </button>
            <button
                wire:click="filterByStatus('approved')"
                @class([
                    'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                    'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-[var(--hs-neutral-200)]' => $statusFilter === 'approved',
                    'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-0)]' => $statusFilter !== 'approved',
                ])
            >
                Approved
            </button>
            <button
                wire:click="filterByStatus('rejected')"
                @class([
                    'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                    'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-[var(--hs-neutral-200)]' => $statusFilter === 'rejected',
                    'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-0)]' => $statusFilter !== 'rejected',
                ])
            >
                Rejected
            </button>
        </div>

        {{-- Onboarding submissions table --}}
        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Business Name</flux:table.column>
                    <flux:table.column>Applicant</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Submitted</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
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
                                    Review
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center">
                                <x-hs-empty-state
                                    icon="folder"
                                    title="No submissions"
                                    subtitle="No onboarding submissions found for this status."
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
