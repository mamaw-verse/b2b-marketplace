@if ($product)
    <div class="mb-6 border-b border-[var(--hs-neutral-200)]">
        <nav
            class="flex items-center gap-6 overflow-x-auto"
            aria-label="{{ __('Product navigation') }}"
        >

            {{-- Basic Info --}}
            <a
                href="{{ route('hub.products.edit', $product) }}"
                wire:navigate
                @class([
                    'relative flex items-center gap-2 whitespace-nowrap border-b-2 px-1 pb-3 pt-1 text-sm font-semibold transition-colors',
                    'border-[var(--hs-green-600)] text-[var(--hs-green-700)]' => request()->routeIs('hub.products.edit'),
                    'border-transparent text-[var(--hs-neutral-500)] hover:border-[var(--hs-neutral-300)] hover:text-[var(--hs-neutral-800)]' => !request()->routeIs('hub.products.edit'),
                ])
            >
                <flux:icon
                    name="information-circle"
                    variant="mini"
                    class="shrink-0"
                />
                <span>{{ __('Basic Info') }}</span>
            </a>

            {{-- Variants --}}
            <a
                href="{{ route('hub.products.variants', $product) }}"
                wire:navigate
                @class([
                    'relative flex items-center gap-2 whitespace-nowrap border-b-2 px-1 pb-3 pt-1 text-sm font-semibold transition-colors',
                    'border-[var(--hs-green-600)] text-[var(--hs-green-700)]' => request()->routeIs('hub.products.variants'),
                    'border-transparent text-[var(--hs-neutral-500)] hover:border-[var(--hs-neutral-300)] hover:text-[var(--hs-neutral-800)]' => !request()->routeIs('hub.products.variants'),
                ])
            >
                <flux:icon
                    name="squares-2x2"
                    variant="mini"
                    class="shrink-0"
                />
                <span>{{ __('Variants') }}</span>
            </a>

            {{-- Gallery --}}
            <a
                href="{{ route('hub.products.gallery', $product) }}"
                wire:navigate
                @class([
                    'relative flex items-center gap-2 whitespace-nowrap border-b-2 px-1 pb-3 pt-1 text-sm font-semibold transition-colors',
                    'border-[var(--hs-green-600)] text-[var(--hs-green-700)]' => request()->routeIs('hub.products.gallery'),
                    'border-transparent text-[var(--hs-neutral-500)] hover:border-[var(--hs-neutral-300)] hover:text-[var(--hs-neutral-800)]' => !request()->routeIs('hub.products.gallery'),
                ])
            >
                <flux:icon
                    name="photo"
                    variant="mini"
                    class="shrink-0"
                />
                <span>{{ __('Gallery') }}</span>
            </a>

            {{-- Documents --}}
            <a
                href="{{ route('hub.products.documents', $product) }}"
                wire:navigate
                @class([
                    'relative flex items-center gap-2 whitespace-nowrap border-b-2 px-1 pb-3 pt-1 text-sm font-semibold transition-colors',
                    'border-[var(--hs-green-600)] text-[var(--hs-green-700)]' => request()->routeIs('hub.products.documents'),
                    'border-transparent text-[var(--hs-neutral-500)] hover:border-[var(--hs-neutral-300)] hover:text-[var(--hs-neutral-800)]' => !request()->routeIs('hub.products.documents'),
                ])
            >
                <flux:icon
                    name="document-text"
                    variant="mini"
                    class="shrink-0"
                />
                <span>{{ __('Documents') }}</span>
            </a>

        </nav>
    </div>
@endif