<x-layouts::auth :title="__('Log in')" activeTab="login">
    <p class="mb-5 text-sm text-[var(--hs-neutral-500)] leading-relaxed">
        {{ __('Log in to manage your company profile and listings.') }}
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
            placeholder="you@company.com"
        />

        <!-- Password -->
        <flux:input
            name="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="current-password"
            :placeholder="__('Enter your password')"
            viewable
        />

        <!-- Remember + Forgot -->
        <div class="flex items-center justify-between text-sm">
            <flux:checkbox name="remember" :label="__('Keep me signed in')" :checked="old('remember')" />

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate class="font-bold text-[var(--hs-green-600)] hover:underline">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <flux:button variant="primary" type="submit" class="!w-full" data-test="login-button">
            {{ __('Log in') }}
        </flux:button>
    </form>

    <p class="mt-5 text-center text-sm text-[var(--hs-neutral-500)]">
        {{ __('No account yet?') }}
        <a href="{{ route('register') }}" wire:navigate class="font-bold text-[var(--hs-green-600)] hover:underline">{{ __('Register your company') }}</a>
    </p>
</x-layouts::auth>
