<x-layouts::auth.card :title="__('Check your email')">
    <div class="grid gap-6 text-center">
        <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-[var(--hs-green-50)] text-[var(--hs-green-700)]" aria-hidden="true">
            <flux:icon.envelope class="size-6" />
        </div>

        <div>
            <h1 class="font-[var(--hs-font-display)] text-2xl font-bold text-[var(--hs-neutral-900)]">{{ __('Check your email') }}</h1>
            <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-600)]">
                {{ __('We sent a verification link to') }}
                <strong class="font-semibold text-[var(--hs-neutral-900)]">{{ auth()->user()?->email }}</strong>.
                {{ __('Open the link to finish creating your account.') }}
            </p>
        </div>

        @if (session('status') === 'verification-link-sent')
            <div role="status" class="rounded-[var(--hs-radius-sm)] border border-[var(--hs-success-200)] bg-[var(--hs-success-50)] p-3 text-sm text-[var(--hs-success-700)]">
                {{ __('A new verification link has been sent.') }}
            </div>
        @endif

        <div class="grid gap-3">
            <form method="POST" action="{{ route('verification.send') }}" x-data="{ loading: false }" x-on:submit="loading = true">
                @csrf
                <button
                    type="submit"
                    x-bind:disabled="loading"
                    x-bind:aria-busy="loading"
                    class="flex min-h-12 w-full items-center justify-center gap-2 rounded-[var(--hs-radius-sm)] bg-[var(--hs-green-600)] px-4 text-base font-semibold text-white shadow-xs transition motion-reduce:transition-none hover:bg-[var(--hs-green-700)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <svg x-show="loading" x-cloak class="size-5 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" />
                    </svg>
                    <span x-show="! loading">{{ __('Resend email') }}</span>
                    <span x-show="loading" x-cloak>{{ __('Sending...') }}</span>
                </button>
            </form>

            <a href="{{ route('profile.edit') }}" wire:navigate class="flex min-h-11 items-center justify-center rounded-[var(--hs-radius-sm)] text-sm font-medium text-[var(--hs-green-700)] underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2">
                {{ __('Change email') }}
            </a>
        </div>
    </div>
</x-layouts::auth.card>
