@props(['shop'])
@php
$initials = strtoupper(mb_substr($shop->storefront_name, 0, 2));
$hasCerts = $shop->relationLoaded('certificates') && $shop->certificates->count() > 0;
$activeCert = $hasCerts ? $shop->certificates->first(fn($c) => $c->status === 'active') : null;
$categories = $shop->relationLoaded('categories') ? $shop->categories->take(3) : collect();
@endphp
<a href="{{ route('storefront.company', $shop) }}" wire:navigate {{ $attributes->merge(['class' => 'group block rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-5 shadow-[var(--hs-shadow-sm)] transition hover:shadow-[var(--hs-shadow-md)] hover:border-[var(--hs-green-200)]']) }}>
    <div class="flex items-start gap-3">
        <x-hs-avatar :initials="$initials" size="md" />
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <h3 class="truncate font-semibold text-[var(--hs-neutral-900)] group-hover:text-[var(--hs-green-700)]">
                    {{ $shop->storefront_name }}
                </h3>
                @if($activeCert)
                    <x-hs-badge variant="verified" label="Verified" />
                @endif
            </div>
            <p class="mt-0.5 text-sm text-[var(--hs-neutral-500)] truncate">
                {{ $shop->city }}{{ $shop->city && $shop->province ? ', ' : '' }}{{ $shop->province }}
            </p>
        </div>
    </div>

    {{-- Category tags --}}
    @if($categories->isNotEmpty())
        <div class="mt-3 flex flex-wrap gap-1.5">
            @foreach($categories as $category)
                <x-hs-badge variant="tag" :label="$category->name" />
            @endforeach
        </div>
    @endif

    {{-- Footer info --}}
    <div class="mt-4 flex items-center gap-3 text-xs text-[var(--hs-neutral-500)]">
        @if($shop->average_production_lead_time)
            <span class="inline-flex items-center gap-1">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                {{ $shop->average_production_lead_time }}d lead
            </span>
        @endif
        @if($shop->export_readiness_status && $shop->export_readiness_status !== 'not_ready')
            <x-hs-badge variant="gold" :label="ucfirst(str_replace('_', ' ', $shop->export_readiness_status))" />
        @endif
    </div>
</a>
