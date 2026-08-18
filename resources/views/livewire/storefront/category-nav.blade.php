<nav class="space-y-1">
    <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)] mb-3 px-3">Categories</h3>

    <button
        wire:click="selectCategory(null)"
        @class([
            'flex w-full items-center rounded-lg px-3 py-2 text-sm font-medium transition',
            'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border-l-2 border-[var(--hs-green-600)]' => $selectedId === null,
            'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => $selectedId !== null,
        ])
    >
        All Categories
    </button>

    @foreach ($this->categories as $category)
        <button
            wire:click="selectCategory({{ $category->id }})"
            @class([
                'flex w-full items-center rounded-lg px-3 py-2 text-sm font-medium transition',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border-l-2 border-[var(--hs-green-600)]' => $selectedId === $category->id,
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => $selectedId !== $category->id,
            ])
        >
            {{ $category->name }}
        </button>
    @endforeach
</nav>
