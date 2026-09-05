<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Categories') }}</h1>
            <flux:button variant="primary" icon="plus" href="{{ route('admin.categories.create') }}" wire:navigate>
                {{ __('Add Category') }}
            </flux:button>
        </div>

        {{-- Categories Table --}}
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Slug') }}</flux:table.column>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->categories as $category)
                        <flux:table.row :key="$category->id" class="hover:bg-[var(--hs-neutral-50)] transition">
                            <flux:table.cell variant="strong">{{ $category->name }}</flux:table.cell>
                            <flux:table.cell>
                                <code class="rounded-[var(--hs-radius-tag)] bg-[var(--hs-neutral-100)] px-2 py-0.5 text-xs font-mono text-[var(--hs-neutral-700)]">{{ $category->slug }}</code>
                            </flux:table.cell>
                            <flux:table.cell class="text-[var(--hs-neutral-600)]">{{ Str::limit($category->description ?? '—', 60) }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    <flux:button variant="ghost" size="sm" icon="pencil" href="{{ route('admin.categories.edit', $category) }}" wire:navigate />
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete({{ $category->id }})" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center">
                                <x-hs-empty-state icon="folder" title="{{ __('No categories yet') }}" subtitle="{{ __("Click 'Add Category' to create one.") }}" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <x-hs-confirm
        name="confirm-delete-category"
        title="{{ __('Delete Category') }}"
        message="{{ __('Are you sure you want to delete this category? This action cannot be undone.') }}"
        variant="danger"
        confirmText="{{ __('Delete') }}"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="delete" wire:loading.attr="disabled">
                {{ __('Delete') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
