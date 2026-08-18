<div>
    {{-- ═══════════════════════════════════════════════════════════
         HERO SECTION
    ═══════════════════════════════════════════════════════════ --}}
    <section class="relative bg-[var(--hs-green-950)] overflow-hidden">
        {{-- Hero background image --}}
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-20" style="background-image: url('/images/hero-bg.png'); background-position: center -40px;"></div>

        {{-- Dark green tint --}}
        <div class="absolute inset-0" style="background-color: rgba(5, 41, 27, 0.88);"></div>

        {{-- Radial glow --}}
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,rgba(134,239,172,0.12),transparent)]"></div>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-20 pb-28">
            <div class="text-center">
                {{-- Trust badge --}}
                <div class="mb-5 flex justify-center">
                    <span class="inline-flex items-center gap-2 rounded-full border border-[var(--hs-green-700)] bg-[var(--hs-green-900)]/60 px-4 py-1.5 text-xs font-semibold text-[var(--hs-green-300)] uppercase tracking-widest">
                        <svg class="size-3.5 text-[var(--hs-gold-400)]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1a.75.75 0 0 1 .671.415l1.828 3.703 4.087.594a.75.75 0 0 1 .416 1.279l-2.958 2.88.699 4.064a.75.75 0 0 1-1.088.791L10 12.347l-3.655 1.92a.75.75 0 0 1-1.088-.79l.699-4.065-2.958-2.88a.75.75 0 0 1 .416-1.28l4.087-.593L9.33 1.415A.75.75 0 0 1 10 1Z" clip-rule="evenodd" /></svg>
                        Trusted Halal B2B Marketplace
                    </span>
                </div>

                {{-- Headline --}}
                <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl font-[var(--hs-font-display)] leading-tight">
                    Source Halal-Certified<br class="hidden sm:block">
                    <span class="text-[var(--hs-green-300)]">Products & Suppliers</span>
                </h1>

                <p class="mt-5 text-base sm:text-lg text-[var(--hs-neutral-300)] max-w-2xl mx-auto leading-relaxed">
                    The verified halal B2B directory connecting manufacturers, processors and suppliers with buyers across Southeast Asia and beyond.
                </p>

                {{-- Stats Row --}}
                <div class="mt-10 flex flex-wrap justify-center gap-x-10 gap-y-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white tabular-nums">{{ number_format($this->stats['companies']) }}+</div>
                        <div class="mt-0.5 text-xs text-[var(--hs-neutral-400)] uppercase tracking-wider">Verified Companies</div>
                    </div>
                    <div class="w-px bg-[var(--hs-green-800)] hidden sm:block self-stretch"></div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white tabular-nums">{{ number_format($this->stats['products']) }}+</div>
                        <div class="mt-0.5 text-xs text-[var(--hs-neutral-400)] uppercase tracking-wider">Halal Products</div>
                    </div>
                    <div class="w-px bg-[var(--hs-green-800)] hidden sm:block self-stretch"></div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white tabular-nums">{{ $this->stats['categories'] }}</div>
                        <div class="mt-0.5 text-xs text-[var(--hs-neutral-400)] uppercase tracking-wider">Categories</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         FLOATING SEARCH PANEL
    ═══════════════════════════════════════════════════════════ --}}
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 -mt-12 relative z-10">
        <div class="rounded-2xl bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-xl)] border border-[var(--hs-neutral-100)] p-5">
            {{-- Search Input --}}
            <div class="flex gap-2">
                <div class="flex-1 relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-5 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input
                        wire:model.live.debounce.300ms="query"
                        type="search"
                        placeholder="Search companies, products, categories…"
                        class="w-full rounded-full border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:border-transparent"
                    />
                </div>
                <button class="rounded-full bg-[var(--hs-green-600)] px-6 py-3 text-sm font-semibold text-white hover:bg-[var(--hs-green-700)] transition shadow-sm whitespace-nowrap">
                    Search
                </button>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         COMBINED SEARCH RESULTS
    ═══════════════════════════════════════════════════════════ --}}
    @if (mb_strlen($query) >= 2)
        @php
            $hasCompanies = count($companyResults) > 0;
            $hasProducts  = count($productResults) > 0;
            $hasAny       = $hasCompanies || $hasProducts;
        @endphp
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
            <p class="text-sm text-[var(--hs-neutral-500)] mb-8">
                Showing results for <span class="font-semibold text-[var(--hs-neutral-900)]">"{{ $query }}"</span>
            </p>

            @if ($hasAny)
                <div class="grid grid-cols-1 gap-10 lg:grid-cols-2">

                    {{-- ── Companies column ── --}}
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)]">
                                Companies <span class="ml-1 text-[var(--hs-neutral-400)]">({{ count($companyResults) }})</span>
                            </h2>
                            @if ($hasCompanies)
                                <a href="{{ route('storefront.companies') }}" wire:navigate class="text-xs font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] transition">
                                    View all →
                                </a>
                            @endif
                        </div>

                        @if ($hasCompanies)
                            <div class="space-y-2">
                                @foreach ($companyResults as $result)
                                    <a href="{{ route('storefront.company', $result['id']) }}" wire:navigate
                                       class="group flex items-center gap-3 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-4 py-3 shadow-[var(--hs-shadow-sm)] transition hover:shadow-[var(--hs-shadow-md)] hover:border-[var(--hs-green-200)]">
                                        <x-hs-avatar :initials="strtoupper(mb_substr($result['storefront_name'] ?? '??', 0, 2))" size="sm" />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-[var(--hs-neutral-900)] group-hover:text-[var(--hs-green-700)]">
                                                {{ $result['storefront_name'] ?? 'Unknown' }}
                                            </p>
                                            <p class="text-xs text-[var(--hs-neutral-500)] truncate">
                                                {{ $result['city'] ?? '' }}{{ ! empty($result['city']) && ! empty($result['province']) ? ', ' : '' }}{{ $result['province'] ?? '' }}
                                            </p>
                                        </div>
                                        <svg class="size-4 text-[var(--hs-neutral-300)] shrink-0 group-hover:text-[var(--hs-green-500)] transition" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-[var(--hs-neutral-400)] py-6 text-center border border-dashed border-[var(--hs-neutral-200)] rounded-xl">
                                No companies matched "{{ $query }}"
                            </p>
                        @endif
                    </div>

                    {{-- ── Products column ── --}}
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)]">
                                Products <span class="ml-1 text-[var(--hs-neutral-400)]">({{ count($productResults) }})</span>
                            </h2>
                            @if ($hasProducts)
                                <a href="{{ route('storefront.products') }}" wire:navigate class="text-xs font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] transition">
                                    View all →
                                </a>
                            @endif
                        </div>

                        @if ($hasProducts)
                            <div class="space-y-2">
                                @foreach ($productResults as $result)
                                    <a href="{{ route('storefront.product', $result['id']) }}" wire:navigate
                                       class="group flex items-center gap-3 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-4 py-3 shadow-[var(--hs-shadow-sm)] transition hover:shadow-[var(--hs-shadow-md)] hover:border-[var(--hs-green-200)]">
                                        <div class="size-10 rounded-lg bg-[var(--hs-green-50)] flex items-center justify-center shrink-0 overflow-hidden">
                                            <svg class="size-5 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-[var(--hs-neutral-900)] group-hover:text-[var(--hs-green-700)]">
                                                {{ $result['name'] ?? 'Unknown' }}
                                            </p>
                                            <p class="text-xs text-[var(--hs-neutral-500)] truncate">
                                                {{ $result['shop_name'] ?? '' }}
                                            </p>
                                        </div>
                                        <svg class="size-4 text-[var(--hs-neutral-300)] shrink-0 group-hover:text-[var(--hs-green-500)] transition" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-[var(--hs-neutral-400)] py-6 text-center border border-dashed border-[var(--hs-neutral-200)] rounded-xl">
                                No products matched "{{ $query }}"
                            </p>
                        @endif
                    </div>

                </div>
            @else
                <x-hs-empty-state icon="search" title="No results found" :subtitle="'No matches for &quot;' . $query . '&quot;. Try a different search term.'" />
            @endif
        </section>

    @else
        {{-- ═══════════════════════════════════════════════════════
             DEFAULT HOME CONTENT (when no search)
        ═══════════════════════════════════════════════════════ --}}

        {{-- Categories Row --}}
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-14 pb-4">
            <div class="flex items-center gap-3 overflow-x-auto pb-2 scrollbar-hide">
                <a href="{{ route('storefront.companies') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 shrink-0 rounded-full border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-4 py-2 text-sm font-medium text-[var(--hs-neutral-700)] hover:border-[var(--hs-green-300)] hover:text-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
                    <svg class="size-4 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18" /></svg>
                    All Companies
                </a>
                @foreach ($this->categories as $category)
                    <a href="{{ route('storefront.products') }}" wire:navigate
                       class="inline-flex items-center shrink-0 rounded-full border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-4 py-2 text-sm font-medium text-[var(--hs-neutral-700)] hover:border-[var(--hs-green-300)] hover:text-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </section>

        {{-- ── Featured Companies ─────────────────────────────── --}}
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex items-end justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">Featured Companies</h2>
                    <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">Verified halal manufacturers and suppliers</p>
                </div>
                <a href="{{ route('storefront.companies') }}" wire:navigate
                   class="hidden sm:inline-flex items-center gap-1 text-sm font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] transition">
                    View all companies
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->featuredCompanies as $shop)
                    <x-hs-company-card :shop="$shop" />
                @endforeach
            </div>

            <div class="mt-6 flex justify-center sm:hidden">
                <a href="{{ route('storefront.companies') }}" wire:navigate
                   class="inline-flex items-center gap-1 text-sm font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] transition">
                    View all companies
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>
        </section>

        {{-- ── Featured Products ──────────────────────────────── --}}
        <section class="bg-[var(--hs-neutral-50)] border-t border-b border-[var(--hs-neutral-200)]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
                <div class="flex items-end justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">Latest Products</h2>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">Halal-certified products ready to source</p>
                    </div>
                    <a href="{{ route('storefront.products') }}" wire:navigate
                       class="hidden sm:inline-flex items-center gap-1 text-sm font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] transition">
                        Browse all products
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($this->featuredProducts as $product)
                        <x-hs-product-card :product="$product" />
                    @endforeach
                </div>

                <div class="mt-6 flex justify-center sm:hidden">
                    <a href="{{ route('storefront.products') }}" wire:navigate
                       class="inline-flex items-center gap-1 text-sm font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] transition">
                        Browse all products
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>
            </div>
        </section>

        {{-- ── Why Trust Us / CTA Banner ─────────────────────── --}}
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16">
            <div class="relative overflow-hidden rounded-2xl bg-[var(--hs-green-950)] px-8 py-12 text-center">
                <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 20px 20px;"></div>
                <div class="relative">
                    <h2 class="text-2xl sm:text-3xl font-bold text-white font-[var(--hs-font-display)]">
                        Ready to grow your halal business?
                    </h2>
                    <p class="mt-3 text-[var(--hs-neutral-300)] max-w-xl mx-auto text-sm sm:text-base">
                        Register your company, list your products, and connect with buyers across Southeast Asia and the Middle East.
                    </p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('register') }}" wire:navigate
                           class="inline-flex items-center rounded-full bg-[var(--hs-neutral-0)] px-6 py-3 text-sm font-semibold text-[var(--hs-green-900)] hover:bg-[var(--hs-neutral-100)] transition shadow">
                            Register Your Company
                        </a>
                        <a href="{{ route('storefront.companies') }}" wire:navigate
                           class="inline-flex items-center rounded-full border border-[var(--hs-green-700)] px-6 py-3 text-sm font-semibold text-white hover:bg-[var(--hs-green-900)] transition">
                            Browse Directory
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
