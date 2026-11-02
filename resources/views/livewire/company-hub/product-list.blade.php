<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                {{ __('Products') }}
            </h1>

            <flux:button
                variant="primary"
                icon="plus"
                href="{{ route('hub.products.create') }}"
                wire:navigate
            >
                {{ __('Add Product') }}
            </flux:button>
        </div>

        {{-- Subscription banner --}}
        @if (! $this->isSubscribed)
            <div class="mx-6 mt-5 rounded-[var(--hs-radius-card)] border border-yellow-200 bg-yellow-50 p-4">
                <div class="flex items-start gap-3">
                    <flux:icon name="exclamation-triangle" class="mt-0.5 size-5 shrink-0 text-yellow-600" />
                    <div class="flex-1 text-sm">
                        <p class="font-semibold text-yellow-900">{{ __('Your business profile and products are not visible on the marketplace') }}</p>
                        <p class="mt-0.5 text-yellow-700">
                            {{ __('Subscribe to make your company profile and products discoverable to buyers.') }}
                            <a
                                href="{{ route('hub.subscription') }}"
                                wire:navigate
                                class="font-medium underline hover:text-yellow-900"
                            >{{ __('Go to Subscription') }}</a>
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Filters --}}
        <div class="px-4 sm:px-6 pt-5">
            <div class="flex gap-1.5 bg-[var(--hs-neutral-100)] rounded-[var(--hs-radius-pill)] p-1 w-full sm:w-fit overflow-x-auto scrollbar-hide">

                <button
                    wire:click="$set('statusFilter', '')"
                    @class([
                        'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                        'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm' => $statusFilter === '',
                        'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)]' => $statusFilter !== '',
                    ])
                >
                    {{ __('All') }}
                </button>

                <button
                    wire:click="$set('statusFilter', 'published')"
                    @class([
                        'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                        'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm' => $statusFilter === 'published',
                        'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)]' => $statusFilter !== 'published',
                    ])
                >
                    {{ __('Published') }}
                </button>

                <button
                    wire:click="$set('statusFilter', 'archived')"
                    @class([
                        'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                        'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm' => $statusFilter === 'archived',
                        'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)]' => $statusFilter !== 'archived',
                    ])
                >
                    {{ __('Archived') }}
                </button>

            </div>
        </div>

        {{-- Products: card list on mobile, table on sm+ --}}
        <div class="mt-5">

            {{-- Mobile card list (hidden on sm+) --}}
            <div class="sm:hidden divide-y divide-[var(--hs-neutral-100)]">
                @forelse ($this->products as $product)
                    <div class="flex items-start justify-between gap-3 px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-[var(--hs-neutral-900)] truncate">{{ $product->name }}</p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                <x-hs-status-pill :status="$product->status" />
                                <span class="text-xs text-[var(--hs-neutral-500)]">
                                    {{ trans_choice(':count variant|:count variants', $product->variants_count, ['count' => $product->variants_count]) }}
                                </span>
                                <span class="text-xs text-[var(--hs-neutral-400)]">{{ $product->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            @if ($product->status === 'published')
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="arrow-top-right-on-square"
                                    href="{{ route('storefront.product', $product) }}"
                                    target="_blank"
                                >
                                    <span class="sm:hidden">{{ __('View') }}</span>
                                    <span class="hidden sm:inline">{{ __('View Live') }}</span>
                                </flux:button>
                            @endif
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="pencil"
                                href="{{ route('hub.products.edit', $product) }}"
                                wire:navigate
                            >
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button
                                variant="subtle"
                                size="sm"
                                :icon="$product->status === 'published' ? 'archive-box' : 'arrow-up-circle'"
                                wire:click="confirmToggleStatus({{ $product->id }}, '{{ $product->status === 'published' ? 'archive' : 'publish' }}')"
                                wire:target="confirmToggleStatus"
                                wire:loading.attr="disabled"
                            >
                                {{ $product->status === 'published' ? __('Archive') : __('Publish') }}
                            </flux:button>
                            @if ($product->status === 'archived')
                                <flux:button
                                    variant="danger"
                                    size="sm"
                                    icon="trash"
                                    wire:click="confirmDeleteProduct({{ $product->id }})"
                                    wire:target="confirmDeleteProduct"
                                    wire:loading.attr="disabled"
                                >
                                    {{ __('Delete') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-4">
                        <x-hs-empty-state
                            icon="package"
                            title="{{ __('No products yet') }}"
                            subtitle="{{ __('Create your first product to get started.') }}"
                        />
                    </div>
                @endforelse
            </div>

            {{-- Desktop table (hidden on mobile) --}}
            <div class="hidden sm:block overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Name') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column>{{ __('Variants') }}</flux:table.column>
                        <flux:table.column>{{ __('Created') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->products as $product)
                            <flux:table.row :key="$product->id" class="hover:bg-[var(--hs-neutral-50)] transition">
                                <flux:table.cell variant="strong">{{ $product->name }}</flux:table.cell>
                                <flux:table.cell><x-hs-status-pill :status="$product->status" /></flux:table.cell>
                                <flux:table.cell>{{ $product->variants_count }}</flux:table.cell>
                                <flux:table.cell class="text-[var(--hs-neutral-600)]">{{ $product->created_at->format('M d, Y') }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        @if ($product->status === 'published')
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                icon="arrow-top-right-on-square"
                                                href="{{ route('storefront.product', $product) }}"
                                                target="_blank"
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
                                        >
                                            {{ __('Edit') }}
                                        </flux:button>
                                        <flux:button
                                            variant="subtle"
                                            size="sm"
                                            :icon="$product->status === 'published' ? 'archive-box' : 'arrow-up-circle'"
                                            wire:click="confirmToggleStatus({{ $product->id }}, '{{ $product->status === 'published' ? 'archive' : 'publish' }}')"
                                            wire:target="confirmToggleStatus"
                                            wire:loading.attr="disabled"
                                        >
                                            {{ $product->status === 'published' ? __('Archive') : __('Publish') }}
                                        </flux:button>
                                        @if ($product->status === 'archived')
                                            <flux:button
                                                variant="danger"
                                                size="sm"
                                                icon="trash"
                                                wire:click="confirmDeleteProduct({{ $product->id }})"
                                                wire:target="confirmDeleteProduct"
                                                wire:loading.attr="disabled"
                                            >
                                                {{ __('Delete') }}
                                            </flux:button>
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center">
                                    <x-hs-empty-state
                                        icon="package"
                                        title="{{ __('No products yet') }}"
                                        subtitle="{{ __('Create your first product to get started.') }}"
                                    />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>

    </div>

    {{-- Archive Product Confirmation --}}
    <x-hs-confirm
        name="archive-product-confirm"
        title="{{ __('Archive Product') }}"
        message="{{ __('Archive this product?') }}"
        variant="warning"
        confirmText="{{ __('Archive') }}"
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
        title="{{ __('Publish Product') }}"
        message="{{ __('Publish this product?') }}"
        variant="primary"
        confirmText="{{ __('Publish') }}"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="toggleStatus" wire:loading.attr="disabled">
                {{ __('Publish') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    {{-- Delete Product Confirmation --}}
    <x-hs-confirm
        name="delete-product-confirm"
        title="{{ __('Delete Product') }}"
        message="{{ __('Permanently delete this archived product? This cannot be undone.') }}"
        variant="danger"
        confirmText="{{ __('Delete') }}"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="deleteProduct" wire:loading.attr="disabled">
                {{ __('Delete') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    {{-- Subscription Required --}}
    <flux:modal name="subscription-required" class="w-full sm:w-auto md:w-[420px] !p-0">
        <div class="flex flex-col">
            <div class="px-4 sm:px-6 py-5">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 flex items-center justify-center size-10 rounded-full text-yellow-600 bg-yellow-50">
                        <flux:icon name="exclamation-triangle" variant="mini" class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-[var(--hs-neutral-900)]">{{ __('Active Subscription Required') }}</h3>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">
                            {{ __('You need an active subscription to publish products and make them visible to buyers on the marketplace.') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="shrink-0 border-t border-[var(--hs-neutral-100)] px-4 sm:px-6 py-4">
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" href="{{ route('hub.subscription') }}" wire:navigate>
                        {{ __('Go to Subscription') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>