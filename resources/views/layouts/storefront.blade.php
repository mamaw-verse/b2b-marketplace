<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? '1Halal Store' }}</title>

    {{-- Per-page SEO / Open Graph meta (pushed by individual views) --}}
    @stack('meta')

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    <link rel="preconnect" href="https://challenges.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://challenges.cloudflare.com">

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

            <a href="{{ route('storefront.products') }}" wire:navigate @click="sfNav = false"
               @class([
                   'flex items-center gap-3 px-3 py-3 rounded-[10px] text-[0.9rem] font-semibold no-underline',
                   'text-[var(--hs-green-700)] bg-[var(--hs-green-50)]' => request()->routeIs('storefront.products', 'storefront.product'),
                   'text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-100)]' => ! request()->routeIs('storefront.products', 'storefront.product'),
               ])>
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-[var(--hs-neutral-400)] shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M6 8h12l1 12H5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                {{ __('Products') }}
            </a>

            <a href="{{ route('apply.certificate') }}" wire:navigate @click="sfNav = false"
               @class([
                   'flex items-center gap-3 px-3 py-3 rounded-[10px] text-[0.9rem] font-semibold no-underline',
                   'text-[var(--hs-green-700)] bg-[var(--hs-green-50)]' => request()->routeIs('apply.certificate'),
                   'text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-100)]' => ! request()->routeIs('apply.certificate'),
               ])>
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-[var(--hs-neutral-400)] shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                {{ __('Certification') }}
            </a>

            <a href="{{ route('sourcing-request.create') }}" wire:navigate @click="sfNav = false"
               @class([
                   'flex items-center gap-3 px-3 py-3 rounded-[10px] text-[0.9rem] font-semibold no-underline',
                   'text-[var(--hs-green-700)] bg-[var(--hs-green-50)]' => request()->routeIs('sourcing-request.create'),
                   'text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-100)]' => ! request()->routeIs('sourcing-request.create'),
               ])>
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-[var(--hs-neutral-400)] shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z"/></svg>
                {{ __('Sourcing') }}
            </a>

            <a href="{{ route('about') }}" wire:navigate @click="sfNav = false"
               @class([
                   'flex items-center gap-3 px-3 py-3 rounded-[10px] text-[0.9rem] font-semibold no-underline',
                   'text-[var(--hs-green-700)] bg-[var(--hs-green-50)]' => request()->routeIs('about'),
                   'text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-100)]' => ! request()->routeIs('about'),
               ])>
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-[var(--hs-neutral-400)] shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2Zm0 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm0 5c.55 0 1 .45 1 1v4c0 .55-.45 1-1 1s-1-.45-1-1v-4c0-.55.45-1 1-1Z"/></svg>
                {{ __('About') }}
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

        {{-- ══════════════════════════════════════════
             MOBILE FOOTER (sm:hidden)
             Accordion-based, left-aligned, compact
             Reduces scroll height ~35% vs flat link lists
        ══════════════════════════════════════════ --}}
        <div class="sm:hidden"
             x-data="{ open: '' }"
             style="padding-bottom: env(safe-area-inset-bottom);">

            {{-- Logo + Halal Seal row (left-aligned, seal ≥48px) --}}
            <div class="flex items-center gap-3 px-5 pt-8 pb-2">
                <img src="/images/brand-logo.png" alt="{{ __('1Halal Store') }}" class="h-10 w-auto opacity-90">
                <img src="/images/halal-stamp.png" alt="{{ __('Halal Certified') }}" class="h-12 w-auto">
            </div>

            {{-- Shortened tagline (14px, WCAG AA contrast on dark green) --}}
            <p class="px-5 text-sm text-white/70 leading-relaxed max-w-[32ch]">
                {{ __('The trusted halal supply-chain marketplace.') }}
            </p>

            {{-- Primary CTA row (2-up, 48px high) --}}
            <div class="flex gap-2 px-5 pt-4 pb-6">
                <a href="{{ route('register') }}" wire:navigate
                   class="flex-1 h-12 flex items-center justify-center rounded-[var(--hs-radius-sm)] bg-[var(--hs-gold-500)] text-[var(--hs-green-950)] font-semibold text-sm hover:bg-[var(--hs-gold-400)] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--hs-gold-400)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--hs-green-950)]">
                    {{ __('List Your Business') }}
                </a>
                <a href="{{ route('sourcing-request.create') }}" wire:navigate
                   class="flex-1 h-12 flex items-center justify-center rounded-[var(--hs-radius-sm)] border border-white/30 text-white font-semibold text-sm hover:border-white/50 hover:bg-white/[0.06] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--hs-gold-400)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--hs-green-950)]">
                    {{ __('Request Sourcing') }}
                </a>
            </div>

            {{-- Trust Badges 2×2 Grid (placed high to build trust early) --}}
            <div class="grid grid-cols-2 gap-2 px-5 pb-6">
                @foreach ([
                    ['icon' => 'check-badge',       'label' => 'Certification Support'],
                    ['icon' => 'globe-alt',          'label' => 'Global & Local Access'],
                    ['icon' => 'arrow-trending-up',  'label' => 'Growth Solutions'],
                    ['icon' => 'shield-check',       'label' => 'Trusted Ecosystem'],
                ] as $badge)
                    <div class="flex items-center gap-2.5 rounded-[var(--hs-radius-md)] border border-white/[0.12] px-3 py-2.5">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white/[0.08] text-[var(--hs-gold-400)]">
                            <flux:icon :icon="$badge['icon']" class="size-4" />
                        </div>
                        <span class="text-xs font-semibold text-white/80 leading-snug">{{ __($badge['label']) }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Divider --}}
            <div class="h-px bg-white/[0.12] mx-5"></div>

            {{-- Accordion Link Groups --}}
            {{-- Accordions chosen over 2-col grid: on 360px, columns are ~160px each,
                 causing link text to wrap. Accordions give full width + 44px tap targets. --}}
            <div class="px-5">

                {{-- Discover --}}
                <div class="border-b border-white/[0.12]">
                    <button @click="open === 'discover' ? open = '' : open = 'discover'"
                            aria-expanded="false"
                            :aria-expanded="open === 'discover'"
                            aria-controls="footer-mobile-discover"
                            class="flex items-center justify-between w-full min-h-[44px] py-3 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--hs-gold-400)] focus-visible:ring-inset rounded-sm">
                        <span class="text-[0.8rem] font-semibold uppercase tracking-[0.08em] text-[var(--hs-gold-400)]">{{ __('Discover') }}</span>
                        <svg data-accordion-chevron class="size-4 text-white/60 transition-transform duration-200" :class="open === 'discover' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                    <div id="footer-mobile-discover"
                         data-accordion-content
                         x-show="open === 'discover'"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         style="display:none;">
                        <ul class="pb-2">
                            <li><a href="{{ route('storefront.products') }}" wire:navigate class="flex items-center min-h-[44px] py-2 text-[0.94rem] text-white/80 hover:text-[var(--hs-gold-300)] transition">{{ __('Browse Products') }}</a></li>
                            <li><a href="{{ route('storefront.companies') }}" wire:navigate class="flex items-center min-h-[44px] py-2 text-[0.94rem] text-white/80 hover:text-[var(--hs-gold-300)] transition">{{ __('Browse Businesses') }}</a></li>
                            <li><a href="{{ route('apply.certificate') }}" wire:navigate class="flex items-center min-h-[44px] py-2 text-[0.94rem] text-white/80 hover:text-[var(--hs-gold-300)] transition">{{ __('Halal Certification') }}</a></li>
                            <li><a href="{{ route('sourcing-request.create') }}" wire:navigate class="flex items-center min-h-[44px] py-2 text-[0.94rem] text-white/80 hover:text-[var(--hs-gold-300)] transition">{{ __('Request Sourcing') }}</a></li>
                        </ul>
                    </div>
                </div>

                {{-- Company --}}
                <div class="border-b border-white/[0.12]">
                    <button @click="open === 'company' ? open = '' : open = 'company'"
                            aria-expanded="false"
                            :aria-expanded="open === 'company'"
                            aria-controls="footer-mobile-company"
                            class="flex items-center justify-between w-full min-h-[44px] py-3 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--hs-gold-400)] focus-visible:ring-inset rounded-sm">
                        <span class="text-[0.8rem] font-semibold uppercase tracking-[0.08em] text-[var(--hs-gold-400)]">{{ __('Company') }}</span>
                        <svg data-accordion-chevron class="size-4 text-white/60 transition-transform duration-200" :class="open === 'company' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                    <div id="footer-mobile-company"
                         data-accordion-content
                         x-show="open === 'company'"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         style="display:none;">
                        <ul class="pb-2">
                            <li><a href="{{ route('about') }}" wire:navigate class="flex items-center min-h-[44px] py-2 text-[0.94rem] text-white/80 hover:text-[var(--hs-gold-300)] transition">{{ __('About Us') }}</a></li>
                            <li><a href="{{ route('about') }}#mission" wire:navigate class="flex items-center min-h-[44px] py-2 text-[0.94rem] text-white/80 hover:text-[var(--hs-gold-300)] transition">{{ __('Our Mission') }}</a></li>
                            <li><a href="#" class="flex items-center min-h-[44px] py-2 text-[0.94rem] text-white/80 hover:text-[var(--hs-gold-300)] transition">{{ __('Events & Training') }}</a></li>
                            <li><a href="{{ route('help.faq') }}" wire:navigate class="flex items-center min-h-[44px] py-2 text-[0.94rem] text-white/80 hover:text-[var(--hs-gold-300)] transition">{{ __('FAQs') }}</a></li>
                        </ul>
                    </div>
                </div>

                {{-- Get Started --}}
                <div>
                    <button @click="open === 'started' ? open = '' : open = 'started'"
                            aria-expanded="false"
                            :aria-expanded="open === 'started'"
                            aria-controls="footer-mobile-started"
                            class="flex items-center justify-between w-full min-h-[44px] py-3 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--hs-gold-400)] focus-visible:ring-inset rounded-sm">
                        <span class="text-[0.8rem] font-semibold uppercase tracking-[0.08em] text-[var(--hs-gold-400)]">{{ __('Get Started') }}</span>
                        <svg data-accordion-chevron class="size-4 text-white/60 transition-transform duration-200" :class="open === 'started' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                    <div id="footer-mobile-started"
                         data-accordion-content
                         x-show="open === 'started'"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         style="display:none;">
                        <ul class="pb-2">
                            <li><a href="{{ route('register') }}" wire:navigate class="flex items-center min-h-[44px] py-2 text-[0.94rem] text-white/80 hover:text-[var(--hs-gold-300)] transition">{{ __('List Your Business') }}</a></li>
                            <li><a href="{{ route('storefront.companies') }}" wire:navigate class="flex items-center min-h-[44px] py-2 text-[0.94rem] text-white/80 hover:text-[var(--hs-gold-300)] transition">{{ __('Find Suppliers') }}</a></li>
                            <li><a href="{{ route('sourcing-request.create') }}" wire:navigate class="flex items-center min-h-[44px] py-2 text-[0.94rem] text-white/80 hover:text-[var(--hs-gold-300)] transition">{{ __('Source a Product') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Seller Login (visible secondary button, not buried in a link list) --}}
            <div class="px-5 pt-4 pb-4">
                <a href="{{ route('login') }}" wire:navigate
                   class="flex items-center justify-center h-11 w-full rounded-[var(--hs-radius-sm)] border border-white/25 text-white/90 text-sm font-medium hover:border-white/40 hover:bg-white/[0.06] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--hs-gold-400)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--hs-green-950)]">
                    {{ __('Seller Login') }}
                </a>
            </div>

            {{-- Social + Contact icons --}}
            <div class="flex items-center gap-3 px-5 pb-4">
                <a href="mailto:hello@1halalstore.com"
                   aria-label="{{ __('Email 1HalalStore') }}"
                   class="flex size-9 items-center justify-center rounded-full border border-white/15 text-white/70 hover:text-[var(--hs-gold-300)] hover:border-white/30 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--hs-gold-400)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--hs-green-950)]">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                </a>
                <a href="https://facebook.com/1halalstore" target="_blank" rel="noopener noreferrer"
                   aria-label="{{ __('1HalalStore on Facebook') }}"
                   class="flex size-9 items-center justify-center rounded-full border border-white/15 text-white/70 hover:text-[var(--hs-gold-300)] hover:border-white/30 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--hs-gold-400)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--hs-green-950)]">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="https://instagram.com/1halalstore" target="_blank" rel="noopener noreferrer"
                   aria-label="{{ __('1HalalStore on Instagram') }}"
                   class="flex size-9 items-center justify-center rounded-full border border-white/15 text-white/70 hover:text-[var(--hs-gold-300)] hover:border-white/30 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--hs-gold-400)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--hs-green-950)]">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/></svg>
                </a>
            </div>

            {{-- Divider --}}
            <div class="h-px bg-white/[0.12] mx-5"></div>

            {{-- Legal links (deduplicated — Terms & Privacy only, 44px tap height, no dot separators) --}}
            <nav class="flex items-center gap-6 px-5" aria-label="{{ __('Legal links') }}">
                <a href="{{ route('legal.terms') }}" wire:navigate class="text-[0.82rem] text-white/60 hover:text-[var(--hs-gold-300)] transition min-h-[44px] flex items-center">{{ __('Terms') }}</a>
                <a href="{{ route('legal.privacy') }}" wire:navigate class="text-[0.82rem] text-white/60 hover:text-[var(--hs-gold-300)] transition min-h-[44px] flex items-center">{{ __('Privacy') }}</a>
            </nav>

            {{-- Copyright (12px, muted, left-aligned) --}}
            <p class="px-5 pb-5 text-xs text-white/50">
                &copy; {{ date('Y') }} 1HalalStore.com — {{ __('All rights reserved.') }}
            </p>
        </div>

        {{-- ══════════════════════════════════════════
             DESKTOP FOOTER (hidden sm:block)
             Grid-based, spacious — preserved from original
             Fixed: removed duplicate About/FAQs from bottom bar
        ══════════════════════════════════════════ --}}
        <div class="hidden sm:block">
            {{-- Top grid: brand + 3 link columns --}}
            <div class="border-b border-white/[0.08]">
                <div class="mx-auto max-w-7xl px-6 lg:px-8 py-16 pb-12">
                    <div class="grid grid-cols-2 gap-10 lg:grid-cols-[1.4fr_repeat(3,1fr)]">

                        {{-- Brand column --}}
                        <div class="text-left col-span-2 lg:col-span-1">
                            <div class="flex items-center justify-start gap-3 mb-4">
                                <img src="/images/brand-logo.png" alt="{{ __('1Halal Store') }}" class="h-12 w-auto opacity-90">
                                <img src="/images/halal-stamp.png" alt="{{ __('Halal Certified') }}" class="h-12 w-auto">
                            </div>
                            <p class="text-sm text-white/55 leading-relaxed max-w-[34ch]">
                                {{ __('Your halal supply-chain marketplace — connecting farmers, producers, certifiers, logistics providers, retailers and consumers in one trusted ecosystem.') }}
                            </p>
                        </div>

                        {{-- Discover --}}
                        <div class="text-left">
                            <h4 class="text-[0.82rem] font-semibold text-white uppercase tracking-[0.03em] mb-4">{{ __('Discover') }}</h4>
                            <ul class="grid gap-[11px]">
                                <li><a href="{{ route('storefront.products') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Browse Products') }}</a></li>
                                <li><a href="{{ route('storefront.companies') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Browse Businesses') }}</a></li>
                                <li><a href="{{ route('apply.certificate') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Halal Certification') }}</a></li>
                                <li><a href="{{ route('sourcing-request.create') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Request Sourcing') }}</a></li>
                            </ul>
                        </div>

                        {{-- Company --}}
                        <div class="text-left">
                            <h4 class="text-[0.82rem] font-semibold text-white uppercase tracking-[0.03em] mb-4">{{ __('Company') }}</h4>
                            <ul class="grid gap-[11px]">
                                <li><a href="{{ route('about') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('About Us') }}</a></li>
                                <li><a href="{{ route('about') }}#mission" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Our Mission') }}</a></li>
                                <li><a href="#" class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Events & Training') }}</a></li>
                                <li><a href="{{ route('help.faq') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('FAQs') }}</a></li>
                            </ul>
                        </div>

                        {{-- Get Started --}}
                        <div class="text-left">
                            <h4 class="text-[0.82rem] font-semibold text-white uppercase tracking-[0.03em] mb-4">{{ __('Get Started') }}</h4>
                            <ul class="grid gap-[11px]">
                                <li><a href="{{ route('register') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('List Your Business') }}</a></li>
                                <li><a href="{{ route('storefront.companies') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Find Suppliers') }}</a></li>
                                <li><a href="{{ route('sourcing-request.create') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Source a Product') }}</a></li>
                                <li><a href="{{ route('login') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Seller Login') }}</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom bar: trust badges + social + legal + copyright --}}
            <div class="mx-auto max-w-7xl px-6 lg:px-8 py-6">
                <div class="flex flex-col items-center gap-5 lg:flex-row lg:justify-between lg:gap-4">

                    {{-- Trust badges --}}
                    <div class="flex flex-wrap gap-5">
                        @foreach ([
                            ['icon' => 'check-badge',       'label' => 'Halal Certification Support'],
                            ['icon' => 'globe-alt',          'label' => 'Market Access Global & Local'],
                            ['icon' => 'arrow-trending-up',  'label' => 'Business Growth Solutions'],
                            ['icon' => 'shield-check',       'label' => 'Trusted Halal Ecosystem'],
                        ] as $badge)
                            <div class="flex items-center gap-2.5">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-[9px] bg-white/[0.06] text-[var(--hs-gold-400)]">
                                    <flux:icon :icon="$badge['icon']" class="size-[17px]" />
                                </div>
                                <span class="text-[0.78rem] font-semibold text-white/75 max-w-[14ch] leading-snug">{{ __($badge['label']) }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Social + Legal + Copyright --}}
                    <div class="flex flex-col items-center gap-3 lg:items-end">
                        <div class="flex items-center gap-3">
                            <a href="mailto:hello@1halalstore.com"
                               aria-label="{{ __('Email 1HalalStore') }}"
                               class="flex size-8 items-center justify-center rounded-full border border-white/15 text-white/70 hover:text-[var(--hs-gold-300)] hover:border-white/30 transition">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                            </a>
                            <a href="https://facebook.com/1halalstore" target="_blank" rel="noopener noreferrer"
                               aria-label="{{ __('1HalalStore on Facebook') }}"
                               class="flex size-8 items-center justify-center rounded-full border border-white/15 text-white/70 hover:text-[var(--hs-gold-300)] hover:border-white/30 transition">
                                <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="https://instagram.com/1halalstore" target="_blank" rel="noopener noreferrer"
                               aria-label="{{ __('1HalalStore on Instagram') }}"
                               class="flex size-8 items-center justify-center rounded-full border border-white/15 text-white/70 hover:text-[var(--hs-gold-300)] hover:border-white/30 transition">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/></svg>
                            </a>
                            <nav class="flex items-center gap-1 text-[0.78rem] font-medium ml-2" aria-label="{{ __('Legal') }}">
                                <a href="{{ route('legal.terms') }}" wire:navigate class="px-2 py-1 text-white/60 hover:text-[var(--hs-gold-300)] transition">{{ __('Terms') }}</a>
                                <span class="text-white/25" aria-hidden="true">&middot;</span>
                                <a href="{{ route('legal.privacy') }}" wire:navigate class="px-2 py-1 text-white/60 hover:text-[var(--hs-gold-300)] transition">{{ __('Privacy') }}</a>
                            </nav>
                        </div>
                        <span class="text-[0.78rem] text-white/40 text-right">
                            &copy; {{ date('Y') }} 1HalalStore.com — {{ __('All rights reserved.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reduced-motion support: disable accordion transitions --}}
        <style>
        @media (prefers-reduced-motion: reduce) {
            [data-accordion-chevron] { transition: none !important; }
            [data-accordion-content] { transition: none !important; }
        }
        </style>
    </footer>

    <x-turnstile-gate />

    @livewireScripts
</body>
</html>
