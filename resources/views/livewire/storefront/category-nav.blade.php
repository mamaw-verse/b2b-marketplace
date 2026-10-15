<nav class="space-y-1">
    <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)] mb-3 px-3">{{ __('Categories') }}</h3>

    <button
        wire:click="selectCategory(null)"
        @class([
            'flex w-full items-center rounded-lg px-3 py-2 text-sm font-medium transition',
            'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border-l-2 border-[var(--hs-green-600)]' => $selectedId === null,
            'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => $selectedId !== null,
        ])
    >
        {{ __('All Categories') }}
    </button>

    @foreach ($this->categoryGroups as $group)
        <div wire:key="category-group-{{ $group->id }}">
            <button
                wire:click="selectCategory({{ $group->id }})"
                @class([
                    'flex w-full items-center rounded-lg px-3 py-2 text-sm font-semibold transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border-l-2 border-[var(--hs-green-600)]' => $selectedId === $group->id,
                    'text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-100)]' => $selectedId !== $group->id,
                ])
            >
                {{ $group->name }}
            </button>

            @foreach ($group->children as $category)
                <button
                    wire:key="category-{{ $category->id }}"
                    wire:click="selectCategory({{ $category->id }})"
                    @class([
                        'flex w-full items-center rounded-lg py-1.5 pl-7 pr-3 text-sm font-medium transition',
                        'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border-l-2 border-[var(--hs-green-600)]' => $selectedId === $category->id,
                        'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => $selectedId !== $category->id,
                    ])
                >
                    {{ $category->name }}
                </button>
            @endforeach
        </div>
    @endforeach
</nav>
