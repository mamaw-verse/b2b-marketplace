<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'One Halal Global' }}</title>

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
        $hasCompanyAccount = $user?->hasRole('seller') || $user?->shop()->exists();

        if ($user?->hasRole('admin')) {
            $portal = 'admin';
        } elseif ($hasCompanyAccount) {
            $portal = 'hub';
        } elseif (! isset($portal) || ! in_array($portal, ['hub', 'account'])) {
            $portal = 'hub';
        }
    @endphp
    @include('partials.portal-header')

    {{-- Portal Content --}}
    <div class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
        <div x-data="{ mobileNavOpen: false }" class="flex flex-col lg:flex-row gap-8">

            {{-- ─── Mobile nav toggle + drawer (hidden on lg+) ─────────────────── --}}
            <div class="lg:hidden">
                <button
                    @click="mobileNavOpen = !mobileNavOpen"
                    class="flex items-center gap-2 rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-3 py-2 text-sm font-medium text-[var(--hs-neutral-700)] shadow-[var(--hs-shadow-sm)] transition hover:bg-[var(--hs-neutral-100)]"
                    :aria-expanded="mobileNavOpen"
                    aria-label="Toggle navigation"
                >
                    <svg x-show="!mobileNavOpen" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    <svg x-show="mobileNavOpen" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    <span x-text="mobileNavOpen ? '{{ __('Close') }}' : '{{ __('Menu') }}'"></span>
                </button>

                <div
                    x-show="mobileNavOpen"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    class="mt-2 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-md)] p-4"
                    @click="mobileNavOpen = false"
                    style="display: none;"
                >
                    @include('partials.portal-nav', ['portal' => $portal])
                </div>
            </div>

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
                &copy; {{ date('Y') }} One Halal Global. {{ __('All rights reserved.') }}
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
