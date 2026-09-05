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
    @fluxAppearance
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col bg-[var(--hs-neutral-50)] text-[var(--hs-neutral-900)]">
    @php
        $user = auth()->user();

        // Phase 6: unified portal — no longer a separate hub vs account split.
        // Admin keeps its own portal; all other authenticated users use the
        // single 'portal' context regardless of whether they're a buyer or seller.
        if ($user?->hasRole('admin')) {
            $portal = 'admin';
        } else {
            $portal = 'portal';
        }
    @endphp
    @include('partials.portal-header')

    {{-- Portal Content --}}
    <div class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 lg:py-8">
        <div class="flex flex-col lg:flex-row lg:gap-8">

            {{-- ─── Mobile nav slide-in drawer (hidden on lg+) ─────────────────── --}}
            @include('partials.portal-nav-mobile', ['portal' => $portal])

            {{-- ─── Desktop sidebar (hidden below lg) ──────────────────────────── --}}
            <aside class="hidden lg:block lg:w-60 shrink-0">
                @include('partials.portal-nav', ['portal' => $portal])
            </aside>

            {{-- ─── Main content ─────────────────────────────────────────────────── --}}
            <div class="flex-1 min-w-0">
                {{ $slot }}
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="border-t border-[var(--hs-neutral-200)] mt-auto">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4">
            <p class="text-center text-xs text-[var(--hs-neutral-500)]">
                &copy; {{ date('Y') }} 1Halal Store. {{ __('All rights reserved.') }}
            </p>
        </div>
    </footer>

    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>

    @fluxScripts
    @livewireScripts
</body>
</html>
