<x-layouts::auth :title="__('Create account')" activeTab="register">
    @php
        $hasSummaryErrors = $errors->hasAny(['first_name', 'last_name', 'email', 'password', 'terms', 'cf-turnstile-response']);
    @endphp

    <div
        x-data="registrationForm({
            firstName: @js(old('first_name', '')),
            lastName: @js(old('last_name', '')),
            email: @js(old('email', '')),
            password: '',
            terms: @js((bool) old('terms')),
        }, @js($hasSummaryErrors))"
    >
        <div>
            <h1 class="font-[var(--hs-font-display)] text-2xl font-bold text-[var(--hs-neutral-900)]">{{ __('Create account') }}</h1>
            <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-600)]">
                {{ __('Create your account to set up your business profile. You’ll add your business details next.') }}
            </p>
        </div>

        <x-auth-session-status class="mt-4" :status="session('status')" />

        @if ($hasSummaryErrors)
            <div
                x-ref="errorSummary"
                tabindex="-1"
                role="alert"
                class="mt-4 rounded-[var(--hs-radius-sm)] border border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)] p-3 text-sm text-[var(--hs-danger-700)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2"
            >
                <p class="font-semibold">{{ __('Please check the following:') }}</p>
                <ul class="mt-1 list-disc space-y-1 ps-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div
            x-ref="clientErrorSummary"
            tabindex="-1"
            role="alert"
            x-show="Object.values(errors).some(Boolean)"
            x-cloak
            class="mt-4 rounded-[var(--hs-radius-sm)] border border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)] p-3 text-sm text-[var(--hs-danger-700)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2"
        >
            <p class="font-semibold">{{ __('Please check the following:') }}</p>
            <ul class="mt-1 list-disc space-y-1 ps-5">
                <template x-for="message in Object.values(errors).filter(Boolean)" :key="message">
                    <li x-text="message"></li>
                </template>
            </ul>
        </div>

        <div class="mt-6">
            <x-google-auth-button />
        </div>

        <div class="relative my-6" aria-hidden="true">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[var(--hs-neutral-300)]"></div></div>
            <div class="relative flex justify-center"><span class="bg-[var(--hs-neutral-0)] px-3 text-sm text-[var(--hs-neutral-700)]">{{ __('or') }}</span></div>
        </div>

        <form method="POST" action="{{ route('register.store') }}" data-human x-on:submit="submit($event)" novalidate class="grid gap-4">
            @csrf

            <div class="grid gap-2">
                <label data-flux-label for="register-first-name" class="text-sm font-medium leading-tight text-[var(--hs-neutral-900)]">{{ __('First name') }}</label>
                <div class="relative">
                    <input
                        id="register-first-name"
                        name="first_name"
                        type="text"
                        autocomplete="given-name"
                        autocapitalize="words"
                        enterkeyhint="next"
                        required
                        autofocus
                        x-model="firstName"
                        x-on:blur="validateOnBlur('firstName')"
                        x-on:input="if (errors.firstName) validate('firstName')"
                        x-bind:aria-invalid="Boolean(errors.firstName) || @js($errors->has('first_name'))"
                        aria-describedby="register-first-name-error"
                        x-bind:class="errors.firstName ? '!border-[var(--hs-danger-500)] pe-11' : ''"
                        data-flux-control data-flux-group-target
                        @class([
                            'h-12 w-full appearance-none rounded-[var(--hs-radius-sm)] border shadow-xs leading-[1.375rem] border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] px-3 text-base text-[var(--hs-neutral-900)] focus:border-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
                            '!border-[var(--hs-danger-500)] pe-11' => $errors->has('first_name'),
                        ])
                    />
                    @if ($errors->has('first_name'))
                        <flux:icon.exclamation-circle class="pointer-events-none absolute inset-y-0 end-3 my-auto size-5 text-[var(--hs-danger-700)]" />
                    @endif
                    <flux:icon.exclamation-circle x-show="errors.firstName" x-cloak class="pointer-events-none absolute inset-y-0 end-3 my-auto size-5 text-[var(--hs-danger-700)]" />
                </div>
                <p id="register-first-name-error" x-show="errors.firstName" x-text="errors.firstName" x-cloak class="text-sm text-[var(--hs-danger-700)]"></p>
                @error('first_name')<p class="text-sm text-[var(--hs-danger-700)]">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-2">
                <label data-flux-label for="register-last-name" class="text-sm font-medium leading-tight text-[var(--hs-neutral-900)]">{{ __('Last name') }}</label>
                <div class="relative">
                    <input
                        id="register-last-name"
                        name="last_name"
                        type="text"
                        autocomplete="family-name"
                        autocapitalize="words"
                        enterkeyhint="next"
                        required
                        x-model="lastName"
                        x-on:blur="validateOnBlur('lastName')"
                        x-on:input="if (errors.lastName) validate('lastName')"
                        x-bind:aria-invalid="Boolean(errors.lastName) || @js($errors->has('last_name'))"
                        aria-describedby="register-last-name-error"
                        x-bind:class="errors.lastName ? '!border-[var(--hs-danger-500)] pe-11' : ''"
                        data-flux-control data-flux-group-target
                        @class([
                            'h-12 w-full appearance-none rounded-[var(--hs-radius-sm)] border shadow-xs leading-[1.375rem] border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] px-3 text-base text-[var(--hs-neutral-900)] focus:border-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
                            '!border-[var(--hs-danger-500)] pe-11' => $errors->has('last_name'),
                        ])
                    />
                    @if ($errors->has('last_name'))
                        <flux:icon.exclamation-circle class="pointer-events-none absolute inset-y-0 end-3 my-auto size-5 text-[var(--hs-danger-700)]" />
                    @endif
                    <flux:icon.exclamation-circle x-show="errors.lastName" x-cloak class="pointer-events-none absolute inset-y-0 end-3 my-auto size-5 text-[var(--hs-danger-700)]" />
                </div>
                <p id="register-last-name-error" x-show="errors.lastName" x-text="errors.lastName" x-cloak class="text-sm text-[var(--hs-danger-700)]"></p>
                @error('last_name')<p class="text-sm text-[var(--hs-danger-700)]">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-2">
                <label data-flux-label for="register-email" class="text-sm font-medium leading-tight text-[var(--hs-neutral-900)]">{{ __('Work email') }}</label>
                <div class="relative">
                    <input
                        id="register-email"
                        name="email"
                        type="email"
                        inputmode="email"
                        autocomplete="email"
                        autocapitalize="none"
                        autocorrect="off"
                        enterkeyhint="next"
                        required
                        x-model="email"
                        x-on:blur="validateOnBlur('email')"
                        x-on:input="if (errors.email) validate('email')"
                        x-bind:aria-invalid="Boolean(errors.email) || @js($errors->has('email'))"
                        aria-describedby="register-email-error"
                        x-bind:class="errors.email ? '!border-[var(--hs-danger-500)]' : ''"
                        data-flux-control data-flux-group-target
                        @class([
                            'h-12 w-full appearance-none rounded-[var(--hs-radius-sm)] border shadow-xs leading-[1.375rem] border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] px-3 pe-11 text-base text-[var(--hs-neutral-900)] placeholder:text-[var(--hs-neutral-600)] focus:border-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
                            '!border-[var(--hs-danger-500)]' => $errors->has('email'),
                        ])
                        placeholder="you@company.com"
                    />
                    @if ($errors->has('email'))
                        <flux:icon.exclamation-circle class="pointer-events-none absolute inset-y-0 end-3 my-auto size-5 text-[var(--hs-danger-700)]" />
                    @endif
                    <flux:icon.exclamation-circle x-show="errors.email" x-cloak class="pointer-events-none absolute inset-y-0 end-3 my-auto size-5 text-[var(--hs-danger-700)]" />
                </div>
                <p id="register-email-error" x-show="errors.email" x-text="errors.email" x-cloak class="text-sm text-[var(--hs-danger-700)]"></p>
                @error('email')<p class="text-sm text-[var(--hs-danger-700)]">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-2" x-data="{ showPassword: false }">
                <label data-flux-label for="register-password" class="text-sm font-medium leading-tight text-[var(--hs-neutral-900)]">{{ __('Password') }}</label>
                <div class="relative">
                    <input
                        id="register-password"
                        name="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        enterkeyhint="done"
                        required
                        minlength="6"
                        passwordrules="minlength: 6"
                        x-model="password"
                        x-on:blur="validateOnBlur('password'); capsLock = false"
                        x-on:input="if (errors.password) validate('password')"
                        x-on:keyup="capsLock = $event.getModifierState('CapsLock')"
                        x-bind:aria-invalid="Boolean(errors.password) || @js($errors->has('password'))"
                        aria-describedby="register-password-help register-password-strength register-password-error register-caps-lock"
                        x-bind:class="errors.password ? '!border-[var(--hs-danger-500)]' : ''"
                        data-flux-control data-flux-group-target
                        @class([
                            'h-12 w-full appearance-none rounded-[var(--hs-radius-sm)] border shadow-xs leading-[1.375rem] border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] px-3 pe-12 text-base text-[var(--hs-neutral-900)] focus:border-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
                            '!border-[var(--hs-danger-500)]' => $errors->has('password'),
                        ])
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
                <p id="register-password-help" class="text-sm text-[var(--hs-neutral-700)]">{{ __('At least 6 characters. Longer passphrases are welcome.') }}</p>
                <div id="register-password-strength" x-show="password.length > 0" x-cloak class="grid gap-1.5" aria-live="polite">
                    <div class="flex gap-1" role="meter" aria-valuemin="1" aria-valuemax="4" x-bind:aria-valuenow="strength" x-bind:aria-valuetext="strengthLabel">
                        <template x-for="segment in 4" :key="segment">
                            <span class="h-1.5 flex-1 rounded-full motion-reduce:transition-none" x-bind:class="segment <= strength ? strengthClass : 'bg-[var(--hs-neutral-200)]'"></span>
                        </template>
                    </div>
                    <p class="text-xs font-medium text-[var(--hs-neutral-700)]"><span>{{ __('Password strength:') }}</span> <span x-text="strengthLabel"></span></p>
                </div>
                <p id="register-password-error" x-show="errors.password" x-text="errors.password" x-cloak class="text-sm text-[var(--hs-danger-700)]"></p>
                @error('password')<p class="text-sm text-[var(--hs-danger-700)]">{{ $message }}</p>@enderror
                <p id="register-caps-lock" x-show="capsLock" x-cloak class="text-sm text-[var(--hs-warning-700)]">{{ __('Caps Lock is on.') }}</p>
            </div>

            <input type="hidden" name="cf-turnstile-response" value="">
            <flux:error name="cf-turnstile-response" />

            <div class="grid gap-2">
                <label class="flex min-h-11 cursor-pointer items-start gap-3 text-sm leading-relaxed text-[var(--hs-neutral-800)]">
                    <input
                        id="terms"
                        name="terms"
                        type="checkbox"
                        value="1"
                        required
                        x-model="terms"
                        x-on:change="if (errors.terms) validate('terms')"
                        x-bind:aria-invalid="Boolean(errors.terms) || @js($errors->has('terms'))"
                        aria-describedby="register-terms-error"
                        class="mt-2 size-5 shrink-0 rounded border-[var(--hs-neutral-400)] accent-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2"
                    />
                    <span>
                        {{ __('By creating an account, you agree to our') }}
                        <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--hs-green-700)] underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2">{{ __('Terms of Service') }}</a>
                        {{ __('and') }}
                        <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--hs-green-700)] underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2">{{ __('Privacy Policy') }}</a>.
                    </span>
                </label>
                <p id="register-terms-error" x-show="errors.terms" x-text="errors.terms" x-cloak class="text-sm text-[var(--hs-danger-700)]"></p>
                @error('terms')<p class="text-sm text-[var(--hs-danger-700)]">{{ $message }}</p>@enderror
            </div>

            <button
                type="submit"
                x-bind:disabled="! isValid || loading"
                x-bind:aria-busy="loading"
                class="flex min-h-12 w-full items-center justify-center gap-2 rounded-[var(--hs-radius-sm)] bg-[var(--hs-green-600)] px-4 text-base font-semibold text-white shadow-xs transition motion-reduce:transition-none hover:bg-[var(--hs-green-700)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-[var(--hs-neutral-300)] disabled:text-[var(--hs-neutral-700)]"
                data-test="register-user-button"
            >
                <svg x-show="loading" x-cloak class="size-5 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" />
                </svg>
                <span x-show="! loading">{{ __('Create account') }}</span>
                <span x-show="loading" x-cloak>{{ __('Creating account...') }}</span>
            </button>
        </form>
    </div>
</x-layouts::auth>
