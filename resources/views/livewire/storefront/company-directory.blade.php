<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">Company Directory</h1>
        <p class="mt-1 text-[var(--hs-neutral-500)]">Discover verified halal-certified businesses</p>
    </div>

    <div class="flex flex-col gap-8 lg:flex-row">
        {{-- Sidebar: Category Filter --}}
        <aside class="w-full lg:w-56 shrink-0">
            <nav class="space-y-1">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)] mb-3 px-3">Categories</h3>

                <button
                    wire:click="$set('categoryId', null)"
                    @class([
                        'flex w-full items-center rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border-l-2 border-[var(--hs-green-600)]' => $categoryId === null,
                        'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => $categoryId !== null,
                    ])
                >
                    All Categories
                </button>

                @foreach ($this->categories as $category)
                    <button
                        wire:click="$set('categoryId', {{ $category->id }})"
                        @class([
                            'flex w-full items-center rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border-l-2 border-[var(--hs-green-600)]' => $categoryId === $category->id,
                            'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => $categoryId !== $category->id,
                        ])
                    >
                        {{ $category->name }}
                    </button>
                @endforeach
            </nav>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1">
            {{-- Search --}}
            <div class="mb-6">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input
                        wire:model.live.debounce.300ms="query"
                        type="search"
                        placeholder="Search companies..."
                        class="w-full rounded-[var(--hs-radius-pill)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:border-transparent shadow-[var(--hs-shadow-sm)]"
                    />
                </div>
            </div>

            {{-- Company Grid --}}
            @if ($this->shops->count() > 0)
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->shops as $shop)
                        <x-hs-company-card :shop="$shop" />
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $this->shops->links() }}
                </div>
            @else
                <x-hs-empty-state icon="search" title="No companies found" subtitle="Try adjusting your search or category filter." />
            @endif
        </div>
    </div>
</div>
