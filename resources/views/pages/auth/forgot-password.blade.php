<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-[var(--hs-neutral-900)]" style="font-family: var(--hs-font-display);">{{ __('Forgot your password?') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Enter your email and we\'ll send you a reset link') }}</p>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
            @csrf
            <div>
                <label for="email" class="block text-sm font-semibold text-[var(--hs-neutral-700)] mb-1.5">{{ __('Email address') }}</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="you@example.com"
                    class="w-full px-3.5 py-2.5 rounded-lg border border-[var(--hs-neutral-200)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)]/20 focus:border-[var(--hs-green-600)] transition"
                />
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <!-- Turnstile -->
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.key') }}"></div>
            @error('cf-turnstile-response') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

            <button type="submit" class="w-full py-2.5 rounded-lg bg-[var(--hs-green-600)] hover:bg-[var(--hs-green-700)] text-white text-sm font-semibold transition shadow-[var(--hs-shadow-sm)]">
                {{ __('Send reset link') }}
            </button>
        </form>

        <div class="text-center text-sm text-[var(--hs-neutral-500)]">
            <a href="{{ route('login') }}" class="font-semibold text-[var(--hs-green-700)] hover:underline" wire:navigate>{{ __('Back to login') }}</a>
        </div>
    </div>
</x-layouts::auth>
