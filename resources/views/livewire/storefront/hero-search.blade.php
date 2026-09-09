<div>
    {{-- ═══════════════════════════════════════════════════════════
         HERO SECTION
    ═══════════════════════════════════════════════════════════ --}}
    <section id="top" class="relative bg-[var(--hs-green-950)] overflow-hidden">

        {{-- Dark green tint --}}
        <div class="absolute inset-0" style="background-color: rgba(0, 19, 17, 0.88);"></div>

        {{-- Hero background image --}}
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-20" style="background-image: url('/images/globe_bg.png');"></div>

        {{-- Radial glow (gold, offset right to sit behind the carousel) --}}
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_70%_60%_at_80%_15%,rgba(238,201,0,0.12),transparent)]"></div>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-14 pb-14 lg:pt-20 lg:pb-24">
            <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-12 lg:gap-12">

                {{-- ── Copy column ─────────────────────────────────────────── --}}
                <div class="text-center lg:text-left lg:col-span-7">

                    <span class="inline-flex items-center gap-2 rounded-full border border-[var(--hs-green-700)] bg-white/10 px-4 py-1.5 text-xs font-semibold text-[var(--hs-green-300)] uppercase tracking-widest">
                        <flux:icon.shield-check class="size-3.5 text-[var(--hs-gold-400)]" />
                        {{ __('MHCB-Verified Halal Marketplace') }}
                    </span>

                    <h1 class="mt-5 lg:mt-6 mx-auto lg:mx-0 max-w-xl text-3xl sm:text-4xl font-bold tracking-tight text-white lg:text-5xl font-[var(--hs-font-display)] leading-tight">
                        {{ __('We connect the entire halal supply chain,') }}
                        <span class="text-[var(--hs-green-300)]">{{ __('not just a storefront.') }}</span>
                    </h1>

                    <p class="mt-4 lg:mt-5 mx-auto lg:mx-0 max-w-lg text-base sm:text-lg text-white/70 leading-relaxed">
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

                {{-- ── Visual column: Featured Voice card (desktop only) ─────── --}}
                <div class="hidden lg:block lg:col-span-5">

                    {{--
                        Fixed 2:3 aspect ratio card (matches tall creative: 400×600).
                        All slides are absolute-positioned inside a relative container,
                        so the card height is always stable regardless of content.
                        Slides: 0 = Ismail Abaya, 1 = MHCB, 2 = Sponsored tall ads.
                    --}}
                    <div
                        x-data="{
                            index: 0,
                            total: 0,
                            timer: null,
                            paused: false,
                            autoplay: 4500,
                            reduceMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                            init() {
                                this.total = this.$refs.track.children.length;
                                this.startAutoplay();
                            },
                            goTo(i, userInitiated = false) {
                                this.index = ((i % this.total) + this.total) % this.total;
                                if (userInitiated) { this.stopAutoplay(); this.startAutoplay(); }
                            },
                            next() { this.goTo(this.index + 1, true); },
                            prev() { this.goTo(this.index - 1, true); },
                            startAutoplay() {
                                if (this.reduceMotion || this.total <= 1) return;
                                this.stopAutoplay();
                                this.timer = setInterval(() => { if (!this.paused) this.next(); }, this.autoplay);
                            },
                            stopAutoplay() { clearInterval(this.timer); this.timer = null; }
                        }"
                        x-init="init()"
                        @mouseenter="paused = true"
                        @mouseleave="paused = false"
                        @focusin="paused = true"
                        @focusout="paused = false"
                        class="relative mx-auto max-w-sm"
                        aria-label="{{ __('Hero highlights carousel') }}"
                        aria-roledescription="carousel"
                    >
                        {{-- Gradient border wrapper --}}
                        <div class="bg-gradient-to-b from-white/20 to-white/5 p-[1px] rounded-3xl shadow-2xl">

                            {{-- Fixed 2:3 aspect-ratio frame — all slides stack inside here --}}
                            <div class="relative rounded-[22px] overflow-hidden" style="aspect-ratio: 2/3;">

                                {{-- Slide track: children are absolute-fill --}}
                                <div x-ref="track" class="absolute inset-0">

                                    {{-- ── Slide 0: Ismail Abaya ────────────────────── --}}
                                    <div x-show="index === 0"
                                         x-transition:enter="transition ease-out duration-500"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         x-transition:leave="transition ease-in duration-300"
                                         x-transition:leave-start="opacity-100"
                                         x-transition:leave-end="opacity-0"
                                         class="absolute inset-0"
                                         style="background: linear-gradient(165deg, var(--hs-gold-100), var(--hs-neutral-100) 70%);"
                                         role="group" aria-roledescription="slide"
                                         aria-label="{{ __('Slide 1: Featured Voice') }}">

                                        {{-- Full-bleed portrait photo --}}
                                        <img src="/images/ismail_abaya_phd.png"
                                             alt="{{ __('Ismail Abaya, PhD — Halal Business Guru') }}"
                                             class="absolute inset-0 w-full h-full object-cover object-top">

                                        {{-- Bottom scrim --}}
                                        <div class="absolute inset-0"
                                             style="background: linear-gradient(to top, rgba(0,19,11,0.95) 0%, rgba(0,19,11,0.55) 40%, transparent 65%);"></div>

                                        {{-- Top badge --}}
                                        <div class="absolute top-4 left-4 z-10">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-[var(--hs-gold-500)] text-[var(--hs-green-950)]">
                                                {{ __('Featured Voice') }}
                                            </span>
                                        </div>

                                        {{-- Bottom text overlay --}}
                                        <div class="absolute bottom-0 inset-x-0 z-10 px-5 pb-6">
                                            <div class="flex items-center gap-1.5 mb-2">
                                                <span class="size-2 rounded-full bg-[var(--hs-green-400)]"></span>
                                                <span class="text-[10px] font-semibold text-[var(--hs-green-300)]">{{ __('Verified Halal Auditor') }}</span>
                                            </div>
                                            <h3 class="text-xl font-bold text-white leading-tight">{{ __('Ismail Abaya, PhD') }}</h3>
                                            <p class="text-xs font-semibold text-[var(--hs-gold-400)] mt-0.5">{{ __('Halal Business & Supply Chain Authority') }}</p>
                                            <p class="mt-2 text-xs text-white/70 leading-relaxed italic border-l-2 border-[var(--hs-green-600)]/50 pl-3">
                                                {{ __('"True halal compliance demands uncompromising integrity across transport, temperature governance, and farm-level custody."') }}
                                            </p>
                                        </div>

                                    </div>

                                    {{-- ── Slide 1: MHCB Certified ─────────────────── --}}
                                    <div x-show="index === 1"
                                         x-transition:enter="transition ease-out duration-500"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         x-transition:leave="transition ease-in duration-300"
                                         x-transition:leave-start="opacity-100"
                                         x-transition:leave-end="opacity-0"
                                         class="absolute inset-0 flex flex-col"
                                         style="background: radial-gradient(circle at 50% 38%, var(--hs-gold-100), var(--hs-green-50) 72%);"
                                         role="group" aria-roledescription="slide"
                                         aria-label="{{ __('Slide 2: MHCB Certified') }}">

                                        {{-- Seal centred in top 60% --}}
                                        <div class="flex-1 flex items-center justify-center">
                                            <div class="flex flex-col items-center gap-4">
                                                <div class="relative flex items-center justify-center"
                                                     style="width:150px; height:150px;">
                                                    <div class="absolute inset-0 rounded-full"
                                                         style="background: conic-gradient(var(--hs-gold-500), var(--hs-green-700), var(--hs-gold-500));"></div>
                                                    <div class="relative z-10 flex items-center justify-center rounded-full bg-[var(--hs-neutral-0)]"
                                                         style="width:133px; height:133px;">
                                                        <img src="/images/halal-stamp.png"
                                                             alt="{{ __('MHCB seal') }}"
                                                             class="size-24 object-contain">
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <p class="text-base font-extrabold text-[var(--hs-green-800)] leading-tight">{{ __('Maslaha Halal') }}</p>
                                                    <p class="text-base font-extrabold text-[var(--hs-green-800)] leading-tight">{{ __('Certification Board') }}</p>
                                                    <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-[var(--hs-gold-700)]">{{ __('Philippines') }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Bottom scrim + caption --}}
                                        <div class="relative z-10 px-5 pb-6 pt-8"
                                             style="background: linear-gradient(to top, rgba(0,19,11,0.92) 0%, rgba(0,19,11,0.50) 60%, transparent 100%);">
                                            <div class="absolute top-2 left-5">
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider text-white"
                                                      style="background: rgba(34,114,73,0.85);">
                                                    <flux:icon.shield-check class="inline size-3 mr-0.5" />
                                                    {{ __('Verified') }}
                                                </span>
                                            </div>
                                            <h3 class="text-xl font-bold text-white leading-tight">{{ __('MHCB Certified') }}</h3>
                                            <p class="text-xs font-semibold text-[var(--hs-gold-400)] mt-0.5">{{ __('Maslaha Halal Certification Board · Philippines') }}</p>
                                            <p class="mt-2 text-xs text-white/70 leading-relaxed">
                                                {{ __('Every listed supplier is verified against Philippine halal certification standards before appearing on the marketplace.') }}
                                            </p>
                                        </div>

                                    </div>

                                    {{-- ── Slides 2+: Sponsored (one slide per active ad) ── --}}
                                    @foreach ($this->heroSponsoredSlides as $adIndex => $ad)
                                        @if ($ad->tallMedia)
                                            <div x-show="index === {{ $adIndex + 2 }}"
                                                 x-transition:enter="transition ease-out duration-500"
                                                 x-transition:enter-start="opacity-0"
                                                 x-transition:enter-end="opacity-100"
                                                 x-transition:leave="transition ease-in duration-300"
                                                 x-transition:leave-start="opacity-100"
                                                 x-transition:leave-end="opacity-0"
                                                 class="absolute inset-0"
                                                 role="group" aria-roledescription="slide"
                                                 aria-label="{{ __('Slide :n: :title', ['n' => $adIndex + 3, 'title' => $ad->title]) }}">

                                                {{-- Full-bleed tall creative image --}}
                                                <a href="{{ route('sponsored-content.click', $ad) }}"
                                                   target="_blank" rel="noopener noreferrer"
                                                   class="absolute inset-0 block">
                                                    <img src="{{ route('media.view', $ad->tallMedia) }}"
                                                         alt="{{ $ad->title }}"
                                                         class="w-full h-full object-cover"
                                                         loading="{{ $adIndex === 0 ? 'eager' : 'lazy' }}">
                                                    <div class="absolute inset-0"
                                                         style="background: linear-gradient(to top, rgba(0,0,0,0.70) 0%, rgba(0,0,0,0.20) 45%, transparent 65%);"></div>
                                                </a>

                                                {{-- Top sponsored badge --}}
                                                <div class="absolute top-4 left-4 z-10 pointer-events-none">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider text-white"
                                                          style="background: rgba(255,255,255,0.16); backdrop-filter: blur(4px);">
                                                        <flux:icon.megaphone class="size-3" />
                                                        {{ __('Sponsored') }}
                                                    </span>
                                                </div>

                                                {{-- Bottom title overlay --}}
                                                <div class="absolute bottom-0 inset-x-0 z-10 px-5 pb-6 pointer-events-none">
                                                    <p class="text-sm font-bold text-white leading-snug line-clamp-2">{{ $ad->title }}</p>
                                                </div>

                                            </div>
                                        @endif
                                    @endforeach

                                </div>{{-- /track --}}

                                {{-- Prev / Next arrows --}}
                                <button type="button" @click="prev()"
                                    class="absolute left-3 top-1/2 -translate-y-1/2 z-20 size-8 flex items-center justify-center rounded-full text-white border-none transition hover:bg-black/50 focus-visible:outline-2 focus-visible:outline-[var(--hs-gold-400)]"
                                    style="background: rgba(0,0,0,0.30); backdrop-filter: blur(3px);"
                                    aria-label="{{ __('Previous slide') }}">
                                    <flux:icon.chevron-left class="size-4" />
                                </button>
                                <button type="button" @click="next()"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 z-20 size-8 flex items-center justify-center rounded-full text-white border-none transition hover:bg-black/50 focus-visible:outline-2 focus-visible:outline-[var(--hs-gold-400)]"
                                    style="background: rgba(0,0,0,0.30); backdrop-filter: blur(3px);"
                                    aria-label="{{ __('Next slide') }}">
                                    <flux:icon.chevron-right class="size-4" />
                                </button>

                                {{-- Dot indicators --}}
                                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5"
                                     role="tablist" aria-label="{{ __('Hero highlights') }}">
                                    <template x-for="(_, i) in Array.from({ length: total })" :key="i">
                                        <button
                                            type="button"
                                            @click="goTo(i, true)"
                                            :aria-current="index === i ? 'true' : 'false'"
                                            :aria-label="`Go to slide ${i + 1}`"
                                            class="h-1.5 rounded-full border-none p-0 transition-all duration-300 cursor-pointer"
                                            :class="index === i ? 'w-5 bg-[var(--hs-gold-400)]' : 'w-1.5 bg-white/35 hover:bg-white/60'"
                                            role="tab"
                                        ></button>
                                    </template>
                                </div>

                            </div>{{-- /aspect-ratio frame --}}
                        </div>{{-- /gradient border --}}
                    </div>{{-- /x-data --}}
                </div>{{-- /visual column --}}

            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         FLOATING SEARCH + CATEGORY PILLS
    ═══════════════════════════════════════════════════════════ --}}
    <section class="relative -mt-8 sm:-mt-10 z-30 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-[var(--hs-neutral-0)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-xl)] p-4 sm:p-5">

            {{-- Search row --}}
            <div class="flex flex-col sm:flex-row gap-2">
                <div class="flex-1 relative">
                    <flux:icon.magnifying-glass class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-[var(--hs-neutral-400)] pointer-events-none" />
                    <input
                        wire:model.live.debounce.300ms="query"
                        type="search"
                        placeholder="{{ __('Search halal products') }}"
                        class="w-full pl-12 pr-4 py-3.5 bg-[var(--hs-neutral-50)] border border-[var(--hs-neutral-200)] rounded-xl text-sm text-[var(--hs-neutral-800)] placeholder:text-[var(--hs-neutral-400)] focus:bg-[var(--hs-neutral-0)] focus:border-[var(--hs-green-600)] focus:ring-2 focus:ring-[var(--hs-green-600)]/20 outline-none transition"
                    />
                </div>
                <button
                    wire:click="search"
                    class="w-full sm:w-auto px-8 py-3.5 bg-[var(--hs-green-600)] hover:bg-[var(--hs-green-700)] text-white text-sm font-bold rounded-xl shadow-[var(--hs-shadow-sm)] transition flex items-center justify-center gap-2 whitespace-nowrap"
                >
                    {{ __('Search') }}
                    <flux:icon.arrow-right class="size-4" />
                </button>
            </div>

            {{-- Category pills --}}
            <div class="mt-4 pt-3 flex items-center gap-2 overflow-x-auto pb-0.5 scrollbar-hide">
                <span class="text-xs text-[var(--hs-neutral-400)] font-medium whitespace-nowrap mr-1 shrink-0">{{ __('Popular:') }}</span>
                <a href="{{ route('storefront.companies') }}" wire:navigate
                   class="shrink-0 px-3 py-1.5 rounded-lg bg-[var(--hs-green-800)] text-white text-xs font-semibold whitespace-nowrap">
                    {{ __('All Businesses') }}
                </a>
                @foreach ($this->categories as $category)
                    <a href="{{ route('storefront.products') }}" wire:navigate
                       class="shrink-0 px-3 py-1.5 rounded-lg bg-[var(--hs-neutral-100)] hover:bg-[var(--hs-neutral-200)] text-[var(--hs-neutral-700)] text-xs font-medium whitespace-nowrap transition">
                        {{ $category->name }}
                    </a>
                @endforeach
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
             Reordered for marketplace priority:
             1. Categories        (navigation — pairs with search)
             2. Featured Products (core commerce content, moved up)
             3. Value Strip       (quick trust signals)
             4. Sponsored Content (monetization, once engaged)
             5. Who We Serve / What We Offer (about/context)
             6. Supply Chain Flow (educational/model explainer)
             7. Country Portals   (navigation utility, niche)
             8. Sourcing CTA      (secondary conversion)
             9. Join CTA          (closing banner)
        ═══════════════════════════════════════════════════════ --}}

        {{-- ── Featured Products ──────────────────────────────── --}}
        <section id="featured-products" class="bg-[var(--hs-neutral-50)] border-t border-b border-[var(--hs-neutral-200)] mt-10 sm:mt-14">
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
                            <div class="flex size-11 sm:size-[52px] items-center justify-center rounded-full bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-[var(--hs-shadow-sm)]">
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
            <livewire:shared.sponsored-content-banner placement="homepage" orientation="row" />
        </div>

        {{-- ── Who We Serve / What We Offer ───────────────────── --}}
        <section id="who-we-serve" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            {{-- Section header --}}
            <div class="max-w-2xl mb-8 sm:mb-12">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--hs-green-100)] bg-[var(--hs-green-50)] px-3.5 py-1.5 text-[0.75rem] font-bold tracking-wide text-[var(--hs-green-700)]">
                    <flux:icon.squares-2x2 class="size-3.5" />
                    {{ __('A Trusted Halal Ecosystem') }}
                </span>
                <h2 class="mt-4 text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[var(--hs-neutral-900)] leading-tight tracking-tight">
                    {{ __('Built on integrity, transparency & technology.') }}
                </h2>
                <p class="mt-3 sm:mt-4 text-base text-[var(--hs-neutral-500)]">
                    {{ __('Every stakeholder in the halal economy has a place on the platform — and a reason to trust what happens next.') }}
                </p>
            </div>

            {{-- Two-column panels --}}
            <div class="grid grid-cols-1 gap-5 sm:gap-7 lg:grid-cols-2">

                {{-- Who We Serve (dark) --}}
                <div id="who-we-serve-panel" class="rounded-[var(--hs-radius-panel)] bg-[var(--hs-green-950)] p-5 sm:p-7">
                    <div class="flex items-center gap-3 mb-4 sm:mb-5">
                        <div class="flex size-9 sm:size-10 items-center justify-center rounded-xl bg-[var(--hs-gold-500)]/15 text-[var(--hs-gold-400)]">
                            <flux:icon.users class="size-5" />
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold text-white">{{ __('Who We Serve') }}</h3>
                    </div>
                    <ul class="divide-y divide-white/[0.08]">
                        @foreach ([
                            ['icon' => 'map-pin',             'label' => 'Farmers & Fisherfolk'],
                            ['icon' => 'cog-6-tooth',         'label' => 'Producers & Manufacturers'],
                            ['icon' => 'shield-check',        'label' => 'Halal Certified Suppliers'],
                            ['icon' => 'truck',               'label' => 'Logistics & Cold Chain Providers'],
                            ['icon' => 'rectangle-stack',     'label' => 'Distributors & Wholesalers'],
                            ['icon' => 'building-storefront', 'label' => 'Retailers & Halal Businesses'],
                            ['icon' => 'user-group',          'label' => 'Consumers (B2C)'],
                        ] as $item)
                            <li class="flex items-center gap-2.5 sm:gap-3 py-2 sm:py-2.5">
                                <div class="flex size-7 sm:size-8 shrink-0 items-center justify-center rounded-lg bg-white/[0.06] text-[var(--hs-gold-300)]">
                                    <flux:icon :icon="$item['icon']" class="size-4" />
                                </div>
                                <span class="text-[0.85rem] sm:text-[0.9rem] font-semibold text-white/90">{{ __($item['label']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- What We Offer (light) --}}
                <div id="what-we-offer" class="rounded-[var(--hs-radius-panel)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-0)] p-5 sm:p-7 shadow-[var(--hs-shadow-sm)]">
                    <div class="flex items-center gap-3 mb-4 sm:mb-5">
                        <div class="flex size-9 sm:size-10 items-center justify-center rounded-xl bg-[var(--hs-green-50)] text-[var(--hs-green-700)]">
                            <flux:icon.shopping-bag class="size-5" />
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold text-[var(--hs-neutral-900)]">{{ __('What We Offer') }}</h3>
                    </div>
                    <ul class="divide-y divide-[var(--hs-neutral-100)]">
                        @foreach ([
                            ['icon' => 'clipboard-document-list', 'label' => 'Halal-verified suppliers directory'],
                            ['icon' => 'shopping-bag',            'label' => 'Products & services sourcing (B2B & B2C)'],
                            ['icon' => 'shield-check',            'label' => 'Halal certification awareness & support'],
                            ['icon' => 'map',                     'label' => 'Supply chain visibility & traceability'],
                            ['icon' => 'paper-airplane',          'label' => 'Logistics & export assistance'],
                            ['icon' => 'link',                    'label' => 'Business matching & partnerships'],
                            ['icon' => 'academic-cap',            'label' => 'Halal events, training & consultancy'],
                        ] as $item)
                            <li class="flex items-center gap-2.5 sm:gap-3 py-2 sm:py-2.5">
                                <div class="flex size-7 sm:size-8 shrink-0 items-center justify-center rounded-lg bg-[var(--hs-green-50)] text-[var(--hs-green-700)]">
                                    <flux:icon :icon="$item['icon']" class="size-4" />
                                </div>
                                <span class="text-[0.85rem] sm:text-[0.9rem] font-semibold text-[var(--hs-neutral-700)]">{{ __($item['label']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

            </div>
        </section>

        {{-- ── Supply Chain Flow ───────────────────────────────── --}}
        <section id="supply-chain" class="bg-[var(--hs-neutral-0)] border-t border-b border-[var(--hs-neutral-100)] py-14 sm:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {{-- Header --}}
                <div class="text-center mb-10 sm:mb-14">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--hs-green-100)] bg-[var(--hs-green-50)] px-3.5 py-1.5 text-[0.75rem] font-bold tracking-wide text-[var(--hs-green-700)]">
                        <flux:icon.arrow-path-rounded-square class="size-3.5" />
                        {{ __('End-to-End Traceability') }}
                    </span>
                    <h2 class="mt-4 text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[var(--hs-neutral-900)] leading-tight tracking-tight max-w-[20ch] mx-auto">
                        {{ __('The halal supply chain we connect') }}
                    </h2>
                </div>

                {{-- Steps grid --}}
                <div class="relative">
                    {{-- connecting line (desktop only) --}}
                    <div class="hidden lg:block absolute left-[7%] right-[7%] top-[34px] border-t-2 border-dashed border-[var(--hs-neutral-300)]"></div>

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
                                <div class="flex size-14 sm:size-16 lg:size-[68px] items-center justify-center rounded-full border-2 border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-[var(--hs-shadow-sm)] transition group-hover:border-[var(--hs-gold-500)] group-hover:text-[var(--hs-gold-600)] group-hover:-translate-y-1">
                                    <flux:icon :icon="$step['icon']" class="size-5 sm:size-6" />
                                </div>
                                <span class="mt-3 sm:mt-4 block text-[0.62rem] sm:text-[0.68rem] font-extrabold tracking-[0.06em] text-[var(--hs-gold-600)]">{{ __('STAGE :stage', ['stage' => $step['stage']]) }}</span>
                                <h4 class="mt-1 text-[0.8rem] sm:text-[0.88rem] font-extrabold text-[var(--hs-neutral-900)]">{{ __($step['title']) }}</h4>
                                <p class="mt-1 text-[0.72rem] sm:text-[0.76rem] leading-snug text-[var(--hs-neutral-500)]">{{ __($step['desc']) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ── Country Portals ─────────────────────────────────── --}}
        <section id="country-portals" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
            <div class="text-center mb-6 sm:mb-10">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--hs-green-100)] bg-[var(--hs-green-50)] px-3.5 py-1.5 text-[0.75rem] font-bold tracking-wide text-[var(--hs-green-700)]">
                    <flux:icon.globe-alt class="size-3.5" />
                    {{ __('Global Halal Network') }}
                </span>
                <h2 class="mt-4 text-xl sm:text-2xl lg:text-3xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">
                    {{ __('1HalalStore across the Halal world') }}
                </h2>
                <p class="mt-3 text-sm sm:text-base text-[var(--hs-neutral-500)] max-w-2xl mx-auto">
                    {{ __("Each country runs its own independent portal. Click to visit a portal, or browse the one you're on now.") }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:gap-5 sm:grid-cols-3 lg:grid-cols-6">

                {{-- Current instance — "You are here" --}}
                @php $p = $this->instancePortal; @endphp
                <div class="relative group flex flex-col rounded-3xl overflow-hidden shadow-[0_6px_32px_rgba(0,0,0,0.22)]"
                     style="min-height: 280px;">
                    {{-- Full-bleed landmark photo --}}
                    @if (!empty($p['landmark_image']))
                        <img src="{{ $p['landmark_image'] }}"
                             alt="{{ $p['display_name'] }} landmark"
                             class="absolute inset-0 w-full h-full object-cover object-center">
                    @endif

                    {{-- Top gradient + country name + flag --}}
                    <div class="relative z-10 flex-1 flex flex-col items-center justify-start pt-4 px-3"
                         style="background: linear-gradient(to bottom, rgba(3,25,16,0.78) 0%, rgba(3,25,16,0.18) 55%, transparent 100%);">
                        <p class="text-[0.72rem] sm:text-[0.85rem] font-black tracking-[0.12em] text-white uppercase leading-tight text-center drop-shadow-[0_1px_4px_rgba(0,0,0,0.9)] line-clamp-2">
                            {{ $p['card_heading'] ?? $p['display_name'] }}
                        </p>
                        <span class="text-4xl sm:text-5xl leading-none mt-2 drop-shadow-[0_2px_8px_rgba(0,0,0,0.7)]" aria-hidden="true">{{ $p['flag'] }}</span>
                    </div>

                    {{-- Bottom gradient + "You are here" badge only --}}
                    <div class="relative z-10 px-2 pt-8 pb-3 text-center"
                         style="background: linear-gradient(to top, rgba(3,25,16,0.88) 0%, rgba(3,25,16,0.50) 55%, transparent 100%);">
                        <span class="inline-flex items-center gap-0.5 rounded-full bg-[var(--hs-green-600)] px-2 py-0.5 text-[0.55rem] font-bold text-white uppercase tracking-wide">
                            <flux:icon.map-pin class="size-2.5 shrink-0" />
                            {{ __('You are here') }}
                        </span>
                    </div>
                </div>

                {{-- Other country portals --}}
                @foreach ($this->otherPortals as $portal)
                    @php
                        $tag      = $portal['url'] ? 'a' : 'div';
                        $attrs    = $portal['url']
                            ? 'href="'.e($portal['url']).'" target="_blank" rel="noopener noreferrer"'
                            : '';
                        $isActive = (bool) $portal['url'];
                    @endphp

                    <{{ $tag }} {{ $attrs }}
                        class="relative group flex flex-col rounded-3xl overflow-hidden shadow-[0_4px_24px_rgba(0,0,0,0.16)] {{ $isActive ? 'cursor-pointer transition-transform hover:-translate-y-1 hover:shadow-[0_12px_40px_rgba(0,0,0,0.26)]' : '' }}"
                        style="min-height: 280px;"
                    >
                        {{-- Full-bleed landmark photo --}}
                        @if (!empty($portal['landmark_image']))
                            <img src="{{ $portal['landmark_image'] }}"
                                 alt="{{ $portal['display_name'] }} landmark"
                                 class="absolute inset-0 w-full h-full object-cover object-center {{ $isActive ? 'transition-transform duration-500 group-hover:scale-105' : '' }}">
                        @endif

                        {{-- Top gradient + country name + flag --}}
                        <div class="relative z-10 flex-1 flex flex-col items-center justify-start pt-4 px-3"
                             style="background: linear-gradient(to bottom, rgba(3,25,16,0.78) 0%, rgba(3,25,16,0.18) 55%, transparent 100%);">
                            <p class="text-[0.72rem] sm:text-[0.85rem] font-black tracking-[0.12em] text-white uppercase leading-tight text-center drop-shadow-[0_1px_4px_rgba(0,0,0,0.9)] line-clamp-2">
                                {{ $portal['card_heading'] ?? $portal['display_name'] }}
                            </p>
                            <span class="text-4xl sm:text-5xl leading-none mt-2 drop-shadow-[0_2px_8px_rgba(0,0,0,0.7)]" aria-hidden="true">{{ $portal['flag'] }}</span>
                        </div>

                        {{-- Bottom gradient + status only --}}
                        <div class="relative z-10 px-2 pt-8 pb-3 text-center"
                             style="background: linear-gradient(to top, rgba(3,25,16,0.88) 0%, rgba(3,25,16,0.50) 55%, transparent 100%);">
                            @if ($isActive)
                                <span class="inline-flex items-center gap-0.5 text-[0.62rem] font-semibold text-white/80">
                                    {{ __('Visit Portal') }}
                                    <flux:icon.arrow-top-right-on-square class="size-2.5 shrink-0" />
                                </span>
                            @else
                                <span class="inline-flex items-center gap-0.5 rounded-full bg-white/20 px-2 py-0.5 text-[0.55rem] font-bold text-white uppercase tracking-wide backdrop-blur-sm">
                                    {{ __('Coming Soon') }}
                                </span>
                            @endif
                        </div>
                    </{{ $tag }}>
                @endforeach

            </div>
        </section>

        {{-- ── Can't Find What You're Looking For? / Sourcing ─── --}}
        <section id="sourcing" class="bg-[var(--hs-neutral-50)] border-t border-b border-[var(--hs-neutral-200)]">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 text-center">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--hs-green-100)] bg-[var(--hs-green-50)] px-3.5 py-1.5 text-[0.75rem] font-bold tracking-wide text-[var(--hs-green-700)]">
                    <flux:icon.magnifying-glass class="size-3.5" />
                    {{ __('Custom Sourcing') }}
                </span>
                <h2 class="mt-4 text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[var(--hs-neutral-900)] leading-tight tracking-tight">
                    {{ __('Can\'t Find What You\'re Looking For?') }}
                </h2>
                <p class="mt-3 text-base sm:text-lg font-semibold text-[var(--hs-neutral-900)]">
                    {{ __('We can source it for you.') }}
                </p>
                <p class="mx-auto mt-3 text-sm sm:text-base text-[var(--hs-neutral-500)] leading-relaxed max-w-2xl">
                    {{ __('Our product listings are just the beginning. If you\'re looking for a specific product, custom specification, or item that isn\'t currently listed, our sourcing team can help you find it.') }}
                </p>
                <p class="mx-auto mt-3 text-sm sm:text-base text-[var(--hs-neutral-500)] leading-relaxed max-w-2xl">
                    {{ __('Tell us what you need, and we\'ll work with our supplier network to find suitable options based on your requirements, quantity, and target budget.') }}
                </p>
                <div class="mt-6">
                    <a href="{{ route('sourcing-request.create') }}" wire:navigate
                       class="inline-flex items-center gap-2 rounded-full bg-[var(--hs-green-600)] px-6 py-3 text-sm font-semibold text-white hover:bg-[var(--hs-green-700)] transition shadow-sm">
                        <flux:icon.paper-airplane class="size-4" />
                        {{ __('Request a Product') }}
                    </a>
                </div>
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
                        {{ __('Register your business, list your products, and connect with buyers across Southeast Asia and the Middle East.') }}
                    </p>
                    <div class="mt-7 sm:mt-8 flex flex-col sm:flex-row flex-wrap justify-center gap-3">
                        <a href="{{ route('register') }}" wire:navigate
                           class="inline-flex items-center justify-center rounded-full bg-[var(--hs-neutral-0)] px-6 py-3 text-sm font-semibold text-[var(--hs-green-900)] hover:bg-[var(--hs-neutral-100)] transition shadow">
                            {{ __('Register Your Business') }}
                        </a>
                        <a href="{{ route('storefront.companies') }}" wire:navigate
                           class="inline-flex items-center justify-center rounded-full border border-[var(--hs-green-700)] px-6 py-3 text-sm font-semibold text-white hover:bg-[var(--hs-green-900)] transition">
                            {{ __('Browse Directory') }}
                        </a>
                        <a href="{{ route('apply.certificate') }}" wire:navigate
                           class="inline-flex items-center justify-center rounded-full border border-[var(--hs-green-600)] px-6 py-3 text-sm font-semibold text-white hover:bg-[var(--hs-green-800)] transition">
                            {{ __('Apply for Certification') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>