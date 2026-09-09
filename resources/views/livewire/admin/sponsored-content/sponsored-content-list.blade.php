<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Sponsored Content') }}</h1>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Manage tiered ad placements across the storefront.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <flux:modal.trigger name="tier-settings">
                    <flux:button variant="ghost" icon="cog-6-tooth">{{ __('Settings') }}</flux:button>
                </flux:modal.trigger>
                <flux:button
                    href="{{ route('admin.sponsored-content.create') }}"
                    wire:navigate
                    variant="primary"
                    icon="plus"
                >
                    {{ __('New Content') }}
                </flux:button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="px-4 sm:px-6 py-4 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">

                {{-- Search --}}
                <div class="relative flex-1 max-w-xs">
                    <flux:icon.magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-[var(--hs-neutral-400)] pointer-events-none" />
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        placeholder="{{ __('Search title or URL…') }}"
                        class="w-full rounded-[var(--hs-radius-sm)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:border-transparent"
                    />
                </div>

                <flux:select wire:model.live="tierFilter" class="w-36" placeholder="{{ __('All Tiers') }}">
                    @foreach ($this->tiers as $tier)
                        <flux:select.option value="{{ $tier->id }}">{{ $tier->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="statusFilter" class="w-40" placeholder="{{ __('All Statuses') }}">
                    <flux:select.option value="active">{{ __('Active now') }}</flux:select.option>
                    <flux:select.option value="scheduled">{{ __('Scheduled') }}</flux:select.option>
                    <flux:select.option value="expired">{{ __('Expired') }}</flux:select.option>
                    <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="placementFilter" class="w-44" placeholder="{{ __('All Placements') }}">
                    <flux:select.option value="homepage">{{ __('Homepage') }}</flux:select.option>
                    <flux:select.option value="category_page">{{ __('Category Page') }}</flux:select.option>
                    <flux:select.option value="search_results">{{ __('Search Results') }}</flux:select.option>
                </flux:select>

            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Title') }}</flux:table.column>
                    <flux:table.column>{{ __('Tier') }}</flux:table.column>
                    <flux:table.column>{{ __('Placements') }}</flux:table.column>
                    <flux:table.column>{{ __('Schedule') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Actions') }}</flux:table.column>
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
                                    <flux:badge color="zinc">{{ __('Inactive') }}</flux:badge>
                                @elseif ($item->starts_at->isFuture())
                                    <flux:badge color="blue">{{ __('Scheduled') }}</flux:badge>
                                @elseif ($item->ends_at->isPast())
                                    <flux:badge color="zinc">{{ __('Expired') }}</flux:badge>
                                @else
                                    <flux:badge color="green">{{ __('Live') }}</flux:badge>
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
                                {{ __('No sponsored content found.') }}
                                <a href="{{ route('admin.sponsored-content.create') }}" wire:navigate class="text-[var(--hs-green-600)] underline ml-1">{{ __('Create your first entry.') }}</a>
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

    {{-- ══ Tier Settings Modal ══════════════════════════════════════ --}}
    <flux:modal name="tier-settings" class="w-full max-w-2xl">
        <div class="space-y-5">

            {{-- Modal header --}}
            <div>
                <flux:heading size="lg">{{ __('Tier Settings') }}</flux:heading>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                    {{ __('Tiers control impression weighting. Higher priority weight = more ad wins.') }}
                </p>
                <div class="mt-3 flex items-start gap-2 rounded-lg bg-[var(--hs-green-50)] border border-[var(--hs-green-100)] px-3 py-2.5 text-xs text-[var(--hs-green-800)]">
                    <flux:icon name="information-circle" class="size-4 shrink-0 mt-0.5 text-[var(--hs-green-600)]" />
                    {{ __('Example: Gold (100) + Silver (50) + Bronze (25) = 175 total. Gold wins ~57%, Silver ~29%, Bronze ~14%.') }}
                </div>
            </div>

            {{-- Tiers table --}}
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Weight') }}</flux:table.column>
                    <flux:table.column>{{ __('Slots') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->allTiers as $tier)
                        @if ($tierEditingId === $tier->id)
                            <flux:table.row>
                                <flux:table.cell>
                                    <flux:input wire:model="tierEditName" size="sm" placeholder="{{ __('Tier name') }}" class="w-32" />
                                    <flux:error name="tierEditName" />
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:input wire:model="tierEditWeight" type="number" min="1" max="10000" size="sm" class="w-20" />
                                    <flux:error name="tierEditWeight" />
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-xs text-[var(--hs-neutral-500)]">{{ $tier->sponsoredContent()->count() }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:switch wire:model="tierEditIsActive" />
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <flux:button wire:click="updateTier" size="sm" variant="primary">{{ __('Save') }}</flux:button>
                                        <flux:button wire:click="cancelTierEdit" size="sm" variant="ghost">{{ __('Cancel') }}</flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @else
                            <flux:table.row>
                                <flux:table.cell>
                                    <span class="font-medium text-[var(--hs-neutral-900)]">{{ $tier->name }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-20 rounded-full bg-[var(--hs-green-100)] overflow-hidden">
                                            <div class="h-full rounded-full bg-[var(--hs-green-500)]" style="width: {{ min(100, round($tier->priority_weight / 100 * 100)) }}%"></div>
                                        </div>
                                        <span class="text-sm tabular-nums text-[var(--hs-neutral-700)]">{{ $tier->priority_weight }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-sm text-[var(--hs-neutral-600)]">{{ $tier->sponsoredContent()->count() }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($tier->is_active)
                                        <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <flux:button wire:click="startTierEdit({{ $tier->id }})" size="sm" variant="ghost" icon="pencil-square" />
                                        <flux:button wire:click="toggleTierActive({{ $tier->id }})" size="sm" variant="ghost" icon="{{ $tier->is_active ? 'eye-slash' : 'eye' }}" />
                                        <flux:button wire:click="confirmTierDelete({{ $tier->id }})" size="sm" variant="ghost" icon="trash" class="text-[var(--hs-danger-500)]" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endif
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-8 text-[var(--hs-neutral-500)]">
                                {{ __('No tiers yet.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            {{-- Add new tier --}}
            <div class="border-t border-[var(--hs-neutral-100)] pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)] mb-3">{{ __('Add New Tier') }}</p>
                <form wire:submit="createTier" class="flex flex-wrap items-end gap-3">
                    <flux:field class="flex-1 min-w-32">
                        <flux:label>{{ __('Name') }}</flux:label>
                        <flux:input wire:model="newTierName" placeholder="{{ __('e.g. Platinum') }}" />
                        <flux:error name="newTierName" />
                    </flux:field>
                    <flux:field class="w-28">
                        <flux:label>{{ __('Weight') }}</flux:label>
                        <flux:input wire:model="newTierWeight" type="number" min="1" max="10000" />
                        <flux:error name="newTierWeight" />
                    </flux:field>
                    <div class="flex items-center gap-2 pb-1">
                        <flux:switch wire:model="newTierIsActive" />
                        <span class="text-sm text-[var(--hs-neutral-700)]">{{ __('Active') }}</span>
                    </div>
                    <flux:button type="submit" variant="primary" icon="plus" size="sm" wire:loading.attr="disabled">
                        {{ __('Add') }}
                    </flux:button>
                </form>
            </div>

            {{-- Close --}}
            <div class="flex justify-end pt-1">
                <flux:button variant="ghost" x-on:click="$flux.modal('tier-settings').close()">
                    {{ __('Close') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete content confirmation --}}
    <x-hs-confirm
        name="delete-confirm"
        title="{{ __('Delete Sponsored Content') }}"
        message="{{ __('This will permanently remove this content and all its event records. This cannot be undone.') }}"
        variant="danger"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="delete" wire:loading.attr="disabled">{{ __('Delete') }}</flux:button>
        </x-slot>
    </x-hs-confirm>

    {{-- Delete tier confirmation --}}
    <x-hs-confirm
        name="delete-tier-confirm"
        title="{{ __('Delete Tier') }}"
        message="{{ __('This will permanently remove this tier. Any sponsored content using it must be reassigned first.') }}"
        variant="danger"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="deleteTier" wire:loading.attr="disabled">{{ __('Delete') }}</flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
