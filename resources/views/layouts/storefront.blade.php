<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? '1Halal Store' }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    @fonts
    @fluxAppearance
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    @if(config('services.google_analytics.measurement_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.measurement_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ config('services.google_analytics.measurement_id') }}');
        </script>
    @endif
</head>
<body class="min-h-screen flex flex-col bg-[var(--hs-neutral-50)]" x-data="{ sfNav: false }" @keydown.escape.window="sfNav = false">

    {{-- ══════════════════════════════════════════
         STOREFRONT MOBILE NAV DRAWER (sm:hidden)
    ══════════════════════════════════════════ --}}

    {{-- Backdrop --}}
    <div x-show="sfNav"
         x-transition.opacity.duration.200ms
         @click="sfNav = false"
         class="fixed inset-0 z-[9998] bg-black/50"
         style="display:none;">
    </div>

    {{-- Drawer --}}
    <div x-show="sfNav"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="transform -translate-x-full"
         x-transition:enter-end="transform translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="transform translate-x-0"
         x-transition:leave-end="transform -translate-x-full"
         class="fixed top-0 left-0 h-screen w-[290px] z-[9999] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-lg)] overflow-y-auto"
         style="display:none;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-[var(--hs-neutral-200)]">
            <a href="{{ route('home') }}" wire:navigate @click="sfNav = false">
                <img src="/images/brand-logo.png" alt="{{ __('1Halal Store') }}" class="h-8 w-auto">
            </a>
            <button @click="sfNav = false"
                    class="size-8 flex items-center justify-center rounded-lg text-[var(--hs-neutral-500)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)] transition border-none bg-transparent cursor-pointer">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Nav links --}}
        <nav class="p-3 space-y-0.5">

            <a href="{{ route('storefront.companies') }}" wire:navigate @click="sfNav = false"
               @class([
                   'flex items-center gap-3 px-3 py-3 rounded-[10px] text-[0.9rem] font-semibold no-underline',
                   'text-[var(--hs-green-700)] bg-[var(--hs-green-50)]' => request()->routeIs('storefront.companies', 'storefront.company'),
                   'text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-100)]' => ! request()->routeIs('storefront.companies', 'storefront.company'),
               ])>
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-[var(--hs-neutral-400)] shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M13 2H3a1 1 0 0 0-1 1v18a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V9l-9-7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 9v11h18V9"/></svg>
                {{ __('Businesses') }}
            </a>

            <a href="{{ route('storefront.products') }}" wire:navigate @click="sfNav = false"
               @class([
                   'flex items-center gap-3 px-3 py-3 rounded-[10px] text-[0.9rem] font-semibold no-underline',
                   'text-[var(--hs-green-700)] bg-[var(--hs-green-50)]' => request()->routeIs('storefront.products', 'storefront.product'),
                   'text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-100)]' => ! request()->routeIs('storefront.products', 'storefront.product'),
               ])>
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-[var(--hs-neutral-400)] shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M6 8h12l1 12H5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                {{ __('Products') }}
            </a>

            <div class="px-3 pt-4 pb-1.5 text-[0.65rem] font-bold uppercase tracking-[0.1em] text-[var(--hs-neutral-400)]">
                {{ __('Discover') }}
            </div>

            @foreach ([
                ['anchor' => 'who-we-serve',  'label' => __('Who We Serve')],
                ['anchor' => 'what-we-offer', 'label' => __('What We Offer')],
                ['anchor' => 'supply-chain',  'label' => __('Supply Chain')],
                ['anchor' => 'join',          'label' => __('Join Us')],
            ] as $item)
                <a href="{{ route('home') }}#{{ $item['anchor'] }}"
                   @click.prevent="sfNav = false; setTimeout(() => {
                       const t = document.getElementById('{{ $item['anchor'] }}');
                       if (t) t.scrollIntoView({ behavior:'smooth' });
                       else window.location.href = '{{ route('home') }}#{{ $item['anchor'] }}';
                   }, 200);"
                   class="flex items-center gap-3 px-3 py-3 rounded-[10px] text-[0.9rem] font-semibold text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-100)] no-underline">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-[var(--hs-neutral-400)] shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach

            <a href="{{ route('apply.certificate') }}" wire:navigate @click="sfNav = false"
               @class([
                   'flex items-center gap-3 px-3 py-3 rounded-[10px] text-[0.9rem] font-semibold no-underline',
                   'text-[var(--hs-green-700)] bg-[var(--hs-green-50)]' => request()->routeIs('apply.certificate'),
                   'text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-100)]' => ! request()->routeIs('apply.certificate'),
               ])>
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-[var(--hs-neutral-400)] shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                {{ __('Halal Certification') }}
            </a>
        </nav>

        {{-- Auth footer --}}
        @auth
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]">
                <div class="flex items-center gap-2.5 px-3 py-2.5 bg-[var(--hs-neutral-100)] rounded-[10px] mb-2">
                    <span class="size-8 rounded-full bg-[var(--hs-green-600)] text-white flex items-center justify-center text-xs font-bold shrink-0">
                        {{ strtoupper(mb_substr(auth()->user()->first_name,0,1).mb_substr(auth()->user()->last_name,0,1)) }}
                    </span>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-[var(--hs-neutral-900)] truncate">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</div>
                        <div class="text-xs text-[var(--hs-neutral-500)] truncate">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-2.5 w-full px-3 py-2.5 rounded-[10px] bg-transparent border-none cursor-pointer text-sm font-semibold text-[var(--hs-danger-700)] hover:bg-[var(--hs-danger-50)] transition">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                        {{ __('Log out') }}
                    </button>
                </form>
            </div>
        @endauth

    </div>

    {{-- Sticky Header --}}
    @include('partials.portal-header')

    {{-- Main Content --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-[var(--hs-green-950)] text-white mt-auto">
        {{-- Top grid: brand + 3 link columns --}}
        <div class="border-b border-white/[0.08]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 sm:py-16 pb-8 sm:pb-12">
                <div class="grid grid-cols-1 gap-8 sm:gap-10 sm:grid-cols-2 lg:grid-cols-[1.4fr_repeat(3,1fr)]">

                    {{-- Brand column --}}
                    <div class="text-center sm:text-left col-span-1 sm:col-span-2 lg:col-span-1">
                        <div class="flex items-center justify-center sm:justify-start gap-3 mb-4">
                            <img src="/images/brand-logo.png" alt="{{ __('1Halal Store') }}" class="h-10 sm:h-12 w-auto opacity-90">
                            <img src="/images/halal-stamp.png" alt="{{ __('Halal Certified') }}" class="h-10 sm:h-12 w-auto">
                        </div>
                        <p class="text-sm text-white/55 leading-relaxed max-w-[34ch] mx-auto sm:mx-0">
                            {{ __('Your halal supply-chain marketplace — connecting farmers, producers, certifiers, logistics providers, retailers and consumers in one trusted ecosystem.') }}
                        </p>
                    </div>

                    {{-- Platform --}}
                    <div class="text-center sm:text-left">
                        <h4 class="text-[0.82rem] font-semibold text-white uppercase tracking-[0.03em] mb-3 sm:mb-4">{{ __('Platform') }}</h4>
                        <ul class="grid gap-2.5 sm:gap-[11px]">
                            <li><a href="{{ route('home') }}#who-we-serve" class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Who We Serve') }}</a></li>
                            <li><a href="{{ route('home') }}#what-we-offer" class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('What We Offer') }}</a></li>
                            <li><a href="{{ route('home') }}#supply-chain" class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Supply Chain') }}</a></li>
                            <li><a href="{{ route('storefront.companies') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Browse Businesses') }}</a></li>
                            <li><a href="{{ route('storefront.products') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Browse Products') }}</a></li>
                        </ul>
                    </div>

                    {{-- Business --}}
                    <div class="text-center sm:text-left">
                        <h4 class="text-[0.82rem] font-semibold text-white uppercase tracking-[0.03em] mb-3 sm:mb-4">{{ __('Business') }}</h4>
                        <ul class="grid gap-2.5 sm:gap-[11px]">
                            <li><a href="#" class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('About Us') }}</a></li>
                            <li><a href="{{ route('apply.certificate') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Halal Certification') }}</a></li>
                            <li><a href="#" class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Events & Training') }}</a></li>
                        </ul>
                    </div>

                    {{-- Get Started --}}
                    <div class="text-center sm:text-left">
                        <h4 class="text-[0.82rem] font-semibold text-white uppercase tracking-[0.03em] mb-3 sm:mb-4">{{ __('Get Started') }}</h4>
                        <ul class="grid gap-2.5 sm:gap-[11px]">
                            <li><a href="{{ route('register') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('List Your Business') }}</a></li>
                            <li><a href="{{ route('storefront.companies') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Find Suppliers') }}</a></li>
                            <li><a href="{{ route('login') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Seller Login') }}</a></li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>

        {{-- Bottom bar: trust badges + copyright --}}
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-5 sm:py-6">
            <div class="flex flex-col items-center gap-5 sm:flex-row sm:justify-between sm:gap-4">

                {{-- Trust badges --}}
                <div class="grid grid-cols-2 gap-x-4 gap-y-4 w-full sm:flex sm:w-auto sm:flex-wrap sm:justify-start sm:gap-5">
                    @foreach ([
                        ['icon' => 'check-badge',       'label' => 'Halal Certification Support'],
                        ['icon' => 'globe-alt',          'label' => 'Market Access Global & Local'],
                        ['icon' => 'arrow-trending-up',  'label' => 'Business Growth Solutions'],
                        ['icon' => 'shield-check',       'label' => 'Trusted Halal Ecosystem'],
                    ] as $badge)
                        <div class="flex items-center gap-2 sm:gap-2.5">
                            <div class="flex size-7 sm:size-8 shrink-0 items-center justify-center rounded-[9px] bg-white/[0.06] text-[var(--hs-gold-400)]">
                                <flux:icon :icon="$badge['icon']" class="size-[15px] sm:size-[17px]" />
                            </div>
                            <span class="text-[0.72rem] sm:text-[0.78rem] font-semibold text-white/75 max-w-[14ch] leading-snug">{{ __($badge['label']) }}</span>
                        </div>
                    @endforeach
                </div>

                <span class="text-[0.72rem] sm:text-[0.78rem] text-white/40 text-center sm:text-right">
                    &copy; {{ date('Y') }} 1HalalStore.com — {{ __('All rights reserved.') }}
                </span>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
