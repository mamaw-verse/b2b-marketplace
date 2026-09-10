<div>
@if (config('marketplace.features.sponsored_content_enabled'))

    {{-- ══ WIDE / SINGLE PICK ════════════════════════════════════════════
         Default variant: one weighted ad rendered as a full-width banner.
    ══════════════════════════════════════════════════════════════════════ --}}
    @if ($orientation === 'wide' && $content && $content->wideMedia)
        <div class="w-full py-4">
            <a
                href="{{ route('sponsored-content.click', $content) }}"
                target="_blank"
                rel="noopener noreferrer"
                class="group block relative rounded-xl overflow-hidden border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] hover:shadow-[var(--hs-shadow-md)] transition-shadow"
                aria-label="{{ __('Sponsored: :title', ['title' => $content->title]) }}"
            >
                <img
                    src="{{ route('media.view', $content->wideMedia) }}"
                    alt="{{ $content->title }}"
                    class="w-full object-cover group-hover:scale-[1.01] transition-transform duration-300"
                    style="aspect-ratio: 4/1;"
                    loading="lazy"
                />
                <div class="absolute top-2 left-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-black/50 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm">
                        <flux:icon name="megaphone" class="size-3" />
                        {{ __('Sponsored') }}
                    </span>
                </div>
                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent px-4 py-3">
                    <p class="text-sm font-semibold text-white truncate">{{ $content->title }}</p>
                </div>
            </a>
        </div>

    {{-- ══ TALL / CAROUSEL ═══════════════════════════════════════════════
         Hero variant: tall-format ad cards in an auto-rotating carousel.
         Cards use 2:3 aspect ratio (portrait). Up to N ads rotate in view.
    ══════════════════════════════════════════════════════════════════════ --}}
    @elseif ($orientation === 'tall' && $carouselItems->isNotEmpty())
        {{-- (tall carousel — unchanged) --}}
        <div
            x-data="{
                current: 0,
                total: {{ $carouselItems->count() }},
                interval: null,
                start() {
                    if (this.total <= 1) return;
                    this.interval = setInterval(() => {
                        this.current = (this.current + 1) % this.total;
                    }, 4000);
                },
                stop() { clearInterval(this.interval); },
                go(idx) { this.current = idx; clearInterval(this.interval); this.start(); }
            }"
            x-init="start()"
            @mouseenter="stop()"
            @mouseleave="start()"
            class="relative overflow-hidden rounded-2xl"
            style="aspect-ratio: 2/3;"
            aria-label="{{ __('Sponsored content carousel') }}"
        >
            @foreach ($carouselItems as $idx => $item)
                @if ($item->tallMedia)
                    <a
                        href="{{ route('sponsored-content.click', $item) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        x-show="current === {{ $idx }}"
                        x-transition:enter="transition ease-out duration-500"
                        x-transition:enter-start="opacity-0 scale-105"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute inset-0 block"
                        aria-label="{{ __('Sponsored: :title', ['title' => $item->title]) }}"
                    >
                        <img
                            src="{{ route('media.view', $item->tallMedia) }}"
                            alt="{{ $item->title }}"
                            class="w-full h-full object-cover"
                            loading="{{ $idx === 0 ? 'eager' : 'lazy' }}"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                        <div class="absolute top-3 left-3">
                            <span class="inline-flex items-center gap-1 rounded-full bg-black/50 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm">
                                <flux:icon name="megaphone" class="size-3" />
                                {{ __('Sponsored') }}
                            </span>
                        </div>
                        <div class="absolute bottom-0 inset-x-0 px-4 py-4">
                            <p class="text-sm font-semibold text-white leading-snug line-clamp-2">{{ $item->title }}</p>
                        </div>
                    </a>
                @endif
            @endforeach
            @if ($carouselItems->count() > 1)
                <div class="absolute bottom-3 inset-x-0 flex justify-center gap-1.5 z-10">
                    @foreach ($carouselItems as $idx => $item)
                        <button
                            type="button"
                            x-on:click="go({{ $idx }})"
                            x-bind:class="current === {{ $idx }} ? 'bg-white w-4' : 'bg-white/40 w-2'"
                            class="h-2 rounded-full transition-all duration-300"
                            aria-label="{{ __('Go to slide :n', ['n' => $idx + 1]) }}"
                        ></button>
                    @endforeach
                </div>
            @endif
        </div>

    {{-- ══ ROW / HORIZONTAL STRIP ════════════════════════════════════════
         Homepage section: always shows 4 portrait cards side-by-side.

         Two modes depending on how many real ads exist:

         A) realCount <= 3 (1–3 ads):
            Static grid — show all real ads + fill remaining slots to 4
            with "Advertise Here" promo cards. No rotation needed.

         B) realCount >= 4:
            Alpine rotating window — offset advances by 1 every 5 s so
            every ad in the pool gets equal airtime. Always 4 visible.

         Zero ads → section stays hidden (isNotEmpty guard below).
    ══════════════════════════════════════════════════════════════════════ --}}
    @elseif ($orientation === 'row' && $carouselItems->isNotEmpty())
        @php
            $adminEmail = config('mail.admin_address', 'admin@example.com');
            $pool       = $carouselItems->filter(fn ($c) => $c->tallMedia)->values();
            $realCount  = $pool->count();
        @endphp

        <div class="w-full py-6 sm:py-8">

            {{-- Section label --}}
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)]">
                    <flux:icon name="megaphone" class="size-3.5" />
                    {{ __('Sponsored') }}
                </span>
            </div>

            @if ($realCount <= 3)

                {{-- ── MODE A: static grid + fillers ──────────────────── --}}
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">

                    {{-- Real ad cards --}}
                    @foreach ($pool as $item)
                        <a href="{{ route('sponsored-content.click', $item) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="group relative overflow-hidden rounded-2xl border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] hover:shadow-[var(--hs-shadow-md)] transition-shadow bg-[var(--hs-neutral-100)]"
                           style="aspect-ratio: 2/3;"
                           aria-label="{{ __('Sponsored: :title', ['title' => $item->title]) }}">
                            <img src="{{ route('media.view', $item->tallMedia) }}"
                                 alt="{{ $item->title }}"
                                 class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-500"
                                 loading="lazy" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                            <div class="absolute top-3 left-3">
                                <span class="inline-flex items-center gap-1 rounded-full bg-black/50 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm">
                                    <flux:icon name="megaphone" class="size-3" />
                                    {{ __('Sponsored') }}
                                </span>
                            </div>
                            <div class="absolute bottom-0 inset-x-0 px-3 py-3">
                                <p class="text-xs font-semibold text-white leading-snug line-clamp-2">{{ $item->title }}</p>
                            </div>
                        </a>
                    @endforeach

                    {{-- Filler cards to pad to 4 --}}
                    @php
                        $fillerPalettes = [
                            // Emerald — brand primary
                            [
                                'bg'      => 'linear-gradient(180deg, var(--hs-green-700) 0%, #0b2c23 55%, #061b16 100%)',
                                'border'  => 'rgba(201,162,39,0.22)',
                                'seal'    => '#9ed4bd',
                                'rosette' => '#9ed4bd',
                                'ledger'  => 'rgba(180,220,204,0.6)',
                                'heading' => '#efe7d6',
                                'accent'  => 'var(--hs-green-300)',
                                'body'    => '#8fa89a',
                                'divider' => 'rgba(158,212,189,0.28)',
                                'metric'  => '#efe7d6',
                                'check'   => 'var(--hs-green-300)',
                                'benefit' => '#c7d4cc',
                                'cta_top' => 'rgba(180,220,204,0.18)',
                                'email'   => 'var(--hs-green-300)',
                            ],
                            // Gold — accent
                            [
                                'bg'      => 'linear-gradient(180deg, #2e2200 0%, #1c1500 55%, #0e0b00 100%)',
                                'border'  => 'rgba(201,162,39,0.28)',
                                'seal'    => '#e6cd77',
                                'rosette' => '#e6cd77',
                                'ledger'  => 'rgba(239,231,214,0.6)',
                                'heading' => '#efe7d6',
                                'accent'  => 'var(--hs-gold-400)',
                                'body'    => '#8fa89a',
                                'divider' => 'rgba(201,162,39,0.28)',
                                'metric'  => '#efe7d6',
                                'check'   => '#c9a227',
                                'benefit' => '#c7d4cc',
                                'cta_top' => 'rgba(239,231,214,0.18)',
                                'email'   => 'var(--hs-gold-400)',
                            ],
                            // Navy
                            [
                                'bg'      => 'linear-gradient(180deg, #0c1e35 0%, #080f1c 55%, #040810 100%)',
                                'border'  => 'rgba(99,179,237,0.22)',
                                'seal'    => '#93c5fd',
                                'rosette' => '#93c5fd',
                                'ledger'  => 'rgba(147,197,253,0.4)',
                                'heading' => '#eff6ff',
                                'accent'  => 'var(--hs-info-200)',
                                'body'    => '#7ab8e8',
                                'divider' => 'rgba(99,179,237,0.28)',
                                'metric'  => '#eff6ff',
                                'check'   => '#60a5fa',
                                'benefit' => '#bfdbfe',
                                'cta_top' => 'rgba(99,179,237,0.18)',
                                'email'   => 'var(--hs-info-200)',
                            ],
                        ];
                    @endphp
                    @for ($f = 0; $f < (4 - $realCount); $f++)
                        @php $fp = $fillerPalettes[$f % 3]; @endphp
                        <a href="mailto:{{ $adminEmail }}?subject={{ rawurlencode('Sponsored Ad Placement Inquiry') }}"
                           class="group relative flex flex-col overflow-hidden transition-all duration-200 hover:-translate-y-0.5"
                           style="aspect-ratio: 2/3;
                                  border-radius: var(--hs-radius-panel);
                                  border: 1px solid {{ $fp['border'] }};
                                  background: {{ $fp['bg'] }};"
                           aria-label="{{ __('Advertise here — contact us about sponsored placement') }}">

                            {{-- Ledger-rule texture --}}
                            <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
                                 style="background-image: repeating-linear-gradient(to bottom, {{ $fp['ledger'] }} 0px, {{ $fp['ledger'] }} 1px, transparent 1px, transparent 27px);
                                        -webkit-mask-image: linear-gradient(to bottom, transparent, black 14%, black 78%, transparent);
                                        mask-image: linear-gradient(to bottom, transparent, black 14%, black 78%, transparent);"></div>

                            {{-- Rosette watermark --}}
                            <svg class="pointer-events-none absolute -bottom-10 -right-10 size-[160px] opacity-[0.06]"
                                 viewBox="0 0 100 100" fill="none" stroke="{{ $fp['rosette'] }}" stroke-width="0.6" aria-hidden="true">
                                <circle cx="50" cy="50" r="46"/>
                                <circle cx="50" cy="50" r="38"/>
                                <path d="M50 4 L58 42 L96 50 L58 58 L50 96 L42 58 L4 50 L42 42 Z"/>
                            </svg>

                            {{-- Certification seal stamp --}}
                            <svg class="absolute left-4 top-4 size-10" viewBox="0 0 52 52" fill="none"
                                 style="color: {{ $fp['seal'] }};" aria-hidden="true">
                                <circle cx="26" cy="26" r="24" stroke="currentColor" stroke-width="1"/>
                                <circle cx="26" cy="26" r="19" stroke="currentColor" stroke-width="1"/>
                                <path d="M26 10 L29.5 22.5 L42 26 L29.5 29.5 L26 42 L22.5 29.5 L10 26 L22.5 22.5 Z"
                                      fill="currentColor" opacity="0.9"/>
                            </svg>

                            {{-- Body content --}}
                            <div class="relative z-10 flex flex-1 flex-col px-5 pt-16">

                                {{-- Headline --}}
                                <p class="m-0 text-[17px] font-semibold leading-tight tracking-tight"
                                   style="font-family: var(--hs-font-display); color: {{ $fp['heading'] }};">
                                    {{ __('Reserved space for a') }}
                                    <span style="color: {{ $fp['accent'] }};">{{ __('trusted brand') }}</span>
                                    {{ __('in the halal trade') }}
                                </p>
                                <p class="mt-1.5 max-w-[26ch] text-[11px] leading-relaxed"
                                   style="color: {{ $fp['body'] }};">
                                    {{ __('One placement, seen by the buyers already sourcing on 1HalalStore.') }}
                                </p>

                                {{-- Ledger metrics row --}}
                                <div class="mt-4 flex"
                                     style="border-top: 1px solid {{ $fp['divider'] }}; border-bottom: 1px solid {{ $fp['divider'] }};">
                                    <div class="flex-1 py-2 text-center"
                                         style="border-right: 1px solid {{ $fp['divider'] }};">
                                        <strong class="block text-sm font-semibold leading-tight"
                                                style="font-family: var(--hs-font-display); color: {{ $fp['metric'] }};">10K+</strong>
                                        <small class="mt-0.5 block text-[9px]" style="color: {{ $fp['body'] }};">{{ __('Monthly views') }}</small>
                                    </div>
                                    <div class="flex-1 py-2 text-center"
                                         style="border-right: 1px solid {{ $fp['divider'] }};">
                                        <strong class="block text-sm font-semibold leading-tight"
                                                style="font-family: var(--hs-font-display); color: {{ $fp['metric'] }};">B2B</strong>
                                        <small class="mt-0.5 block text-[9px]" style="color: {{ $fp['body'] }};">{{ __('Verified buyers') }}</small>
                                    </div>
                                    <div class="flex-1 py-2 text-center">
                                        <strong class="block text-sm font-semibold leading-tight"
                                                style="font-family: var(--hs-font-display); color: {{ $fp['metric'] }};">{{ __('Halal') }}</strong>
                                        <small class="mt-0.5 block text-[9px]" style="color: {{ $fp['body'] }};">{{ __('Certified market') }}</small>
                                    </div>
                                </div>

                                {{-- Benefits --}}
                                <div class="mt-3 flex flex-col gap-1.5 text-left">
                                    @foreach ([
                                        'Screened, halal-verified audience',
                                        'Flexible terms, month to month',
                                        'Front-page storefront visibility',
                                    ] as $benefit)
                                        <div class="flex items-baseline gap-2 text-[11px]"
                                             style="color: {{ $fp['benefit'] }};">
                                            <svg class="size-[11px] shrink-0 translate-y-px" viewBox="0 0 24 24"
                                                 fill="none" stroke="{{ $fp['check'] }}" stroke-width="2.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            {{ __($benefit) }}
                                        </div>
                                    @endforeach
                                </div>

                            </div>

                            {{-- Signature-style contact footer --}}
                            <div class="relative z-10 mt-auto px-5 pb-4 pt-3"
                                 style="border-top: 1px dashed {{ $fp['cta_top'] }};">
                                <p class="m-0 text-[9px] tracking-wide" style="color: {{ $fp['body'] }};">{{ __('Inquire about this slot') }}</p>
                                <strong class="text-[13px] font-normal italic"
                                        style="font-family: var(--hs-font-display); color: {{ $fp['email'] }};">{{ $adminEmail }}</strong>
                            </div>

                        </a>
                    @endfor

                </div>

            @else

                {{-- ── MODE B: Alpine rotating window of 4 ─────────────── --}}
                <div
                    x-data="{
                        offset: 0,
                        paused: false,
                        timer: null,
                        items: {{ Js::from($pool->map(fn ($c) => [
                            'href'  => route('sponsored-content.click', $c),
                            'src'   => route('media.view', $c->tallMedia),
                            'alt'   => $c->title,
                            'title' => $c->title,
                        ])->values()->all()) }},
                        get total() { return this.items.length; },
                        item(i) { return this.items[(this.offset + i) % this.total]; },
                        advance() { this.offset = (this.offset + 1) % this.total; },
                        start() {
                            this.stop();
                            this.timer = setInterval(() => { if (!this.paused) this.advance(); }, 5000);
                        },
                        stop() { clearInterval(this.timer); this.timer = null; }
                    }"
                    x-init="start()"
                    @mouseenter="paused = true"
                    @mouseleave="paused = false"
                    class="grid grid-cols-2 gap-3 sm:grid-cols-4"
                    aria-label="{{ __('Sponsored content') }}"
                >
                    <template x-for="i in [0, 1, 2, 3]" :key="i">
                        <a
                            :href="item(i).href"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group relative overflow-hidden rounded-2xl border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] hover:shadow-[var(--hs-shadow-md)] transition-shadow bg-[var(--hs-neutral-100)]"
                            style="aspect-ratio: 2/3;"
                            :aria-label="item(i).title"
                        >
                            <img
                                :src="item(i).src"
                                :alt="item(i).alt"
                                class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-500"
                                loading="lazy"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                            <div class="absolute top-3 left-3">
                                <span class="inline-flex items-center gap-1 rounded-full bg-black/50 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3" aria-hidden="true"><path d="M13.92 3.845a19.36 19.36 0 0 1-6.3 1.98C6.765 5.942 5.89 6 5 6a4 4 0 0 0-.504 7.969l.39 2.652A1 1 0 0 0 5.875 17.5h.745a1 1 0 0 0 .921-.608l.437-1.04a19.27 19.27 0 0 1 5.943 1.993c.517.29 1.079-.162 1.079-.75V4.595c0-.587-.562-1.04-1.079-.75Z" /></svg>
                                    Sponsored
                                </span>
                            </div>
                            <div class="absolute bottom-0 inset-x-0 px-3 py-3">
                                <p class="text-xs font-semibold text-white leading-snug line-clamp-2" x-text="item(i).title"></p>
                            </div>
                        </a>
                    </template>
                </div>

            @endif

        </div>
    @endif

@endif
</div>
