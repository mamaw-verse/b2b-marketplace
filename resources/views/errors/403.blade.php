@extends('errors::layout')

@section('content')
<div class="text-center max-w-lg mx-auto">
    {{-- Decorative circle --}}
    <div class="relative inline-flex items-center justify-center mb-8">
        <div class="absolute size-40 rounded-full bg-[var(--hs-danger-50)] border border-[var(--hs-danger-100)]"></div>
        <div class="absolute size-52 rounded-full border border-dashed border-[var(--hs-danger-200)] opacity-60"></div>
        <div class="relative flex items-center justify-center size-24 rounded-full bg-[var(--hs-danger-700)] shadow-[var(--hs-shadow-lg)]">
            <svg class="size-12 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
        </div>
    </div>

    {{-- Error code --}}
    <h1 class="text-7xl sm:text-8xl font-extrabold text-[var(--hs-danger-700)] tracking-tight font-[var(--hs-font-display)]">
        403
    </h1>

    {{-- Title --}}
    <h2 class="mt-4 text-2xl sm:text-3xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">
        {{ __('Access denied') }}
    </h2>

    {{-- Description --}}
    <p class="mt-3 text-base text-[var(--hs-neutral-500)] leading-relaxed max-w-md mx-auto">
        {{ __('You don\'t have permission to access this page. If you believe this is an error, please contact your administrator.') }}
    </p>

    {{-- Actions --}}
    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
        <a href="{{ route('home') }}" wire:navigate
           class="inline-flex items-center gap-2 rounded-[var(--hs-radius-pill)] bg-[var(--hs-green-600)] px-6 py-3 text-sm font-semibold text-white hover:bg-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            {{ __('Back to Home') }}
        </a>
        @auth
            <a href="{{ route('portal.dashboard') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-[var(--hs-radius-pill)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-6 py-3 text-sm font-semibold text-[var(--hs-neutral-700)] hover:border-[var(--hs-green-300)] hover:text-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6Z" />
                </svg>
                {{ __('Go to Portal') }}
            </a>
        @else
            <a href="{{ route('login') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-[var(--hs-radius-pill)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-6 py-3 text-sm font-semibold text-[var(--hs-neutral-700)] hover:border-[var(--hs-green-300)] hover:text-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
                {{ __('Log in') }}
            </a>
        @endauth
    </div>
</div>
@endsection
