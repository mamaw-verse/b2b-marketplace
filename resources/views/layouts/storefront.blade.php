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
<body class="min-h-screen flex flex-col bg-[var(--hs-neutral-50)]">
    {{-- Sticky Header --}}
    @include('partials.portal-header')

    {{-- Main Content --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-[var(--hs-green-950)] text-white mt-auto">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Brand --}}
                <div>
                    <div class="flex items-center gap-3">
                        <img src="/images/brand-logo.png" alt="1Halal Store" class="h-12 w-auto opacity-90">
                        <img src="/images/halal-stamp.png" alt="Halal Certified" class="h-12 w-auto">
                    </div>
                    <p class="mt-3 text-sm text-[var(--hs-neutral-400)] leading-relaxed">
                        The verified halal B2B directory connecting certified manufacturers, processors and suppliers with buyers across the value chain.
                    </p>
                </div>

                {{-- Marketplace --}}
                <div>
                    <h4 class="text-sm font-semibold text-[var(--hs-neutral-300)] uppercase tracking-wider mb-3">Marketplace</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('storefront.companies') }}" class="text-[var(--hs-neutral-400)] hover:text-white transition" wire:navigate>Browse Companies</a></li>
                        <li><a href="{{ route('storefront.products') }}" class="text-[var(--hs-neutral-400)] hover:text-white transition" wire:navigate>Browse Products</a></li>
                    </ul>
                </div>

                {{-- For Businesses --}}
                <div>
                    <h4 class="text-sm font-semibold text-[var(--hs-neutral-300)] uppercase tracking-wider mb-3">For Businesses</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('register') }}" class="text-[var(--hs-neutral-400)] hover:text-white transition" wire:navigate>Register Your Company</a></li>
                        <li><a href="{{ route('login') }}" class="text-[var(--hs-neutral-400)] hover:text-white transition" wire:navigate>Seller Login</a></li>
                    </ul>
                </div>

                {{-- Trust & Compliance --}}
                <div>
                    <h4 class="text-sm font-semibold text-[var(--hs-neutral-300)] uppercase tracking-wider mb-3">Trust & Compliance</h4>
                    <ul class="space-y-2 text-sm">
                        <li><span class="text-[var(--hs-neutral-400)]">Halal Certification Verified</span></li>
                        <li><span class="text-[var(--hs-neutral-400)]">Secure Document Storage</span></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Copyright Bar --}}
        <div class="border-t border-white/10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4">
                <p class="text-center text-xs text-[var(--hs-neutral-500)]">
                    &copy; {{ date('Y') }} 1Halal Store. {{ __('All rights reserved.') }}
                </p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
