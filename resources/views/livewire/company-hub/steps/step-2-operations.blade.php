<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Operation Capacity') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Tell us about your production capabilities and export readiness.') }}</flux:text>
    </div>

    @php
        $categoryCount = $this->categoryGroups->sum(
            fn ($group) => $group->children->isNotEmpty() ? $group->children->count() : 1,
        );
        $categoryError = $errors->first('form.product_categories');
    @endphp

    <section
        x-data="{
            selected: $wire.entangle('form.product_categories'),
            search: '',
            matches(name) {
                return name.toLowerCase().includes(this.search.trim().toLowerCase());
            },
            clearAll() {
                this.selected = [];
            },
        }"
        aria-labelledby="product-categories-title"
        @if ($categoryError) aria-describedby="product-categories-error" @endif
        class="space-y-4"
        data-product-categories
    >
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <h2 id="product-categories-title" class="text-lg font-semibold text-[var(--hs-neutral-900)]">
                        {{ __('Product categories') }}
                    </h2>
                    <span class="text-sm font-medium text-[var(--hs-neutral-700)]" aria-live="polite" aria-atomic="true">
                        <span x-text="selected.length"></span> <span x-text="selected.length === 1 ? '{{ __('selected') }}' : '{{ __('selected') }}'"></span>
                    </span>
                </div>
                <p class="mt-1 text-sm leading-relaxed text-[var(--hs-neutral-700)]">
                    {{ __('Select the product categories you sell or manufacture.') }}
                </p>
                @if ($categoryError)
                    <p id="product-categories-error" role="alert" class="mt-2 text-sm font-medium text-[var(--hs-danger-700)]">
                        {{ $categoryError }}
                    </p>
                @endif
            </div>

            <button
                type="button"
                x-show="selected.length > 0"
                x-cloak
                x-on:click="clearAll()"
                class="hidden min-h-11 shrink-0 items-center rounded-[var(--hs-radius-sm)] px-2 text-sm font-medium text-[var(--hs-green-700)] underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--hs-green-600)] sm:inline-flex"
            >
                {{ __('Clear all') }}
            </button>
        </div>

        @if ($categoryCount > 12)
            <div class="relative">
                <flux:icon.magnifying-glass class="pointer-events-none absolute inset-y-0 start-3 my-auto size-5 text-[var(--hs-neutral-600)]" />
                <input
                    type="search"
                    x-model.debounce.150ms="search"
                    placeholder="{{ __('Search categories') }}"
                    aria-label="{{ __('Search categories') }}"
                    class="h-11 w-full appearance-none rounded-[var(--hs-radius-sm)] border border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] ps-10 pe-3 text-base text-[var(--hs-neutral-900)] shadow-xs placeholder:text-[var(--hs-neutral-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2 sm:text-sm"
                />
            </div>
        @endif

        @if ($this->categoryGroups->isEmpty())
            <p class="text-sm text-[var(--hs-neutral-700)]">{{ __('No categories are available yet.') }}</p>
        @else
            <div class="space-y-6 lg:space-y-8">
                @foreach ($this->categoryGroups as $group)
                    <fieldset
                        wire:key="category-group-{{ $group->id }}"
                        @if ($categoryError) aria-invalid="true" aria-describedby="product-categories-error" @endif
                        class="min-w-0 rounded-[var(--hs-radius-card)] border bg-[var(--hs-neutral-0)] p-3 sm:p-4 {{ $categoryError ? 'border-[var(--hs-danger-500)]' : 'border-[var(--hs-neutral-300)]' }}"
                    >
                        <legend class="px-1 text-[13px] font-semibold uppercase tracking-[0.04em] text-[var(--hs-neutral-700)]">
                            {{ $group->name }}
                        </legend>

                        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-[repeat(auto-fill,minmax(240px,1fr))]">
                            @foreach ($group->children->isNotEmpty() ? $group->children : collect([$group]) as $category)
                                <label
                                    wire:key="category-{{ $category->id }}"
                                    x-show="matches(@js($category->name))"
                                    x-transition.opacity.duration.150ms
                                    x-bind:aria-disabled="$el.querySelector('input')?.disabled ? 'true' : null"
                                    title="{{ $category->name }}"
                                    class="group flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-[var(--hs-neutral-400)] bg-[var(--hs-neutral-0)] px-3.5 py-2.5 text-[var(--hs-neutral-900)] shadow-xs transition duration-150 motion-reduce:transition-none hover:border-[var(--hs-neutral-500)] hover:bg-[var(--hs-neutral-100)] has-[:checked]:border-[var(--hs-green-600)] has-[:checked]:bg-[var(--hs-green-50)] has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-[var(--hs-green-600)] has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-50"
                                    data-onboarding-category
                                >
                                    <span class="relative flex size-[22px] shrink-0 items-center justify-center self-center">
                                        <input
                                            type="checkbox"
                                            wire:model="form.product_categories"
                                            value="{{ $category->id }}"
                                            class="peer size-5 appearance-none rounded-[5px] border-2 border-[var(--hs-neutral-500)] bg-transparent transition duration-150 motion-reduce:transition-none checked:border-[var(--hs-green-600)] checked:bg-[var(--hs-green-600)] focus:outline-none disabled:cursor-not-allowed"
                                        />
                                        <svg class="pointer-events-none absolute size-3.5 text-white opacity-0 transition-opacity duration-150 motion-reduce:transition-none peer-checked:opacity-100" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M3.5 8.25 6.5 11 12.5 4.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <span class="line-clamp-2 min-w-0 text-sm leading-[1.3] group-has-[:checked]:font-semibold">
                                        {{ $category->name }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>
        @endif

        @if ($categoryCount > 10)
            <div
                x-show="selected.length > 0"
                x-cloak
                class="sticky bottom-0 z-10 -mx-3 flex min-h-12 items-center justify-between gap-3 border-t border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-0)] px-4 py-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] shadow-[var(--hs-shadow-md)] sm:hidden"
            >
                <span class="text-sm font-semibold text-[var(--hs-neutral-900)]" aria-live="polite">
                    <span x-text="selected.length"></span> {{ __('selected') }}
                </span>
                <button
                    type="button"
                    x-on:click="clearAll()"
                    class="min-h-11 rounded-[var(--hs-radius-sm)] px-2 text-sm font-medium text-[var(--hs-green-700)] underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--hs-green-600)]"
                >
                    {{ __('Clear') }}
                </button>
            </div>
        @endif
    </section>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <flux:input
            wire:model.blur="form.fda_lto_number"
            :label="__('FDA LTO Number')"
            type="text"
            :description="__('Food and Drug Administration License to Operate number.')"
        />

        <flux:input
            wire:model.blur="form.average_production_lead_time"
            :label="__('Average Production Lead Time (days)')"
            type="number"
            min="1"
            :description="__('How many days on average to fulfill an order.')"
        />
    </div>

    <flux:select wire:model.blur="form.export_readiness_status" :label="__('Export Readiness')" placeholder="{{ __('Select status...') }}">
        <flux:select.option value="never_exported">{{ __('Never Exported') }}</flux:select.option>
        <flux:select.option value="exporting_regionally">{{ __('Exporting Regionally') }}</flux:select.option>
        <flux:select.option value="exporting_globally">{{ __('Exporting Globally') }}</flux:select.option>
    </flux:select>
</div>
