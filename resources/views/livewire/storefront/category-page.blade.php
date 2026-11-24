<div>
    {{-- ═══════════════════════════════════════════════════════════
         HERO — breadcrumb, h1, intro and subcategory links
    ═══════════════════════════════════════════════════════════ --}}
    <section class="border-b border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-6 pb-6 sm:pt-10 sm:pb-10">

            <nav class="mb-4 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-[var(--hs-neutral-500)]" aria-label="{{ __('Breadcrumb') }}">
                <a href="{{ route('home') }}" class="hover:text-[var(--hs-green-700)] transition" wire:navigate>{{ __('Home') }}</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('storefront.products') }}" class="hover:text-[var(--hs-green-700)] transition" wire:navigate>{{ __('Products') }}</a>
                @if ($category->parent)
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('storefront.category', $category->parent) }}" class="hover:text-[var(--hs-green-700)] transition" wire:navigate>{{ $category->parent->name }}</a>
                @endif
                <span aria-hidden="true">/</span>
                <span class="font-medium text-[var(--hs-neutral-700)]" aria-current="page">{{ $category->name }}</span>
            </nav>

            <h1 class="font-[var(--hs-font-display)] font-semibold text-2xl sm:text-4xl tracking-tight leading-tight text-[var(--hs-neutral-900)] mb-2 sm:mb-3">
                {{ $category->name }}
            </h1>

            <p class="max-w-3xl text-sm sm:text-base text-[var(--hs-neutral-600)]">
                {{ $category->description ?: __('Browse halal-certified :category products from verified suppliers on :app.', ['category' => $category->name, 'app' => config('app.name')]) }}
            </p>

            <div class="mt-4 flex flex-wrap items-baseline gap-x-4 gap-y-1.5 text-sm text-[var(--hs-neutral-500)]">
                <span class="font-sans text-xs text-[var(--hs-neutral-800)] border-l-2 border-[var(--hs-neutral-300)] pl-3">
                    <b class="text-[var(--hs-green-700)] font-semibold">{{ $this->totalProductCount }}</b>
                    {{ trans_choice('product|products', $this->totalProductCount) }}
                </span>
                <span class="font-sans text-xs text-[var(--hs-neutral-800)] border-l-2 border-[var(--hs-neutral-300)] pl-3">
                    <b class="text-[var(--hs-green-700)] font-semibold">{{ $this->totalSupplierCount }}</b>
                    {{ trans_choice('supplier|suppliers', $this->totalSupplierCount) }}
                </span>
            </div>

            @if ($this->subcategories->isNotEmpty())
                <div class="mt-6">
                    <h2 class="mb-2 text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)]">{{ __('Subcategories') }}</h2>
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($this->subcategories as $child)
                            <li wire:key="subcategory-{{ $child->id }}">
                                <a
                                    href="{{ route('storefront.category', $child) }}"
                                    wire:navigate
                                    class="inline-flex min-h-11 items-center gap-2 rounded-[var(--hs-radius-pill)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-4 py-2 text-sm font-medium text-[var(--hs-neutral-700)] transition hover:border-[var(--hs-neutral-300)] hover:bg-[var(--hs-neutral-50)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--hs-green-600)]"
                                >
                                    <span>{{ $child->name }}</span>
                                    <span class="text-xs tabular-nums text-[var(--hs-neutral-500)]">{{ $child->published_products_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         PRODUCT GRID
    ═══════════════════════════════════════════════════════════ --}}
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-10 pb-20">
        @if ($this->products->count() > 0)
            <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 xl:grid-cols-4">
                @foreach ($this->products as $product)
                    <x-hs-product-card :product="$product" />
                @endforeach
            </div>

            @if ($this->products->hasPages())
                <div class="mt-10 pt-6 border-t border-[var(--hs-neutral-200)]">
                    {{ $this->products->links() }}
                </div>
            @endif
        @else
            <div class="py-16 sm:py-24">
                <x-hs-empty-state
                    icon="package"
                    title="{{ __('No products found') }}"
                    subtitle="{{ __('Try adjusting your search or selecting a different category.') }}"
                />
                <p class="mt-6 text-center">
                    <a href="{{ route('storefront.products') }}" wire:navigate class="text-sm font-medium text-[var(--hs-green-700)] hover:underline">{{ __('Browse all products') }}</a>
                </p>
            </div>
        @endif
    </section>
</div>
