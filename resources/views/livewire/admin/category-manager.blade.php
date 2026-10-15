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
        <div>
            <flux:table class="w-full">
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Slug') }}</flux:table.column>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->categoryGroups as $group)
                        {{-- Parent group row --}}
                        <flux:table.row :key="'group-'.$group->id" class="bg-[var(--hs-neutral-50)]">
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-[var(--hs-neutral-900)]">{{ $group->name }}</span>
                                    <span class="inline-flex items-center rounded-full bg-[var(--hs-neutral-100)] px-2 py-0.5 text-[0.65rem] font-medium text-[var(--hs-neutral-500)]">
                                        {{ trans_choice(':count subcategory|:count subcategories', $group->children->count(), ['count' => $group->children->count()]) }}
                                    </span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <code class="rounded-[var(--hs-radius-tag)] bg-[var(--hs-neutral-100)] px-2 py-0.5 text-xs font-mono text-[var(--hs-neutral-700)]">{{ $group->slug }}</code>
                            </flux:table.cell>
                            <flux:table.cell class="w-full max-w-0 whitespace-normal break-words text-[var(--hs-neutral-600)]">{{ $group->description ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    <flux:button variant="ghost" size="sm" icon="pencil" href="{{ route('admin.categories.edit', $group) }}" wire:navigate />
                                    @if ($group->children->isEmpty())
                                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete({{ $group->id }})" />
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>

                        {{-- Subcategory rows --}}
                        @foreach ($group->children as $category)
                            <flux:table.row :key="$category->id" class="hover:bg-[var(--hs-neutral-50)] transition">
                                <flux:table.cell>
                                    <span class="pl-6 text-[var(--hs-neutral-700)]">{{ $category->name }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <code class="rounded-[var(--hs-radius-tag)] bg-[var(--hs-neutral-100)] px-2 py-0.5 text-xs font-mono text-[var(--hs-neutral-700)]">{{ $category->slug }}</code>
                                </flux:table.cell>
                                <flux:table.cell class="w-full max-w-0 whitespace-normal break-words text-[var(--hs-neutral-600)]">{{ $category->description ?? '—' }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        <flux:button variant="ghost" size="sm" icon="pencil" href="{{ route('admin.categories.edit', $category) }}" wire:navigate />
                                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete({{ $category->id }})" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center">
                                <x-hs-empty-state icon="folder" title="{{ __('No categories yet') }}" :subtitle='__("Click Add Category to create one.")' />
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
