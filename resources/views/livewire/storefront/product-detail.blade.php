<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    {{-- Breadcrumb --}}
    <nav class="mb-6 flex items-center gap-2 text-sm text-[var(--hs-neutral-500)]">
        <a href="{{ route('storefront.products') }}" class="hover:text-[var(--hs-green-700)] transition" wire:navigate>Products</a>
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
        <span class="text-[var(--hs-neutral-700)] font-medium">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-10 lg:grid-cols-2">
        {{-- Gallery --}}
        <div>
            @if ($product->gallery->count() > 0)
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($product->gallery as $image)
                        <div class="rounded-[var(--hs-radius-card)] overflow-hidden border border-[var(--hs-neutral-200)]">
                            <img
                                src="{{ $image->image_url }}"
                                alt="{{ $product->name }}"
                                class="w-full aspect-square object-cover"
                            />
                        </div>
                    @endforeach
                </div>
            @elseif ($product->featured_image_path)
                <div class="rounded-[var(--hs-radius-card)] overflow-hidden border border-[var(--hs-neutral-200)]">
                    <img
                        src="{{ $product->featured_image_url }}"
                        alt="{{ $product->name }}"
                        class="w-full aspect-square object-cover"
                    />
                </div>
            @else
                <div class="flex items-center justify-center rounded-[var(--hs-radius-card)] bg-[var(--hs-neutral-100)] aspect-square border border-[var(--hs-neutral-200)]">
                    <svg class="size-16 text-[var(--hs-neutral-300)]" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 0 0 2.25-2.25V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
                </div>
            @endif
        </div>

        {{-- Product Info --}}
        <div class="space-y-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <x-hs-badge variant="halal" label="Halal Certified" />
                </div>
                <h1 class="text-3xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">{{ $product->name }}</h1>
                @if ($product->shop)
                    <p class="mt-2 text-[var(--hs-neutral-500)]">
                        by <a href="{{ route('storefront.company', $product->shop) }}" class="text-[var(--hs-green-700)] hover:underline font-medium" wire:navigate>{{ $product->shop->storefront_name }}</a>
                    </p>
                @endif
            </div>

            @if ($product->description)
                <p class="text-[var(--hs-neutral-700)] leading-relaxed">{{ $product->description }}</p>
            @endif

            {{-- Categories --}}
            @if ($product->categories->count() > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach ($product->categories as $category)
                        <x-hs-badge variant="tag" :label="$category->name" />
                    @endforeach
                </div>
            @endif

            {{-- Variants / Price & MOQ Table --}}
            @if ($product->variants->count() > 0)
                <section>
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)] mb-3">Variants & Pricing</h2>
                    <div class="overflow-x-auto rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)]">
                        <table class="w-full text-sm">
                            <thead class="bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-200)]">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--hs-neutral-600)] uppercase tracking-wider">SKU</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--hs-neutral-600)] uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-[var(--hs-neutral-600)] uppercase tracking-wider">Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-[var(--hs-neutral-600)] uppercase tracking-wider">MOQ</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--hs-neutral-600)] uppercase tracking-wider">Unit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--hs-neutral-100)]">
                                @foreach ($product->variants as $variant)
                                    <tr class="hover:bg-[var(--hs-neutral-50)] transition">
                                        <td class="px-4 py-3 font-mono text-xs text-[var(--hs-neutral-700)]">{{ $variant->sku }}</td>
                                        <td class="px-4 py-3 text-[var(--hs-neutral-700)]">{{ $variant->description ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-[var(--hs-green-700)]">{{ $variant->currency }} {{ number_format((float) $variant->price, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-[var(--hs-neutral-700)]">{{ $variant->minimum_order_quantity }}</td>
                                        <td class="px-4 py-3 text-[var(--hs-neutral-600)]">{{ $variant->unit }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            {{-- Documents --}}
            @if ($product->documents->count() > 0)
                <section>
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)] mb-3">Documents</h2>
                    <ul class="space-y-2">
                        @foreach ($product->documents as $doc)
                            <li class="flex items-center gap-2 text-sm text-[var(--hs-neutral-700)]">
                                <svg class="size-4 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                {{ $doc->original_filename ?? $doc->filename }}
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Request Quote Action --}}
            @if ($product->shop)
                <div class="rounded-[var(--hs-radius-card)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] p-5">
                    <div class="flex items-start gap-3">
                        <svg class="size-5 text-[var(--hs-green-600)] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-[var(--hs-green-800)]">{{ __('Interested in this product?') }}</p>
                            <p class="text-xs text-[var(--hs-green-600)] mt-0.5">{{ __('Send a Request for Quotation to get a price from the supplier.') }}</p>
                            <div class="mt-3">
                                @auth
                                    <a
                                        href="{{ route('account.rfqs.create', ['shop_id' => $product->shop->id, 'title' => $product->name]) }}"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-[var(--hs-radius-pill)] bg-[var(--hs-green-600)] text-white text-sm font-medium hover:bg-[var(--hs-green-700)] transition"
                                        wire:navigate
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                                        {{ __('Request Quote') }}
                                    </a>
                                @else
                                    <a
                                        href="{{ route('login') }}"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-[var(--hs-radius-pill)] bg-[var(--hs-green-600)] text-white text-sm font-medium hover:bg-[var(--hs-green-700)] transition"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                                        {{ __('Log in to Request Quote') }}
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
