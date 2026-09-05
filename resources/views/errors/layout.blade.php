<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $statusCode ?? 500 }} — {{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    @fonts
    @vite(['resources/css/app.css'])
    @fluxAppearance
</head>
<body class="min-h-screen flex flex-col bg-[var(--hs-neutral-50)]">

    {{-- Sticky Header --}}
    <header class="sticky top-0 z-50 border-b border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]/80 backdrop-blur-md">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
                    <span class="relative inline-flex shrink-0">
                        <img src="/images/brand-logo.png" alt="1Halal Store" class="h-9 w-auto">
                        <span class="absolute -bottom-1 -right-1 flex items-center justify-center rounded-full bg-white shadow ring-1 ring-[var(--hs-neutral-200)] size-[18px] text-[11px] leading-none select-none">
                            @php
                                $flagMap = ['PH'=>'🇵🇭','MY'=>'🇲🇾','SA'=>'🇸🇦','BN'=>'🇧🇳','ID'=>'🇮🇩','TR'=>'🇹🇷'];
                                echo $flagMap[config('marketplace.instance_country')] ?? '🌐';
                            @endphp
                        </span>
                    </span>
                </a>

                <div class="flex items-center gap-1">
                    <a href="{{ route('storefront.companies') }}" wire:navigate
                       class="hidden sm:inline-flex px-3 py-2 text-sm font-medium text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-900)] transition">
                        {{ __('Companies') }}
                    </a>
                    <a href="{{ route('storefront.products') }}" wire:navigate
                       class="hidden sm:inline-flex px-3 py-2 text-sm font-medium text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-900)] transition">
                        {{ __('Products') }}
                    </a>

                    @auth
                        <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                            <button @click="open = !open"
                                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-100)] transition">
                                <span class="inline-flex size-7 items-center justify-center rounded-full bg-[var(--hs-green-600)] text-xs font-bold text-white shrink-0">
                                    {{ strtoupper(mb_substr(auth()->user()->first_name, 0, 1) . mb_substr(auth()->user()->last_name, 0, 1)) }}
                                </span>
                                <span class="hidden sm:block max-w-[120px] truncate">{{ auth()->user()->first_name }}</span>
                                <svg class="size-4 text-[var(--hs-neutral-400)] transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                            <div x-show="open" x-transition.opacity class="absolute right-0 mt-1 w-52 origin-top-right rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-lg)] py-1 z-50" style="display:none;">
                                <div class="px-4 py-3 border-b border-[var(--hs-neutral-100)]">
                                    <p class="text-sm font-semibold text-[var(--hs-neutral-900)] truncate">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                                    <p class="text-xs text-[var(--hs-neutral-500)] truncate mt-0.5">{{ auth()->user()->email }}</p>
                                </div>
                                <div class="py-1">
                                    @unless(auth()->user()->hasRole('admin'))
                                        <a href="{{ route('portal.dashboard') }}" wire:navigate @click="open = false"
                                           class="flex items-center gap-3 px-4 py-2 text-sm text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-50)] transition">
                                            {{ __('My Portal') }}
                                        </a>
                                    @endunless
                                    @if(auth()->user()->hasRole('admin'))
                                        <a href="{{ route('admin.onboardings.index') }}" wire:navigate @click="open = false"
                                           class="flex items-center gap-3 px-4 py-2 text-sm text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-50)] transition">
                                            {{ __('Admin Console') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" wire:navigate
                           class="px-3 py-2 text-sm font-medium text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-900)] transition">
                            {{ __('Log in') }}
                        </a>
                        <a href="{{ route('register') }}" wire:navigate
                           class="inline-flex items-center rounded-[var(--hs-radius-pill)] bg-[var(--hs-green-600)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
                            {{ __('Get Started') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- Error Content --}}
    <main class="flex-1 flex items-center justify-center px-4 py-16 sm:py-20">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-[var(--hs-green-950)] text-white mt-auto">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
                <div class="flex items-center gap-3">
                    <img src="/images/brand-logo.png" alt="1Halal Store" class="h-7 w-auto opacity-80">
                    <img src="/images/halal-stamp.png" alt="{{ __('Halal Certified') }}" class="h-7 w-auto opacity-80">
                </div>
                <span class="text-[0.75rem] text-white/40">
                    &copy; {{ date('Y') }} 1HalalStore.com — {{ __('All rights reserved.') }}
                </span>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
