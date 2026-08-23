<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    {{-- Breadcrumb --}}
    <nav class="mb-6 flex items-center gap-2 text-sm text-[var(--hs-neutral-500)]">
        <a href="{{ route('storefront.products') }}" class="hover:text-[var(--hs-green-700)] transition" wire:navigate>Products</a>
        <flux:icon name="chevron-right" variant="mini" class="size-4 text-[var(--hs-neutral-300)]" />
        <span class="text-[var(--hs-neutral-700)] font-medium">{{ $product->name }}</span>
    </nav>

    @php
        // Build attribute groups across all variants (e.g. Color => [Black, Red], Voltage => [12V, 24V])
        $attributeGroups = [];
        foreach ($product->variants as $variant) {
            foreach ((array) ($variant->attributes ?? []) as $key => $value) {
                $attributeGroups[$key][$value] = true;
            }
        }
        foreach ($attributeGroups as $key => $values) {
            $attributeGroups[$key] = array_keys($values);
        }

        // Flatten variants into a JS-friendly array for client-side matching
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

        $galleryImages = $product->gallery->count() > 0
            ? $product->gallery->map(fn ($img) => $img->image_url)->values()
            : ($product->featured_image_path ? collect([$product->featured_image_url]) : collect());

        // Precompute the RFQ CTA up front — keeps the <a> tag itself a clean single line
        $ctaHref = auth()->check()
            ? route('account.rfqs.create', ['shop_id' => $product->shop?->id, 'title' => $product->name])
            : route('login');
        $ctaLabel = auth()->check() ? __('Request Quote') : __('Log in to Request Quote');
        $ctaAttrs = auth()->check() ? 'wire:navigate' : '';
    @endphp

    <div
        class="grid grid-cols-1 gap-10 lg:grid-cols-2"
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
                valuesFor(key) {
                    return [...new Set(this.variants.map(v => v.attributes[key]).filter(v => v !== undefined))];
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
                discountFor(price) {
                    const base = this.priceTiers[0]?.price ?? price;
                    if (base <= 0 || price >= base) return null;
                    return Math.round(100 - (price / base) * 100);
                }
            }"
        @endif
    >
        {{-- Gallery --}}
        <div @if ($galleryImages->count() > 1) x-data="{ active: 0 }" @endif>
            @if ($galleryImages->count() > 0)
                <div class="rounded-[var(--hs-radius-card)] overflow-hidden border border-[var(--hs-neutral-200)] mb-3">
                    @if ($galleryImages->count() > 1)
                        <template x-for="(img, i) in {{ \Illuminate\Support\Js::from($galleryImages) }}" :key="i">
                            <img
                                x-show="active === i"
                                :src="img"
                                alt="{{ $product->name }}"
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

                @if ($galleryImages->count() > 1)
                    <div class="grid grid-cols-5 gap-2">
                        @foreach ($galleryImages as $i => $img)
                            <button
                                type="button"
                                @click="active = {{ $i }}"
                                class="rounded-[var(--hs-radius-card)] overflow-hidden border-2 transition"
                                :class="active === {{ $i }} ? 'border-[var(--hs-green-600)]' : 'border-[var(--hs-neutral-200)] hover:border-[var(--hs-neutral-300)]'"
                            >
                                <img src="{{ $img }}" alt="" class="w-full aspect-square object-cover" />
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center gap-2 rounded-[var(--hs-radius-card)] bg-[var(--hs-neutral-50)] aspect-square border border-[var(--hs-neutral-200)]">
                    <flux:icon name="photo" class="size-16 text-[var(--hs-neutral-300)]" />
                    <p class="text-sm text-[var(--hs-neutral-400)]">{{ __('No images available') }}</p>
                </div>
            @endif
        </div>

        {{-- Product Info --}}
        <div class="space-y-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <x-hs-badge variant="halal" label="Halal Certified" />
                    @if ($product->categories->count() > 0)
                        @foreach ($product->categories->take(2) as $category)
                            <x-hs-badge variant="tag" :label="$category->name" />
                        @endforeach
                    @endif
                </div>
                <h1 class="text-3xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">{{ $product->name }}</h1>
                @if ($product->shop)
                    <p class="mt-2 text-[var(--hs-neutral-500)]">
                        {{ __('by') }} <a href="{{ route('storefront.company', $product->shop) }}" class="text-[var(--hs-green-700)] hover:underline font-medium" wire:navigate>{{ $product->shop->storefront_name }}</a>
                    </p>
                @endif
            </div>

            {{-- Price Ladder (Alibaba-style tiered pricing) --}}
            @if ($product->variants->count() > 0)
                <div class="rounded-[var(--hs-radius-card)] border-2 border-[var(--hs-green-200)] bg-[var(--hs-green-50)]/40 p-4">
                    <div class="flex flex-wrap gap-x-8 gap-y-4">
                        <template x-for="tier in priceTiers" :key="tier.minQty">
                            <div>
                                <div class="flex items-baseline gap-2">
                                    <span
                                        x-show="discountFor(tier.price)"
                                        x-text="currentVariant.currency + ' ' + priceTiers[0].price.toFixed(2)"
                                        class="text-sm text-[var(--hs-neutral-400)] line-through"
                                    ></span>
                                    <span
                                        x-text="currentVariant.currency + ' ' + tier.price.toFixed(2)"
                                        class="text-xl font-bold text-[var(--hs-neutral-900)]"
                                    ></span>
                                </div>
                                <p class="text-xs text-[var(--hs-neutral-500)] mt-0.5">
                                    <span x-text="tier.minQty"></span>+ <span x-text="currentVariant.unit"></span>
                                    <span x-show="discountFor(tier.price)" class="text-[var(--hs-green-600)] font-medium ml-1">
                                        (<span x-text="discountFor(tier.price)"></span>% off)
                                    </span>
                                </p>
                            </div>
                        </template>
                    </div>
                </div>
            @endif

            {{-- Variant Selectors --}}
            @if (! empty($attributeGroups))
                <div class="space-y-4">
                    @foreach ($attributeGroups as $key => $values)
                        <div>
                            <p class="text-sm font-medium text-[var(--hs-neutral-700)] mb-2">{{ ucfirst(str_replace('_', ' ', $key)) }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($values as $value)
                                    <button
                                        type="button"
                                        @click="if (isAvailable('{{ $key }}', '{{ $value }}')) selected['{{ $key }}'] = '{{ $value }}'"
                                        class="px-3 py-1.5 rounded-[var(--hs-radius-tag)] border text-sm transition"
                                        :class="{
                                            'border-[var(--hs-green-600)] bg-[var(--hs-green-50)] text-[var(--hs-green-700)] font-medium': selected['{{ $key }}'] === '{{ $value }}',
                                            'border-[var(--hs-neutral-200)] text-[var(--hs-neutral-700)] hover:border-[var(--hs-neutral-300)]': selected['{{ $key }}'] !== '{{ $value }}' && isAvailable('{{ $key }}', '{{ $value }}'),
                                            'border-[var(--hs-neutral-100)] text-[var(--hs-neutral-300)] cursor-not-allowed line-through': !isAvailable('{{ $key }}', '{{ $value }}')
                                        }"
                                    >
                                        {{ $value }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <p class="text-xs text-[var(--hs-neutral-400)] font-mono" x-text="'SKU: ' + currentVariant.sku"></p>
                </div>
            @endif

            @if ($product->description)
                <p class="text-[var(--hs-neutral-700)] leading-relaxed">{{ $product->description }}</p>
            @endif

            {{-- Documents --}}
            @if ($product->documents->count() > 0)
                <section>
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)] mb-3">{{ __('Documents') }}</h2>
                    <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] p-1">
                        <ul class="divide-y divide-[var(--hs-neutral-100)]">
                            @foreach ($product->documents as $doc)
                                <li>
                                    
                                        href="{{ route('media.view', $doc) }}"
                                        target="_blank"
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

            {{-- Supplier Card --}}
            @if ($product->shop)
                <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] p-4 flex items-center justify-between gap-4">
                    <div>
                        <a href="{{ route('storefront.company', $product->shop) }}" class="font-semibold text-[var(--hs-neutral-900)] hover:text-[var(--hs-green-700)] transition" wire:navigate>
                            {{ $product->shop->storefront_name }}
                        </a>
                        <p class="text-xs text-[var(--hs-neutral-500)] mt-0.5">{{ __('Supplier') }}</p>
                    </div>
                    <a href="{{ route('storefront.company', $product->shop) }}" wire:navigate class="text-sm font-medium text-[var(--hs-green-700)] hover:underline whitespace-nowrap">
                        {{ __('View Shop') }} →
                    </a>
                </div>
            @endif

            {{-- Request Quote Action --}}
            @if ($product->shop)
                <div class="rounded-[var(--hs-radius-card)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] p-5">
                    <div class="flex items-start gap-3">
                        <flux:icon name="chat-bubble-left-right" class="size-5 text-[var(--hs-green-600)] shrink-0 mt-0.5" />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-[var(--hs-green-800)]">{{ __('Interested in this product?') }}</p>
                            <p class="text-xs text-[var(--hs-green-600)] mt-0.5">{{ __('Send a Request for Quotation to get a price from the supplier.') }}</p>
                            <div class="mt-3">
                                <a href="{{ $ctaHref }}" {!! $ctaAttrs !!} class="inline-flex items-center gap-2 px-4 py-2 rounded-[var(--hs-radius-pill)] bg-[var(--hs-green-600)] text-white text-sm font-medium hover:bg-[var(--hs-green-700)] transition">
                                    <flux:icon name="document-plus" variant="mini" />
                                    {{ $ctaLabel }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>