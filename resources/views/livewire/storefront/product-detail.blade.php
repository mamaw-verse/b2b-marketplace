@php
    $ogTitle       = $product->name . ($product->shop ? ' — ' . $product->shop->storefront_name : '');
    $ogDescription = Str::limit(strip_tags($product->description ?? ''), 160);
    $ogImage       = $product->featured_image_path
        ? route('products.featured-image', $product)
        : asset('images/brand-logo.png');
    $ogUrl         = route('storefront.product', $product);
@endphp

@push('meta')
    <link rel="canonical" href="{{ $ogUrl }}">
    {{-- Primary --}}
    <meta name="description" content="{{ $ogDescription }}">

    {{-- Open Graph --}}
    <meta property="og:type"        content="product">
    <meta property="og:url"         content="{{ $ogUrl }}">
    <meta property="og:title"       content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:image"       content="{{ $ogImage }}">
    <meta property="og:image:alt"   content="{{ $product->name }}">
    <meta property="og:site_name"   content="{{ config('app.name') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image"       content="{{ $ogImage }}">
    <meta name="twitter:image:alt"   content="{{ $product->name }}">
@endpush

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    {{-- Breadcrumb — includes category level when available --}}
    @php
        $breadcrumbCategory = $product->categories
            ->filter(fn ($c) => $c->parent !== null)
            ->first();
        $breadcrumbParent = $breadcrumbCategory?->parent;
    @endphp
    <nav class="mb-6 flex items-center gap-2 text-sm text-[var(--hs-neutral-500)]" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('storefront.products') }}" class="hover:text-[var(--hs-green-700)] transition shrink-0" wire:navigate>{{ __('Products') }}</a>
        @if ($breadcrumbParent)
            <flux:icon name="chevron-right" variant="mini" class="size-4 text-[var(--hs-neutral-300)] shrink-0" aria-hidden="true" />
            <a href="{{ route('storefront.products', ['category' => $breadcrumbParent->id]) }}" class="hover:text-[var(--hs-green-700)] transition shrink-0" wire:navigate>{{ $breadcrumbParent->name }}</a>
        @endif
        @if ($breadcrumbCategory)
            <flux:icon name="chevron-right" variant="mini" class="size-4 text-[var(--hs-neutral-300)] shrink-0" aria-hidden="true" />
            <a href="{{ route('storefront.products', ['category' => $breadcrumbCategory->id]) }}" class="hover:text-[var(--hs-green-700)] transition truncate min-w-0" wire:navigate>{{ $breadcrumbCategory->name }}</a>
        @endif
        <flux:icon name="chevron-right" variant="mini" class="size-4 text-[var(--hs-neutral-300)] shrink-0" aria-hidden="true" />
        <span class="text-[var(--hs-neutral-700)] font-medium truncate min-w-0" aria-current="page">{{ $product->name }}</span>
    </nav>

    @php
        // Build attribute groups across all variants
        $attributeGroups = [];
        foreach ($product->variants as $variant) {
            foreach ((array) ($variant->attributes ?? []) as $key => $value) {
                $attributeGroups[$key][$value] = true;
            }
        }
        foreach ($attributeGroups as $key => $values) {
            $attributeGroups[$key] = array_keys($values);
        }

        // Flatten variants into a JS-friendly array
        $variantsForJs = $product->variants->map(function ($variant) {
            return [
                'sku' => $variant->sku,
                'attributes' => (object) ($variant->attributes ?? []),
                'price' => (float) $variant->price,
                'currency' => $variant->currency,
                'moq' => (int) $variant->minimum_order_quantity,
                'unit' => $variant->unit,
                'quantityBreaks' => collect($variant->quantity_breaks ?? [])
                    ->map(fn ($b) => ['minQty' => (int) $b['min_qty'], 'price' => (float) ($b['price'] ?? $variant->price)])
                    ->values(),
            ];
        })->values();

        // Keep the product video first, followed by the featured and gallery images.
        // Each entry is an object { type, src, alt } for the Alpine imageGallery component.
        $galleryImages = collect();
        $productName = $product->name;
        $galleryIdx = 0;
        if (config('marketplace.features.product_video_enabled') && $product->video?->scan_status === 'clean') {
            $galleryImages->push([
                'type' => 'video',
                'src' => route('media.view', $product->video),
                'poster' => $product->featured_image_url,
                'mimeType' => $product->video->mime_type,
                'alt' => $productName . ' — ' . __('Product video'),
            ]);
        }
        if ($product->featured_image_path) {
            $galleryImages->push(['type' => 'image', 'src' => $product->featured_image_url, 'alt' => $productName]);
            $galleryIdx++;
        }
        if ($product->gallery->count() > 0) {
            $featured = $product->featured_image_url ?? null;
            $product->gallery->each(function ($img) use ($galleryImages, $featured, $productName, &$galleryIdx) {
                if ($img->image_url !== $featured) {
                    $galleryIdx++;
                    $galleryImages->push(['type' => 'image', 'src' => $img->image_url, 'alt' => $productName . ' — image ' . $galleryIdx]);
                }
            });
        }
        $galleryImages = $galleryImages->values();

        // RFQ CTA
        $ctaHref = auth()->check()
            ? route('account.rfqs.create', [
                'shop_id' => $product->shop?->id,
                'product_id' => $product->id,
                'title' => $product->name,
            ])
            : route('login');
        $ctaLabel = auth()->check() ? __('Request Quote') : __('Log in to Request Quote');
        $ctaAttrs = auth()->check() ? 'wire:navigate' : '';

        // Star rating helper
        $ratingStars = collect(range(1, 5))->map(function ($i) use ($product) {
            $rating = (float) $product->average_rating;
            return $rating >= $i ? 'full' : ($rating >= $i - 0.5 ? 'half' : 'empty');
        });
    @endphp

    <div
        class="grid grid-cols-1 gap-6 lg:gap-10 lg:grid-cols-2 lg:items-start"
        @if ($product->variants->count() > 0)
            x-data="{
                variants: {{ \Illuminate\Support\Js::from($variantsForJs) }},
                selected: {},
                init() {
                    this.selected = { ...this.variants[0].attributes };
                },
                get currentVariant() {
                    return this.variants.find(v =>
                        Object.keys(this.selected).every(k => v.attributes[k] === this.selected[k])
                    ) || this.variants[0];
                },
                isAvailable(key, value) {
                    const test = { ...this.selected, [key]: value };
                    return this.variants.some(v =>
                        Object.keys(test).every(k => v.attributes[k] === test[k])
                    );
                },
                get priceTiers() {
                    const v = this.currentVariant;
                    const tiers = [{ minQty: v.moq, price: v.price }, ...v.quantityBreaks];
                    return tiers.sort((a, b) => a.minQty - b.minQty);
                },
                get basePrice() {
                    return this.priceTiers[0]?.price ?? 0;
                },
                discountFor(price) {
                    if (this.basePrice <= 0 || price >= this.basePrice) return null;
                    return Math.round(100 - (price / this.basePrice) * 100);
                },
                get bestTierIndex() {
                    let best = -1;
                    let bestDiscount = 0;
                    this.priceTiers.forEach((t, i) => {
                        const d = this.discountFor(t.price);
                        if (d && d > bestDiscount) { bestDiscount = d; best = i; }
                    });
                    return best;
                }
            }"
        @endif
    >
        {{-- ============ GALLERY (sticky) ============ --}}
        <div class="lg:sticky lg:top-6 space-y-3"
             x-data="imageGallery({{ \Illuminate\Support\Js::from($galleryImages) }}, '{{ $product->name }}')"
        >
            @if ($galleryImages->count() > 0)
                {{-- Main image with hover-zoom --}}
                <div class="rounded-[var(--hs-radius-card)] overflow-hidden border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)] p-3">
                    <div
                        class="relative rounded-[var(--hs-radius-md)] overflow-hidden bg-[var(--hs-neutral-50)] group aspect-square select-none"
                        @mousemove.passive="onMouseMove($event)"
                        @mouseleave="onMouseLeave()"
                        @click="if (images[active].type !== 'video') openLightbox(active)"
                        :role="images[active].type === 'video' ? null : 'button'"
                        :tabindex="images[active].type === 'video' ? -1 : 0"
                        @keydown.enter.prevent="if (images[active].type !== 'video') openLightbox(active)"
                        @keydown.space.prevent="if (images[active].type !== 'video') openLightbox(active)"
                        :class="images[active].type === 'video' ? 'cursor-default' : 'cursor-zoom-in'"
                        :aria-label="images[active].type === 'video' ? images[active].alt : '{{ __('Enlarge image') }}: ' + images[active].alt"
                    >
                        <img
                            x-show="images[active].type !== 'video'"
                            x-ref="mainImg"
                            :src="images[active].type === 'image' ? images[active].src : ''"
                            :alt="images[active].alt"
                            x-effect="$el.style.opacity = '0'; requestAnimationFrame(() => { $el.style.opacity = '1'; })"
                            class="w-full h-full object-contain"
                            style="will-change: transform; transition: opacity 150ms ease-out, transform 200ms ease-out;"
                            :style="zooming
                                ? `transform: scale(2.2); transform-origin: ${originX}% ${originY}%; transition: opacity 150ms ease-out;`
                                : 'transform: scale(1);'"
                            draggable="false"
                        />
                        <template x-if="images[active].type === 'video'">
                            <video controls autoplay muted preload="metadata" playsinline class="absolute inset-0 size-full object-contain bg-black" :poster="images[active].poster || undefined" :aria-label="images[active].alt" @click.stop>
                                <source :src="images[active].src" :type="images[active].mimeType">
                                {{ __('Your browser does not support video playback.') }}
                            </video>
                        </template>

                        {{-- Hover hint — fades after first interaction --}}
                        <div
                            class="absolute bottom-2 left-1/2 -translate-x-1/2 px-2.5 py-1 rounded-[var(--hs-radius-pill)] bg-black/50 text-white text-[11px] font-medium pointer-events-none transition-opacity duration-300"
                            x-show="showHint && images[active].type !== 'video'"
                            x-transition:leave="transition ease-in duration-500"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            aria-hidden="true"
                        >{{ __('Hover to zoom · Click to enlarge') }}</div>

                        {{-- Expand button (always-visible affordance) --}}
                        <button
                            type="button"
                            class="absolute bottom-2 end-2 size-10 flex items-center justify-center rounded-[var(--hs-radius-md)] bg-black/40 text-white hover:bg-black/60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--hs-green-600)] opacity-0 group-hover:opacity-100 focus-visible:opacity-100 transition-opacity"
                            x-show="images[active].type !== 'video'"
                            @click.stop="openLightbox(active)"
                            aria-label="{{ __('Enlarge image') }}"
                        >
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                            </svg>
                        </button>
                    </div>

                    {{-- Thumbnails --}}
                    @if ($galleryImages->count() > 1)
                        <div
                            class="flex gap-2 mt-3 overflow-x-auto pb-1"
                            role="list"
                            aria-label="{{ __('Product images') }}"
                        >
                            <template x-for="(img, i) in images" :key="i">
                                <button
                                    type="button"
                                    role="listitem"
                                    @click="active = i; showHint = false;"
                                    @keydown.arrow-right.prevent="active = Math.min(active + 1, images.length - 1)"
                                    @keydown.arrow-left.prevent="active = Math.max(active - 1, 0)"
                                    class="relative size-20 shrink-0 rounded-[var(--hs-radius-sm)] overflow-hidden border-2 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[var(--hs-green-600)]"
                                    :class="active === i ? 'border-[var(--hs-green-600)] ring-1 ring-[var(--hs-green-600)]' : 'border-[var(--hs-neutral-200)] hover:border-[var(--hs-neutral-300)]'"
                                    :aria-label="(img.type === 'video' ? '{{ __('Play product video') }}' : '{{ __('View image') }}') + ' ' + (i + 1)"
                                    :aria-current="active === i ? 'true' : 'false'"
                                >
                                    <template x-if="img.type === 'video'">
                                        <div class="size-full flex items-center justify-center bg-black text-white" aria-hidden="true">
                                            <flux:icon name="play" variant="mini" class="size-7" />
                                        </div>
                                    </template>
                                    <img x-show="img.type !== 'video'" :src="img.type === 'image' ? img.src : ''" :alt="img.alt" class="size-full object-contain" loading="lazy" />
                                </button>
                            </template>
                        </div>
                    @endif
                </div>

                {{-- Trust/certification strip --}}
                @if ($product->shop && $product->shop->certificates?->isNotEmpty())
                    @php
                        $activeCert = $product->shop->certificates->first(fn ($c) => $c->status === 'active');
                    @endphp
                    @if ($activeCert)
                        <a
                            href="{{ route('storefront.company', $product->shop) }}"
                            wire:navigate
                            class="flex items-center gap-2 rounded-[var(--hs-radius-sm)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] px-3.5 py-2.5 hover:bg-[var(--hs-green-100)] transition group"
                        >
                            <flux:icon name="check-badge" variant="mini" class="size-4 text-[var(--hs-green-700)] shrink-0" aria-hidden="true" />
                            <p class="text-xs font-medium text-[var(--hs-green-800)] flex-1">{{ __('Halal chain of custody verified — certified supplier') }}</p>
                            <flux:icon name="arrow-right" variant="mini" class="size-3.5 text-[var(--hs-green-700)] opacity-0 group-hover:opacity-100 transition-opacity" aria-hidden="true" />
                        </a>
                    @else
                        <div class="flex items-center gap-2 rounded-[var(--hs-radius-sm)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] px-3.5 py-2.5">
                            <flux:icon name="check-badge" variant="mini" class="size-4 text-[var(--hs-green-700)] shrink-0" aria-hidden="true" />
                            <p class="text-xs font-medium text-[var(--hs-green-800)]">{{ __('Halal chain of custody verified — certified supplier') }}</p>
                        </div>
                    @endif
                @else
                    <div class="flex items-center gap-2 rounded-[var(--hs-radius-sm)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] px-3.5 py-2.5">
                        <flux:icon name="check-badge" variant="mini" class="size-4 text-[var(--hs-green-700)] shrink-0" aria-hidden="true" />
                        <p class="text-xs font-medium text-[var(--hs-green-800)]">{{ __('Halal chain of custody verified — certified supplier') }}</p>
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center gap-2 rounded-[var(--hs-radius-card)] bg-[var(--hs-neutral-50)] aspect-square border border-[var(--hs-neutral-200)]">
                    <flux:icon name="photo" class="size-16 text-[var(--hs-neutral-300)]" aria-hidden="true" />
                    <p class="text-sm text-[var(--hs-neutral-400)]">{{ __('No images available') }}</p>
                </div>
            @endif

            {{-- ============ LIGHTBOX (teleported to body) ============ --}}
            <template x-teleport="body">
                <div
                    x-show="lightboxOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-50 flex flex-col bg-black/90 backdrop-blur-sm"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="'{{ __('Image lightbox') }}: ' + (images[lightboxIndex] ? images[lightboxIndex].alt : '')"
                    @keydown.escape.window="if (lightboxOpen) closeLightbox()"
                    @keydown.arrow-left.window="if (lightboxOpen) lightboxPrev()"
                    @keydown.arrow-right.window="if (lightboxOpen) lightboxNext()"
                    x-trap="lightboxOpen"
                >
                    {{-- Top bar: counter + close --}}
                    <div class="flex items-center justify-between px-4 py-3 flex-shrink-0">
                        <span class="text-white/70 text-sm tabular-nums" aria-live="polite" aria-atomic="true">
                            <span x-text="lightboxIndex + 1"></span> / <span x-text="images.length"></span>
                        </span>
                        <button
                            type="button"
                            class="size-10 flex items-center justify-center rounded-full text-white/70 hover:text-white hover:bg-white/10 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-white"
                            @click="closeLightbox()"
                            :aria-label="'{{ __('Close lightbox') }}'"
                        >
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Main image area --}}
                    <div
                        class="flex-1 flex items-center justify-center relative overflow-hidden"
                        @click.self="closeLightbox()"
                        @wheel.passive="onLightboxWheel($event)"
                    >
                        {{-- Prev arrow --}}
                        <button
                            type="button"
                            x-show="images.length > 1"
                            class="absolute start-3 z-10 size-10 flex items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-white transition"
                            @click="lightboxPrev()"
                            aria-label="{{ __('Previous image') }}"
                        >
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                            </svg>
                        </button>

                        {{-- Image container with pan & zoom --}}
                        <div
                            class="w-full h-full flex items-center justify-center cursor-zoom-in"
                            :class="lbZoom > 1 ? 'cursor-grab active:cursor-grabbing' : 'cursor-zoom-in'"
                            @click="lbZoom <= 1 ? lbZoomIn() : lbZoomReset()"
                            @dblclick.prevent="lbZoom > 1 ? lbZoomReset() : lbZoomIn()"
                            @mousedown="onLbDragStart($event)"
                            @mousemove.window="onLbDragMove($event)"
                            @mouseup.window="onLbDragEnd()"
                            @touchstart.passive="onLbTouchStart($event)"
                            @touchmove.passive="onLbTouchMove($event)"
                            @touchend.passive="onLbTouchEnd($event)"
                        >
                            <template x-for="(img, i) in images" :key="i">
                                    <img
                                    x-show="lightboxIndex === i && img.type !== 'video'"
                                    :src="img.type === 'image' ? img.src : ''"
                                    :alt="img.alt"
                                    class="max-w-full max-h-full object-contain select-none"
                                    :style="`transform: scale(${lbZoom}) translate(${lbPanX / lbZoom}px, ${lbPanY / lbZoom}px); transition: ${lbDragging ? 'none' : 'transform 200ms ease-out'};`"
                                    draggable="false"
                                />
                            </template>
                            <template x-if="images[lightboxIndex]?.type === 'video'">
                                <video controls autoplay playsinline class="max-w-full max-h-full" :poster="images[lightboxIndex].poster || undefined" @click.stop>
                                    <source :src="images[lightboxIndex].src" :type="images[lightboxIndex].mimeType">
                                    {{ __('Your browser does not support video playback.') }}
                                </video>
                            </template>
                        </div>

                        {{-- Next arrow --}}
                        <button
                            type="button"
                            x-show="images.length > 1"
                            class="absolute end-3 z-10 size-10 flex items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-white transition"
                            @click="lightboxNext()"
                            aria-label="{{ __('Next image') }}"
                        >
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>

                    {{-- Zoom controls + thumbnail strip --}}
                    <div class="flex-shrink-0 flex flex-col items-center gap-3 px-4 py-3">
                        {{-- Zoom +/- reset --}}
                        <div class="flex items-center gap-1" role="group" aria-label="{{ __('Zoom controls') }}">
                            <button
                                type="button"
                                class="size-8 flex items-center justify-center rounded-[var(--hs-radius-sm)] bg-white/10 text-white/70 hover:text-white hover:bg-white/20 transition disabled:opacity-30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-white text-lg leading-none"
                                @click="lbZoomOut()"
                                :disabled="lbZoom <= 1"
                                aria-label="{{ __('Zoom out') }}"
                            >−</button>
                            <button
                                type="button"
                                class="px-3 h-8 text-xs text-white/70 hover:text-white transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-white"
                                @click="lbZoomReset()"
                                aria-label="{{ __('Reset zoom') }}"
                                x-text="Math.round(lbZoom * 100) + '%'"
                            ></button>
                            <button
                                type="button"
                                class="size-8 flex items-center justify-center rounded-[var(--hs-radius-sm)] bg-white/10 text-white/70 hover:text-white hover:bg-white/20 transition disabled:opacity-30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-white text-lg leading-none"
                                @click="lbZoomIn()"
                                :disabled="lbZoom >= 4"
                                aria-label="{{ __('Zoom in') }}"
                            >+</button>
                        </div>

                        {{-- Thumbnail strip --}}
                        @if ($galleryImages->count() > 1)
                            <div
                                class="flex gap-2 overflow-x-auto pb-1 max-w-full scroll-smooth"
                                style="-ms-overflow-style: none; scrollbar-width: none;"
                                role="list"
                                aria-label="{{ __('Image thumbnails') }}"
                            >
                                <template x-for="(img, i) in images" :key="i">
                                    <button
                                        type="button"
                                        role="listitem"
                                        @click="lightboxIndex = i; lbZoomReset();"
                                        class="relative shrink-0 size-20 rounded-[var(--hs-radius-sm)] overflow-hidden border-2 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-white"
                                        :class="lightboxIndex === i ? 'border-white ring-1 ring-white' : 'border-white/20 hover:border-white/50'"
                                        :aria-label="'{{ __('View image') }} ' + (i + 1)"
                                        :aria-current="lightboxIndex === i ? 'true' : 'false'"
                                    >
                                        <template x-if="img.type === 'video'">
                                            <div class="size-full flex items-center justify-center bg-black text-white" aria-hidden="true">
                                                <flux:icon name="play" variant="mini" class="size-7" />
                                            </div>
                                        </template>
                                        <img x-show="img.type !== 'video'" :src="img.type === 'image' ? img.src : ''" :alt="img.alt" class="size-full object-contain" loading="lazy" />
                                    </button>
                                </template>
                            </div>
                        @endif
                    </div>
                </div>
            </template>
        </div>

        {{-- ============ INFO COLUMN ============ --}}
        <div class="space-y-6">
            {{-- Badges --}}
            @php
                // Filter out pure "marketing/matching" categories — only show leaf categories with a parent
                $visibleCategories = $product->categories
                    ->filter(fn ($c) => $c->parent !== null)
                    ->values();
                $primaryCategory = $visibleCategories->first();
                $overflowCategories = $visibleCategories->slice(1);

                // Halal cert for the trust badge popover
                $shopCert = $product->shop?->certificates?->first(fn ($c) => $c->status === 'active');
            @endphp

            {{-- ── Area A: badge row ── --}}
            {{--
                Layout: [🛡 Halal Certified] [Foods › Oils, Sugar & Sweeteners] [+N]
                - Trust badge: green pill with shield-check, always first
                - Category: white/border chip, shows parent › child, max 1 visible
                - Overflow: +N chip if more categories
                - Mobile: single scrollable row (overflow-x-auto), no wrapping
            --}}
            <div
                class="flex items-center gap-2 pb-3 mb-1 overflow-x-auto"
                style="-ms-overflow-style: none; scrollbar-width: none;"
                x-data="{ certPopoverOpen: false, overflowOpen: false }"
                aria-label="{{ __('Product badges') }}"
            >
                {{-- Halal trust badge (button → popover with cert details) --}}
                @php
                    $hasCertDetails = $shopCert !== null;
                @endphp
                <div class="relative shrink-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 h-8 px-3 rounded-[var(--hs-radius-pill)] bg-[var(--hs-green-600)] text-white text-xs font-semibold whitespace-nowrap focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--hs-green-600)] transition hover:bg-[var(--hs-green-700)]"
                        @if ($hasCertDetails)
                            @click="certPopoverOpen = !certPopoverOpen"
                            @click.outside="certPopoverOpen = false"
                            :aria-expanded="certPopoverOpen"
                            aria-haspopup="true"
                        @endif
                        aria-label="{{ __('Halal Certified') }}{{ $hasCertDetails ? ' — ' . __('click to view certificate details') : '' }}"
                    >
                        <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                        {{ __('Halal Certified') }}
                        @if ($hasCertDetails)
                            <svg class="size-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        @endif
                    </button>

                    {{-- Certificate popover --}}
                    @if ($hasCertDetails)
                        <div
                            x-show="certPopoverOpen"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                            class="absolute top-full start-0 mt-2 z-20 w-72 rounded-[var(--hs-radius-card)] bg-[var(--hs-neutral-0)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-lg)] p-4"
                            @keydown.escape.window="certPopoverOpen = false"
                            role="tooltip"
                        >
                            <div class="flex items-start gap-3">
                                <div class="size-9 rounded-[var(--hs-radius-md)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] flex items-center justify-center shrink-0">
                                    <svg class="size-5 text-[var(--hs-green-700)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-[var(--hs-neutral-900)]">{{ __('Halal Certificate') }}</p>
                                    @if ($shopCert->certification_body)
                                        <p class="mt-1 text-xs text-[var(--hs-neutral-600)]">
                                            <span class="font-medium">{{ __('Body:') }}</span>
                                            {{ ucwords(str_replace('_', ' ', $shopCert->certification_body)) }}{{ $shopCert->certification_body_other ? ' (' . $shopCert->certification_body_other . ')' : '' }}
                                        </p>
                                    @endif
                                    @if ($shopCert->registration_number)
                                        <p class="mt-0.5 text-xs text-[var(--hs-neutral-600)]">
                                            <span class="font-medium">{{ __('No.:') }}</span>
                                            {{ $shopCert->registration_number }}
                                        </p>
                                    @endif
                                    @if ($shopCert->expiry_date)
                                        <p class="mt-0.5 text-xs text-[var(--hs-neutral-600)]">
                                            <span class="font-medium">{{ __('Expires:') }}</span>
                                            {{ $shopCert->expiry_date->format('M j, Y') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Primary category chip: parent › child as breadcrumb-style --}}
                @if ($primaryCategory)
                    <a
                        href="{{ route('storefront.products', ['category' => $primaryCategory->id]) }}"
                        wire:navigate
                        class="inline-flex items-center gap-1 h-8 px-3 rounded-[var(--hs-radius-pill)] bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-700)] border border-[var(--hs-neutral-200)] text-xs font-medium whitespace-nowrap shrink-0 hover:bg-[var(--hs-neutral-50)] hover:border-[var(--hs-neutral-300)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--hs-green-600)] transition max-w-[220px] truncate"
                        :title="'{{ addslashes($primaryCategory->parent?->name) }} › {{ addslashes($primaryCategory->name) }}'"
                    >
                        @if ($primaryCategory->parent)
                            <span class="text-[var(--hs-neutral-400)] truncate min-w-0">{{ $primaryCategory->parent->name }}</span>
                            <svg class="size-3 shrink-0 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        @endif
                        <span class="truncate min-w-0">{{ $primaryCategory->name }}</span>
                    </a>
                @endif

                {{-- +N overflow chip --}}
                @if ($overflowCategories->isNotEmpty())
                    <div class="relative shrink-0" x-data="{ anchorRect: null }">
                        <button
                            type="button"
                            x-ref="overflowBtn"
                            @click="
                                if (overflowOpen) {
                                    overflowOpen = false;
                                } else {
                                    anchorRect = $refs.overflowBtn.getBoundingClientRect();
                                    overflowOpen = true;
                                }
                            "
                            :aria-expanded="overflowOpen"
                            aria-haspopup="true"
                            class="inline-flex items-center h-8 px-3 rounded-[var(--hs-radius-pill)] bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-700)] border border-[var(--hs-neutral-200)] text-xs font-semibold whitespace-nowrap hover:bg-[var(--hs-neutral-50)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--hs-green-600)] transition"
                        >+{{ $overflowCategories->count() }}</button>

                        <template x-teleport="body">
                            <div
                                x-show="overflowOpen"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                @click.outside="if (!$refs.overflowBtn.contains($event.target)) overflowOpen = false"
                                @keydown.escape.window="overflowOpen = false"
                                :style="anchorRect ? `position:fixed; top:${anchorRect.bottom + 8}px; left:${anchorRect.left}px; z-index:50;` : ''"
                                class="min-w-[180px] rounded-[var(--hs-radius-card)] bg-[var(--hs-neutral-0)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-lg)] p-1.5"
                                role="menu"
                                aria-label="{{ __('More categories') }}"
                            >
                                @foreach ($overflowCategories as $cat)
                                    <a
                                        href="{{ route('storefront.products', ['category' => $cat->id]) }}"
                                        wire:navigate
                                        role="menuitem"
                                        class="flex items-center gap-2 px-3 py-2 rounded-md text-sm text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-100)] focus-visible:outline-none focus-visible:bg-[var(--hs-neutral-100)] transition"
                                    >
                                        @if ($cat->parent)
                                            <span class="text-xs text-[var(--hs-neutral-400)]">{{ $cat->parent->name }} ›</span>
                                        @endif
                                        {{ $cat->name }}
                                    </a>
                                @endforeach
                            </div>
                        </template>
                    </div>
                @endif
            </div>

            {{-- Title --}}
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-3xl font-bold text-[var(--hs-neutral-900)] leading-tight">{{ $product->name }}</h1>
                @auth
                    @if (auth()->user()->activeShop?->id === $product->shop_id)
                        <a href="{{ route('hub.products.edit', $product) }}" wire:navigate
                           class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-[var(--hs-neutral-200)] px-3 py-1.5 text-sm font-medium text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-50)] transition">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                            {{ __('Edit') }}
                        </a>
                    @endif
                @endauth
            </div>

            {{-- Supplier line --}}
            @if ($product->shop)
                <p class="mt-2 text-sm text-[var(--hs-neutral-500)]">
                    {{ __('by') }}
                    <a href="{{ route('storefront.company', $product->shop) }}" class="text-[var(--hs-green-700)] hover:underline font-semibold" wire:navigate>{{ $product->shop->storefront_name }}</a>
                </p>
            @endif

            {{-- Rating row --}}
            @if ($product->average_rating)
                <div class="mt-2 flex items-center gap-2">
                    <div class="flex items-center gap-0.5">
                        @foreach ($ratingStars as $state)
                            <span class="text-sm {{ $state === 'full' ? 'text-yellow-400' : ($state === 'half' ? 'text-yellow-300' : 'text-[var(--hs-neutral-300)]') }}" aria-hidden="true">★</span>
                        @endforeach
                    </div>
                    <span class="text-sm font-semibold text-[var(--hs-neutral-700)]">{{ number_format((float) $product->average_rating, 1) }}</span>
                    <span class="text-sm text-[var(--hs-neutral-500)]">({{ $product->total_reviews }} {{ Str::plural('review', $product->total_reviews) }})</span>
                </div>
            @endif

            {{-- ============ VOLUME PRICING LEDGER ============ --}}
            @if ($product->variants->count() > 0)
                <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)] overflow-hidden">
                    {{-- Perforated top edge --}}
                    <div class="h-3 bg-[var(--hs-neutral-50)] border-b border-dashed border-[var(--hs-neutral-200)]"></div>

                    {{-- Header --}}
                    <div class="flex items-baseline justify-between px-3 sm:px-5 pt-3 pb-1">
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-[var(--hs-neutral-500)]">{{ __('Volume Pricing') }}</span>
                        <span class="text-xs text-[var(--hs-neutral-400)] tabular-nums" x-text="currentVariant.currency + ' / ' + currentVariant.unit"></span>
                    </div>

                    {{-- Tier rows --}}
                    <div class="px-3 sm:px-5 pb-5 divide-y divide-[var(--hs-neutral-100)]">
                        <template x-for="(tier, idx) in priceTiers" :key="tier.minQty">
                            <div
                                class="flex items-center gap-2 sm:gap-3 py-3 -mx-3 px-3 sm:-mx-5 sm:px-5 relative"
                                :class="idx === bestTierIndex ? 'bg-[var(--hs-green-50)]' : ''"
                            >
                                {{-- Quantity --}}
                                <span class="text-sm font-semibold text-[var(--hs-neutral-600)] whitespace-nowrap" x-text="tier.minQty + '+ ' + currentVariant.unit"></span>

                                {{-- Leader dots --}}
                                <span class="flex-1 border-b border-dotted border-[var(--hs-neutral-200)]"></span>

                                {{-- Prices --}}
                                <span class="flex items-baseline gap-1.5 sm:gap-2 whitespace-nowrap">
                                    <span
                                        x-show="discountFor(tier.price)"
                                        x-text="'₱' + basePrice.toFixed(2)"
                                        class="text-xs text-[var(--hs-neutral-400)] line-through tabular-nums"
                                    ></span>
                                    <span
                                        x-text="'₱' + tier.price.toFixed(2)"
                                        class="text-base sm:text-lg font-bold text-[var(--hs-neutral-900)] tabular-nums"
                                    ></span>
                                </span>

                                {{-- Discount badge --}}
                                <span
                                    x-show="discountFor(tier.price)"
                                    x-text="discountFor(tier.price) + '% OFF'"
                                    class="text-[10px] font-bold uppercase tracking-wide bg-[var(--hs-green-600)] text-white px-1.5 py-0.5 rounded-[var(--hs-radius-pill)] whitespace-nowrap"
                                ></span>
                            </div>
                        </template>
                    </div>
                </div>
            @endif

            {{-- ============ ATTRIBUTE SELECTORS ============ --}}
            @if (! empty($attributeGroups))
                <div class="space-y-5">
                    @foreach ($attributeGroups as $key => $values)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)] mb-2.5">
                                {{ ucfirst(str_replace('_', ' ', preg_replace('/^(attr|attribute)_/i', '', $key))) }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($values as $value)
                                    <button
                                        type="button"
                                        @click="if (isAvailable('{{ $key }}', '{{ $value }}')) selected['{{ $key }}'] = '{{ $value }}'"
                                        class="px-4 py-2 rounded-[var(--hs-radius-md)] border text-sm font-medium transition-all"
                                        :class="{
                                            'border-[var(--hs-green-600)] bg-[var(--hs-green-600)] text-white shadow-sm': selected['{{ $key }}'] === '{{ $value }}',
                                            'border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-700)] hover:border-[var(--hs-neutral-300)] hover:bg-[var(--hs-neutral-50)]': selected['{{ $key }}'] !== '{{ $value }}' && isAvailable('{{ $key }}', '{{ $value }}'),
                                            'border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] text-[var(--hs-neutral-300)] cursor-not-allowed line-through': !isAvailable('{{ $key }}', '{{ $value }}')
                                        }"
                                        :disabled="!isAvailable('{{ $key }}', '{{ $value }}')"
                                    >
                                        {{ $value }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <p class="text-xs text-[var(--hs-neutral-400)] tabular-nums" x-text="'SKU: ' + currentVariant.sku"></p>
                </div>
            @endif

            {{-- ============ DESCRIPTION ============ --}}
            @if ($product->description)
                @php
                    $descriptionText = strip_tags($product->description ?? '');
                    $isLong = mb_strlen($descriptionText) > 300;
                    // Legacy plain-text descriptions have no HTML tags; convert newlines to <br>.
                    // Trix-produced HTML already contains block elements so nl2br is not applied.
                    $isPlainText = $product->description === $descriptionText;
                    $renderedDescription = $isPlainText
                        ? nl2br(e($product->description))
                        : $product->description;
                @endphp
                <div class="pt-5 border-t border-[var(--hs-neutral-100)]" x-data="{ expanded: false }">
                    <div
                        class="hs-prose overflow-hidden transition-all duration-300"
                        :style="expanded ? 'max-height: none' : 'max-height: 8rem'"
                        :class="!expanded && {{ $isLong ? 'true' : 'false' }} ? 'hs-prose--clamped' : ''"
                        aria-expanded="false"
                        :aria-expanded="expanded"
                    >
                        {!! $renderedDescription !!}
                    </div>
                    @if ($isLong)
                        <button
                            type="button"
                            @click="expanded = !expanded"
                            class="mt-2 text-sm font-semibold text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--hs-green-600)] transition"
                            :aria-expanded="expanded"
                        >
                            <span x-show="!expanded">{{ __('Read more') }}</span>
                            <span x-show="expanded">{{ __('Show less') }}</span>
                        </button>
                    @endif
                </div>
            @endif

            {{-- ============ SUPPLIER CARD ============ --}}
            @if ($product->shop)
                <a
                    href="{{ route('storefront.company', $product->shop) }}"
                    wire:navigate
                    class="flex items-center justify-between gap-4 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-4 hover:border-[var(--hs-green-200)] hover:shadow-[var(--hs-shadow-md)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--hs-green-600)] transition group"
                    :aria-label="'{{ __('View supplier') }}: {{ $product->shop->storefront_name }}'"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        {{-- Avatar/initials --}}
                        <div class="size-10 rounded-[var(--hs-radius-md)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] flex items-center justify-center text-sm font-bold text-[var(--hs-green-700)] shrink-0">
                            {{ strtoupper(mb_substr($product->shop->storefront_name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <p class="text-sm font-semibold text-[var(--hs-neutral-900)] group-hover:text-[var(--hs-green-700)] transition truncate">{{ $product->shop->storefront_name }}</p>
                                @if ($product->shop->certificates?->contains('status', 'active'))
                                    <svg class="size-4 text-[var(--hs-green-600)] shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-label="{{ __('Verified') }}">
                                        <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                            @if ($product->shop->city || $product->shop->province)
                                <p class="mt-0.5 text-xs text-[var(--hs-neutral-500)] truncate">
                                    <svg class="size-3 inline-block mr-0.5 -mt-0.5 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    {{ collect([$product->shop->city, $product->shop->province])->filter()->implode(', ') }}
                                </p>
                            @else
                                <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Supplier') }}</p>
                            @endif
                        </div>
                    </div>
                    <span class="text-sm font-semibold text-[var(--hs-green-700)] group-hover:text-[var(--hs-green-800)] whitespace-nowrap flex items-center gap-1 shrink-0">
                        {{ __('View Shop') }}
                        <flux:icon name="arrow-right" variant="mini" class="size-3.5" aria-hidden="true" />
                    </span>
                </a>
            @endif

            {{-- ============ REQUEST QUOTE CTA ============ --}}
            @if ($product->shop)
                @if (auth()->check() && auth()->user()->activeShop?->id === $product->shop_id)
                    <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5 flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <p class="text-sm font-semibold text-[var(--hs-neutral-900)]">{{ __('This is your product') }}</p>
                            <p class="text-xs text-[var(--hs-neutral-500)] mt-0.5">{{ __('You cannot request a quote for your own products.') }}</p>
                        </div>
                        <a
                            href="{{ route('hub.products.edit', $product) }}"
                            wire:navigate
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[var(--hs-radius-md)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] text-sm font-semibold text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-100)] transition whitespace-nowrap"
                        >
                            {{ __('Manage product') }}
                        </a>
                    </div>
                @else
                    <div class="rounded-[var(--hs-radius-card)] bg-[var(--hs-green-900)] p-5 flex flex-col gap-4">
                        <div>
                            <p class="text-sm font-semibold text-white">{{ __('Interested in this product?') }}</p>
                            <p class="text-xs text-[var(--hs-green-300)] mt-0.5">{{ __('Send a Request for Quotation to get a price from the supplier. Suppliers typically reply within 24–48 hrs.') }}</p>
                        </div>
                        <div class="flex items-center gap-3 flex-wrap">
                            {{-- Primary CTA: high-contrast gold accent for visibility on dark panel --}}
                            <a
                                href="{{ $ctaHref }}"
                                {!! $ctaAttrs !!}
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[var(--hs-radius-md)] bg-[var(--hs-gold-500)] text-[var(--hs-green-900)] text-sm font-bold hover:bg-[var(--hs-gold-400)] shadow-sm transition whitespace-nowrap focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                            >
                                <flux:icon name="document-plus" variant="mini" class="size-4" aria-hidden="true" />
                                {{ $ctaLabel }}
                            </a>
                            @guest
                                <a
                                    href="{{ route('register') }}"
                                    wire:navigate
                                    class="text-sm font-medium text-[var(--hs-green-300)] hover:text-white underline underline-offset-2 transition whitespace-nowrap"
                                >{{ __('Create account') }}</a>
                            @endguest
                        </div>
                    </div>
                @endif
            @endif

            {{-- ============ DOCUMENTS ============ --}}
            @if ($product->documents->count() > 0)
                <section>
                    <h2 class="text-base font-semibold text-[var(--hs-neutral-800)] mb-3">{{ __('Documents') }}</h2>
                    <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] p-1">
                        <ul class="divide-y divide-[var(--hs-neutral-100)]">
                            @foreach ($product->documents as $doc)
                                <li>
                                    <a
                                        href="{{ route('media.view', $doc) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center gap-3 rounded-[var(--hs-radius-card)] px-3 py-2.5 text-sm text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-50)] transition"
                                    >
                                        <flux:icon name="document" variant="mini" class="shrink-0 text-[var(--hs-neutral-400)]" />
                                        <span class="truncate">{{ $doc->original_filename ?? $doc->filename }}</span>
                                        <flux:icon name="arrow-top-right-on-square" variant="mini" class="ml-auto shrink-0 text-[var(--hs-neutral-300)]" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>
            @endif
        </div>
    </div>

    {{-- ============ REVIEWS SECTION ============ --}}
    @if ($product->total_reviews > 0)
        <section class="mt-12 pt-10 border-t border-[var(--hs-neutral-200)]">
            {{-- Reviews header with rating summary --}}
            <div class="flex items-end justify-between flex-wrap gap-6 mb-8">
                <h2 class="text-xl font-bold text-[var(--hs-neutral-900)]">{{ __('Customer Reviews') }}</h2>
                <div class="flex items-center gap-5">
                    {{-- Large rating number --}}
                    <span class="text-4xl font-bold text-[var(--hs-neutral-900)] tabular-nums leading-none">
                        {{ number_format((float) $product->average_rating, 1) }}
                    </span>
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-0.5">
                            @foreach ($ratingStars as $state)
                                <span class="text-base {{ $state === 'full' ? 'text-yellow-400' : ($state === 'half' ? 'text-yellow-300' : 'text-[var(--hs-neutral-300)]') }}">★</span>
                            @endforeach
                        </div>
                        <span class="text-xs text-[var(--hs-neutral-500)]">{{ __('Based on') }} {{ $product->total_reviews }} {{ Str::plural('review', $product->total_reviews) }}</span>
                    </div>
                </div>
            </div>

            {{-- Review list (Livewire component) --}}
            <livewire:storefront.review-list reviewable-type="product" :reviewable-id="$product->id" />
        </section>
    @endif

    {{-- ============ SOURCING / LOOKING FOR SOMETHING ELSE ============ --}}
    <section id="sourcing-form" class="mt-12 pt-12 border-t border-[var(--hs-neutral-200)] scroll-mt-24">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start">
            <div class="lg:sticky lg:top-6">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--hs-green-100)] bg-[var(--hs-green-50)] px-3.5 py-1.5 text-[0.75rem] font-bold tracking-wide text-[var(--hs-green-700)]">
                    <flux:icon name="magnifying-glass" class="size-3.5" />
                    {{ __('Custom Sourcing') }}
                </span>
                <h2 class="mt-4 text-2xl sm:text-3xl font-extrabold text-[var(--hs-neutral-900)] leading-tight tracking-tight">
                    {{ __('Looking for Something Else?') }}
                </h2>
                <p class="mt-3 text-base font-semibold text-[var(--hs-neutral-900)]">
                    {{ __('Don’t see exactly what you need? We can help.') }}
                </p>
                <p class="mt-3 text-sm sm:text-base text-[var(--hs-neutral-500)] leading-relaxed">
                    {{ __('If this product isn’t quite right, or you’re looking for something that isn’t listed on our website, send us your requirements. Our sourcing team can help find products that match your specifications.') }}
                </p>
                <p class="mt-3 text-sm sm:text-base text-[var(--hs-neutral-500)] leading-relaxed">
                    {{ __('Whether you have a product name, photo, specification, or just an idea of what you need, tell us what you’re looking for and we’ll take it from there.') }}
                </p>
            </div>
            <livewire:storefront.product-sourcing-form :prefill-product-name="$product->name" />
        </div>
    </section>
