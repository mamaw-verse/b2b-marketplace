<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Sponsored Content</h1>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">Manage tiered ad placements across the storefront.</p>
            </div>
            <flux:button
                href="{{ route('admin.sponsored-content.create') }}"
                wire:navigate
                variant="primary"
                icon="plus"
            >
                New Content
            </flux:button>
        </div>

        {{-- Filters --}}
        <div class="px-4 sm:px-6 py-4 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <flux:select wire:model.live="tierFilter" class="w-36" placeholder="All Tiers">
                    @foreach ($this->tiers as $tier)
                        <flux:select.option value="{{ $tier->id }}">{{ $tier->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="statusFilter" class="w-40" placeholder="All Statuses">
                    <flux:select.option value="active">Active now</flux:select.option>
                    <flux:select.option value="scheduled">Scheduled</flux:select.option>
                    <flux:select.option value="expired">Expired</flux:select.option>
                    <flux:select.option value="inactive">Inactive</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="placementFilter" class="w-44" placeholder="All Placements">
                    <flux:select.option value="homepage">Homepage</flux:select.option>
                    <flux:select.option value="category_page">Category Page</flux:select.option>
                    <flux:select.option value="search_results">Search Results</flux:select.option>
                </flux:select>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Title</flux:table.column>
                    <flux:table.column>Tier</flux:table.column>
                    <flux:table.column>Placements</flux:table.column>
                    <flux:table.column>Schedule</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column class="text-right">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->items as $item)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="font-medium text-[var(--hs-neutral-900)]">{{ $item->title }}</div>
                                <div class="text-xs text-[var(--hs-neutral-500)] truncate max-w-xs">{{ $item->destination_url }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($item->tier)
                                    <flux:badge color="{{ match($item->tier->name) { 'Gold' => 'yellow', 'Silver' => 'zinc', 'Bronze' => 'orange', default => 'zinc' } }}">
                                        {{ $item->tier->name }}
                                    </flux:badge>
                                @else
                                    <span class="text-xs text-[var(--hs-neutral-400)]">—</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($item->placements as $placement)
                                        <flux:badge size="sm" color="blue">
                                            {{ str_replace('_', ' ', ucfirst($placement->placement_key)) }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-xs text-[var(--hs-neutral-600)]">
                                    {{ $item->starts_at->format('M j, Y') }}
                                </div>
                                <div class="text-xs text-[var(--hs-neutral-400)]">
                                    → {{ $item->ends_at->format('M j, Y') }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if (! $item->is_active)
                                    <flux:badge color="zinc">Inactive</flux:badge>
                                @elseif ($item->starts_at->isFuture())
                                    <flux:badge color="blue">Scheduled</flux:badge>
                                @elseif ($item->ends_at->isPast())
                                    <flux:badge color="zinc">Expired</flux:badge>
                                @else
                                    <flux:badge color="green">Live</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <flux:button
                                        href="{{ route('admin.sponsored-content.edit', $item) }}"
                                        wire:navigate
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil-square"
                                    />
                                    <flux:button
                                        wire:click="toggleActive({{ $item->id }})"
                                        size="sm"
                                        variant="ghost"
                                        icon="{{ $item->is_active ? 'eye-slash' : 'eye' }}"
                                        title="{{ $item->is_active ? 'Deactivate' : 'Activate' }}"
                                    />
                                    <flux:button
                                        wire:click="confirmDelete({{ $item->id }})"
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        class="text-[var(--hs-danger-500)]"
                                    />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-12 text-[var(--hs-neutral-500)]">
                                No sponsored content found.
                                <a href="{{ route('admin.sponsored-content.create') }}" wire:navigate class="text-[var(--hs-green-600)] underline ml-1">Create your first entry.</a>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($this->items->hasPages())
            <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">
                {{ $this->items->links() }}
            </div>
        @endif
    </div>

    {{-- Delete confirmation --}}
    <x-hs-confirm
        name="delete-confirm"
        title="Delete Sponsored Content"
        message="This will permanently remove this content and all its event records. This cannot be undone."
        variant="danger"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="delete" wire:loading.attr="disabled">Delete</flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
