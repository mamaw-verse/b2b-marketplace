@props([
    'optionsRoute' => 'passkey.login-options',
    'submitRoute' => 'passkey.login',
    'label' => __('Continue with a passkey'),
    'loadingLabel' => __('Checking passkey...'),
    'autofill' => true,
])

@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    {{ $attributes }}
    x-data="{
        supported: false,
        loading: false,
        error: null,
        async ready() {
            this.supported = Boolean(window.Passkeys?.isSupported());

            if (this.supported && @js($autofill)) {
                try {
                    const response = await window.Passkeys.autofill({
                        routes: {
                            options: '{{ route($optionsRoute) }}',
                            submit: '{{ route($submitRoute) }}',
                        },
                    });

                    if (response) {
                        window.localStorage.setItem('auth.lastMethod', 'passkey');
                        window.Livewire?.navigate(response.redirect || '/dashboard') ?? window.location.assign(response.redirect || '/dashboard');
                    }
                } catch (error) {
                    if (error.constructor?.name !== 'UserCancelledError') {
                        this.error = error.message;
                    }
                }
            }
        },
        init() {
            if (window.Passkeys) {
                this.ready();
                return;
            }

            window.addEventListener('passkeys:ready', () => this.ready(), { once: true });
        },
        async verify() {
            this.loading = true;
            this.error = null;

            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: '{{ route($optionsRoute) }}',
                        submit: '{{ route($submitRoute) }}',
                    },
                });
                window.localStorage.setItem('auth.lastMethod', 'passkey');
                window.Livewire?.navigate(response.redirect || '/dashboard') ?? window.location.assign(response.redirect || '/dashboard');
            } catch (error) {
                if (error.constructor?.name !== 'UserCancelledError') {
                    this.error = error.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }"
    x-show="supported"
    x-cloak
>
    <button
        type="button"
        class="flex min-h-12 w-full items-center justify-center gap-3 rounded-[var(--hs-radius-sm)] border border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] px-4 text-base font-semibold text-[var(--hs-neutral-900)] shadow-xs transition motion-reduce:transition-none hover:bg-[var(--hs-neutral-100)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
        x-on:click="verify()"
        x-bind:disabled="loading"
        x-bind:aria-busy="loading"
    >
        <flux:icon.finger-print class="size-5 shrink-0" />
        <span x-show="! loading">{{ $label }}</span>
        <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
    </button>
    <p x-show="error" x-text="error" x-cloak role="alert" class="mt-2 text-center text-sm text-[var(--hs-danger-700)]"></p>
</div>
