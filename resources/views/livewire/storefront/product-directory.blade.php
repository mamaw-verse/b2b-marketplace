<div>

    {{-- ═══════════════════════════════════════════════════════════
         HERO
    ═══════════════════════════════════════════════════════════ --}}
    <section class="border-b border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-12 pb-12">

            {{-- Eyebrow --}}
            <div class="flex items-center gap-3 mb-4">
                <span class="w-6 h-px bg-[var(--hs-gold-500)]"></span>

                <span class="font-sans text-[11px] tracking-[2.2px] uppercase text-[var(--hs-gold-600)]">
                    {{ __('Verified Supplier Catalog — Philippines') }}
                </span>
            </div>

            {{-- Heading --}}
            <h1 class="font-[var(--hs-font-display)] font-semibold text-4xl sm:text-[46px] tracking-tight leading-tight text-[var(--hs-neutral-900)] mb-3">
                {{ __('Product Directory') }}
            </h1>

            {{-- Description + Stats --}}
            <div class="flex flex-wrap items-baseline gap-x-5 gap-y-2 text-[var(--hs-neutral-500)] text-sm mb-8 max-w-3xl">
                <span>
                    {{ __('Browse halal-certified products sourced directly from audited suppliers.') }}
                </span>

                <span class="font-sans text-xs text-[var(--hs-neutral-800)] border-l-2 border-[var(--hs-neutral-300)] pl-3">
                    <b class="text-[var(--hs-green-700)] font-semibold">
                        {{ $this->totalProductCount }}
                    </b>
                    {{ __('products') }}
                </span>

                <span class="font-sans text-xs text-[var(--hs-neutral-800)] border-l-2 border-[var(--hs-neutral-300)] pl-3">
                    <b class="text-[var(--hs-green-700)] font-semibold">
                        {{ $this->totalSupplierCount }}
                    </b>
                    {{ __('suppliers') }}
                </span>

                <span class="font-sans text-xs text-[var(--hs-neutral-800)] border-l-2 border-[var(--hs-neutral-300)] pl-3">
                    <b class="text-[var(--hs-green-700)] font-semibold">
                        {{ $this->totalCategoryCount }}
                    </b>
                    {{ __('categories') }}
                </span>
            </div>


            {{-- ═══════════════════════════════════════════════════
                 SEARCH
            ═══════════════════════════════════════════════════ --}}
            <div class="mb-6 max-w-2xl">
                <div class="relative">

                    <svg
                        class="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-[var(--hs-neutral-400)]"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                        />
                    </svg>

                    <input
                        wire:model.live.debounce.300ms="query"
                        type="search"
                        placeholder="{{ __('Search products...') }}"
                        class="w-full rounded-[var(--hs-radius-pill)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] pl-10 pr-4 py-2.5 text-sm text-[var(--hs-neutral-900)] placeholder:text-[var(--hs-neutral-400)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:border-transparent shadow-[var(--hs-shadow-sm)] transition"
                    />

                </div>
            </div>


        </div>
    </section>

    {{-- ── Sponsored Content — Category Page Placement ────── --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <livewire:shared.sponsored-content-banner placement="category_page" />
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PRODUCT GRID
    ═══════════════════════════════════════════════════════════ --}}
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 pb-20">

        <div class="flex flex-col gap-8 lg:flex-row">

            {{-- ═══════════════════════════════════════════════
                 SIDEBAR: CATEGORY TREE
            ═══════════════════════════════════════════════ --}}
            <aside class="w-full lg:w-64 shrink-0 lg:sticky lg:top-24 lg:self-start" x-data="{ open: false }">

                <button
                    @click="open = !open"
                    class="lg:hidden flex items-center justify-between w-full py-2 px-3 rounded-lg text-sm font-semibold text-[var(--hs-neutral-700)] bg-[var(--hs-neutral-100)] hover:bg-[var(--hs-neutral-200)] transition mb-1"
                    :aria-expanded="open"
                >
                    <span>{{ __('Categories') }}</span>
                    <svg class="size-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </button>

                <nav
                    class="space-y-1 lg:block max-h-[calc(100dvh-9rem)] overflow-y-auto overscroll-contain lg:pr-1"
                    :class="open ? 'block' : 'hidden'"
                    x-cloak
                >

                    <h3 class="hidden lg:block text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)] mb-3 px-3">
                        {{ __('Categories') }}
                    </h3>

                    <button
                        wire:click="$set('categoryId', null)"
                        @class([
                            'flex w-full items-center rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200',
                            'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-inset ring-[var(--hs-green-200)]' => $categoryId === null,
                            'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => $categoryId !== null,
                        ])
                    >
                        @if ($categoryId === null)
                            <span class="mr-2 flex size-1.5 shrink-0 rounded-full bg-[var(--hs-green-500)]" aria-hidden="true"></span>
                        @endif
                        <span class="truncate">{{ __('All Categories') }}</span>
                        <span class="ml-auto pl-2 text-[10px] tabular-nums text-[var(--hs-neutral-400)]">{{ $this->totalProductCount }}</span>
                    </button>

                    @foreach ($this->categoryGroups as $group)
                        <div wire:key="category-group-{{ $group->id }}" class="pt-2 first:pt-0">
                            <button
                                wire:click="$set('categoryId', {{ $group->id }})"
                                @class([
                                    'flex w-full items-center rounded-lg px-3 py-2 text-sm font-semibold transition-all duration-200',
                                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-inset ring-[var(--hs-green-200)]' => $categoryId === $group->id,
                                    'text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-100)]' => $categoryId !== $group->id,
                                ])
                            >
                                @if ($categoryId === $group->id)
                                    <span class="mr-2 flex size-1.5 shrink-0 rounded-full bg-[var(--hs-green-500)]" aria-hidden="true"></span>
                                @endif
                                <span class="truncate">{{ $group->name }}</span>
                                <span
                                    @class([
                                        'ml-auto pl-2 text-[10px] tabular-nums',
                                        'text-[var(--hs-green-600)]' => $categoryId === $group->id,
                                        'text-[var(--hs-neutral-400)]' => $categoryId !== $group->id,
                                    ])
                                >{{ $group->published_products_count }}</span>
                            </button>

                            @if ($group->children->isNotEmpty())
                                <div class="mt-1 space-y-1">
                                    @foreach ($group->children as $category)
                                        <button
                                            wire:key="category-{{ $category->id }}"
                                            wire:click="$set('categoryId', {{ $category->id }})"
                                            @class([
                                                'flex w-full items-center rounded-lg py-1.5 pl-8 pr-3 text-sm font-medium transition-all duration-200',
                                                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-inset ring-[var(--hs-green-200)]' => $categoryId === $category->id,
                                                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => $categoryId !== $category->id,
                                            ])
                                        >
                                            <span class="truncate">{{ $category->name }}</span>
                                            <span
                                                @class([
                                                    'ml-auto pl-2 text-[10px] tabular-nums',
                                                    'text-[var(--hs-green-600)]' => $categoryId === $category->id,
                                                    'text-[var(--hs-neutral-400)]' => $categoryId !== $category->id,
                                                ])
                                            >{{ $category->published_products_count }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach

                </nav>
            </aside>

            {{-- ═══════════════════════════════════════════════
                 PRODUCT GRID
            ═══════════════════════════════════════════════ --}}
            <div class="flex-1 min-w-0">

                @if ($this->products->count() > 0)

                    {{-- Results Header --}}
                    <div class="flex items-end justify-between mb-6">

                        <div>
                            <h2 class="font-[var(--hs-font-display)] text-xl font-semibold text-[var(--hs-neutral-900)]">
                                @if ($categoryId)
                                    {{ $this->selectedCategory?->display_name ?? __('Products') }}
                                @elseif (filled($query))
                                    {{ __('Search results') }}
                                @else
                                    {{ __('Latest Products') }}
                                @endif
                            </h2>

                            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                                @if (filled($query))
                                    {{ __('Products matching') }}
                                    <span class="font-medium text-[var(--hs-neutral-700)]">
                                        "{{ $query }}"
                                    </span>
                                @elseif ($categoryId)
                                    {{ $this->selectedCategory?->description ?: __('Halal-certified products in this category') }}
                                @else
                                    {{ __('Halal-certified products ready to source') }}
                                @endif
                            </p>
                        </div>

                        <div class="hidden sm:block shrink-0 pl-4 text-xs text-[var(--hs-neutral-400)]">
                            {{ $this->products->total() }}
                            {{ Str::plural('product', $this->products->total()) }}
                        </div>

                    </div>


                    {{-- Product Cards --}}
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">

                        @foreach ($this->products as $product)
                            <x-hs-product-card :product="$product" />
                        @endforeach

                    </div>


                    {{-- Pagination --}}
                    @if ($this->products->hasPages())
                        <div class="mt-10 pt-6 border-t border-[var(--hs-neutral-200)]">
                            {{ $this->products->links() }}
                        </div>
                    @endif

                @else

                    {{-- Empty State --}}
                    <div class="py-24">
                        <x-hs-empty-state
                            icon="package"
                            title="{{ __('No products found') }}"
                            subtitle="{{ __('Try adjusting your search or selecting a different category.') }}"
                        />
                    </div>

                @endif

            </div>

        </div>

    </section>

</div>