<x-layouts::auth :title="__('Verify email')">
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-[var(--hs-green-50)] flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-[var(--hs-green-600)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-[var(--hs-neutral-900)]" style="font-family: var(--hs-font-display);">{{ __('Verify your email') }}</h1>
            <p class="mt-2 text-sm text-[var(--hs-neutral-500)] leading-relaxed">{{ __('We\'ve sent a verification link to your email address. Please check your inbox and click the link to continue.') }}</p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="text-sm text-[var(--hs-green-700)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] rounded-lg p-3 text-center">
                {{ __('A new verification link has been sent to your email address.') }}
            </div>
        @endif

        <div class="flex flex-col gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="w-full py-2.5 rounded-lg bg-[var(--hs-green-600)] hover:bg-[var(--hs-green-700)] text-white text-sm font-semibold transition shadow-[var(--hs-shadow-sm)]">
                    {{ __('Resend verification email') }}
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full py-2.5 rounded-lg border border-[var(--hs-neutral-200)] text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-50)] text-sm font-semibold transition">
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts::auth>
