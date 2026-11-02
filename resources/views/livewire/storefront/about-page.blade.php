@push('meta')
    <meta name="description" content="{{ __('Learn about :app — the trusted halal supply-chain marketplace connecting farmers, producers, certifiers, and retailers in one ecosystem.', ['app' => config('app.name')]) }}">

    <meta property="og:type"        content="website">
    <meta property="og:url"         content="{{ url()->current() }}">
    <meta property="og:title"       content="{{ __('About Us — :app', ['app' => config('app.name')]) }}">
    <meta property="og:description" content="{{ __('Learn about :app — the trusted halal supply-chain marketplace connecting farmers, producers, certifiers, and retailers in one ecosystem.', ['app' => config('app.name')]) }}">
    <meta property="og:image"       content="{{ asset('images/brand-logo.png') }}">
    <meta property="og:site_name"   content="{{ config('app.name') }}">

    <meta name="twitter:card"        content="summary">
    <meta name="twitter:title"       content="{{ __('About Us — :app', ['app' => config('app.name')]) }}">
    <meta name="twitter:description" content="{{ __('Learn about :app — the trusted halal supply-chain marketplace connecting farmers, producers, certifiers, and retailers in one ecosystem.', ['app' => config('app.name')]) }}">
    <meta name="twitter:image"       content="{{ asset('images/brand-logo.png') }}">
@endpush

<style>
    html { scroll-behavior: smooth; }
    @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
