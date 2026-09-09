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
         Homepage section: 4–6 portrait cards side-by-side.
         The visible window advances by one card every 5 s, wrapping around
         the full pool so every ad gets equal airtime.
    ══════════════════════════════════════════════════════════════════════ --}}
    @elseif ($orientation === 'row' && $carouselItems->isNotEmpty())
        @php
            // Repeat the pool so the sliding window always has enough cards
            // even when there are fewer ads than the visible count (min 6 slots).
            $pool     = $carouselItems->filter(fn ($c) => $c->tallMedia)->values();
            $poolSize = $pool->count();
        @endphp
        @if ($poolSize > 0)
        <div class="w-full py-6 sm:py-8">
            {{-- Section label --}}
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)]">
                    <flux:icon name="megaphone" class="size-3.5" />
                    {{ __('Sponsored') }}
                </span>
            </div>

            {{--
                Alpine state:
                  offset   — index of the leftmost visible card (in the REPEATED pool)
                  visible  — how many cards fit (6 lg, 4 md, 2 sm — driven by CSS, JS reads it)
                  items    — the server-rendered pool as a JS array of {href, src, alt, title}
            --}}
            <div
                x-data="{
                    offset: 0,
                    interval: null,
                    paused: false,
                    cols: 2,
                    items: {{ Js::from($pool->map(fn ($c) => [
                        'href'  => route('sponsored-content.click', $c),
                        'src'   => route('media.view', $c->tallMedia),
                        'alt'   => $c->title,
                        'title' => $c->title,
                    ])->values()->all()) }},
                    get total() { return this.items.length; },
                    updateCols() {
                        const w = window.innerWidth;
                        if (w >= 1024)     this.cols = 6;
                        else if (w >= 640) this.cols = 4;
                        else               this.cols = 2;
                    },
                    item(i) {
                        return this.items[(this.offset + i) % this.total];
                    },
                    advance() {
                        this.offset = (this.offset + 1) % this.total;
                    },
                    start() {
                        if (this.total <= 1) return;
                        this.interval = setInterval(() => { if (!this.paused) this.advance(); }, 5000);
                    },
                    stop() { clearInterval(this.interval); }
                }"
                x-init="
                    updateCols();
                    window.addEventListener('resize', () => updateCols());
                    start();
                "
                @mouseenter="paused = true"
                @mouseleave="paused = false"
                class="grid gap-3"
                x-bind:style="'grid-template-columns: repeat(' + cols + ', minmax(0,1fr));'"
                aria-label="{{ __('Sponsored content') }}"
            >
                {{--
                    We always render visibleCount() slots. Since visibleCount() is
                    determined at runtime in Alpine we render up to 6 items and hide
                    extras via CSS. Alpine swaps src/href as offset changes.
                --}}
                <template x-for="i in [0,1,2,3,4,5]" :key="i">
                    <a
                        x-show="i < cols && total > 0"
                        x-bind:href="total > 0 ? item(i).href : '#'"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group relative overflow-hidden rounded-2xl border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] hover:shadow-[var(--hs-shadow-md)] transition-shadow bg-[var(--hs-neutral-100)]"
                        style="aspect-ratio: 2/3;"
                        x-bind:aria-label="total > 0 ? item(i).title : ''"
                    >
                        <img
                            x-bind:src="total > 0 ? item(i).src : ''"
                            x-bind:alt="total > 0 ? item(i).alt : ''"
                            class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-500"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                        <div class="absolute bottom-0 inset-x-0 px-3 py-3">
                            <p class="text-xs font-semibold text-white leading-snug line-clamp-2"
                               x-text="total > 0 ? item(i).title : ''"></p>
                        </div>
                    </a>
                </template>
            </div>
        </div>
        @endif
    @endif

@endif
</div>
