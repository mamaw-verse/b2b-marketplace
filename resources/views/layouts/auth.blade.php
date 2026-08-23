@props(['title' => config('app.name'), 'activeTab' => 'login'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <title>{{ $title }}</title>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[var(--hs-neutral-50)] antialiased">

        {{-- Minimal header --}}
        <div class="flex items-center justify-between px-6 py-5 sm:px-10">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5" wire:navigate>
                <img src="/images/logo.png" alt="{{ config('app.name') }}" class="h-9 w-auto">
            </a>
            <div class="text-sm text-[var(--hs-neutral-500)]">
                @if ($activeTab === 'login')
                    {{ __('First time here?') }}
                    <a href="{{ route('register') }}" wire:navigate class="font-bold text-[var(--hs-green-900)] hover:underline">{{ __('Register your company') }}</a>
                @else
                    {{ __('Already registered?') }}
                    <a href="{{ route('login') }}" wire:navigate class="font-bold text-[var(--hs-green-900)] hover:underline">{{ __('Log in') }}</a>
                @endif
            </div>
        </div>

        {{-- Center stage --}}
        <div class="flex justify-center px-6 pb-16">
            <div class="w-full max-w-3xl overflow-hidden rounded-[var(--hs-radius-panel)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-xs grid grid-cols-1 lg:grid-cols-2">

                {{-- Left: promo / benefits column --}}
                <div class="hidden lg:flex flex-col border-r border-[var(--hs-neutral-200)] bg-[var(--hs-green-50)] p-10">
                    {{-- Badge --}}
                    <div class="mb-5 inline-flex w-fit items-center gap-2 rounded-[var(--hs-radius-pill)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-3 py-1.5 text-xs font-bold text-[var(--hs-green-900)]">
                        <span class="text-[var(--hs-gold-500)]">&#9733;</span>
                        {{ __('Trusted halal B2B marketplace') }}
                    </div>

                    {{-- Headline --}}
                    <h2 class="mb-6 text-xl font-extrabold leading-tight text-[var(--hs-green-900)]" style="font-family: var(--hs-font-display);">
                        {{ __('Everything you need to buy and sell halal-certified, in one account.') }}
                    </h2>

                    {{-- Benefits --}}
                    <div class="space-y-5">
                        <div class="flex gap-3">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]">
                                <svg class="size-4 text-[var(--hs-green-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                            </div>
                            <div>
                                <b class="block text-sm font-bold text-[var(--hs-neutral-900)]">{{ __('Certification, verified') }}</b>
                                <span class="text-xs text-[var(--hs-neutral-500)] leading-relaxed">{{ __('Every listed company is checked against valid halal certification.') }}</span>
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

                    {{-- Testimonial --}}
                    <div class="mt-auto border-t border-[var(--hs-green-900)]/12 pt-5">
                        <p class="mb-2.5 text-sm leading-relaxed text-[var(--hs-neutral-700)]">
                            &ldquo;{{ __('We filled our export pipeline for the quarter within three weeks of listing.') }}&rdquo;
                        </p>
                        <div class="flex items-center gap-2.5">
                            <div class="flex size-7 items-center justify-center rounded-full bg-[var(--hs-green-900)] text-[10px] font-extrabold text-[var(--hs-green-300)]">RH</div>
                            <div>
                                <b class="block text-xs font-bold text-[var(--hs-neutral-900)]">Rodriguez, Hickle &amp; Kuhlman</b>
                                <span class="text-xs text-[var(--hs-neutral-500)]">{{ __('Verified supplier, South Carolina') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: form column --}}
                <div class="p-8 sm:p-10">
                    {{-- Tabs --}}
                    <div class="mb-6 flex gap-5 border-b border-[var(--hs-neutral-200)]">
                        <a href="{{ route('login') }}" wire:navigate
                           @class([
                               'pb-3 text-sm font-bold border-b-2 -mb-px transition',
                               'text-[var(--hs-green-900)] border-[var(--hs-green-900)]' => $activeTab === 'login',
                               'text-[var(--hs-neutral-400)] border-transparent hover:text-[var(--hs-neutral-600)]' => $activeTab !== 'login',
                           ])
                        >{{ __('Log in') }}</a>
                        <a href="{{ route('register') }}" wire:navigate
                           @class([
                               'pb-3 text-sm font-bold border-b-2 -mb-px transition',
                               'text-[var(--hs-green-900)] border-[var(--hs-green-900)]' => $activeTab === 'register',
                               'text-[var(--hs-neutral-400)] border-transparent hover:text-[var(--hs-neutral-600)]' => $activeTab !== 'register',
                           ])
                        >{{ __('Create account') }}</a>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
        @livewireScripts
    </body>
</html>
