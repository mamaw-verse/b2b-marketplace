<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                Products
            </h1>

            <flux:button
                variant="primary"
                icon="plus"
                href="{{ route('hub.products.create') }}"
                wire:navigate
            >
                Add Product
            </flux:button>
        </div>

        {{-- Filters --}}
        <div class="px-6 pt-5">
            <div class="flex gap-1.5 bg-[var(--hs-neutral-100)] rounded-[var(--hs-radius-pill)] p-1 w-fit">

                <button
                    wire:click="$set('statusFilter', '')"
                    @class([
                        'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                        'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm' => $statusFilter === '',
                        'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)]' => $statusFilter !== '',
                    ])
                >
                    All
                </button>

                <button
                    wire:click="$set('statusFilter', 'published')"
                    @class([
                        'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                        'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm' => $statusFilter === 'published',
                        'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)]' => $statusFilter !== 'published',
                    ])
                >
                    Published
                </button>

                <button
                    wire:click="$set('statusFilter', 'archived')"
                    @class([
                        'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                        'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm' => $statusFilter === 'archived',
                        'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)]' => $statusFilter !== 'archived',
                    ])
                >
                    Archived
                </button>

            </div>
        </div>

        {{-- Products Table --}}
        <div class="mt-5 overflow-x-auto">
            <flux:table>

                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Variants</flux:table.column>
                    <flux:table.column>Created</flux:table.column>
                    <flux:table.column align="end">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->products as $product)

                        <flux:table.row
                            :key="$product->id"
                            class="hover:bg-[var(--hs-neutral-50)] transition"
                        >

                            {{-- Name --}}
                            <flux:table.cell variant="strong">
                                {{ $product->name }}
                            </flux:table.cell>

                            {{-- Status --}}
                            <flux:table.cell>
                                <x-hs-status-pill :status="$product->status" />
                            </flux:table.cell>

                            {{-- Variants --}}
                            <flux:table.cell>
                                {{ $product->variants_count }}
                            </flux:table.cell>

                            {{-- Created --}}
                            <flux:table.cell class="text-[var(--hs-neutral-600)]">
                                {{ $product->created_at->format('M d, Y') }}
                            </flux:table.cell>

                            {{-- Actions --}}
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">

                                    @if ($product->status === 'published')
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon="arrow-top-right-on-square"
                                            href="{{ route('storefront.product', $product) }}"
                                            target="_blank"
                                            tooltip="{{ __('View Live') }}"
                                        >
                                            {{ __('View Live') }}
                                        </flux:button>
                                    @endif

                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="pencil"
                                        href="{{ route('hub.products.edit', $product) }}"
                                        wire:navigate
                                        tooltip="{{ __('Edit') }}"
                                    />

                                    <flux:button
                                        variant="subtle"
                                        size="sm"
                                        :icon="$product->status === 'published'
                                            ? 'archive-box'
                                            : 'arrow-up-circle'"
                                        wire:click="confirmToggleStatus({{ $product->id }}, '{{ $product->status === 'published' ? 'archive' : 'publish' }}')"
                                        wire:target="confirmToggleStatus"
                                        wire:loading.attr="disabled"
                                        tooltip="{{ $product->status === 'published' ? __('Archive') : __('Publish') }}"
                                    />

                                </div>
                            </flux:table.cell>

                        </flux:table.row>

                    @empty

                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center">
                                <x-hs-empty-state
                                    icon="package"
                                    title="No products yet"
                                    subtitle="Create your first product to get started."
                                />
                            </flux:table.cell>
                        </flux:table.row>

                    @endforelse
                </flux:table.rows>

            </flux:table>
        </div>

    </div>

    {{-- Archive Product Confirmation --}}
    <x-hs-confirm
        name="archive-product-confirm"
        title="Archive Product"
        message="Archive this product?"
        variant="warning"
        confirmText="Archive"
    >
        <x-slot name="action">
            <flux:button variant="filled" wire:click="toggleStatus" wire:loading.attr="disabled">
                {{ __('Archive') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    {{-- Publish Product Confirmation --}}
    <x-hs-confirm
        name="publish-product-confirm"
        title="Publish Product"
        message="Publish this product?"
        variant="primary"
        confirmText="Publish"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="toggleStatus" wire:loading.attr="disabled">
                {{ __('Publish') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>