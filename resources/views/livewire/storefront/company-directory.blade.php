<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-[var(--hs-neutral-900)] font-[var(--hs-font-display)]">{{ __('Business Directory') }}</h1>
        <p class="mt-1 text-[var(--hs-neutral-500)]">{{ __('Discover verified halal-certified businesses') }}</p>
    </div>

    {{-- ── Sponsored Content — Search Results Placement ────── --}}
    <div class="mb-8">
        <livewire:shared.sponsored-content-banner placement="search_results" />
    </div>

    <div class="flex flex-col gap-8 lg:flex-row">
        {{-- Sidebar: Category Filter --}}
        <aside class="w-full lg:w-56 shrink-0" x-data="{ open: false }">
            <button
                @click="open = !open"
                class="lg:hidden flex items-center justify-between w-full py-2 px-3 rounded-lg text-sm font-semibold text-[var(--hs-neutral-700)] bg-[var(--hs-neutral-100)] hover:bg-[var(--hs-neutral-200)] transition mb-1"
                :aria-expanded="open"
            >
                <span>{{ __('Categories') }}</span>
                <svg class="size-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </button>
            <nav class="space-y-1 lg:block" :class="open ? 'block' : 'hidden'" x-cloak>
                <h3 class="hidden lg:block text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)] mb-3 px-3">{{ __('Categories') }}</h3>

                <button
                    wire:click="$set('categoryId', null)"
                    @class([
                        'flex w-full items-center rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200',
                        'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-inset ring-[var(--hs-green-200)]' => $categoryId === null,
                        'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => $categoryId !== null,
                    ])
                >
                    @if ($categoryId === null)
                        <span class="mr-2 flex size-1.5 shrink-0 rounded-full bg-[var(--hs-green-500)]" aria-hidden="true"></span>
                    @endif
                    {{ __('All Categories') }}
                </button>

                @foreach ($this->categoryGroups as $group)
                    <div wire:key="category-group-{{ $group->id }}" class="pt-2 first:pt-0">
                        <button
                            wire:click="$set('categoryId', {{ $group->id }})"
                            @class([
                                'flex w-full items-center rounded-lg px-3 py-2 text-sm font-semibold transition-all duration-200',
                                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-inset ring-[var(--hs-green-200)]' => $categoryId === $group->id,
                                'text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-100)]' => $categoryId !== $group->id,
                            ])
                        >
                            @if ($categoryId === $group->id)
                                <span class="mr-2 flex size-1.5 shrink-0 rounded-full bg-[var(--hs-green-500)]" aria-hidden="true"></span>
                            @endif
                            {{ $group->name }}
                        </button>

                        @if ($group->children->isNotEmpty())
                            <div class="mt-1 space-y-1">
                                @foreach ($group->children as $category)
                                    <button
                                        wire:key="category-{{ $category->id }}"
                                        wire:click="$set('categoryId', {{ $category->id }})"
                                        @class([
                                            'flex w-full items-center rounded-lg py-1.5 pl-8 pr-3 text-sm font-medium transition-all duration-200',
                                            'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-inset ring-[var(--hs-green-200)]' => $categoryId === $category->id,
                                            'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => $categoryId !== $category->id,
                                        ])
                                    >
                                        {{ $category->name }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </nav>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1">
            {{-- Search & Sort --}}
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input
                        wire:model.live.debounce.300ms="query"
                        type="search"
                        placeholder="{{ __('Search businesses...') }}"
                        class="w-full rounded-[var(--hs-radius-pill)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:border-transparent shadow-[var(--hs-shadow-sm)]"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <label for="sort-by" class="text-xs font-medium text-[var(--hs-neutral-500)] whitespace-nowrap">{{ __('Sort by') }}</label>
                    <select
                        id="sort-by"
                        wire:model.live="sortBy"
                        class="rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-3 py-2 text-sm text-[var(--hs-neutral-700)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:border-transparent"
                    >
                        <option value="latest">{{ __('Latest') }}</option>
                        <option value="rating">{{ __('Highest Rated') }}</option>
                    </select>
                </div>
            </div>

            {{-- Business Grid --}}
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
                <x-hs-empty-state icon="search" title="{{ __('No businesses found') }}" subtitle="{{ __('Try adjusting your search or category filter.') }}" />
            @endif
        </div>
    </div>
</div>
