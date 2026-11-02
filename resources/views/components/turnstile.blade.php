@props(['model' => null, 'errorName' => 'cf-turnstile-response'])

@once
    <script>
        window.hsTurnstileLoad ??= () => (window.hsTurnstilePromise ??= new Promise((resolve, reject) => {
            if (window.turnstile) {
                return resolve(window.turnstile);
            }

            const script = document.createElement('script');
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
            script.async = true;
            script.onload = () => resolve(window.turnstile);
            script.onerror = () => {
                window.hsTurnstilePromise = null;
                reject(new Error('Turnstile failed to load'));
            };
            document.head.appendChild(script);
        }));

        window.hsTurnstileLoad().catch(() => {});
    </script>
@endonce

<div
    wire:ignore.self
    data-turnstile
    x-data="{
        widgetId: null,
        async init() {
            const turnstile = await window.hsTurnstileLoad();
            const setToken = (token) => @js($model) && this.$wire.set(@js($model), token, false);

            this.widgetId = turnstile.render(this.$refs.widget, {
                sitekey: @js(config('services.turnstile.key')),
                size: 'flexible',
                appearance: 'interaction-only',
                theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                callback: (token) => setToken(token),
                'expired-callback': () => setToken(''),
                'error-callback': () => setToken(''),
            });
        },
        reset() {
            if (this.widgetId !== null) {
                window.turnstile.reset(this.widgetId);
            }
        },
        destroy() {
            if (this.widgetId !== null) {
                window.turnstile?.remove(this.widgetId);
            }
        },
    }"
    x-on:turnstile-reset.window="reset()"
    class="w-full"
>
    <div wire:ignore x-ref="widget" class="w-full overflow-hidden"></div>
    <flux:error :name="$errorName" />
</div>
