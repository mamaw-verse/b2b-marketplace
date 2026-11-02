<div class="space-y-6">
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Product Sourcing Requests') }}</h1>
        </div>

        {{-- Filters --}}
        <div class="px-4 sm:px-6 py-5 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex-1">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name, email, or product...') }}" />
                </div>
                <flux:select wire:model.live="statusFilter" class="w-44" placeholder="{{ __('All Statuses') }}">
                    <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                    <flux:select.option value="in_progress">{{ __('In Progress') }}</flux:select.option>
                    <flux:select.option value="completed">{{ __('Completed') }}</flux:select.option>
                </flux:select>
            </div>
        </div>

        {{-- Mobile: stacked card list --}}
        <div class="lg:hidden divide-y divide-[var(--hs-neutral-100)]">
            @forelse ($this->requests as $sourcingRequest)
                <div class="px-6 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('admin.sourcing-requests.show', $sourcingRequest) }}" wire:navigate class="font-medium text-[var(--hs-green-700)] hover:underline">
                                {{ $sourcingRequest->name }}
                            </a>
                            <p class="text-sm text-[var(--hs-neutral-500)]">{{ $sourcingRequest->email }}</p>
                        </div>
                        <flux:badge :color="match ($sourcingRequest->status) {
                            'completed' => 'green',
                            'in_progress' => 'yellow',
                            default => 'gray',
                        }">{{ str($sourcingRequest->status)->headline() }}</flux:badge>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <div>
                            <dt class="text-xs text-[var(--hs-neutral-500)]">{{ __('Product') }}</dt>
                            <dd class="text-[var(--hs-neutral-900)]">{{ $sourcingRequest->product_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-[var(--hs-neutral-500)]">{{ __('Date') }}</dt>
                            <dd class="text-[var(--hs-neutral-600)]">{{ $sourcingRequest->created_at?->format('M d, Y') }}</dd>
                        </div>
                    </dl>
                    <div class="mt-3">
                        <flux:button size="sm" variant="ghost" icon="eye" href="{{ route('admin.sourcing-requests.show', $sourcingRequest) }}" wire:navigate>
                            {{ __('View') }}
                        </flux:button>
                    </div>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-sm text-[var(--hs-neutral-500)]">{{ __('No sourcing requests found.') }}</div>
            @endforelse
        </div>

        {{-- Desktop: table --}}
        <div class="hidden overflow-hidden lg:block">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Email') }}</flux:table.column>
                    <flux:table.column>{{ __('Product') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->requests as $sourcingRequest)
                        <flux:table.row wire:key="sourcing-{{ $sourcingRequest->id }}">
                            <flux:table.cell variant="strong">
                                <a href="{{ route('admin.sourcing-requests.show', $sourcingRequest) }}" wire:navigate class="text-[var(--hs-green-700)] hover:underline">
                                    {{ $sourcingRequest->name }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>{{ $sourcingRequest->email }}</flux:table.cell>
                            <flux:table.cell>{{ $sourcingRequest->product_name }}</flux:table.cell>
                            <flux:table.cell>{{ $sourcingRequest->created_at?->format('M d, Y') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="match ($sourcingRequest->status) {
                                    'completed' => 'green',
                                    'in_progress' => 'yellow',
                                    default => 'gray',
                                }">{{ str($sourcingRequest->status)->headline() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="ghost" icon="eye" href="{{ route('admin.sourcing-requests.show', $sourcingRequest) }}" wire:navigate>
                                        {{ __('View') }}
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="6" class="text-center">{{ __('No sourcing requests found.') }}</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($this->requests->hasPages())
            <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">{{ $this->requests->links() }}</div>
        @endif
    </div>

    @include('livewire.admin.document-viewer')
</div>
