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
<body class="min-h-screen flex flex-col bg-[var(--hs-neutral-50)]">
    @include('partials.portal-header')

    {{-- Portal Content --}}
    <div class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            {{-- Sidebar --}}
            <aside class="lg:w-60 shrink-0">
                @if ($portal === 'hub')
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Company Hub') }}</h2>
                    <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                        @php
                            $shop = auth()->user()?->shop;
                            $statusLine = match ($shop?->status ?? null) {
                                'active' => 'Your company is live on the directory.',
                                'pending' => 'Onboarding in progress.',
                                'suspended' => 'Your account is suspended.',
                                default => 'Complete onboarding to go live.',
                            };
                        @endphp
                        {{ $statusLine }}
                    </p>
                @elseif ($portal === 'admin')
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Admin Console') }}</h2>
                    <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Review, verify and manage the directory.') }}</p>
                @endif

                <nav class="mt-6 space-y-1">
                    @if ($portal === 'hub')
                        @if (auth()->user()?->shop?->status !== 'active')
                            {{-- Onboarding Wizard --}}
                            <a
                                href="{{ route('hub.onboarding') }}"
                                @class([
                                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.onboarding'),
                                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.onboarding'),
                                ])
                                wire:navigate
                            >
                                {{-- clipboard-list icon --}}
                                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                </svg>
                                {{ __('Onboarding Wizard') }}
                            </a>
                        @endif

                        @if (auth()->user()?->shop?->status === 'active')
                            {{-- Products --}}
                            <a
                                href="{{ route('hub.products.index') }}"
                                @class([
                                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.products.*'),
                                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.products.*'),
                                ])
                                wire:navigate
                            >
                                {{-- package icon --}}
                                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                                {{ __('Products') }}
                            </a>
                        @endif
                    @elseif ($portal === 'admin')
                        {{-- Onboarding Queue --}}
                        <a
                            href="{{ route('admin.onboardings.index') }}"
                            @class([
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.onboardings.*'),
                                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.onboardings.*'),
                            ])
                            wire:navigate
                        >
                            {{-- inbox icon --}}
                            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" />
                            </svg>
                            {{ __('Onboarding Queue') }}
                        </a>

                        {{-- Categories --}}
                        <a
                            href="{{ route('admin.categories.index') }}"
                            @class([
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.categories.*'),
                                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.categories.*'),
                            ])
                            wire:navigate
                        >
                            {{-- shapes icon --}}
                            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" />
                            </svg>
                            {{ __('Categories') }}
                        </a>
                    @endif
                </nav>
            </aside>

            {{-- Main Content --}}
            <div class="flex-1 min-w-0">
                {{ $slot }}
            </div>
        </div>
    </div>

    {{-- Simple Copyright Footer --}}
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
