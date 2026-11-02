<a
    href="{{ route('auth.google.redirect') }}"
    x-on:click="window.localStorage.setItem('auth.lastMethod', 'google')"
    {{ $attributes->class('flex min-h-12 w-full items-center justify-center gap-3 rounded-[var(--hs-radius-sm)] border border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] px-4 text-base font-semibold text-[var(--hs-neutral-900)] shadow-xs transition motion-reduce:transition-none hover:bg-[var(--hs-neutral-100)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60') }}
    data-test="google-auth-button"
>
    <svg class="size-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.92h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.41Z"/>
        <path fill="#34A853" d="M12 22c2.7 0 4.98-.9 6.63-2.36l-3.24-2.54c-.9.6-2.05.96-3.39.96-2.61 0-4.82-1.76-5.61-4.13H3.04v2.62A10 10 0 0 0 12 22Z"/>
        <path fill="#FBBC05" d="M6.39 13.93A6.02 6.02 0 0 1 6.07 12c0-.67.12-1.32.32-1.93V7.45H3.04A10 10 0 0 0 2 12c0 1.61.38 3.14 1.04 4.55l3.35-2.62Z"/>
        <path fill="#EA4335" d="M12 5.94c1.47 0 2.79.51 3.83 1.5l2.87-2.88A9.63 9.63 0 0 0 12 2a10 10 0 0 0-8.96 5.45l3.35 2.62C7.18 7.7 9.39 5.94 12 5.94Z"/>
    </svg>
    {{ __('Continue with Google') }}
</a>