</div>

@script
<script>
/**
 * imageGallery Alpine.js component
 *
 * Handles:
 * - Active thumbnail switching
 * - Desktop hover-zoom (cursor-following inner zoom via transform-origin)
 * - Lightbox with prev/next, keyboard nav, focus trap, scroll lock
 * - Lightbox zoom (+/−/reset, scroll wheel, double-click)
 * - Lightbox pan/drag when zoomed
 * - Touch: swipe to change images, pinch-to-zoom, swipe-down to dismiss
 * - Respects prefers-reduced-motion
 */
Alpine.data('imageGallery', (images, productName) => ({
    images: images.map((img, i) => ({
        ...img,
        type: img.type || 'image',
        src: img.src || img,
        alt: img.alt || (productName + (images.length > 1 ? ' — image ' + (i + 1) : '')),
    })),
    active: 0,
    // Hover zoom
    zooming: false,
    originX: 50,
    originY: 50,
    showHint: true,
    _hasHovered: false,
    // Lightbox
    lightboxOpen: false,
    lightboxIndex: 0,
    lbZoom: 1,
    lbPanX: 0,
    lbPanY: 0,
    lbDragging: false,
    _lbDragStartX: 0,
    _lbDragStartY: 0,
    _lbPanStartX: 0,
    _lbPanStartY: 0,
    // Touch tracking
    _touchStartX: 0,
    _touchStartY: 0,
    _touchStartDist: 0,
    _touchStartZoom: 1,
    _touchSwipeHandled: false,

    init() {
        // Preload the first image without fetching a video before playback.
        if (this.images.length > 0 && this.images[0].type === 'image') {
            const img = new Image();
            img.src = this.images[0].src;
        }
    },

    get reducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    },

    get isTouch() {
        return window.matchMedia('(hover: none)').matches;
    },

    // ── Hover zoom ──────────────────────────────────────────────────────────
    onMouseMove(e) {
        if (this.isTouch || this.reducedMotion || this.images[this.active]?.type === 'video') {
            return;
        }
        const rect = e.currentTarget.getBoundingClientRect();
        this.originX = Math.round(((e.clientX - rect.left) / rect.width) * 100);
        this.originY = Math.round(((e.clientY - rect.top) / rect.height) * 100);
        this.zooming = true;
        if (!this._hasHovered) {
            this._hasHovered = true;
            setTimeout(() => { this.showHint = false; }, 1200);
        }
    },

    onMouseLeave() {
        this.zooming = false;
    },

    // ── Lightbox ─────────────────────────────────────────────────────────────
    _triggerEl: null,

    openLightbox(index) {
        this._triggerEl = document.activeElement;
        this.lightboxIndex = index;
        this.lbZoomReset();
        this.lightboxOpen = true;
        document.documentElement.style.overflow = 'hidden';
    },

    closeLightbox() {
        this.lightboxOpen = false;
        document.documentElement.style.overflow = '';
        this.$nextTick(() => {
            if (this._triggerEl) {
                this._triggerEl.focus();
            }
        });
    },

    lightboxNext() {
        this.lightboxIndex = (this.lightboxIndex + 1) % this.images.length;
        this.lbZoomReset();
    },

    lightboxPrev() {
        this.lightboxIndex = (this.lightboxIndex - 1 + this.images.length) % this.images.length;
        this.lbZoomReset();
    },

    lbZoomIn() {
        this.lbZoom = Math.min(this.lbZoom * 1.5, 4);
    },

    lbZoomOut() {
        const next = this.lbZoom / 1.5;
        if (next <= 1) {
            this.lbZoomReset();
        } else {
            this.lbZoom = next;
        }
    },

    lbZoomReset() {
        this.lbZoom = 1;
        this.lbPanX = 0;
        this.lbPanY = 0;
    },

    onLightboxWheel(e) {
        if (e.deltaY < 0) {
            this.lbZoomIn();
        } else {
            this.lbZoomOut();
        }
    },

    // Drag to pan when zoomed
    onLbDragStart(e) {
        if (this.lbZoom <= 1) {
            return;
        }
        e.preventDefault();
        this.lbDragging = true;
        this._lbDragStartX = e.clientX;
        this._lbDragStartY = e.clientY;
        this._lbPanStartX = this.lbPanX;
        this._lbPanStartY = this.lbPanY;
    },

    onLbDragMove(e) {
        if (!this.lbDragging) {
            return;
        }
        this.lbPanX = this._lbPanStartX + (e.clientX - this._lbDragStartX);
        this.lbPanY = this._lbPanStartY + (e.clientY - this._lbDragStartY);
    },

    onLbDragEnd() {
        this.lbDragging = false;
    },

    // Touch handlers (swipe to change, pinch to zoom, swipe-down to dismiss)
    onLbTouchStart(e) {
        this._touchSwipeHandled = false;
        if (e.touches.length === 1) {
            this._touchStartX = e.touches[0].clientX;
            this._touchStartY = e.touches[0].clientY;
        } else if (e.touches.length === 2) {
            const dx = e.touches[0].clientX - e.touches[1].clientX;
            const dy = e.touches[0].clientY - e.touches[1].clientY;
            this._touchStartDist = Math.hypot(dx, dy);
            this._touchStartZoom = this.lbZoom;
        }
    },

    onLbTouchMove(e) {
        if (e.touches.length === 2) {
            const dx = e.touches[0].clientX - e.touches[1].clientX;
            const dy = e.touches[0].clientY - e.touches[1].clientY;
            const dist = Math.hypot(dx, dy);
            const scale = dist / this._touchStartDist;
            this.lbZoom = Math.min(4, Math.max(1, this._touchStartZoom * scale));
        }
    },

    onLbTouchEnd(e) {
        if (e.changedTouches.length !== 1 || this._touchSwipeHandled) {
            return;
        }
        const dx = e.changedTouches[0].clientX - this._touchStartX;
        const dy = e.changedTouches[0].clientY - this._touchStartY;
        const absDx = Math.abs(dx);
        const absDy = Math.abs(dy);

        // Swipe down to dismiss (only when not zoomed)
        if (this.lbZoom <= 1 && absDy > 80 && dy > 0 && absDy > absDx) {
            this._touchSwipeHandled = true;
            this.closeLightbox();
            return;
        }

        // Horizontal swipe to change image (only when not zoomed)
        if (this.lbZoom <= 1 && absDx > 50 && absDx > absDy) {
            this._touchSwipeHandled = true;
            if (dx < 0) {
                this.lightboxNext();
            } else {
                this.lightboxPrev();
            }
        }
    },
}));
</script>
@endscript
