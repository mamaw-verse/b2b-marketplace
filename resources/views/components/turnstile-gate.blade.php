{{--
    Turnstile Gate: Single widget per page for the entire application.
    Exposes window.HumanCheck with ensure() and fresh().
--}}
<div
    wire:ignore
    id="turnstile-gate-wrapper"
    data-turnstile
    class="fixed bottom-0 right-0 z-0 pointer-events-none opacity-0 select-none overflow-hidden h-0 w-0"
    aria-hidden="true"
>
    <div id="turnstile-gate-widget" wire:ignore></div>
</div>

@once
<script>
(function () {
    'use strict';

    const SITE_KEY = @js(config('services.turnstile.key'));
    const CHECK_URL = @js(route('human-check'));

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    let _widgetId = null;
    let _widgetPromise = null;
    let _pendingResolve = null;
    let _pendingReject = null;
    let _expiresAt = 0;
    let _ensuringPromise = null;

    function showToast(message) {
        if (window.Flux && typeof window.Flux.toast === 'function') {
            window.Flux.toast(message, { variant: 'danger' });
        } else {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { text: message, variant: 'danger' }
            }));
        }
    }

    function loadScript() {
        if (window._hsTurnstilePromise) return window._hsTurnstilePromise;
        window._hsTurnstilePromise = new Promise((resolve, reject) => {
            if (window.turnstile) {
                resolve(window.turnstile);
                return;
            }
            let script = document.querySelector('script[data-hs-turnstile]');
            if (!script) {
                script = document.createElement('script');
                script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
                script.defer = true;
                script.dataset.hsTurnstile = '';
                document.head.appendChild(script);
            }
            script.addEventListener('load', () => resolve(window.turnstile), { once: true });
            script.addEventListener('error', () => {
                window._hsTurnstilePromise = null;
                reject(new Error('Turnstile failed to load'));
            }, { once: true });
        });
        return window._hsTurnstilePromise;
    }

    function initWidget() {
        if (_widgetPromise) return _widgetPromise;
        _widgetPromise = new Promise(async (resolve, reject) => {
            try {
                const turnstile = await loadScript();
                const container = document.getElementById('turnstile-gate-widget');
                if (!container) {
                    _widgetPromise = null;
                    return reject(new Error('Widget container not found'));
                }
                _widgetId = turnstile.render(container, {
                    sitekey: SITE_KEY,
                    size: 'invisible',
                    appearance: 'interaction-only',
                    execution: 'execute',
                    callback: (token) => {
                        if (_pendingResolve) {
                            const res = _pendingResolve;
                            _pendingResolve = null;
                            _pendingReject = null;
                            res(token);
                        }
                    },
                    'error-callback': () => {
                        showToast('Verification failed, please try again.');
                        if (_pendingReject) {
                            const rej = _pendingReject;
                            _pendingResolve = null;
                            _pendingReject = null;
                            rej(new Error('Turnstile verification failed'));
                        }
                    },
                    'expired-callback': () => {
                        _expiresAt = 0;
                        if (_pendingReject) {
                            const rej = _pendingReject;
                            _pendingResolve = null;
                            _pendingReject = null;
                            rej(new Error('Turnstile expired'));
                        }
                    },
                    'timeout-callback': () => {
                        showToast('Verification failed, please try again.');
                        if (_pendingReject) {
                            const rej = _pendingReject;
                            _pendingResolve = null;
                            _pendingReject = null;
                            rej(new Error('Turnstile timed out'));
                        }
                    },
                });
                resolve(_widgetId);
            } catch (err) {
                _widgetPromise = null;
                reject(err);
            }
        });
        return _widgetPromise;
    }

    function executeWidget() {
        return new Promise(async (resolve, reject) => {
            try {
                await initWidget();
                _pendingResolve = resolve;
                _pendingReject = reject;
                window.turnstile.reset(_widgetId);
                window.turnstile.execute(_widgetId);
            } catch (e) {
                _pendingResolve = null;
                _pendingReject = null;
                reject(e);
            }
        });
    }

    window.HumanCheck = {
        isReady() {
            return Date.now() < _expiresAt;
        },

        ensure() {
            if (this.isReady()) {
                return Promise.resolve();
            }
            if (_ensuringPromise) {
                return _ensuringPromise;
            }
            _ensuringPromise = (async () => {
                try {
                    const token = await executeWidget();
                    const res = await fetch(CHECK_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                        },
                        body: JSON.stringify({ token }),
                        credentials: 'same-origin',
                    });

                    if (!res.ok) {
                        const errData = await res.json().catch(() => ({}));
                        showToast(errData.error?.message || 'Verification failed, please try again.');
                        throw new Error('Human verification failed');
                    }

                    const data = await res.json();
                    _expiresAt = (data.expires_at || (Math.floor(Date.now() / 1000) + 1200)) * 1000;
                } finally {
                    _ensuringPromise = null;
                }
            })();
            return _ensuringPromise;
        },

        async fresh() {
            return await executeWidget();
        },

        invalidate() {
            _expiresAt = 0;
        },
    };

    // Pre-warm on first form interaction
    document.addEventListener('focusin', function onFirstFocus(e) {
        if (e.target && e.target.closest && e.target.closest('form')) {
            document.removeEventListener('focusin', onFirstFocus);
            window.HumanCheck.ensure().catch(() => {});
        }
    }, { passive: true });

    // Handle wire:navigate
    document.addEventListener('livewire:navigated', () => {
        // If container was refreshed, re-init
        const container = document.getElementById('turnstile-gate-widget');
        if (container && (!container.hasChildNodes() || !_widgetId)) {
            _widgetPromise = null;
            _widgetId = null;
            initWidget().catch(() => {});
        }
    });

    // Intercept Livewire 403 HUMAN_CHECK_REQUIRED
    document.addEventListener('livewire:init', () => {
        if (!window.Livewire) return;

        Livewire.hook('request', ({ uri, options, respond }) => {
            if (!respond) return;
            respond(({ status, response }) => {
                if (status === 403) {
                    try {
                        response.clone().json().then(data => {
                            if (data && data.code === 'HUMAN_CHECK_REQUIRED' && !options._humanRetried) {
                                options._humanRetried = true;
                                window.HumanCheck.ensure().then(() => {
                                    fetch(uri, options).then(retryRes => {
                                        if (retryRes.ok) {
                                            window.Livewire.dispatch('$refresh');
                                        }
                                    });
                                }).catch(() => {});
                            }
                        }).catch(() => {});
                    } catch (e) {}
                }
            });
        });
    });

    // Listen for custom dispatch from Livewire
    document.addEventListener('human-check-required', () => {
        window.HumanCheck.ensure().catch(() => {});
    });

    // Fallback for non-Livewire forms
    document.addEventListener('submit', async function (e) {
        const form = e.target.closest ? e.target.closest('form[data-human]') : null;
        if (!form) return;

        const freshInput = form.querySelector('input[name="cf-turnstile-response"]');
        if (freshInput) {
            if (freshInput.value) {
                return;
            }
            e.preventDefault();
            e.stopImmediatePropagation();
            try {
                const token = await window.HumanCheck.fresh();
                freshInput.value = token;
                form.requestSubmit ? form.requestSubmit() : form.submit();
            } catch (err) {}
            return;
        }

        if (window.HumanCheck.isReady()) {
            return;
        }

        e.preventDefault();
        e.stopImmediatePropagation();
        try {
            await window.HumanCheck.ensure();
            form.requestSubmit ? form.requestSubmit() : form.submit();
        } catch (err) {}
    }, true);
})();
</script>
@endonce
