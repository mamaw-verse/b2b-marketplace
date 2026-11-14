<x-layouts::auth :title="__('Log in')" activeTab="login">
    @php
        $credentialMessage = $errors->first('email') ?: $errors->first('password');
        $isRateLimited = str_contains(mb_strtolower($credentialMessage), 'too many');
        $hasSummaryErrors = $errors->hasAny(['email', 'password', 'cf-turnstile-response', 'google']);
    @endphp

    <div
        x-data="loginForm({
            email: @js(old('email', request()->query('email', ''))),
            password: '',
        }, @js($hasSummaryErrors))"
        class="grid gap-6"
    >
        <div>
            <h1 class="font-[var(--hs-font-display)] text-2xl font-bold text-[var(--hs-neutral-900)]">{{ __('Log in') }}</h1>
            <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-600)]">
                {{ __('Welcome back. Log in to manage your business profile.') }}
            </p>
        </div>

        <x-auth-session-status :status="session('status')" />

        <div
            x-ref="clientErrorSummary"
            tabindex="-1"
            role="alert"
            x-show="Object.values(errors).some(Boolean)"
            x-cloak
            class="rounded-[var(--hs-radius-sm)] border border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)] p-3 text-sm text-[var(--hs-danger-700)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2"
        >
            <p class="font-semibold">{{ __('Please check the following:') }}</p>
            <ul class="mt-1 list-disc space-y-1 ps-5">
                <template x-for="message in Object.values(errors).filter(Boolean)" :key="message">
                    <li x-text="message"></li>
                </template>
            </ul>
        </div>

        @if ($hasSummaryErrors)
            <div
                x-ref="errorSummary"
                tabindex="-1"
                role="alert"
                class="rounded-[var(--hs-radius-sm)] border border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)] p-3 text-sm text-[var(--hs-danger-700)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2"
            >
                <p class="font-semibold">{{ __('We could not log you in.') }}</p>
                <ul class="mt-1 list-disc space-y-1 ps-5">
                    @if ($errors->hasAny(['email', 'password']))
                        <li>{{ $isRateLimited ? $credentialMessage : __('Email or password is incorrect.') }}</li>
                    @endif
                    @error('cf-turnstile-response')
                        <li>{{ $message }}</li>
                    @enderror
                    @error('google')
                        <li>{{ $message }}</li>
                    @enderror
                </ul>
            </div>
        @endif

        <template x-if="lastMethod === 'google'">
            <div class="grid gap-2">
                <p class="text-xs font-medium text-[var(--hs-neutral-600)]">{{ __('Last used') }}</p>
                <x-google-auth-button />
            </div>
        </template>

        <template x-if="lastMethod === 'passkey'">
            <div class="grid gap-2">
                <p class="text-xs font-medium text-[var(--hs-neutral-600)]">{{ __('Last used') }}</p>
                <x-passkey-verify />
            </div>
        </template>

        <form method="POST" action="{{ route('login.store') }}" data-human x-on:submit="submit($event)" novalidate class="grid gap-4">
            @csrf

            <div class="grid gap-2">
                <label data-flux-label for="login-email" class="text-sm font-medium leading-tight text-[var(--hs-neutral-900)]">{{ __('Email') }}</label>
                <div class="relative">
                    <input
                        id="login-email"
                        name="email"
                        type="email"
                        inputmode="email"
                        autocomplete="username webauthn"
                        autocapitalize="none"
                        autocorrect="off"
                        enterkeyhint="next"
                        required
                        autofocus
                        x-model="email"
                        x-on:blur="validateOnBlur('email')"
                        x-on:input="if (errors.email) validate('email')"
                        x-bind:aria-invalid="Boolean(errors.email) || @js($errors->has('email'))"
                        aria-describedby="login-email-error"
                        data-flux-control data-flux-group-target
                        class="h-12 w-full appearance-none rounded-[var(--hs-radius-sm)] border shadow-xs leading-[1.375rem] border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] px-3 pe-11 text-base text-[var(--hs-neutral-900)] placeholder:text-[var(--hs-neutral-600)] focus:border-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                        placeholder="you@company.com"
                    />
                    <flux:icon.exclamation-circle x-show="errors.email" x-cloak class="pointer-events-none absolute inset-y-0 end-3 my-auto size-5 text-[var(--hs-danger-700)]" />
                </div>
                <p id="login-email-error" x-show="errors.email" x-text="errors.email" x-cloak class="text-sm text-[var(--hs-danger-700)]"></p>
            </div>

            <div class="grid gap-2" x-data="{ showPassword: false }">
                <label data-flux-label for="login-password" class="text-sm font-medium leading-tight text-[var(--hs-neutral-900)]">{{ __('Password') }}</label>
                <div class="relative">
                    <input
                        id="login-password"
                        name="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        autocomplete="current-password"
                        enterkeyhint="go"
                        required
                        x-model="password"
                        x-on:blur="validateOnBlur('password'); capsLock = false"
                        x-on:input="if (errors.password) validate('password')"
                        x-on:keyup="capsLock = $event.getModifierState('CapsLock')"
                        x-bind:aria-invalid="Boolean(errors.password) || @js($errors->has('password'))"
                        aria-describedby="login-password-error login-caps-lock"
                        data-flux-control data-flux-group-target
                        class="h-12 w-full appearance-none rounded-[var(--hs-radius-sm)] border shadow-xs leading-[1.375rem] border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] px-3 pe-12 text-base text-[var(--hs-neutral-900)] focus:border-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    />
                    <button
                        type="button"
                        x-on:click="showPassword = ! showPassword"
                        x-bind:aria-label="showPassword ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                        x-bind:aria-pressed="showPassword"
                        class="absolute inset-y-0 end-0 flex min-h-11 min-w-11 items-center justify-center rounded-[var(--hs-radius-sm)] text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-900)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2"
                    >
                        <flux:icon.eye x-show="! showPassword" class="size-5" />
                        <flux:icon.eye-slash x-show="showPassword" x-cloak class="size-5" />
                    </button>
                </div>
                <p id="login-password-error" x-show="errors.password" x-text="errors.password" x-cloak class="text-sm text-[var(--hs-danger-700)]"></p>
                <p id="login-caps-lock" x-show="capsLock" x-cloak class="text-sm text-[var(--hs-warning-700)]">{{ __('Caps Lock is on.') }}</p>
            </div>

            <div class="flex min-h-11 items-center justify-between gap-3 text-sm">
                <label class="flex min-h-11 cursor-pointer items-center gap-2 text-[var(--hs-neutral-800)]">
                    <input
                        name="remember"
                        type="checkbox"
                        value="1"
                        @checked(old('remember'))
                        class="size-5 rounded border-[var(--hs-neutral-400)] accent-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2"
                    />
                    <span>{{ __('Keep me signed in') }}</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate class="flex min-h-11 items-center rounded-[var(--hs-radius-sm)] text-[var(--hs-green-700)] underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>

            <input type="hidden" name="cf-turnstile-response" value="">
            <flux:error name="cf-turnstile-response" />

            <button
                type="submit"
                x-bind:disabled="loading"
                x-bind:aria-busy="loading"
                class="flex min-h-12 w-full items-center justify-center gap-2 rounded-[var(--hs-radius-sm)] bg-[var(--hs-green-600)] px-4 text-base font-semibold text-white shadow-xs transition motion-reduce:transition-none hover:bg-[var(--hs-green-700)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                data-test="login-button"
            >
                <svg x-show="loading" x-cloak class="size-5 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" />
                </svg>
                <span x-show="! loading">{{ __('Log in') }}</span>
                <span x-show="loading" x-cloak>{{ __('Logging in...') }}</span>
            </button>
        </form>

        <div class="relative my-0 py-1" aria-hidden="true">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[var(--hs-neutral-300)]"></div></div>
            <div class="relative flex justify-center"><span class="bg-[var(--hs-neutral-0)] px-3 text-sm text-[var(--hs-neutral-700)]">{{ __('or') }}</span></div>
        </div>

        <div class="grid gap-3">
            <template x-if="lastMethod !== 'google'">
                <x-google-auth-button />
            </template>
            <template x-if="lastMethod !== 'passkey'">
                <x-passkey-verify />
            </template>
        </div>
    </div>
</x-layouts::auth>
