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
        <flux:field>
            <flux:label>{{ __('Create password') }}</flux:label>
            <div x-data="{ show: false }" class="relative">
                <input
                    name="password"
                    :type="show ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    placeholder="{{ __('At least 8 characters') }}"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
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

        <!-- Confirm Password -->
        <flux:field>
            <flux:label>{{ __('Confirm password') }}</flux:label>
            <div x-data="{ show: false }" class="relative">
                <input
                    name="password_confirmation"
                    :type="show ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    placeholder="{{ __('Confirm password') }}"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
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
            <flux:error name="password_confirmation" />
        </flux:field>

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
