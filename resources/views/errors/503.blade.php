@extends('errors::layout')

@section('content')
<div class="text-center max-w-lg mx-auto">
    {{-- Decorative circle --}}
    <div class="relative inline-flex items-center justify-center mb-8">
        <div class="absolute size-40 rounded-full bg-[var(--hs-warning-50)] border border-[var(--hs-warning-100)]"></div>
        <div class="absolute size-52 rounded-full border border-dashed border-[var(--hs-warning-200)] opacity-60"></div>
        <div class="relative flex items-center justify-center size-24 rounded-full bg-[var(--hs-warning-700)] shadow-[var(--hs-shadow-lg)]">
            <svg class="size-12 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.572.398.967 1.021 1.085 1.723" />
            </svg>
        </div>
    </div>

    {{-- Error code --}}
    <h1 class="text-7xl sm:text-8xl font-extrabold text-[var(--hs-warning-700)] tracking-tight font-[var(--hs-font-display)]">
        503
    </h1>

    {{-- Title --}}
    <h2 class="mt-4 text-2xl sm:text-3xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">
        {{ __('Service unavailable') }}
    </h2>

    {{-- Description --}}
    <p class="mt-3 text-base text-[var(--hs-neutral-500)] leading-relaxed max-w-md mx-auto">
        {{ __('We\'re currently performing scheduled maintenance. We\'ll be back online shortly. Thank you for your patience.') }}
    </p>

    {{-- Auto-refresh notice --}}
    <div class="mt-6 inline-flex items-center gap-2 rounded-full bg-[var(--hs-neutral-50)] border border-[var(--hs-neutral-200)] px-4 py-2">
        <div class="size-2 rounded-full bg-[var(--hs-warning-500)] animate-pulse"></div>
        <span class="text-xs text-[var(--hs-neutral-500)]">{{ __('This page will refresh automatically in 30 seconds.') }}</span>
    </div>

    {{-- Actions --}}
    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
        <a href="{{ url()->current() }}" wire:navigate
           class="inline-flex items-center gap-2 rounded-[var(--hs-radius-pill)] bg-[var(--hs-green-600)] px-6 py-3 text-sm font-semibold text-white hover:bg-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
            </svg>
            {{ __('Retry Now') }}
        </a>
        <a href="{{ route('home') }}" wire:navigate
           class="inline-flex items-center gap-2 rounded-[var(--hs-radius-pill)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-6 py-3 text-sm font-semibold text-[var(--hs-neutral-700)] hover:border-[var(--hs-green-300)] hover:text-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            {{ __('Back to Home') }}
        </a>
    </div>

    {{-- Maintenance info --}}
    <div class="mt-10 pt-6 border-t border-[var(--hs-neutral-100)]">
        <div class="flex items-center justify-center gap-3">
            <img src="/images/halal-stamp.png" alt="1HalalStore" class="size-8 opacity-60">
            <p class="text-xs text-[var(--hs-neutral-400)]">
                {{ __('1HalalStore.com — Halal Supply Chain Marketplace') }}
            </p>
        </div>
    </div>
</div>

<script>
    setTimeout(function() {
        window.location.reload();
    }, 30000);
</script>
@endsection
