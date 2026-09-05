@extends('errors::layout')

@section('content')
<div class="text-center max-w-lg mx-auto">
    {{-- Decorative circle --}}
    <div class="relative inline-flex items-center justify-center mb-8">
        <div class="absolute size-40 rounded-full bg-[var(--hs-danger-50)] border border-[var(--hs-danger-100)]"></div>
        <div class="absolute size-52 rounded-full border border-dashed border-[var(--hs-danger-200)] opacity-60"></div>
        <div class="relative flex items-center justify-center size-24 rounded-full bg-[var(--hs-neutral-800)] shadow-[var(--hs-shadow-lg)]">
            <svg class="size-12 text-[var(--hs-danger-500)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>
    </div>

    {{-- Error code --}}
    <h1 class="text-7xl sm:text-8xl font-extrabold text-[var(--hs-neutral-800)] tracking-tight font-[var(--hs-font-display)]">
        500
    </h1>

    {{-- Title --}}
    <h2 class="mt-4 text-2xl sm:text-3xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">
        {{ __('Server error') }}
    </h2>

    {{-- Description --}}
    <p class="mt-3 text-base text-[var(--hs-neutral-500)] leading-relaxed max-w-md mx-auto">
        {{ __('Something went wrong on our end. Our team has been notified and is working to fix the issue.') }}
    </p>

    {{-- Actions --}}
    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
        <a href="{{ url()->current() }}" wire:navigate
           class="inline-flex items-center gap-2 rounded-[var(--hs-radius-pill)] bg-[var(--hs-green-600)] px-6 py-3 text-sm font-semibold text-white hover:bg-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
            </svg>
            {{ __('Reload Page') }}
        </a>
        <a href="{{ route('home') }}" wire:navigate
           class="inline-flex items-center gap-2 rounded-[var(--hs-radius-pill)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-6 py-3 text-sm font-semibold text-[var(--hs-neutral-700)] hover:border-[var(--hs-green-300)] hover:text-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            {{ __('Back to Home') }}
        </a>
    </div>

    {{-- Error reference --}}
    @if(config('app.debug'))
        <div class="mt-10 pt-6 border-t border-[var(--hs-neutral-100)]">
            <div class="inline-flex items-center gap-2 rounded-full bg-[var(--hs-danger-50)] border border-[var(--hs-danger-100)] px-4 py-2">
                <svg class="size-4 text-[var(--hs-danger-500)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <span class="text-xs text-[var(--hs-danger-700)] font-medium">{{ __('Debug mode is enabled. Check the logs for details.') }}</span>
            </div>
        </div>
    @endif
</div>
@endsection
