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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col bg-[var(--hs-neutral-50)]" x-data="{ sfNav: false }" @keydown.escape.window="sfNav = false">

    {{-- ══════════════════════════════════════════
         STOREFRONT MOBILE NAV DRAWER (sm:hidden)
    ══════════════════════════════════════════ --}}

    {{-- Backdrop --}}
    <div x-show="sfNav"
         x-transition.opacity.duration.200ms
         @click="sfNav = false"
         style="display:none;position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,0.5);">
    </div>

    {{-- Drawer --}}
    <div x-show="sfNav"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="transform -translate-x-full"
         x-transition:enter-end="transform translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="transform translate-x-0"
         x-transition:leave-end="transform -translate-x-full"
         style="display:none;position:fixed;top:0;left:0;height:100vh;width:290px;z-index:9999;background:#fff;box-shadow:8px 0 32px rgba(0,0,0,0.15);overflow-y:auto;font-family:inherit;">

        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e5e5e5;">
            <a href="{{ route('home') }}" wire:navigate @click="sfNav = false">
                <img src="/images/brand-logo.png" alt="1Halal Store" style="height:32px;width:auto;">
            </a>
            <button @click="sfNav = false"
                    style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:none;border:none;cursor:pointer;color:#555;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Nav links --}}
        <nav style="padding:16px 12px;">

            <a href="{{ route('storefront.companies') }}" wire:navigate @click="sfNav = false"
               style="display:flex;align-items:center;gap:12px;padding:12px 12px;border-radius:10px;font-size:0.9rem;font-weight:600;color:{{ request()->routeIs('storefront.companies','storefront.company') ? '#175636' : '#1a1a1a' }};background:{{ request()->routeIs('storefront.companies','storefront.company') ? '#EAF4EE' : 'transparent' }};text-decoration:none;margin-bottom:2px;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="color:#888;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M13 2H3a1 1 0 0 0-1 1v18a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V9l-9-7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 9v11h18V9"/></svg>
                {{ __('Companies') }}
            </a>

            <a href="{{ route('storefront.products') }}" wire:navigate @click="sfNav = false"
               style="display:flex;align-items:center;gap:12px;padding:12px 12px;border-radius:10px;font-size:0.9rem;font-weight:600;color:{{ request()->routeIs('storefront.products','storefront.product') ? '#175636' : '#1a1a1a' }};background:{{ request()->routeIs('storefront.products','storefront.product') ? '#EAF4EE' : 'transparent' }};text-decoration:none;margin-bottom:2px;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="color:#888;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 8h12l1 12H5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                {{ __('Products') }}
            </a>

            <div style="padding:16px 12px 6px;font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#999;">
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
                   style="display:flex;align-items:center;gap:12px;padding:12px 12px;border-radius:10px;font-size:0.9rem;font-weight:600;color:#1a1a1a;text-decoration:none;margin-bottom:2px;">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="color:#888;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Auth footer --}}
        @auth
            <div style="position:absolute;bottom:0;left:0;right:0;padding:16px;border-top:1px solid #e5e5e5;background:#fff;">
                <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f5f5f5;border-radius:10px;margin-bottom:8px;">
                    <span style="width:32px;height:32px;border-radius:50%;background:#1E6B45;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;flex-shrink:0;">
                        {{ strtoupper(mb_substr(auth()->user()->first_name,0,1).mb_substr(auth()->user()->last_name,0,1)) }}
                    </span>
                    <div style="min-width:0;">
                        <div style="font-size:0.85rem;font-weight:600;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</div>
                        <div style="font-size:0.72rem;color:#777;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="display:flex;align-items:center;gap:10px;width:100%;padding:10px 12px;border-radius:10px;background:none;border:none;cursor:pointer;font-size:0.875rem;font-weight:600;color:#932E2E;">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
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
                            <img src="/images/brand-logo.png" alt="1Halal Store" class="h-10 sm:h-12 w-auto opacity-90">
                            <img src="/images/halal-stamp.png" alt="Halal Certified" class="h-10 sm:h-12 w-auto">
                        </div>
                        <p class="text-sm text-white/55 leading-relaxed max-w-[34ch] mx-auto sm:mx-0">
                            Your halal supply-chain marketplace — connecting farmers, producers, certifiers, logistics providers, retailers and consumers in one trusted ecosystem.
                        </p>
                    </div>

                    {{-- Platform --}}
                    <div class="text-center sm:text-left">
                        <h4 class="text-[0.82rem] font-semibold text-white uppercase tracking-[0.03em] mb-3 sm:mb-4">Platform</h4>
                        <ul class="grid gap-2.5 sm:gap-[11px]">
                            <li><a href="{{ route('home') }}#who-we-serve" class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Who We Serve') }}</a></li>
                            <li><a href="{{ route('home') }}#what-we-offer" class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('What We Offer') }}</a></li>
                            <li><a href="{{ route('home') }}#supply-chain" class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Supply Chain') }}</a></li>
                            <li><a href="{{ route('storefront.companies') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Browse Companies') }}</a></li>
                            <li><a href="{{ route('storefront.products') }}" wire:navigate class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Browse Products') }}</a></li>
                        </ul>
                    </div>

                    {{-- Company --}}
                    <div class="text-center sm:text-left">
                        <h4 class="text-[0.82rem] font-semibold text-white uppercase tracking-[0.03em] mb-3 sm:mb-4">Company</h4>
                        <ul class="grid gap-2.5 sm:gap-[11px]">
                            <li><a href="#" class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('About Us') }}</a></li>
                            <li><a href="#" class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Halal Certification') }}</a></li>
                            <li><a href="#" class="text-[0.88rem] text-white/62 hover:text-[var(--hs-gold-300)] transition">{{ __('Events & Training') }}</a></li>
                        </ul>
                    </div>

                    {{-- Get Started --}}
                    <div class="text-center sm:text-left">
                        <h4 class="text-[0.82rem] font-semibold text-white uppercase tracking-[0.03em] mb-3 sm:mb-4">Get Started</h4>
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
                            <span class="text-[0.72rem] sm:text-[0.78rem] font-semibold text-white/75 max-w-[14ch] leading-snug">{{ $badge['label'] }}</span>
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
