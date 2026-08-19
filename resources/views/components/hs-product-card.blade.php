@props(['product'])
@php
$cheapestVariant = $product->relationLoaded('variants') ? $product->variants->sortBy('price')->first() : null;
$shopName = $product->relationLoaded('shop') && $product->shop ? $product->shop->storefront_name : null;
$bgColors = ['bg-[var(--hs-green-50)]', 'bg-[var(--hs-gold-100)]', 'bg-[var(--hs-neutral-50)]', 'bg-[var(--hs-info-50)]'];
$bgColor = $bgColors[$product->id % count($bgColors)];
@endphp
<a href="{{ route('storefront.product', $product) }}" wire:navigate {{ $attributes->merge(['class' => 'group block rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] overflow-hidden shadow-[var(--hs-shadow-sm)] transition hover:shadow-[var(--hs-shadow-md)] hover:border-[var(--hs-green-200)]']) }}>
    {{-- Image / Placeholder --}}
    <div class="relative aspect-[4/3] {{ $bgColor }} flex items-center justify-center overflow-hidden">
        @if($product->featured_image_path)
            <img src="{{ $product->featured_image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover" />
        @else
            <svg class="size-12 text-[var(--hs-neutral-300)]" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 0 0 2.25-2.25V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
        @endif
        <div class="absolute top-2 left-2">
            <x-hs-badge variant="halal" label="Halal" />
        </div>
    </div>

    {{-- Content --}}
    <div class="p-4">
        @if($shopName)
            <p class="text-xs text-[var(--hs-neutral-500)] mb-1 truncate">{{ $shopName }}</p>
        @endif
        <h3 class="font-semibold text-[var(--hs-neutral-900)] group-hover:text-[var(--hs-green-700)] line-clamp-2 text-sm">
            {{ $product->name }}
        </h3>

        {{-- Price & MOQ --}}
        @if($cheapestVariant)
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-sm font-bold text-[var(--hs-green-700)]">
                    {{ $cheapestVariant->currency ?? 'PHP' }} {{ number_format((float)$cheapestVariant->price, 2) }}
                </span>
                <span class="text-xs text-[var(--hs-neutral-500)]">
                    MOQ: {{ $cheapestVariant->minimum_order_quantity }} {{ $cheapestVariant->unit ?? 'pcs' }}
                </span>
            </div>
        @endif
    </div>
</a>
