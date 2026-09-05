<div>
<style>
@keyframes hs-spin-cw  { to { transform: rotate(360deg);  } }
@keyframes hs-spin-ccw { to { transform: rotate(-360deg); } }
@media (prefers-reduced-motion: reduce) {
    [style*="hs-spin"] { animation: none !important; }
}
</style>
    {{-- ═══════════════════════════════════════════════════════════
         HERO SECTION
    ═══════════════════════════════════════════════════════════ --}}
    <section id="top" class="relative bg-[var(--hs-green-950)] overflow-hidden">
        
        {{-- Dark green tint --}}
        <div class="absolute inset-0" style="background-color: rgba(5, 41, 27, 0.80);"></div>

        {{-- Hero background image --}}
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-20" style="background-image: url('/images/globe_bg.png'); background-position: center;"></div>

        {{-- Radial glow (gold, offset right to sit behind the mark) --}}
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_70%_60%_at_80%_15%,rgba(217,163,36,0.16),transparent)]"></div>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-14 pb-14 lg:pt-20 lg:pb-24">
            <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2 lg:gap-14">

                {{-- Copy column --}}
                <div class="text-center lg:text-left">
                    <span class="inline-flex items-center gap-2 rounded-full border border-[var(--hs-green-700)] bg-[var(--hs-green-900)]/60 px-4 py-1.5 text-xs font-semibold text-[var(--hs-green-300)] uppercase tracking-widest">
                        <flux:icon.shield-check class="size-3.5 text-[var(--hs-gold-400)]" />
                        {{ __('MHCB-Verified Halal Marketplace') }}
                    </span>

                    <h1 class="mt-5 lg:mt-6 mx-auto lg:mx-0 max-w-xl text-3xl sm:text-4xl font-bold tracking-tight text-white lg:text-5xl font-[var(--hs-font-display)] leading-tight">
                        {{ __('We connect the entire halal supply chain,') }}
                        <span class="text-[var(--hs-green-300)]">{{ __('not just a storefront.') }}</span>
                    </h1>

                    <p class="mt-4 lg:mt-5 mx-auto lg:mx-0 max-w-lg text-base sm:text-lg text-[var(--hs-neutral-300)] leading-relaxed">
                        {{ __('1HalalStore.com is a B2B & B2C marketplace linking every player in the halal ecosystem — from farm to plate, locally and globally.') }}
                    </p>

                    <div class="mt-7 lg:mt-8 flex flex-wrap justify-center lg:justify-start gap-3">
                        <a href="{{ route('register') }}" wire:navigate
                           class="inline-flex items-center gap-2 rounded-full bg-[var(--hs-gold-500)] px-6 py-3 text-sm font-semibold text-[var(--hs-green-950)] shadow-[var(--hs-shadow-md)] hover:bg-[var(--hs-gold-400)] transition">
                            <flux:icon.arrow-right class="size-4" />
                            {{ __('Join the Movement') }}
                        </a>
                        <a href="#supply-chain"
                           class="inline-flex items-center gap-2 rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10 transition">
                            <flux:icon.play class="size-4" />
                            {{ __('See How It Works') }}
                        </a>
                    </div>


                </div>

                {{-- Visual column: animated rings + mark + floating cards — hidden below lg --}}
                <div class="relative mx-auto hidden h-[460px] w-[460px] max-w-full items-center justify-center lg:flex">

                    {{-- Outer ring: slow clockwise --}}
                    <div class="absolute inset-0 rounded-full border border-dashed border-white/20"
                         style="animation: hs-spin-cw 52s linear infinite;"></div>

                    {{-- Middle ring: faster counter-clockwise --}}
                    <div class="absolute inset-[40px] rounded-full border border-dashed border-white/14"
                         style="animation: hs-spin-ccw 36s linear infinite;"></div>

                    {{-- Inner ring: slow clockwise --}}
                    <div class="absolute inset-[90px] rounded-full border border-dashed border-white/10"
                         style="animation: hs-spin-cw 68s linear infinite;"></div>

                    {{-- Gold glow behind mark --}}
                    <div class="absolute size-56 rounded-full bg-[var(--hs-gold-500)]/20 blur-3xl pointer-events-none"></div>

                    {{-- Centre mark --}}
                    <img src="/images/halal_store_main_logo.png" alt="{{ __('1HalalStore mark') }}"
                         class="relative z-10 size-44 object-contain drop-shadow-2xl">

                    {{-- Guru card — sits on the outer ring at the bottom-right (~4–5 o'clock) --}}
                    <div class="absolute z-20 overflow-hidden rounded-2xl bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-xl)] ring-1 ring-black/5"
                         style="width:160px; bottom: 14px; right: -16px;">
                        <div class="relative overflow-hidden bg-gradient-to-b from-[var(--hs-gold-100)] via-[var(--hs-gold-50)] to-[var(--hs-neutral-100)]"
                             style="height:148px;">
                            <img src="/images/ismail_abaya_phd.png"
                                 alt="{{ __('Portrait of Ismail Abaya, PhD') }}"
                                 class="absolute top-0 left-1/2 -translate-x-1/2 object-contain object-bottom"
                                 style="height:155px; width:auto;">
                        </div>
                        <div class="px-3 py-2.5">
                            <div class="text-[0.8rem] font-extrabold text-[var(--hs-green-900)] leading-tight">{{ __('Ismail Abaya, PhD') }}</div>
                            <div class="mt-0.5 text-[0.6rem] font-bold uppercase tracking-widest text-[var(--hs-gold-700)]">{{ __('Halal Business Guru') }}</div>
                        </div>
                    </div>

                    {{-- MHCB stamp card — top-left (~10–11 o'clock) --}}
                    <div class="absolute z-20 flex items-center gap-2.5 rounded-2xl bg-[var(--hs-neutral-0)] px-3.5 py-3 shadow-[var(--hs-shadow-xl)] ring-1 ring-black/5"
                         style="top: 72px; left: -5px;">
                        <img src="/images/halal-stamp.png"
                             alt="{{ __('Maslaha Halal Certification Board Philippines seal') }}"
                             class="size-11 object-contain shrink-0">
                        <div>
                            <div class="text-[0.72rem] font-bold text-[var(--hs-green-800)] leading-tight">{{ __('MHCB Certified') }}</div>
                            <div class="text-[0.65rem] text-[var(--hs-neutral-500)]">{{ __('Philippines') }}</div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         FLOATING SEARCH PANEL
    ═══════════════════════════════════════════════════════════ --}}
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 -mt-8 sm:-mt-10 lg:-mt-12 relative z-10">
        <div class="rounded-2xl bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-xl)] border border-[var(--hs-neutral-100)] p-4 sm:p-5">
            {{-- Search Input --}}
            <div class="flex flex-col sm:flex-row gap-2">
                <div class="flex-1 relative">
                    <flux:icon.magnifying-glass class="absolute left-3.5 top-1/2 -translate-y-1/2 size-5 text-[var(--hs-neutral-400)]" />
                    <input
                        wire:model.live.debounce.300ms="query"
                        type="search"
                        placeholder="{{ __('Search halal products…') }}"
                        class="w-full rounded-full border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:border-transparent"
                    />
                </div>
                <button wire:click="search" class="w-full sm:w-auto rounded-full bg-[var(--hs-green-600)] px-6 py-3 text-sm font-semibold text-white hover:bg-[var(--hs-green-700)] transition shadow-sm whitespace-nowrap">
                    {{ __('Search') }}
                </button>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         PRODUCT SEARCH RESULTS
    ═══════════════════════════════════════════════════════════ --}}
    @if (mb_strlen($query) >= 2)
        @php $hasProducts = count($productResults) > 0; @endphp
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-10 lg:py-12">
            <div class="flex items-center justify-between mb-6 sm:mb-8">
                <p class="text-sm text-[var(--hs-neutral-500)]">
                    {{ __('Showing results for') }} <span class="font-semibold text-[var(--hs-neutral-900)]">"{{ $query }}"</span>
                </p>
                @if ($hasProducts)
                    <a href="{{ route('storefront.products') }}" wire:navigate class="text-xs font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] transition">
                        {{ __('View all products →') }}
                    </a>
                @endif
            </div>

            @if ($hasProducts)
                <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    @foreach ($productResults as $result)
                        <a href="{{ route('storefront.product', $result['id']) }}" wire:navigate
                           class="group flex flex-col rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)] transition hover:shadow-[var(--hs-shadow-md)] hover:border-[var(--hs-green-200)] overflow-hidden">
                            <div class="aspect-square bg-[var(--hs-green-50)] flex items-center justify-center">
                                <flux:icon.cube class="size-8 text-[var(--hs-neutral-300)]" />
                            </div>
                            <div class="p-3">
                                <p class="truncate text-sm font-semibold text-[var(--hs-neutral-900)] group-hover:text-[var(--hs-green-700)]">
                                    {{ $result['name'] ?? 'Unknown' }}
                                </p>
                                <p class="mt-0.5 text-xs text-[var(--hs-neutral-500)] truncate">
                                    {{ $result['shop_name'] ?? '' }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <x-hs-empty-state icon="search" title="{{ __('No products found') }}" :subtitle="__('No products matched &quot;:query&quot;. Try a different search term or category.', ['query' => $query])" />
            @endif
        </section>

    @else
        {{-- ═══════════════════════════════════════════════════════
             DEFAULT HOME CONTENT (when no search)
        ═══════════════════════════════════════════════════════ --}}

        {{-- Categories Row --}}
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-10 sm:pt-14 pb-4">
            <div class="flex items-center gap-2.5 sm:gap-3 overflow-x-auto pb-2 scrollbar-hide -mx-4 px-4 sm:mx-0 sm:px-0">
                <a href="{{ route('storefront.companies') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 shrink-0 rounded-full border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-3.5 sm:px-4 py-2 text-sm font-medium text-[var(--hs-neutral-700)] hover:border-[var(--hs-green-300)] hover:text-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
                    <flux:icon.table-cells class="size-4 text-[var(--hs-neutral-400)]" />
                    {{ __('All Companies') }}
                </a>
                @foreach ($this->categories as $category)
                    <a href="{{ route('storefront.products') }}" wire:navigate
                       class="inline-flex items-center shrink-0 rounded-full border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-3.5 sm:px-4 py-2 text-sm font-medium text-[var(--hs-neutral-700)] hover:border-[var(--hs-green-300)] hover:text-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </section>

        {{-- ── Who We Serve / What We Offer ───────────────────── --}}
        <section id="who-we-serve" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            {{-- Section header --}}
            <div class="max-w-2xl mb-8 sm:mb-12">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--hs-green-100)] bg-[var(--hs-green-50)] px-3.5 py-1.5 text-[0.75rem] font-bold tracking-wide text-[var(--hs-green-700)]">
                    <flux:icon.squares-2x2 class="size-3.5" />
                    {{ __('A Trusted Halal Ecosystem') }}
                </span>
                <h2 class="mt-4 text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[var(--hs-green-950)] leading-tight tracking-tight">
                    {{ __('Built on integrity, transparency & technology.') }}
                </h2>
                <p class="mt-3 sm:mt-4 text-base text-[var(--hs-neutral-500)]">
                    {{ __('Every stakeholder in the halal economy has a place on the platform — and a reason to trust what happens next.') }}
                </p>
            </div>

            {{-- Two-column panels --}}
            <div class="grid grid-cols-1 gap-5 sm:gap-7 lg:grid-cols-2">

                {{-- Who We Serve (dark) --}}
                <div id="who-we-serve-panel" class="rounded-[var(--hs-radius-panel)] bg-[var(--hs-green-950)] p-6 sm:p-9">
                    <div class="flex items-center gap-3 mb-5 sm:mb-7">
                        <div class="flex size-10 sm:size-11 items-center justify-center rounded-xl bg-[var(--hs-gold-500)]/15 text-[var(--hs-gold-400)]">
                            <flux:icon.users class="size-5" />
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold text-white">{{ __('Who We Serve') }}</h3>
                    </div>
                    <ul class="divide-y divide-white/[0.08]">
                        {{-- Who We Serve --}}
                        @foreach ([
                            ['icon' => 'map-pin',             'label' => 'Farmers & Fisherfolk'],
                            ['icon' => 'cog-6-tooth',         'label' => 'Producers & Manufacturers'],
                            ['icon' => 'shield-check',        'label' => 'Halal Certified Suppliers'],
                            ['icon' => 'truck',               'label' => 'Logistics & Cold Chain Providers'],
                            ['icon' => 'rectangle-stack',     'label' => 'Distributors & Wholesalers'],
                            ['icon' => 'building-storefront', 'label' => 'Retailers & Halal Businesses'],
                            ['icon' => 'user-group',          'label' => 'Consumers (B2C)'],
                        ] as $item)
                            <li class="flex items-center gap-3 sm:gap-3.5 py-3 sm:py-3.5">
                                <div class="flex size-8 sm:size-9 shrink-0 items-center justify-center rounded-[10px] bg-white/[0.06] text-[var(--hs-gold-300)]">
                                    <flux:icon :icon="$item['icon']" class="size-4 sm:size-[18px]" />
                                </div>
                                <span class="text-[0.88rem] sm:text-[0.94rem] font-semibold text-white/90">{{ __($item['label']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- What We Offer (light) --}}
                <div id="what-we-offer" class="rounded-[var(--hs-radius-panel)] border border-[var(--hs-neutral-100)] bg-white p-6 sm:p-9 shadow-[var(--hs-shadow-sm)]">
                    <div class="flex items-center gap-3 mb-5 sm:mb-7">
                        <div class="flex size-10 sm:size-11 items-center justify-center rounded-xl bg-[var(--hs-green-50)] text-[var(--hs-green-700)]">
                            <flux:icon.shopping-bag class="size-5" />
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold text-[var(--hs-green-950)]">{{ __('What We Offer') }}</h3>
                    </div>
                    <ul class="divide-y divide-[var(--hs-neutral-100)]">
                        @foreach ([
                            {{-- What We Offer --}}
                            ['icon' => 'clipboard-document-list', 'label' => 'Halal-verified suppliers directory'],
                            ['icon' => 'shopping-bag',            'label' => 'Products & services sourcing (B2B & B2C)'],
                            ['icon' => 'shield-check',            'label' => 'Halal certification awareness & support'],
                            ['icon' => 'map',                     'label' => 'Supply chain visibility & traceability'],
                            ['icon' => 'paper-airplane',          'label' => 'Logistics & export assistance'],
                            ['icon' => 'link',                    'label' => 'Business matching & partnerships'],
                            ['icon' => 'academic-cap',            'label' => 'Halal events, training & consultancy'],
                        ] as $item)
                            <li class="flex items-center gap-3 sm:gap-3.5 py-3 sm:py-3.5">
                                <div class="flex size-8 sm:size-9 shrink-0 items-center justify-center rounded-[10px] bg-[var(--hs-green-50)] text-[var(--hs-green-700)]">
                                    <flux:icon :icon="$item['icon']" class="size-4 sm:size-[18px]" />
                                </div>
                                <span class="text-[0.88rem] sm:text-[0.94rem] font-semibold text-[var(--hs-neutral-700)]">{{ __($item['label']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

            </div>
        </section>

        {{-- ── Supply Chain Flow ───────────────────────────────── --}}
        <section id="supply-chain" class="bg-white border-t border-b border-[var(--hs-neutral-100)] py-14 sm:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {{-- Header --}}
                <div class="text-center mb-10 sm:mb-14">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--hs-green-100)] bg-[var(--hs-green-50)] px-3.5 py-1.5 text-[0.75rem] font-bold tracking-wide text-[var(--hs-green-700)]">
                        <flux:icon.arrow-path-rounded-square class="size-3.5" />
                        {{ __('End-to-End Traceability') }}
                    </span>
                    <h2 class="mt-4 text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[var(--hs-green-950)] leading-tight tracking-tight max-w-[20ch] mx-auto">
                        {{ __('The halal supply chain we connect') }}
                    </h2>
                </div>

                {{-- Steps grid --}}
                <div class="relative">
                    {{-- connecting line (desktop only) --}}
                    <div class="hidden lg:block absolute left-[7%] right-[7%] top-[34px] border-t-2 border-dashed border-[var(--hs-green-200)]"></div>

                    <div class="relative grid grid-cols-2 gap-y-8 gap-x-3 sm:gap-y-10 sm:gap-x-4 sm:grid-cols-4 lg:grid-cols-7">
                        @foreach ([
                            ['stage' => '01', 'icon' => 'map-pin',            'title' => 'Farm & Source',        'desc' => 'Sustainable & ethical sourcing'],
                            ['stage' => '02', 'icon' => 'cog-6-tooth',        'title' => 'Process & Produce',    'desc' => 'Halal compliant production'],
                            ['stage' => '03', 'icon' => 'shield-check',       'title' => 'Certify & Assure',     'desc' => 'Halal certification & QA'],
                            ['stage' => '04', 'icon' => 'archive-box',        'title' => 'Pack & Store',         'desc' => 'Halal-safe packaging & storage'],
                            ['stage' => '05', 'icon' => 'truck',               'title' => 'Distribute & Deliver', 'desc' => 'Trusted logistics & distribution'],
                            ['stage' => '06', 'icon' => 'building-storefront', 'title' => 'Retail & Sell',        'desc' => 'Halal retail & business partners'],
                            ['stage' => '07', 'icon' => 'heart',                'title' => 'Consume & Trust',      'desc' => 'Confident halal consumers'],
                        ] as $step)
                            <div class="group relative z-10 flex flex-col items-center text-center px-1 sm:px-2">
                                <div class="flex size-14 sm:size-16 lg:size-[68px] items-center justify-center rounded-full border-2 border-[var(--hs-green-200)] bg-white text-[var(--hs-green-700)] shadow-[var(--hs-shadow-sm)] transition group-hover:border-[var(--hs-gold-500)] group-hover:text-[var(--hs-gold-600)] group-hover:-translate-y-1">
                                    <flux:icon :icon="$step['icon']" class="size-5 sm:size-6" />
                                </div>
                                <span class="mt-3 sm:mt-4 block text-[0.62rem] sm:text-[0.68rem] font-extrabold tracking-[0.06em] text-[var(--hs-gold-600)]">{{ __('STAGE :stage', ['stage' => $step['stage']]) }}</span>
                                <h4 class="mt-1 text-[0.8rem] sm:text-[0.88rem] font-extrabold text-[var(--hs-green-950)]">{{ __($step['title']) }}</h4>
                                <p class="mt-1 text-[0.72rem] sm:text-[0.76rem] leading-snug text-[var(--hs-neutral-500)]">{{ __($step['desc']) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ── Value Strip ─────────────────────────────────────── --}}
        <div class="border-b border-[var(--hs-green-100)] bg-[var(--hs-green-50)] py-8 sm:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-5 sm:gap-6 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ([
                        ['icon' => 'shield-check',  'label' => '100% Halal Focused'],
                        ['icon' => 'globe-alt',      'label' => 'Global Market Access'],
                        ['icon' => 'cpu-chip',       'label' => 'Digital Innovation'],
                        ['icon' => 'users',          'label' => 'Community Empowerment'],
                        ['icon' => 'arrow-path',     'label' => 'Sustainable & Ethical Growth'],
                    ] as $value)
                        <div class="flex flex-col items-center gap-2 sm:gap-2.5 text-center">
                            <div class="flex size-11 sm:size-[52px] items-center justify-center rounded-full bg-white text-[var(--hs-green-700)] shadow-[var(--hs-shadow-sm)]">
                                <flux:icon :icon="$value['icon']" class="size-5 sm:size-6" />
                            </div>
                            <span class="text-[0.76rem] sm:text-[0.82rem] font-bold text-[var(--hs-green-900)] max-w-[14ch]">{{ __($value['label']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Sponsored Content — Homepage Placement ─────────── --}}
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <livewire:shared.sponsored-content-banner placement="homepage" />
        </div>

        {{-- ── Featured Products ──────────────────────────────── --}}
        <section id="featured-products" class="bg-[var(--hs-neutral-50)] border-t border-b border-[var(--hs-neutral-200)]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
                <div class="flex items-end justify-between mb-5 sm:mb-6">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">{{ __('Latest Products') }}</h2>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Halal-certified products ready to source') }}</p>
                    </div>
                    <a href="{{ route('storefront.products') }}" wire:navigate
                       class="hidden sm:inline-flex items-center gap-1 text-sm font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] transition">
                        {{ __('Browse all products') }}
                        <flux:icon.arrow-right class="size-4" />
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    @foreach ($this->featuredProducts as $product)
                        <x-hs-product-card :product="$product" />
                    @endforeach
                </div>

                <div class="mt-6 flex justify-center sm:hidden">
                    <a href="{{ route('storefront.products') }}" wire:navigate
                       class="inline-flex items-center gap-1 text-sm font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] transition">
                        {{ __('Browse all products') }}
                        <flux:icon.arrow-right class="size-4" />
                    </a>
                </div>
            </div>
        </section>

        {{-- ── Country Portals ─────────────────────────────────── --}}
        <section id="country-portals" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
            <div class="text-center mb-6 sm:mb-8">
                <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">
                    {{ __('1HalalStore across the Halal world') }}
                </h2>
                <p class="mt-3 text-sm sm:text-base text-[var(--hs-neutral-500)] max-w-2xl mx-auto">
                    {{ __("Each country runs its own independent portal. Click to visit a portal, or browse the one you're on now.") }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-3 lg:grid-cols-6">
                {{-- Current instance — "You are here" --}}
                <div class="relative flex flex-col items-center gap-2 rounded-xl border-2 border-[var(--hs-green-600)] bg-[var(--hs-green-50)] p-4 text-center shadow-[var(--hs-shadow-sm)]">
                    <span class="text-3xl sm:text-4xl" aria-hidden="true">{{ $this->instancePortal['flag'] }}</span>
                    <span class="text-sm font-bold text-[var(--hs-green-800)]">{{ $this->instancePortal['display_name'] }}</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-[var(--hs-green-600)] px-2 py-0.5 text-[0.65rem] font-bold text-white uppercase tracking-wide">
                        <flux:icon.map-pin class="size-2.5" />
                        {{ __('You are here') }}
                    </span>
                </div>

                {{-- Other country portals --}}
                @foreach ($this->otherPortals as $portal)
                    @if ($portal['url'])
                        <a
                            href="{{ $portal['url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group flex flex-col items-center gap-2 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-4 text-center shadow-[var(--hs-shadow-sm)] hover:border-[var(--hs-green-400)] hover:bg-[var(--hs-green-50)] transition"
                        >
                            <span class="text-3xl sm:text-4xl" aria-hidden="true">{{ $portal['flag'] }}</span>
                            <span class="text-sm font-semibold text-[var(--hs-neutral-800)] group-hover:text-[var(--hs-green-700)]">{{ $portal['display_name'] }}</span>
                            <span class="inline-flex items-center gap-1 text-[0.65rem] font-semibold text-[var(--hs-green-700)]">
                                {{ __('Visit Portal') }}
                                <flux:icon.arrow-top-right-on-square class="size-2.5" />
                            </span>
                        </a>
                    @else
                        <div class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-4 text-center opacity-60">
                            <span class="text-3xl sm:text-4xl" aria-hidden="true">{{ $portal['flag'] }}</span>
                            <span class="text-sm font-semibold text-[var(--hs-neutral-600)]">{{ $portal['display_name'] }}</span>
                            <span class="text-[0.65rem] font-semibold text-[var(--hs-neutral-400)] uppercase tracking-wide">{{ __('Coming Soon') }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

        {{-- ── Why Trust Us / CTA Banner ─────────────────────── --}}
        <section id="join" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            <div class="relative overflow-hidden rounded-2xl bg-[var(--hs-green-950)] px-5 sm:px-8 py-9 sm:py-12 text-center">
                <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 20px 20px;"></div>
                <div class="relative">
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-white font-[var(--hs-font-display)]">
                        {{ __('Ready to grow your halal business?') }}
                    </h2>
                    <p class="mt-3 text-[var(--hs-neutral-300)] max-w-xl mx-auto text-sm sm:text-base">
                        {{ __('Register your company, list your products, and connect with buyers across Southeast Asia and the Middle East.') }}
                    </p>
                    <div class="mt-7 sm:mt-8 flex flex-col sm:flex-row flex-wrap justify-center gap-3">
                        <a href="{{ route('register') }}" wire:navigate
                           class="inline-flex items-center justify-center rounded-full bg-[var(--hs-neutral-0)] px-6 py-3 text-sm font-semibold text-[var(--hs-green-900)] hover:bg-[var(--hs-neutral-100)] transition shadow">
                            {{ __('Register Your Company') }}
                        </a>
                        <a href="{{ route('storefront.companies') }}" wire:navigate
                           class="inline-flex items-center justify-center rounded-full border border-[var(--hs-green-700)] px-6 py-3 text-sm font-semibold text-white hover:bg-[var(--hs-green-900)] transition">
                            {{ __('Browse Directory') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>