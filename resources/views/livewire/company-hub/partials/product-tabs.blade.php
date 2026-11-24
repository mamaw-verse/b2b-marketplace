@if ($product)
    <div class="relative mb-6 border-b border-[var(--hs-neutral-200)]">
        <nav
            x-data
            x-init="$nextTick(() => $el.querySelector('[aria-current=page]')?.scrollIntoView({ inline: 'center', block: 'nearest' }))"
            class="flex items-center gap-6 overflow-x-auto scrollbar-hide pr-8"
            aria-label="{{ __('Product navigation') }}"
        >

            {{-- Basic Info --}}
            <a
                href="{{ route('hub.products.edit', $product) }}"
                wire:navigate
                @if (request()->routeIs('hub.products.edit')) aria-current="page" @endif
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
                @if (request()->routeIs('hub.products.variants')) aria-current="page" @endif
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
                @if (request()->routeIs('hub.products.gallery')) aria-current="page" @endif
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
                @if (request()->routeIs('hub.products.documents')) aria-current="page" @endif
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

            @if (config('marketplace.features.product_video_enabled'))
                {{-- Video --}}
                <a
                    href="{{ route('hub.products.video', $product) }}"
                    wire:navigate
                    @if (request()->routeIs('hub.products.video')) aria-current="page" @endif
                    @class([
                        'relative flex items-center gap-2 whitespace-nowrap border-b-2 px-1 pb-3 pt-1 text-sm font-semibold transition-colors',
                        'border-[var(--hs-green-600)] text-[var(--hs-green-700)]' => request()->routeIs('hub.products.video'),
                        'border-transparent text-[var(--hs-neutral-500)] hover:border-[var(--hs-neutral-300)] hover:text-[var(--hs-neutral-800)]' => !request()->routeIs('hub.products.video'),
                    ])
                >
                    <flux:icon name="film" variant="mini" class="shrink-0" />
                    <span>{{ __('Video') }}</span>
                </a>
            @endif

        </nav>

        {{-- Edge fade: hints that more tabs scroll off-screen on narrow viewports --}}
        <div class="pointer-events-none absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-[var(--hs-neutral-0)] to-transparent sm:hidden" aria-hidden="true"></div>
    </div>
@endif
