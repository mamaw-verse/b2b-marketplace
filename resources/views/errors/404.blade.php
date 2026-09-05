@extends('errors::layout')

@section('content')
<div class="text-center max-w-lg mx-auto">
    {{-- Decorative circle --}}
    <div class="relative inline-flex items-center justify-center mb-8">
        <div class="absolute size-40 rounded-full bg-[var(--hs-green-50)] border border-[var(--hs-green-100)]"></div>
        <div class="absolute size-52 rounded-full border border-dashed border-[var(--hs-green-200)] opacity-60"></div>
        <div class="relative flex items-center justify-center size-24 rounded-full bg-[var(--hs-green-600)] shadow-[var(--hs-shadow-lg)]">
            <svg class="size-12 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </div>
    </div>

    {{-- Error code --}}
    <h1 class="text-7xl sm:text-8xl font-extrabold text-[var(--hs-green-600)] tracking-tight font-[var(--hs-font-display)]">
        404
    </h1>

    {{-- Title --}}
    <h2 class="mt-4 text-2xl sm:text-3xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">
        {{ __('Page not found') }}
    </h2>

    {{-- Description --}}
    <p class="mt-3 text-base text-[var(--hs-neutral-500)] leading-relaxed max-w-md mx-auto">
        {{ __('The page you\'re looking for doesn\'t exist or has been moved. Let\'s get you back on track.') }}
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
        <button onclick="history.back()"
                class="inline-flex items-center gap-2 rounded-[var(--hs-radius-pill)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-6 py-3 text-sm font-semibold text-[var(--hs-neutral-700)] hover:border-[var(--hs-green-300)] hover:text-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
            </svg>
            {{ __('Go Back') }}
        </button>
    </div>

    {{-- Helpful links --}}
    <div class="mt-12 pt-8 border-t border-[var(--hs-neutral-100)]">
        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-400)] mb-4">{{ __('Popular Pages') }}</p>
        <div class="flex flex-wrap items-center justify-center gap-2">
            <a href="{{ route('home') }}" wire:navigate
               class="inline-flex items-center gap-1.5 rounded-full border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-3.5 py-1.5 text-xs font-medium text-[var(--hs-neutral-600)] hover:border-[var(--hs-green-300)] hover:text-[var(--hs-green-700)] transition">
                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                {{ __('Home') }}
            </a>
            <a href="{{ route('storefront.companies') }}" wire:navigate
               class="inline-flex items-center gap-1.5 rounded-full border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-3.5 py-1.5 text-xs font-medium text-[var(--hs-neutral-600)] hover:border-[var(--hs-green-300)] hover:text-[var(--hs-green-700)] transition">
                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 2H3a1 1 0 0 0-1 1v18a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V9l-9-7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 9v11h18V9"/></svg>
                {{ __('Companies') }}
            </a>
            <a href="{{ route('storefront.products') }}" wire:navigate
               class="inline-flex items-center gap-1.5 rounded-full border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-3.5 py-1.5 text-xs font-medium text-[var(--hs-neutral-600)] hover:border-[var(--hs-green-300)] hover:text-[var(--hs-green-700)] transition">
                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 8h12l1 12H5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                {{ __('Products') }}
            </a>
        </div>
    </div>
</div>
@endsection
