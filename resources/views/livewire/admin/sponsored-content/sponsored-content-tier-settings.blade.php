<div class="space-y-6">

    {{-- ══ Header card ══════════════════════════════════════════════ --}}
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Tier Settings') }}</h1>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                    {{ __('Tiers control how sponsored content is weighted during random ad selection. Higher priority weight = proportionally more impressions.') }}
                </p>
            </div>
            <flux:button
                href="{{ route('admin.sponsored-content.index') }}"
                wire:navigate
                variant="ghost"
                icon="arrow-left"
                size="sm"
            >
                {{ __('Back to List') }}
            </flux:button>
        </div>

        {{-- How weighting works --}}
        <div class="px-6 py-4 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            <div class="flex items-start gap-3 text-sm text-[var(--hs-neutral-600)]">
                <flux:icon name="information-circle" class="size-5 shrink-0 text-[var(--hs-green-600)] mt-0.5" />
                <p>
                    {{ __('Example: Gold (100) + Silver (50) + Bronze (25) = 175 total weight. Gold wins ~57% of auctions, Silver ~29%, Bronze ~14%.') }}
                </p>
            </div>
        </div>

        {{-- Tier table --}}
        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Priority Weight') }}</flux:table.column>
                    <flux:table.column>{{ __('Ad Slots') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->tiers as $tier)
                        @if ($editingId === $tier->id)
                            {{-- ── Inline edit row ── --}}
                            <flux:table.row>
                                <flux:table.cell>
                                    <flux:input
                                        wire:model="editName"
                                        size="sm"
                                        placeholder="{{ __('Tier name') }}"
                                        class="w-40"
                                    />
                                    <flux:error name="editName" />
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:input
                                        wire:model="editWeight"
                                        type="number"
                                        min="1"
                                        max="10000"
                                        size="sm"
                                        class="w-24"
                                    />
                                    <flux:error name="editWeight" />
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-xs text-[var(--hs-neutral-500)]">{{ $tier->sponsoredContent()->count() }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <flux:switch wire:model="editIsActive" />
                                        <span class="text-xs text-[var(--hs-neutral-600)]">{{ $editIsActive ? __('Active') : __('Inactive') }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <flux:button wire:click="updateTier" size="sm" variant="primary">{{ __('Save') }}</flux:button>
                                        <flux:button wire:click="cancelEdit" size="sm" variant="ghost">{{ __('Cancel') }}</flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @else
                            {{-- ── Display row ── --}}
                            <flux:table.row>
                                <flux:table.cell>
                                    <span class="font-medium text-[var(--hs-neutral-900)]">{{ $tier->name }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 rounded-full bg-[var(--hs-green-100)] w-24 overflow-hidden">
                                            <div
                                                class="h-full rounded-full bg-[var(--hs-green-500)]"
                                                style="width: {{ min(100, round($tier->priority_weight / 100 * 100)) }}%"
                                            ></div>
                                        </div>
                                        <span class="text-sm tabular-nums text-[var(--hs-neutral-700)]">{{ $tier->priority_weight }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-sm text-[var(--hs-neutral-600)]">{{ $tier->sponsoredContent()->count() }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($tier->is_active)
                                        <flux:badge color="green">{{ __('Active') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc">{{ __('Inactive') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <flux:button wire:click="startEdit({{ $tier->id }})" size="sm" variant="ghost" icon="pencil-square" />
                                        <flux:button
                                            wire:click="toggleActive({{ $tier->id }})"
                                            size="sm"
                                            variant="ghost"
                                            icon="{{ $tier->is_active ? 'eye-slash' : 'eye' }}"
                                            title="{{ $tier->is_active ? __('Deactivate') : __('Activate') }}"
                                        />
                                        <flux:button
                                            wire:click="confirmDelete({{ $tier->id }})"
                                            size="sm"
                                            variant="ghost"
                                            icon="trash"
                                            class="text-[var(--hs-danger-500)]"
                                        />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endif
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-10 text-[var(--hs-neutral-500)]">
                                {{ __('No tiers yet. Create one below.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    {{-- ══ Create new tier ══════════════════════════════════════════ --}}
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] px-6 py-5">
        <h2 class="text-base font-semibold text-[var(--hs-neutral-900)] mb-4">{{ __('Add New Tier') }}</h2>

        <form wire:submit="createTier" class="flex flex-col sm:flex-row sm:items-end gap-4">
            <flux:field class="flex-1">
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="newName" placeholder="{{ __('e.g. Platinum') }}" />
                <flux:error name="newName" />
            </flux:field>

            <flux:field class="w-36">
                <flux:label>{{ __('Priority Weight') }}</flux:label>
                <flux:input wire:model="newWeight" type="number" min="1" max="10000" />
                <flux:error name="newWeight" />
            </flux:field>

            <div class="flex items-center gap-3 pb-1">
                <flux:switch wire:model="newIsActive" />
                <span class="text-sm text-[var(--hs-neutral-700)]">{{ __('Active') }}</span>
            </div>

            <flux:button type="submit" variant="primary" icon="plus" wire:loading.attr="disabled">
                {{ __('Add Tier') }}
            </flux:button>
        </form>
    </div>

    {{-- ══ Delete confirmation modal ═══════════════════════════════ --}}
    <x-hs-confirm
        name="delete-tier-confirm"
        title="{{ __('Delete Tier') }}"
        message="{{ __('This will permanently remove this tier. Any sponsored content using it must be reassigned first.') }}"
        variant="danger"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="deleteTier" wire:loading.attr="disabled">
                {{ __('Delete') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

</div>