</style>
<div class="bg-[var(--hs-neutral-50)]">

    {{-- Hero band --}}
    <div class="bg-[var(--hs-green-950)]">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 pt-10 sm:pt-14 pb-10 sm:pb-14">
            <nav class="flex items-center gap-2 text-xs text-white/50" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" wire:navigate class="hover:text-[var(--hs-gold-300)] transition">{{ __('Home') }}</a>
                <span aria-hidden="true">/</span>
                <span class="font-semibold text-white/90">{{ __('About Us') }}</span>
            </nav>
            <h1 class="mt-4 text-3xl sm:text-4xl font-bold tracking-tight text-white">{{ __('About 1HalalStore.com') }}</h1>
            <p class="mt-3 max-w-2xl text-sm sm:text-base text-white/70 leading-relaxed">
                {{ __('A B2B marketplace built to connect the entire halal supply chain — from farm to plate, locally and globally.') }}
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">

        {{-- Mission & Vision side-by-side --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mb-14">

            {{-- Mission --}}
            <div class="rounded-2xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-7 sm:p-8">
                <div class="mb-4 flex size-11 items-center justify-center rounded-xl bg-[var(--hs-green-50)]">
                    <svg class="size-6 text-[var(--hs-green-600)]" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-[var(--hs-neutral-900)] mb-3">{{ __('Our Mission') }}</h2>
                <p class="text-[0.95rem] text-[var(--hs-neutral-600)] leading-relaxed">
                    {{ __('To build a trusted, technology-enabled marketplace that connects every player in the halal supply chain, from farmers and producers to certifiers, logistics providers, retailers and consumers. We make halal sourcing transparent, traceable and accessible, locally and globally.') }}
                </p>
            </div>

            {{-- Vision --}}
            <div class="rounded-2xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-7 sm:p-8">
                <div class="mb-4 flex size-11 items-center justify-center rounded-xl bg-[var(--hs-gold-50)]">
                    <svg class="size-6 text-[var(--hs-gold-600)]" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-[var(--hs-neutral-900)] mb-3">{{ __('Our Vision') }}</h2>
                <p class="text-[0.95rem] text-[var(--hs-neutral-600)] leading-relaxed">
                    {{ __('To be the most trusted halal ecosystem in the world, where every product can be traced from farm to plate with integrity, and where halal businesses of every size can grow and reach new markets.') }}
                </p>
            </div>

        </div>

        {{-- About section --}}
        <div class="mb-14">
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-[var(--hs-neutral-900)] mb-6">{{ __('What We Do') }}</h2>
            <div class="prose-like max-w-none space-y-5 text-[0.96rem] text-[var(--hs-neutral-700)] leading-relaxed">
                <p>
                    {{ __('1HalalStore.com is a B2B marketplace built to connect the entire halal supply chain, not just to be another storefront. We bring farmers and fisherfolk, producers and manufacturers, certified suppliers, cold chain and logistics providers, distributors, retailers and consumers into one trusted platform.') }}
                </p>
                <p>
                    {{ __('Trust is our foundation. Every listed supplier is verified against Philippine halal certification standards through the Maslaha Halal Certification Board (MHCB). Our approach goes beyond a certificate: true halal compliance means integrity in transport, temperature control and farm-level custody.') }}
                </p>
            </div>
        </div>

        {{-- What you can do --}}
        <div class="mb-14">
            <h2 class="text-xl font-bold text-[var(--hs-neutral-900)] mb-5">{{ __('On 1HalalStore.com, you can:') }}</h2>
            <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ([
                    ['icon' => 'building-storefront', 'text' => __('Find halal-verified suppliers and source products in bulk')],
                    ['icon' => 'magnifying-glass', 'text' => __('Request custom sourcing when you can\'t find what you need')],
                    ['icon' => 'shield-check', 'text' => __('Get halal certification awareness and support')],
                    ['icon' => 'arrow-trending-up', 'text' => __('Gain visibility and traceability across the supply chain')],
                    ['icon' => 'truck', 'text' => __('Access logistics and export assistance')],
                    ['icon' => 'user-group', 'text' => __('Join business matching, events, training and consultancy')],
                ] as $item)
                    <li class="flex items-start gap-3 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-5 py-4">
                        <div class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-[var(--hs-green-50)]">
                            <flux:icon :icon="$item['icon']" class="size-4 text-[var(--hs-green-600)]" />
                        </div>
                        <span class="text-[0.92rem] text-[var(--hs-neutral-700)]">{{ $item['text'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Global reach --}}
        <div class="mb-14 rounded-2xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-7 sm:p-8">
            <h2 class="text-xl font-bold text-[var(--hs-neutral-900)] mb-3">{{ __('Our Global Reach') }}</h2>
            <p class="text-[0.95rem] text-[var(--hs-neutral-700)] leading-relaxed">
                {{ __('We began in the Philippines, and each country runs its own independent portal. We are expanding to Malaysia, Saudi Arabia, Brunei, Indonesia, Türkiye and Kenya, so halal businesses can connect across Southeast Asia, the Middle East and Africa.') }}
            </p>
        </div>

        {{-- Values --}}
        <div>
            <h2 class="text-xl font-bold text-[var(--hs-neutral-900)] mb-6">{{ __('Our Values') }}</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

                @foreach ([
                    [
                        'icon'  => 'shield-check',
                        'title' => __('100% Halal Focused'),
                        'body'  => __('Integrity first, always. Every supplier, product and process upholds genuine halal standards.'),
                        'color' => 'green',
                    ],
                    [
                        'icon'  => 'globe-alt',
                        'title' => __('Global Market Access'),
                        'body'  => __('Opening doors for local producers to reach buyers across Southeast Asia, the Middle East and Africa.'),
                        'color' => 'gold',
                    ],
                    [
                        'icon'  => 'cpu-chip',
                        'title' => __('Digital Innovation'),
                        'body'  => __('Technology that makes trust verifiable — from farm-level custody to end-consumer traceability.'),
                        'color' => 'green',
                    ],
                    [
                        'icon'  => 'user-group',
                        'title' => __('Community Empowerment'),
                        'body'  => __('Lifting farmers, SMEs and communities through market connections and capability building.'),
                        'color' => 'gold',
                    ],
                    [
                        'icon'  => 'arrow-trending-up',
                        'title' => __('Sustainable & Ethical Growth'),
                        'body'  => __('Responsible sourcing at every stage of the supply chain, from cultivation to consumption.'),
                        'color' => 'green',
                    ],
                ] as $value)
                    <div class="rounded-2xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-6">
                        <div class="mb-3 flex size-10 items-center justify-center rounded-xl {{ $value['color'] === 'gold' ? 'bg-[var(--hs-gold-50)]' : 'bg-[var(--hs-green-50)]' }}">
                            <flux:icon :icon="$value['icon']" class="size-5 {{ $value['color'] === 'gold' ? 'text-[var(--hs-gold-600)]' : 'text-[var(--hs-green-600)]' }}" />
                        </div>
                        <h3 class="text-[0.96rem] font-bold text-[var(--hs-neutral-900)] mb-1.5">{{ $value['title'] }}</h3>
                        <p class="text-[0.88rem] text-[var(--hs-neutral-600)] leading-relaxed">{{ $value['body'] }}</p>
                    </div>
                @endforeach

            </div>
        </div>

        {{-- CTA --}}
        <div class="mt-14 rounded-2xl bg-[var(--hs-green-950)] px-7 py-10 sm:px-12 text-center">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-2">{{ __('Ready to join the halal ecosystem?') }}</h2>
            <p class="text-sm text-white/65 mb-6 max-w-xl mx-auto">
                {{ __('List your business, find verified suppliers, or request custom sourcing — all in one trusted marketplace.') }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('register') }}" wire:navigate
                   class="inline-flex items-center rounded-[var(--hs-radius-pill)] bg-[var(--hs-gold-500)] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[var(--hs-gold-600)] transition shadow-[var(--hs-shadow-sm)]">
                    {{ __('List Your Business') }}
                </a>
                <a href="{{ route('storefront.products') }}" wire:navigate
                   class="inline-flex items-center rounded-[var(--hs-radius-pill)] border border-white/25 px-6 py-2.5 text-sm font-semibold text-white hover:bg-white/10 transition">
                    {{ __('Browse Products') }}
                </a>
            </div>
        </div>

    </div>
</div>
