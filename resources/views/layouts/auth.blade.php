@props(['title' => config('app.name'), 'activeTab' => 'login'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <title>{{ $title }}</title>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[var(--hs-neutral-50)] antialiased">
        {{-- Center stage --}}
        <div class="flex min-h-svh items-start justify-center px-4 py-4 sm:px-6 sm:py-8 lg:items-center">
            <div class="grid w-full max-w-4xl grid-cols-1 overflow-hidden rounded-[var(--hs-radius-panel)] border border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-0)] shadow-xs lg:grid-cols-[1fr_27rem]">

                {{-- Left: promo / benefits column --}}
                <div class="relative hidden border-r border-[var(--hs-neutral-200)] lg:block">
                    <div class="absolute inset-0 bg-[var(--hs-green-50)]" aria-hidden="true"></div>

                    <div class="relative p-10">
                        <a href="{{ route('home') }}" class="mb-6 inline-flex items-center" wire:navigate>
                            <img src="/images/brand-logo.png" alt="{{ config('app.name') }}" class="h-12 w-auto">
                        </a>

                        <h2 class="mb-6 text-xl font-extrabold leading-tight text-[var(--hs-green-900)] font-[var(--hs-font-display)]">
                            {{ __('Everything you need to buy and sell halal-certified, in one account.') }}
                        </h2>

                        <div class="space-y-5">
                            <div class="flex gap-3">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]">
                                    <svg class="size-4 text-[var(--hs-green-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                </div>
                                <div>
                                    <b class="block text-sm font-bold text-[var(--hs-neutral-900)]">{{ __('Certification, verified') }}</b>
                                    <span class="text-xs text-[var(--hs-neutral-500)] leading-relaxed">{{ __('Every listed business is checked against valid halal certification.') }}</span>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]">
                                    <svg class="size-4 text-[var(--hs-green-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                </div>
                                <div>
                                    <b class="block text-sm font-bold text-[var(--hs-neutral-900)]">{{ __('Direct buyer-supplier contact') }}</b>
                                    <span class="text-xs text-[var(--hs-neutral-500)] leading-relaxed">{{ __('Message and send RFQs without a broker in between.') }}</span>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]">
                                    <svg class="size-4 text-[var(--hs-green-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                </div>
                                <div>
                                    <b class="block text-sm font-bold text-[var(--hs-neutral-900)]">{{ __('One profile, saved documents') }}</b>
                                    <span class="text-xs text-[var(--hs-neutral-500)] leading-relaxed">{{ __('Store certificates once and reuse them across every listing.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: form column --}}
                <main class="p-5 sm:p-8">
                    {{-- Mobile brand header (left promo panel is hidden below lg) --}}
                    <div class="mb-6 flex items-center justify-between lg:hidden">
                        <a href="{{ route('home') }}" wire:navigate>
                            <img src="/images/brand-logo.png" alt="{{ config('app.name') }}" class="h-8 w-auto">
                        </a>
                        <a href="{{ route('home') }}" wire:navigate class="text-sm font-semibold text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)]">
                            &larr; {{ __('Back to site') }}
                        </a>
                    </div>

                    {{-- Tabs --}}
                    <nav
                        class="mb-6 grid min-h-11 grid-cols-2 rounded-[var(--hs-radius-sm)] bg-[var(--hs-neutral-100)] p-1"
                        role="tablist"
                        aria-label="{{ __('Account access') }}"
                        x-data="authTabs(@js($activeTab), @js(route('login')), @js(route('register')))"
                        x-on:keydown.arrow-left.prevent="switchTab('left')"
                        x-on:keydown.arrow-right.prevent="switchTab('right')"
                        x-on:keydown.home.prevent="window.Livewire?.navigate(@js(route('login'))) ?? window.location.assign(@js(route('login')))"
                        x-on:keydown.end.prevent="window.Livewire?.navigate(@js(route('register'))) ?? window.location.assign(@js(route('register')))"
                    >
                        <a
                            href="{{ route('login') }}"
                            wire:navigate
                            role="tab"
                            aria-selected="{{ $activeTab === 'login' ? 'true' : 'false' }}"
                            tabindex="{{ $activeTab === 'login' ? '0' : '-1' }}"
                            @class([
                                'flex min-h-11 items-center justify-center rounded-[calc(var(--hs-radius-sm)-2px)] px-3 text-sm font-semibold transition motion-reduce:transition-none focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2',
                                'bg-[var(--hs-neutral-0)] text-[var(--hs-green-900)] shadow-xs' => $activeTab === 'login',
                                'text-[var(--hs-neutral-700)] hover:text-[var(--hs-neutral-900)]' => $activeTab !== 'login',
                            ])
                        >{{ __('Log in') }}</a>
                        <a
                            href="{{ route('register') }}"
                            wire:navigate
                            role="tab"
                            aria-selected="{{ $activeTab === 'register' ? 'true' : 'false' }}"
                            tabindex="{{ $activeTab === 'register' ? '0' : '-1' }}"
                            @class([
                                'flex min-h-11 items-center justify-center rounded-[calc(var(--hs-radius-sm)-2px)] px-3 text-sm font-semibold transition motion-reduce:transition-none focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2',
                                'bg-[var(--hs-neutral-0)] text-[var(--hs-green-900)] shadow-xs' => $activeTab === 'register',
                                'text-[var(--hs-neutral-700)] hover:text-[var(--hs-neutral-900)]' => $activeTab !== 'register',
                            ])
                        >{{ __('Create account') }}</a>
                    </nav>

                    {{ $slot }}
                </main>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        <x-turnstile-gate />

        @livewireScripts
        @fluxScripts
    </body>
</html>
