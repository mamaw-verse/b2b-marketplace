<div>
    @include('livewire.company-hub.partials.product-tabs')

    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                {{ $product ? __('Edit Product') : __('Create Product') }}
            </h1>
        </div>

        <div class="p-6">
            <form wire:submit="save" class="space-y-6">

                {{-- Product Name --}}
                <flux:input
                    wire:model="name"
                    label="{{ __('Product Name') }}"
                    placeholder="{{ __('Enter product name') }}"
                    required
                />
                @error('name') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                {{-- Description --}}
                <flux:textarea
                    wire:model="description"
                    label="{{ __('Description') }}"
                    placeholder="{{ __('Describe your product...') }}"
                    rows="4"
                />
                @error('description') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                {{-- ── Categories (toggle pills via Livewire action) ─────────── --}}
                <div>
                    <label class="block text-sm font-semibold text-[var(--hs-neutral-700)] mb-1.5">{{ __('Categories') }}</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->categories as $id => $name)
                            <button
                                type="button"
                                wire:click="toggleCategory({{ $id }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[var(--hs-radius-pill)] text-sm font-medium border cursor-pointer transition {{ in_array($id, $category_ids, false) ? 'bg-[var(--hs-green-600)] border-[var(--hs-green-600)] text-white' : 'bg-[var(--hs-neutral-0)] border-[var(--hs-neutral-200)] text-[var(--hs-neutral-600)] hover:border-[var(--hs-green-300)]' }}"
                            >
                                @if (in_array($id, $category_ids, false))
                                    <flux:icon name="check" variant="micro" class="size-3.5" />
                                @endif
                                {{ $name }}
                            </button>
                        @endforeach
                    </div>
                </div>
                @error('category_ids') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                {{-- ── Featured Image ────────────────────────────────────────── --}}
                <div>
                    <label class="block text-sm font-medium text-[var(--hs-neutral-700)]">{{ __('Featured Image') }}</label>
                    @if ($product?->featured_image_path && ! $featured_image)
                        <p class="mt-2 mb-2 text-sm text-[var(--hs-neutral-500)]">{{ __('Current image set. Upload a new one to replace it.') }}</p>
                    @endif
                    <input type="file" wire:model="featured_image" accept="image/*" class="mt-1 block w-full text-sm text-[var(--hs-neutral-500)] file:mr-4 file:rounded-[var(--hs-radius-pill)] file:border-0 file:bg-[var(--hs-green-50)] file:px-4 file:py-2 file:text-sm file:font-medium file:text-[var(--hs-green-700)] hover:file:bg-[var(--hs-green-100)]" />
                    @error('featured_image') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror

                    @if ($featured_image && method_exists($featured_image, 'temporaryUrl'))
                        <div class="mt-3">
                            <img src="{{ $featured_image->temporaryUrl() }}" alt="{{ __('Preview') }}" class="h-32 w-32 rounded-[var(--hs-radius-card)] object-cover border border-[var(--hs-neutral-200)]" />
                        </div>
                    @endif
                </div>

                {{-- ── Product Options (Variant Attribute Schema Editor) ──────── --}}
                <div class="border-t border-[var(--hs-neutral-100)] pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-[var(--hs-neutral-900)]">{{ __('Product Options') }}</h2>
                            <p class="text-sm text-[var(--hs-neutral-500)] mt-0.5">
                                {{ __('Define the attributes buyers will see per variant (e.g. Cut, Weight, Packaging).') }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            @if (! empty($category_ids))
                                <flux:modal.trigger name="confirm-reload-schema">
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        icon="arrow-path"
                                    >
                                        {{ __('Reload from Categories') }}
                                    </flux:button>
                                </flux:modal.trigger>
                            @endif
                            <flux:button
                                type="button"
                                variant="outline"
                                size="sm"
                                icon="plus"
                                wire:click="addAttribute"
                            >
                                {{ __('Add Option') }}
                            </flux:button>
                        </div>
                    </div>

                    @if (! empty($variantAttributesSchema))
                        <div class="space-y-2">
                            @foreach ($variantAttributesSchema as $key => $attr)
                                <div
                                    x-data="{ type: '{{ $attr['type'] ?? 'select' }}' }"
                                    class="flex flex-wrap lg:flex-nowrap items-end gap-3 p-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)]"
                                >
                                    {{-- Column headers on first row only --}}
                                    @if ($loop->first)
                                        {{-- We use a hidden-on-subsequent-rows trick but since we're in a loop, column headers render above the flex --}}
                                    @endif

                                    {{-- Label --}}
                                    <div class="w-64 shrink-0">
                                        @if ($loop->first)
                                            <label class="block text-xs font-medium text-[var(--hs-neutral-500)] mb-1">{{ __('Label') }}</label>
                                        @endif
                                        <flux:input
                                            wire:model="variantAttributesSchema.{{ $key }}.label"
                                            placeholder="{{ __('e.g. Cut') }}"
                                            size="sm"
                                        />
                                    </div>

                                    {{-- Type --}}
                                    <div class="w-32 shrink-0">
                                        @if ($loop->first)
                                            <label class="block text-xs font-medium text-[var(--hs-neutral-500)] mb-1">{{ __('Type') }}</label>
                                        @endif
                                        <flux:select
                                            wire:model="variantAttributesSchema.{{ $key }}.type"
                                            x-model="type"
                                            size="sm"
                                        >
                                            <flux:select.option value="select">{{ __('Select') }}</flux:select.option>
                                            <flux:select.option value="text">{{ __('Text') }}</flux:select.option>
                                            <flux:select.option value="number">{{ __('Number') }}</flux:select.option>
                                        </flux:select>
                                    </div>

                                    {{-- Choices (disabled unless type is select) --}}
                                    <div class="flex-1 min-w-[180px]">
                                        @if ($loop->first)
                                            <label class="block text-xs font-medium text-[var(--hs-neutral-500)] mb-1">{{ __('Choices (comma-separated)') }}</label>
                                        @endif
                                        <flux:input
                                            wire:model="variantAttributesSchema.{{ $key }}.options"
                                            placeholder="{{ __('e.g. Whole, Half, Quarter') }}"
                                            size="sm"
                                            x-bind:disabled="type !== 'select'"
                                            x-bind:class="type !== 'select' ? 'opacity-40 cursor-not-allowed' : ''"
                                        />
                                    </div>

                                    {{-- Required toggle --}}
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <input
                                            type="checkbox"
                                            wire:model="variantAttributesSchema.{{ $key }}.required"
                                            class="rounded border-[var(--hs-neutral-300)] text-[var(--hs-green-600)] focus:ring-[var(--hs-green-400)]"
                                        />
                                        <span class="text-xs text-[var(--hs-neutral-600)]">{{ __('Req.') }}</span>
                                    </div>

                                    {{-- Remove --}}
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        wire:click="confirmRemoveAttribute('{{ $key }}')"
                                        class="text-[var(--hs-danger-500)] hover:bg-[var(--hs-danger-50)] shrink-0"
                                    />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center gap-3 rounded-[var(--hs-radius-card)] border border-dashed border-[var(--hs-neutral-300)] px-5 py-6 text-center">
                            <flux:icon name="squares-2x2" variant="outline" class="size-5 text-[var(--hs-neutral-400)] mx-auto" />
                            <p class="text-sm text-[var(--hs-neutral-500)]">
                                @if (empty($category_ids))
                                    {{ __("Select a category above — options will be populated automatically from the category's schema.") }}
                                @else
                                    {{ __('No options yet. Click') }} <span class="font-medium">"{{ __('Add Option') }}"</span> {{ __('or') }} <span class="font-medium">"{{ __('Reload from Categories') }}"</span> {{ __('to get started.') }}
                                @endif
                            </p>
                        </div>
                    @endif
                </div>

                {{-- ── Form Actions ──────────────────────────────────────────── --}}
                <div class="flex flex-wrap items-center gap-3 border-t border-[var(--hs-neutral-100)] pt-6">
                    <flux:button type="submit" variant="primary">
                        {{ $product ? __('Update Product') : __('Create Product') }}
                    </flux:button>

                    @if ($product)
                        @if ($product->status === 'archived')
                            <flux:button type="button" variant="filled" wire:click="publish" wire:loading.attr="disabled">
                                {{ __('Publish') }}
                            </flux:button>
                        @else
                            <flux:button type="button" variant="ghost" wire:click="archive" wire:loading.attr="disabled">
                                {{ __('Archive') }}
                            </flux:button>
                        @endif
                    @endif

                    <flux:button variant="ghost" href="{{ route('hub.products.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>

    <x-hs-confirm
        name="confirm-reload-schema"
        title="{{ __('Reload Product Options') }}"
        message="{{ __("This will replace all product options with the new category's defaults. Continue?") }}"
        variant="warning"
        confirmText="{{ __('Reload') }}"
    >
        <x-slot name="action">
            <flux:button variant="filled" wire:click="reloadSchemaFromCategories" wire:loading.attr="disabled">
                {{ __('Reload') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    <x-hs-confirm
        name="confirm-remove-attribute"
        title="{{ __('Remove Product Option') }}"
        message="{{ __('Remove this product option?') }}"
        variant="danger"
        confirmText="{{ __('Remove') }}"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="removeAttribute" wire:loading.attr="disabled">
                {{ __('Remove') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
