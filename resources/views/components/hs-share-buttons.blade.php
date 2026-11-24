@props([
    'url',
    'title' => '',
    'text' => '',
    'shareCount' => null,
])

@if (config('marketplace.features.product_share_enabled'))
    @php
        $btnClass = 'size-11 inline-flex items-center justify-center rounded-[var(--hs-radius-md)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-900)] hover:bg-[var(--hs-neutral-50)] hover:border-[var(--hs-neutral-300)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--hs-green-600)] transition shrink-0';
        $publicMin = (int) config('marketplace.share.public_min', 5);
        $encodedUrl = urlencode($url);
        $encodedText = urlencode($text);
    @endphp

    <div
        {{ $attributes->merge(['class' => 'flex items-center flex-wrap gap-2']) }}
        x-data="{
            url: @js($url),
            title: @js($title),
            text: @js($text),
            copied: false,
            copyTimer: null,
            isTouch: false,
            canShare: false,
            init() {
                this.isTouch = window.matchMedia('(pointer: coarse)').matches;
                this.canShare = typeof navigator.share === 'function';
            },
            copy() {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(this.url).then(() => {
                        this.onCopied();
                    }).catch(() => {
                        this.fallbackCopy();
                    });
                } else {
                    this.fallbackCopy();
                }
            },
            fallbackCopy() {
                try {
                    const el = document.createElement('textarea');
                    el.value = this.url;
                    el.setAttribute('readonly', '');
                    el.style.position = 'fixed';
                    el.style.left = '-9999px';
                    document.body.appendChild(el);
                    el.select();
                    const successful = document.execCommand('copy');
                    document.body.removeChild(el);
                    if (successful) {
                        this.onCopied();
                    }
                } catch (e) {
                    // Clipboard fallback failure
                }
            },
            onCopied() {
                this.copied = true;
                try {
                    const wire = typeof $wire !== 'undefined' ? $wire : this.$wire;
                    wire?.trackShare('copy');
                } catch (e) {}
                clearTimeout(this.copyTimer);
                this.copyTimer = setTimeout(() => {
                    this.copied = false;
                }, 2000);
            },
            shareNative() {
                if (typeof navigator.share !== 'function') return;
                navigator.share({
                    title: this.title,
                    text: this.text,
                    url: this.url
                }).then(() => {
                    try {
                        const wire = typeof $wire !== 'undefined' ? $wire : this.$wire;
                        wire?.trackShare('native');
                    } catch (e) {}
                }).catch((err) => {
                    if (err && err.name === 'AbortError') return;
                });
            }
        }"
        role="group"
        aria-label="{{ __('Share this product') }}"
    >
        <span class="text-xs font-semibold text-[var(--hs-neutral-500)] uppercase tracking-wider me-1">
            {{ __('Share') }}:
        </span>

        {{-- Copy link --}}
        <button
            type="button"
            @click="copy()"
            class="{{ $btnClass }}"
            aria-label="{{ __('Copy link') }}"
            title="{{ __('Copy link') }}"
        >
            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
            </svg>
        </button>

        {{-- Facebook --}}
        <a
            href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            @click="(typeof $wire !== 'undefined' ? $wire : this.$wire)?.trackShare('facebook')"
            class="{{ $btnClass }}"
            aria-label="{{ __('Share on Facebook') }}"
            title="{{ __('Share on Facebook') }}"
        >
            <svg class="size-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
            </svg>
        </a>

        {{-- X --}}
        <a
            href="https://x.com/intent/post?url={{ $encodedUrl }}&amp;text={{ $encodedText }}"
            target="_blank"
            rel="noopener noreferrer"
            @click="(typeof $wire !== 'undefined' ? $wire : this.$wire)?.trackShare('x')"
            class="{{ $btnClass }}"
            aria-label="{{ __('Share on X') }}"
            title="{{ __('Share on X') }}"
        >
            <svg class="size-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
            </svg>
        </a>

        {{-- Messenger (touch devices only) --}}
        <a
            href="fb-messenger://share/?link={{ $encodedUrl }}"
            @click="(typeof $wire !== 'undefined' ? $wire : this.$wire)?.trackShare('messenger')"
            x-show="isTouch"
            x-cloak
            class="{{ $btnClass }}"
            aria-label="{{ __('Share on Messenger') }}"
            title="{{ __('Share on Messenger') }}"
        >
            <svg class="size-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.91 1.455 5.512 3.737 7.21V22l3.39-1.86c.905.251 1.868.388 2.873.388 5.523 0 10-4.145 10-9.258C22 6.145 17.523 2 12 2zm1.066 12.443l-2.73-2.91-5.326 2.91 5.856-6.218 2.796 2.91 5.26-2.91-5.856 6.218z" />
            </svg>
        </a>

        {{-- Instagram (native share sheet) --}}
        <button
            type="button"
            @click="shareNative()"
            x-show="canShare"
            x-cloak
            class="{{ $btnClass }}"
            aria-label="{{ __('Share on Instagram') }}"
            title="{{ __('Share via your phone\'s share sheet') }}"
        >
            <svg class="size-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" />
            </svg>
        </button>

        {{-- "Link copied" feedback --}}
        <span
            x-show="copied"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="inline-flex items-center text-xs font-medium text-[var(--hs-green-700)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] px-2.5 py-1 rounded-[var(--hs-radius-pill)]"
            aria-live="polite"
            role="status"
        >
            {{ __('Link copied') }}
        </span>

        {{-- Public share count --}}
        @if ($shareCount !== null && (int) $shareCount >= $publicMin)
            <span class="text-xs font-medium text-[var(--hs-neutral-500)] ms-1 whitespace-nowrap">
                {{ trans_choice(':count share|:count shares', (int) $shareCount, ['count' => number_format((int) $shareCount)]) }}
            </span>
        @endif
    </div>
@endif
