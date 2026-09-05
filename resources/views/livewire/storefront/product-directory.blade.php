@php
    /**
     * Category slug → thumbnail gradient + icon key.
     */
    $categoryStyles = [
        'meat-poultry'         => ['bg' => 'from-[#2C4A38] to-[#1C2B21]', 'icon' => 'meat'],
        'processed-foods'      => ['bg' => 'from-[#3E5D4C] to-[#1F3327]', 'icon' => 'processed'],
        'spices-seasonings'    => ['bg' => 'from-[#8A6A24] to-[#4A3C17]', 'icon' => 'spice'],
        'grains-legumes'       => ['bg' => 'from-[#5C6E3E] to-[#2C3A1E]', 'icon' => 'grain'],
        'beverages'            => ['bg' => 'from-[#3A5B6B] to-[#1D2F38]', 'icon' => 'beverage'],
        'dairy-alternatives'   => ['bg' => 'from-[#4A5B75] to-[#232F42]', 'icon' => 'dairy'],
        'condiments-sauces'    => ['bg' => 'from-[#7A4A28] to-[#3D2414]', 'icon' => 'condiment'],
        'snacks-confectionery' => ['bg' => 'from-[#6B4E7C] to-[#352540]', 'icon' => 'snack'],
    ];

    $fallbackStyle = [
        'bg' => 'from-[var(--hs-green-800)] to-[var(--hs-green-950)]',
        'icon' => 'default',
    ];

    $getStyle = fn ($product) =>
        $categoryStyles[$product->categories->first()?->slug ?? ''] ?? $fallbackStyle;
@endphp

<div>

    {{-- ═══════════════════════════════════════════════════════════
         HERO
    ═══════════════════════════════════════════════════════════ --}}
    <section class="border-b border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-12">

            {{-- Eyebrow --}}
            <div class="flex items-center gap-3 mb-4">
                <span class="w-6 h-px bg-[var(--hs-gold-500)]"></span>

                <span class="font-sans text-[11px] tracking-[2.2px] uppercase text-[var(--hs-gold-600)]">
                    {{ __('Verified Supplier Catalog — Philippines') }}
                </span>
            </div>

            {{-- Heading --}}
            <h1 class="font-[var(--hs-font-display)] font-semibold text-4xl sm:text-[46px] tracking-tight leading-tight text-[var(--hs-green-950)] mb-3">
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
                        {{ $this->categories->count() }}
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


            {{-- ═══════════════════════════════════════════════════
                 CATEGORY NAVIGATION
            ═══════════════════════════════════════════════════ --}}
            <div class="max-w-full overflow-hidden">

                <div
                    class="
                        flex
                        w-full
                        max-w-full
                        items-end
                        overflow-x-auto
                        overflow-y-hidden
                        overscroll-x-contain
                        whitespace-nowrap
                        pb-0

                        [&::-webkit-scrollbar]:hidden
                        [-ms-overflow-style:none]
                        [scrollbar-width:none]
                    "
                >

                    {{-- All --}}
                    <button
                        type="button"
                        wire:click="$set('categoryId', null)"
                        @class([
                            'relative flex-none inline-flex items-center gap-1.5',
                            'font-sans text-[11px] tracking-[0.6px] uppercase',
                            'px-[18px] py-3',
                            'whitespace-nowrap',
                            'border-b-2 transition-colors duration-150',
                            'text-[var(--hs-green-950)] border-[var(--hs-gold-500)] font-semibold' => $categoryId === null,
                            'text-[var(--hs-neutral-500)] border-transparent hover:text-[var(--hs-neutral-800)]' => $categoryId !== null,
                        ])
                    >
                        {{ __('All') }}

                        <span
                            @class([
                                'text-[10px] tabular-nums',
                                'text-[var(--hs-gold-600)]' => $categoryId === null,
                                'text-[var(--hs-neutral-400)]' => $categoryId !== null,
                            ])
                        >
                            {{ $this->totalProductCount }}
                        </span>
                    </button>

                    {{-- Categories --}}
                    @foreach ($this->categories as $cat)
                        <button
                            type="button"
                            wire:click="$set('categoryId', {{ $cat->id }})"
                            @class([
                                'relative flex-none inline-flex items-center gap-1.5',
                                'font-sans text-[11px] tracking-[0.6px] uppercase',
                                'px-[18px] py-3',
                                'whitespace-nowrap',
                                'border-b-2 transition-colors duration-150',
                                'text-[var(--hs-green-950)] border-[var(--hs-gold-500)] font-semibold' => $categoryId === $cat->id,
                                'text-[var(--hs-neutral-500)] border-transparent hover:text-[var(--hs-neutral-800)]' => $categoryId !== $cat->id,
                            ])
                        >
                            {{ $cat->name }}

                            <span
                                @class([
                                    'text-[10px] tabular-nums',
                                    'text-[var(--hs-gold-600)]' => $categoryId === $cat->id,
                                    'text-[var(--hs-neutral-400)]' => $categoryId !== $cat->id,
                                ])
                            >
                                {{ $cat->published_products_count }}
                            </span>
                        </button>
                    @endforeach

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

        @if ($this->products->count() > 0)

            {{-- Results Header --}}
            <div class="flex items-end justify-between mb-6">

                <div>
                    <h2 class="font-[var(--hs-font-display)] text-xl font-semibold text-[var(--hs-neutral-900)]">
                        @if ($categoryId)
                            {{ $this->categories->firstWhere('id', $categoryId)?->name ?? 'Products' }}
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
                            {{ __('Halal-certified products in this category') }}
                        @else
                            {{ __('Halal-certified products ready to source') }}
                        @endif
                    </p>
                </div>

                <div class="hidden sm:block text-xs text-[var(--hs-neutral-400)]">
                    {{ $this->products->total() }}
                    {{ Str::plural('product', $this->products->total()) }}
                </div>

            </div>


            {{-- Product Cards --}}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

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

    </section>

</div>