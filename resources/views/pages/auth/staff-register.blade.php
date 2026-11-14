<x-layouts::auth :title="__('Create your account')" :showTabs="false">
    @php
        $hasSummaryErrors = $errors->hasAny(['first_name', 'last_name', 'password', 'terms']);
    @endphp

    <div
        x-data="{
            firstName: @js(old('first_name', '')),
            lastName: @js(old('last_name', '')),
            password: '',
            terms: @js((bool) old('terms')),
            loading: false,
            showPassword: false,
            capsLock: false,
            get isValid() {
                return this.firstName.trim().length > 0
                    && this.lastName.trim().length > 0
                    && this.password.length >= 6
                    && this.terms;
            },
        }"
    >
        <div>
            <h1 class="font-[var(--hs-font-display)] text-2xl font-bold text-[var(--hs-neutral-900)]">{{ __('Create your account') }}</h1>
            <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-600)]">
                {{ __("You've been invited to join :shop. Create a password to get started.", ['shop' => $invitation->shop->storefront_name]) }}
            </p>
        </div>

        @if ($hasSummaryErrors)
            <div
                tabindex="-1"
                role="alert"
                class="mt-4 rounded-[var(--hs-radius-sm)] border border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)] p-3 text-sm text-[var(--hs-danger-700)]"
            >
                <p class="font-semibold">{{ __('Please check the following:') }}</p>
                <ul class="mt-1 list-disc space-y-1 ps-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('staff.invitations.register.store', $token) }}" x-on:submit="loading = true" class="mt-6 grid gap-4">
            @csrf

            {{-- Email (read-only, pre-filled from invitation) --}}
            <div class="grid gap-2">
                <label data-flux-label for="staff-register-email" class="text-sm font-medium leading-tight text-[var(--hs-neutral-900)]">{{ __('Work email') }}</label>
                <input
                    id="staff-register-email"
                    type="email"
                    value="{{ $invitation->email }}"
                    disabled
                    aria-describedby="staff-register-email-hint"
                    class="h-12 w-full appearance-none rounded-[var(--hs-radius-sm)] border border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-100)] px-3 text-base text-[var(--hs-neutral-500)] shadow-xs disabled:cursor-not-allowed"
                />
                <p id="staff-register-email-hint" class="text-xs text-[var(--hs-neutral-500)]">{{ __('This is the email your invitation was sent to.') }}</p>
            </div>

            {{-- First name --}}
            <div class="grid gap-2">
                <label data-flux-label for="staff-register-first-name" class="text-sm font-medium leading-tight text-[var(--hs-neutral-900)]">{{ __('First name') }}</label>
                <input
                    id="staff-register-first-name"
                    name="first_name"
                    type="text"
                    autocomplete="given-name"
                    autocapitalize="words"
                    enterkeyhint="next"
                    required
                    autofocus
                    x-model="firstName"
                    data-flux-control
                    @class([
                        'h-12 w-full appearance-none rounded-[var(--hs-radius-sm)] border shadow-xs leading-[1.375rem] border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] px-3 text-base text-[var(--hs-neutral-900)] focus:border-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2',
                        '!border-[var(--hs-danger-500)]' => $errors->has('first_name'),
                    ])
                />
                @error('first_name')<p class="text-sm text-[var(--hs-danger-700)]">{{ $message }}</p>@enderror
            </div>

            {{-- Last name --}}
            <div class="grid gap-2">
                <label data-flux-label for="staff-register-last-name" class="text-sm font-medium leading-tight text-[var(--hs-neutral-900)]">{{ __('Last name') }}</label>
                <input
                    id="staff-register-last-name"
                    name="last_name"
                    type="text"
                    autocomplete="family-name"
                    autocapitalize="words"
                    enterkeyhint="next"
                    required
                    x-model="lastName"
                    data-flux-control
                    @class([
                        'h-12 w-full appearance-none rounded-[var(--hs-radius-sm)] border shadow-xs leading-[1.375rem] border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] px-3 text-base text-[var(--hs-neutral-900)] focus:border-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2',
                        '!border-[var(--hs-danger-500)]' => $errors->has('last_name'),
                    ])
                />
                @error('last_name')<p class="text-sm text-[var(--hs-danger-700)]">{{ $message }}</p>@enderror
            </div>

            {{-- Password --}}
            <div class="grid gap-2">
                <label data-flux-label for="staff-register-password" class="text-sm font-medium leading-tight text-[var(--hs-neutral-900)]">{{ __('Password') }}</label>
                <div class="relative">
                    <input
                        id="staff-register-password"
                        name="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        enterkeyhint="done"
                        required
                        minlength="6"
                        passwordrules="minlength: 6"
                        x-model="password"
                        x-on:keyup="capsLock = $event.getModifierState('CapsLock')"
                        x-on:blur="capsLock = false"
                        data-flux-control
                        @class([
                            'h-12 w-full appearance-none rounded-[var(--hs-radius-sm)] border shadow-xs leading-[1.375rem] border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] px-3 pe-12 text-base text-[var(--hs-neutral-900)] focus:border-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2',
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
                <p class="text-sm text-[var(--hs-neutral-700)]">{{ __('At least 6 characters.') }}</p>
                @error('password')<p class="text-sm text-[var(--hs-danger-700)]">{{ $message }}</p>@enderror
                <p x-show="capsLock" x-cloak class="text-sm text-[var(--hs-warning-700)]">{{ __('Caps Lock is on.') }}</p>
            </div>

            {{-- Terms --}}
            <div class="grid gap-2">
                <label class="flex min-h-11 cursor-pointer items-start gap-3 text-sm leading-relaxed text-[var(--hs-neutral-800)]">
                    <input
                        name="terms"
                        type="checkbox"
                        value="1"
                        required
                        x-model="terms"
                        class="mt-2 size-5 shrink-0 rounded border-[var(--hs-neutral-400)] accent-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2"
                    />
                    <span>
                        {{ __('By creating an account, you agree to our') }}
                        <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--hs-green-700)] underline-offset-4 hover:underline">{{ __('Terms of Service') }}</a>
                        {{ __('and') }}
                        <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--hs-green-700)] underline-offset-4 hover:underline">{{ __('Privacy Policy') }}</a>.
                    </span>
                </label>
                @error('terms')<p class="text-sm text-[var(--hs-danger-700)]">{{ $message }}</p>@enderror
            </div>

            @error('invitation')
                <p class="text-sm text-[var(--hs-danger-700)]">{{ $message }}</p>
            @enderror

            <button
                type="submit"
                x-bind:disabled="! isValid || loading"
                x-bind:aria-busy="loading"
                class="flex min-h-12 w-full items-center justify-center gap-2 rounded-[var(--hs-radius-sm)] bg-[var(--hs-green-600)] px-4 text-base font-semibold text-white shadow-xs transition motion-reduce:transition-none hover:bg-[var(--hs-green-700)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-[var(--hs-neutral-300)] disabled:text-[var(--hs-neutral-700)]"
            >
                <svg x-show="loading" x-cloak class="size-5 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" />
                </svg>
                <span x-show="! loading">{{ __('Create account & join team') }}</span>
                <span x-show="loading" x-cloak>{{ __('Creating account...') }}</span>
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-[var(--hs-neutral-600)]">
            {{ __('Already have an account?') }}
            <a href="{{ route('login', ['email' => $invitation->email]) }}" class="font-semibold text-[var(--hs-green-700)] underline-offset-4 hover:underline">{{ __('Sign in') }}</a>
        </p>
    </div>
</x-layouts::auth>
