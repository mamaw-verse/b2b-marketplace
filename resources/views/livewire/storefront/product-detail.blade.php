<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    {{-- Breadcrumb --}}
    <nav class="mb-6 flex items-center gap-2 text-sm text-[var(--hs-neutral-500)]">
        <a href="{{ route('storefront.products') }}" class="hover:text-[var(--hs-green-700)] transition" wire:navigate>{{ __('Products') }}</a>
        <flux:icon name="chevron-right" variant="mini" class="size-4 text-[var(--hs-neutral-300)]" />
        <span class="text-[var(--hs-neutral-700)] font-medium">{{ $product->name }}</span>
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

        // Gallery: featured image first, then gallery images (deduped)
        $galleryImages = collect();
        if ($product->featured_image_path) {
            $galleryImages->push($product->featured_image_url);
        }
        if ($product->gallery->count() > 0) {
            $featured = $product->featured_image_url ?? null;
            $product->gallery->each(function ($img) use ($galleryImages, $featured) {
                if ($img->image_url !== $featured) {
                    $galleryImages->push($img->image_url);
                }
            });
        }
        $galleryImages = $galleryImages->values();

        // RFQ CTA
        $ctaHref = auth()->check()
            ? route('account.rfqs.create', ['shop_id' => $product->shop?->id, 'title' => $product->name])
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
        class="grid grid-cols-1 gap-10 lg:grid-cols-2 lg:items-start"
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
        <div class="lg:sticky lg:top-6 space-y-3" @if ($galleryImages->count() > 1) x-data="{ active: 0 }" @endif>
            @if ($galleryImages->count() > 0)
                {{-- Main image --}}
                <div class="rounded-[var(--hs-radius-card)] overflow-hidden border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)] p-3">
                    <div class="rounded-[var(--hs-radius-md)] overflow-hidden bg-[var(--hs-neutral-50)]">
                        @if ($galleryImages->count() > 1)
                            <template x-for="(img, i) in {{ \Illuminate\Support\Js::from($galleryImages) }}" :key="i">
                                <img
                                    x-show="active === i"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    :src="img"
                                    :alt="'{{ $product->name }} - image ' + (i + 1)"
                                    class="w-full aspect-square object-cover"
                                />
                            </template>
                        @else
                            <img
                                src="{{ $galleryImages->first() }}"
                                alt="{{ $product->name }}"
                                class="w-full aspect-square object-cover"
                            />
                        @endif
                    </div>

                    {{-- Thumbnails --}}
                    @if ($galleryImages->count() > 1)
                        <div class="grid grid-cols-5 gap-2 mt-3">
                            @foreach ($galleryImages as $i => $img)
                                <button
                                    type="button"
                                    @click="active = {{ $i }}"
                                    class="rounded-[var(--hs-radius-sm)] overflow-hidden border-2 transition"
                                    :class="active === {{ $i }} ? 'border-[var(--hs-green-600)] ring-1 ring-[var(--hs-green-600)]' : 'border-[var(--hs-neutral-200)] hover:border-[var(--hs-neutral-300)]'"
                                >
                                    <img src="{{ $img }}" alt="" class="w-full aspect-square object-cover" />
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Trust/certification strip --}}
                <div class="flex items-center gap-2 rounded-[var(--hs-radius-sm)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] px-3.5 py-2.5">
                    <flux:icon name="check-badge" variant="mini" class="size-4 text-[var(--hs-green-600)] shrink-0" />
                    <p class="text-xs font-medium text-[var(--hs-green-800)]">{{ __('Halal chain of custody verified — certified supplier') }}</p>
                </div>
            @else
                <div class="flex flex-col items-center justify-center gap-2 rounded-[var(--hs-radius-card)] bg-[var(--hs-neutral-50)] aspect-square border border-[var(--hs-neutral-200)]">
                    <flux:icon name="photo" class="size-16 text-[var(--hs-neutral-300)]" />
                    <p class="text-sm text-[var(--hs-neutral-400)]">{{ __('No images available') }}</p>
                </div>
            @endif
        </div>

        {{-- ============ INFO COLUMN ============ --}}
        <div class="space-y-6">
            {{-- Badges --}}
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <x-hs-badge variant="halal" label="Halal Certified" />
                    @foreach ($product->categories->take(2) as $category)
                        <x-hs-badge variant="tag" :label="$category->name" />
                    @endforeach
                </div>

                {{-- Title --}}
                <h1 class="text-3xl font-bold text-[var(--hs-neutral-900)]">{{ $product->name }}</h1>

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
                                <span class="text-sm {{ $state === 'full' ? 'text-yellow-400' : ($state === 'half' ? 'text-yellow-300' : 'text-[var(--hs-neutral-300)]') }}">★</span>
                            @endforeach
                        </div>
                        <span class="text-sm font-semibold text-[var(--hs-neutral-700)]">{{ number_format((float) $product->average_rating, 1) }}</span>
                        <span class="text-sm text-[var(--hs-neutral-500)]">({{ $product->total_reviews }} {{ Str::plural('review', $product->total_reviews) }})</span>
                    </div>
                @endif
            </div>

            {{-- ============ VOLUME PRICING LEDGER ============ --}}
            @if ($product->variants->count() > 0)
                <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)] overflow-hidden">
                    {{-- Perforated top edge --}}
                    <div class="h-3 bg-[var(--hs-neutral-50)] border-b border-dashed border-[var(--hs-neutral-200)]"></div>

                    {{-- Header --}}
                    <div class="flex items-baseline justify-between px-5 pt-3 pb-1">
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-[var(--hs-neutral-500)]">{{ __('Volume Pricing') }}</span>
                        <span class="text-xs text-[var(--hs-neutral-400)] tabular-nums" x-text="currentVariant.currency + ' / ' + currentVariant.unit"></span>
                    </div>

                    {{-- Tier rows --}}
                    <div class="px-5 pb-5 divide-y divide-[var(--hs-neutral-100)]">
                        <template x-for="(tier, idx) in priceTiers" :key="tier.minQty">
                            <div
                                class="flex items-center gap-3 py-3 -mx-5 px-5 relative"
                                :class="idx === bestTierIndex ? 'bg-[var(--hs-green-50)]' : ''"
                            >
                                {{-- Quantity --}}
                                <span class="text-sm font-semibold text-[var(--hs-neutral-600)] whitespace-nowrap" x-text="tier.minQty + '+ ' + currentVariant.unit"></span>

                                {{-- Leader dots --}}
                                <span class="flex-1 border-b border-dotted border-[var(--hs-neutral-200)]"></span>

                                {{-- Prices --}}
                                <span class="flex items-baseline gap-2 whitespace-nowrap">
                                    <span
                                        x-show="discountFor(tier.price)"
                                        x-text="'₱' + basePrice.toFixed(2)"
                                        class="text-xs text-[var(--hs-neutral-400)] line-through tabular-nums"
                                    ></span>
                                    <span
                                        x-text="'₱' + tier.price.toFixed(2)"
                                        class="text-lg font-bold text-[var(--hs-neutral-900)] tabular-nums"
                                    ></span>
                                </span>

                                {{-- Discount badge --}}
                                <span
                                    x-show="discountFor(tier.price)"
                                    x-text="discountFor(tier.price) + '% OFF'"
                                    class="text-[10px] font-bold uppercase tracking-wide bg-[var(--hs-green-600)] text-white px-2 py-0.5 rounded-[var(--hs-radius-pill)]"
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
                <div class="pt-5 border-t border-[var(--hs-neutral-100)]">
                    <p class="text-sm text-[var(--hs-neutral-600)] leading-relaxed">{{ $product->description }}</p>
                </div>
            @endif

            {{-- ============ SUPPLIER CARD ============ --}}
            @if ($product->shop)
                <div class="flex items-center justify-between gap-4 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-4">
                    <div class="flex items-center gap-3">
                        {{-- Avatar/initials --}}
                        <div class="size-10 rounded-[var(--hs-radius-md)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] flex items-center justify-center text-sm font-bold text-[var(--hs-green-700)]">
                            {{ strtoupper(mb_substr($product->shop->storefront_name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-[var(--hs-neutral-900)]">{{ $product->shop->storefront_name }}</p>
                            <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Supplier') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('storefront.company', $product->shop) }}" wire:navigate class="text-sm font-semibold text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] hover:underline whitespace-nowrap flex items-center gap-1">
                        {{ __('View Shop') }}
                        <flux:icon name="arrow-right" variant="mini" class="size-3.5" />
                    </a>
                </div>
            @endif

            {{-- ============ REQUEST QUOTE CTA ============ --}}
            @if ($product->shop)
                <div class="rounded-[var(--hs-radius-card)] bg-[var(--hs-green-900)] p-5 flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <p class="text-sm font-semibold text-white">{{ __('Interested in this product?') }}</p>
                        <p class="text-xs text-[var(--hs-green-300)] mt-0.5">{{ __('Send a Request for Quotation to get a price from the supplier.') }}</p>
                    </div>
                    <a
                        href="{{ $ctaHref }}"
                        {!! $ctaAttrs !!}
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[var(--hs-radius-md)] bg-[var(--hs-green-600)] text-white text-sm font-semibold hover:bg-[var(--hs-green-700)] shadow-sm transition whitespace-nowrap"
                    >
                        <flux:icon name="document-plus" variant="mini" class="size-4" />
                        {{ $ctaLabel }}
                    </a>
                </div>
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
</div>
