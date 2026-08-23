<x-layouts::auth :title="__('Register')" activeTab="register">
    <p class="mb-5 text-sm text-[var(--hs-neutral-500)] leading-relaxed">
        {{ __('Set up your company profile in a few minutes.') }}
    </p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf

        <!-- Name (two-col) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <flux:input
                name="first_name"
                :label="__('First name')"
                :value="old('first_name')"
                type="text"
                required
                autofocus
                autocomplete="given-name"
                :placeholder="__('First name')"
            />
            <flux:input
                name="last_name"
                :label="__('Last name')"
                :value="old('last_name')"
                type="text"
                required
                autocomplete="family-name"
                :placeholder="__('Last name')"
            />
        </div>

        <!-- Email Address -->
        <flux:input
            name="email"
            :label="__('Work email')"
            :value="old('email')"
            type="email"
            required
            autocomplete="email"
            placeholder="you@company.com"
        />

        <!-- Password -->
        <flux:input
            name="password"
            :label="__('Create password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('At least 8 characters')"
            passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
            viewable
        />

        <!-- Confirm Password -->
        <flux:input
            name="password_confirmation"
            :label="__('Confirm password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Confirm password')"
            passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
            viewable
        />

        <flux:button type="submit" variant="primary" class="!w-full" data-test="register-user-button">
            {{ __('Create account') }}
        </flux:button>
    </form>

    <p class="mt-4 text-center text-xs text-[var(--hs-neutral-500)] leading-relaxed">
        {{ __('By creating an account you agree to our') }}
        <a href="#" class="font-semibold text-[var(--hs-green-900)] hover:underline">{{ __('Terms') }}</a>
        {{ __('and') }}
        <a href="#" class="font-semibold text-[var(--hs-green-900)] hover:underline">{{ __('Halal Verification Policy') }}</a>.
    </p>

    <p class="mt-4 text-center text-sm text-[var(--hs-neutral-500)]">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" wire:navigate class="font-bold text-[var(--hs-green-600)] hover:underline">{{ __('Log in') }}</a>
    </p>
</x-layouts::auth>
