<x-layouts::auth :title="__('Register')" activeTab="register">
    <p class="mb-5 text-sm text-[var(--hs-neutral-500)] leading-relaxed">
        {{ __('Set up your business profile in a few minutes.') }}
    </p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4" x-data="{
        legalOpen: false,
        agreed: {{ old('terms') ? 'true' : 'false' }},
        readComplete: false,
        progress: 0,
        updateLegalProgress() {
            const el = this.$refs.legalBody;
            if (! el) return;
            const max = el.scrollHeight - el.clientHeight;
            this.progress = max > 0 ? Math.min(100, Math.round((el.scrollTop / max) * 100)) : 100;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 24) {
                this.readComplete = true;
            }
        },
        openLegal() {
            this.legalOpen = true;
            this.$nextTick(() => this.updateLegalProgress());
        }
    }">
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
            placeholder="you@business.com"
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

        <!-- Turnstile -->
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.key') }}"></div>
        @error('cf-turnstile-response') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <!-- Terms acceptance -->
        <flux:field>
            <div class="flex items-start gap-2.5">
                <input
                    type="checkbox"
                    id="terms"
                    name="terms"
                    value="1"
                    x-model="agreed"
                    @click.prevent="agreed ? agreed = false : openLegal()"
                    required
                    class="mt-0.5 size-4 shrink-0 rounded border-[var(--hs-neutral-300)] accent-[var(--hs-green-600)] cursor-pointer"
                />
                <span class="text-xs text-[var(--hs-neutral-500)] leading-relaxed">
                    {{ __('I have read and agree to the') }}
                    <button type="button" @click.stop="openLegal()" class="font-semibold text-[var(--hs-green-900)] hover:underline bg-transparent border-none p-0 cursor-pointer text-xs">{{ __('Terms of Service and Privacy Policy') }}</button>.
                </span>
            </div>
            <flux:error name="terms" />
        </flux:field>

        <!-- Terms / Privacy modal: full combined document, Agree unlocks at the bottom -->
        <div
            x-show="legalOpen"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('Terms of Service and Privacy Policy') }}"
            @keydown.escape.window="legalOpen = false"
            style="display: none;"
        >
            <div class="absolute inset-0 bg-black/50" @click="legalOpen = false"></div>

            <div class="relative flex flex-col w-full max-w-3xl max-h-[85vh] rounded-2xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-xl)]">
                {{-- Header --}}
                <div class="shrink-0 border-b border-[var(--hs-neutral-100)] px-6 py-4">
                    <flux:heading size="lg">{{ __('Terms of Service and Privacy Policy') }}</flux:heading>
                    <flux:text class="mt-1 text-[var(--hs-neutral-500)]">{{ __('Please read the full document, then tap Agree below.') }}</flux:text>
                    {{-- Reading progress --}}
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-[var(--hs-neutral-100)]" role="progressbar" aria-label="{{ __('Reading progress') }}" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full bg-[var(--hs-green-600)] transition-[width]" :style="`width: ${progress}%`"></div>
                    </div>
                </div>

                {{-- Scrollable full document --}}
                <div x-ref="legalBody" @scroll="updateLegalProgress()" class="flex-1 overflow-y-auto px-6 py-5">
                    <h3 class="text-lg font-bold text-[var(--hs-neutral-900)]">{{ __('Terms of Service') }}</h3>
                    <div class="mt-2">
                        @include('partials.legal.terms-body')
                    </div>

                    <hr class="my-8 border-[var(--hs-neutral-200)]">

                    <h3 class="text-lg font-bold text-[var(--hs-neutral-900)]">{{ __('Privacy Policy') }}</h3>
                    <div class="mt-2">
                        @include('partials.legal.privacy-body')
                    </div>

                    {{-- End-of-document marker --}}
                    <div class="mt-8 flex items-center gap-2.5 rounded-xl bg-[var(--hs-green-50)] border border-[var(--hs-green-100)] p-4">
                        <flux:icon.check-circle class="size-5 shrink-0 text-[var(--hs-green-700)]" />
                        <p class="text-sm font-medium text-[var(--hs-neutral-700)]">{{ __('You have reached the end of the document. You may now tap Agree below.') }}</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="shrink-0 flex flex-col gap-3 border-t border-[var(--hs-neutral-100)] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-relaxed" :class="readComplete ? 'text-[var(--hs-green-700)] font-semibold' : 'text-[var(--hs-neutral-500)]'">
                        <span x-show="! readComplete">{{ __('Scroll to the bottom to enable Agree') }} (<span x-text="progress"></span>%)</span>
                        <span x-show="readComplete" style="display: none;">{{ __('Thanks for reading — you can now agree.') }}</span>
                    </p>
                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="legalOpen = false"
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-[var(--hs-neutral-600)] bg-transparent border-none cursor-pointer hover:bg-[var(--hs-neutral-100)] transition"
                        >{{ __('Close') }}</button>
                        <button
                            type="button"
                            @click="agreed = true; legalOpen = false"
                            :disabled="! readComplete"
                            :class="readComplete ? 'bg-[var(--hs-green-600)] hover:bg-[var(--hs-green-700)] cursor-pointer' : 'bg-[var(--hs-neutral-200)] cursor-not-allowed'"
                            class="rounded-lg px-5 py-2 text-sm font-semibold text-white border-none transition"
                        >{{ __('Agree') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <flux:button type="submit" variant="primary" class="!w-full" data-test="register-user-button">
            {{ __('Create account') }}
        </flux:button>
    </form>

    <p class="mt-4 text-center text-sm text-[var(--hs-neutral-500)]">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" wire:navigate class="font-bold text-[var(--hs-green-600)] hover:underline">{{ __('Log in') }}</a>
    </p>
</x-layouts::auth>
