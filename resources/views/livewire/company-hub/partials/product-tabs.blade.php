@if ($product)
    <div class="mb-6 border-b border-[var(--hs-neutral-200)]">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="{{ route('hub.products.edit', $product) }}" wire:navigate
               class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition {{ request()->routeIs('hub.products.edit') ? 'border-[var(--hs-green-600)] text-[var(--hs-green-700)]' : 'border-transparent text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)] hover:border-[var(--hs-neutral-300)]' }}">
                Basic Info
            </a>

            <a href="{{ route('hub.products.variants', $product) }}" wire:navigate
               class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition {{ request()->routeIs('hub.products.variants') ? 'border-[var(--hs-green-600)] text-[var(--hs-green-700)]' : 'border-transparent text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)] hover:border-[var(--hs-neutral-300)]' }}">
                Manage Variants
            </a>

            <a href="{{ route('hub.products.gallery', $product) }}" wire:navigate
               class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition {{ request()->routeIs('hub.products.gallery') ? 'border-[var(--hs-green-600)] text-[var(--hs-green-700)]' : 'border-transparent text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)] hover:border-[var(--hs-neutral-300)]' }}">
                Gallery
            </a>

            <a href="{{ route('hub.products.documents', $product) }}" wire:navigate
               class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition {{ request()->routeIs('hub.products.documents') ? 'border-[var(--hs-green-600)] text-[var(--hs-green-700)]' : 'border-transparent text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)] hover:border-[var(--hs-neutral-300)]' }}">
                Documents
            </a>
        </nav>
    </div>
@endif
