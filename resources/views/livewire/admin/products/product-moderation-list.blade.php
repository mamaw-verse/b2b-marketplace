<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Product Moderation Queue</h1>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">Manage flagged and unpublished products.</p>
            </div>
            @if($shopId)
                <flux:button href="{{ route('admin.merchants.show', $shopId) }}" variant="ghost">Back to Merchant</flux:button>
            @endif
        </div>

        {{-- Filters --}}
        <div class="px-4 sm:px-6 py-5 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex-1">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search product name..." />
                </div>
                
                <div class="flex gap-2">
                    <flux:select wire:model.live="statusFilter" class="w-48" placeholder="All Moderation Statuses">
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="flagged">Flagged</flux:select.option>
                        <flux:select.option value="admin_unpublished">Admin Unpublished</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Product</flux:table.column>
                    <flux:table.column>Merchant</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Moderation</flux:table.column>
                    <flux:table.column class="text-right">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->products as $product)
                        <flux:table.row>
                            <flux:table.cell>
                                @php
                                    $isLive = $product->status === 'published'
                                        && $product->moderation_status === 'active'
                                        && $product->shop->status === 'active';
                                @endphp
                                <div class="flex items-center gap-2">
                                    <button
                                        wire:click="openPreview({{ $product->id }})"
                                        class="font-medium text-[var(--hs-neutral-900)] hover:text-[var(--hs-green-700)] text-left transition cursor-pointer"
                                    >{{ $product->name }}</button>
                                    @if($isLive)
                                        <a href="{{ route('storefront.product', $product) }}"
                                           target="_blank"
                                           title="{{ __('View live') }}"
                                           class="shrink-0 text-[var(--hs-neutral-400)] hover:text-[var(--hs-green-700)] transition">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                        </a>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <a href="{{ route('admin.merchants.show', $product->shop_id) }}" class="text-blue-600 hover:underline">
                                    {{ $product->shop->storefront_name }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $product->status === 'published' ? 'green' : 'zinc' }}">
                                    {{ ucfirst($product->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $product->moderation_status === 'active' ? 'green' : ($product->moderation_status === 'flagged' ? 'yellow' : 'red') }}">
                                    {{ str_replace('_', ' ', ucfirst($product->moderation_status)) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    
                                    <flux:menu>
                                        @if($product->moderation_status !== 'flagged')
                                            <flux:menu.item wire:click="flagProduct({{ $product->id }})" wire:confirm="Flag this product?">
                                                Flag
                                            </flux:menu.item>
                                        @endif
                                        
                                        @if($product->moderation_status !== 'admin_unpublished')
                                            <flux:menu.item wire:click="unpublishProduct({{ $product->id }})" class="text-red-600" wire:confirm="Unpublish this product? It will be removed from search.">
                                                Unpublish
                                            </flux:menu.item>
                                        @endif

                                        @if($product->moderation_status !== 'active')
                                            <flux:menu.item wire:click="restoreProduct({{ $product->id }})" wire:confirm="Restore this product to active status?">
                                                Restore (Active)
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center text-zinc-500 py-8">
                                No products found matching your filters.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($this->products->hasPages())
                <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">
                    {{ $this->products->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ── Product Preview Modal ────────────────────────────────────── --}}
    <flux:modal name="product-preview" class="w-full max-w-2xl">
        @if($this->previewProduct)
            @php
                $preview = $this->previewProduct;
                $galleryImages = collect();
                if ($preview->featured_image_path) {
                    $galleryImages->push($preview->featured_image_url);
                }
                foreach ($preview->gallery as $img) {
                    if ($img->image_url !== $preview->featured_image_url) {
                        $galleryImages->push($img->image_url);
                    }
                }
            @endphp

            <flux:heading size="lg">{{ $preview->name }}</flux:heading>

            <div class="mt-1 flex items-center gap-2 flex-wrap">
                <a href="{{ route('admin.merchants.show', $preview->shop_id) }}"
                   wire:navigate
                   class="text-sm font-medium text-[var(--hs-green-700)] hover:underline">
                    {{ $preview->shop->storefront_name }}
                </a>
                <flux:badge color="{{ $preview->status === 'published' ? 'green' : 'zinc' }}" size="sm">
                    {{ ucfirst($preview->status) }}
                </flux:badge>
                <flux:badge
                    color="{{ $preview->moderation_status === 'active' ? 'green' : ($preview->moderation_status === 'flagged' ? 'yellow' : 'red') }}"
                    size="sm">
                    {{ str_replace('_', ' ', ucfirst($preview->moderation_status)) }}
                </flux:badge>
            </div>

            {{-- Gallery --}}
            @if($galleryImages->isNotEmpty())
                <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                    @foreach($galleryImages as $imgUrl)
                        <img src="{{ $imgUrl }}"
                             alt="{{ $preview->name }}"
                             class="h-36 w-36 shrink-0 rounded-lg object-cover border border-[var(--hs-neutral-200)]" />
                    @endforeach
                </div>
            @else
                <div class="mt-4 flex h-36 items-center justify-center rounded-lg bg-[var(--hs-neutral-50)] border border-[var(--hs-neutral-200)]">
                    <svg class="size-10 text-[var(--hs-neutral-300)]" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 0 0 2.25-2.25V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                    </svg>
                </div>
            @endif

            {{-- Categories --}}
            @if($preview->categories->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach($preview->categories as $cat)
                        <span class="inline-flex items-center rounded-full bg-[var(--hs-neutral-100)] px-2.5 py-0.5 text-xs font-medium text-[var(--hs-neutral-700)]">
                            {{ $cat->name }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Description --}}
            @if($preview->description)
                <p class="mt-4 text-sm text-[var(--hs-neutral-700)] leading-relaxed">{{ $preview->description }}</p>
            @endif

            {{-- Variants --}}
            @if($preview->variants->isNotEmpty())
                <div class="mt-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)] mb-2">Variants</p>
                    <div class="divide-y divide-[var(--hs-neutral-100)] rounded-lg border border-[var(--hs-neutral-200)] overflow-hidden">
                        @foreach($preview->variants as $variant)
                            <div class="flex items-center justify-between px-4 py-3 bg-[var(--hs-neutral-0)] text-sm">
                                <div class="min-w-0">
                                    <span class="font-medium text-[var(--hs-neutral-900)]">{{ $variant->sku }}</span>
                                    @if($variant->attributes)
                                        <span class="ml-2 text-[var(--hs-neutral-500)]">
                                            {{ collect($variant->attributes)->map(fn ($v, $k) => "$k: $v")->join(' · ') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="shrink-0 text-right ml-4">
                                    <span class="font-semibold text-[var(--hs-green-700)]">
                                        {{ $variant->currency ?? 'PHP' }} {{ number_format((float) $variant->price, 2) }}
                                    </span>
                                    <span class="text-[var(--hs-neutral-500)] ml-1">
                                        &middot; MOQ {{ $variant->minimum_order_quantity }} {{ $variant->unit }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>

        @else
            <div class="py-12 text-center text-sm text-[var(--hs-neutral-500)]">Loading…</div>
        @endif
    </flux:modal>

</div>
