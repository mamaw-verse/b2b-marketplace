<x-layouts::auth :title="__('Log in')" activeTab="login">
    <p class="mb-5 text-sm text-[var(--hs-neutral-500)] leading-relaxed">
        {{ __('Log in to manage your business profile and listings.') }}
    </p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <x-passkey-verify />

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <flux:input
            name="email"
            :label="__('Email')"
            :value="old('email')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="you@business.com"
        />

        <!-- Password -->
        <flux:field>
            <flux:label>{{ __('Password') }}</flux:label>
            <div x-data="{ show: false }" class="relative">
                <input
                    name="password"
                    :type="show ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                    placeholder="{{ __('Enter your password') }}"
                    class="w-full rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-3 py-2 pr-10 text-sm text-[var(--hs-neutral-900)] focus:border-[var(--hs-green-600)] focus:outline-none focus:ring-1 focus:ring-[var(--hs-green-600)]"
                />
                <button
                    type="button"
                    @click="show = !show"
                    :aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)]"
                >
                    <flux:icon.eye x-show="!show" class="size-5" />
                    <flux:icon.eye-slash x-show="show" x-cloak class="size-5" />
                </button>
            </div>
            <flux:error name="password" />
        </flux:field>

        <!-- Remember + Forgot -->
        <div class="flex items-center justify-between text-sm">
            <flux:checkbox name="remember" :label="__('Keep me signed in')" :checked="old('remember')" />

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate class="font-bold text-[var(--hs-green-600)] hover:underline">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <!-- Turnstile -->
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.key') }}"></div>
        @error('cf-turnstile-response') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <flux:button variant="primary" type="submit" class="!w-full" data-test="login-button">
            {{ __('Log in') }}
        </flux:button>
    </form>

    <p class="mt-5 text-center text-sm text-[var(--hs-neutral-500)]">
        {{ __('No account yet?') }}
        <a href="{{ route('register') }}" wire:navigate class="font-bold text-[var(--hs-green-600)] hover:underline">{{ __('Register your business') }}</a>
    </p>
</x-layouts::auth>
