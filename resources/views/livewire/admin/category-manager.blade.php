<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Categories</h1>
            <flux:button variant="primary" icon="plus" wire:click="create">Add Category</flux:button>
        </div>

        {{-- Categories Table --}}
        <div class="overflow-x-auto">
            <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Slug</flux:table.column>
                        <flux:table.column>Description</flux:table.column>
                        <flux:table.column align="end">Actions</flux:table.column>
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
                                        <flux:button variant="ghost" size="sm" icon="pencil" wire:click="edit({{ $category->id }})" />
                                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete({{ $category->id }})" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="text-center">
                                    <x-hs-empty-state icon="folder" title="No categories yet" subtitle="Click 'Add Category' to create one." />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
    </div>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showFormModal" class="md:w-96">
        <div class="space-y-6">
            <flux:heading size="lg">
                {{ $editingCategoryId ? 'Edit Category' : 'Create Category' }}
            </flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model.live.debounce.300ms="name" placeholder="e.g. Bakery Ingredients" />
                <flux:error name="name" />
            </flux:field>

            @if ($name)
                <div>
                    <p class="text-xs text-[var(--hs-neutral-500)] mb-1">Slug preview:</p>
                    <code class="rounded-[var(--hs-radius-tag)] bg-[var(--hs-neutral-100)] px-2 py-0.5 text-xs font-mono text-[var(--hs-neutral-700)]">{{ $this->slugPreview }}</code>
                </div>
            @endif

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="description" rows="3" placeholder="Optional description..." />
                <flux:error name="description" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showFormModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save">
                    {{ $editingCategoryId ? 'Update' : 'Create' }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Category</flux:heading>
                <flux:text class="mt-2">Are you sure you want to delete this category? This action cannot be undone.</flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="delete">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
