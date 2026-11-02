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
                <x-hs-wysiwyg
                    wire-model="description"
                    label="{{ __('Description') }}"
                    description="{{ __('Do not include contact information such as phone numbers, email addresses, social media links, or website URLs.') }}"
                    placeholder="{{ __('Describe your product...') }}"
                />
                @error('description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror

                {{-- ── Categories (toggle pills via Livewire action) ─────────── --}}
                <div>
                    <label class="block text-sm font-semibold text-[var(--hs-neutral-700)] mb-1.5">{{ __('Categories') }}</label>
                    <div class="space-y-3">
                        @foreach ($this->categoryGroups as $group)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)] mb-1.5">{{ $group->name }}</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($group->children->isNotEmpty() ? $group->children : collect([$group]) as $category)
                                        <button
                                            type="button"
                                            wire:key="category-{{ $category->id }}"
                                            wire:click="toggleCategory({{ $category->id }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[var(--hs-radius-pill)] text-sm font-medium border cursor-pointer transition {{ in_array($category->id, $category_ids, false) ? 'bg-[var(--hs-green-600)] border-[var(--hs-green-600)] text-white' : 'bg-[var(--hs-neutral-0)] border-[var(--hs-neutral-200)] text-[var(--hs-neutral-600)] hover:border-[var(--hs-green-300)]' }}"
                                        >
                                            @if (in_array($category->id, $category_ids, false))
                                                <flux:icon name="check" variant="micro" class="size-3.5" />
                                            @endif
                                            {{ $category->name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @error('category_ids') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                {{-- ── Featured Image ────────────────────────────────────────── --}}
                <div>
                    <label class="block text-sm font-medium text-[var(--hs-neutral-700)]">{{ __('Featured Image') }}</label>
                    <p class="mb-1.5 text-xs text-[var(--hs-neutral-500)]">{{ __('Images must not contain contact information such as phone numbers, email addresses, social media links, or website URLs.') }}</p>

                    @php
                        $existingImage = (! $removeFeaturedImage && $product?->featured_image_path)
                            ? $product->featuredImageUrl
                            : null;
                        $pendingImage  = $featured_image instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                            ? $featured_image->temporaryUrl()
                            : null;
                        $previewUrl    = $pendingImage ?? $existingImage;
                    @endphp

                    @if ($previewUrl)
                        {{-- Preview state: show image with remove/replace button --}}
                        <div class="inline-block group">
                            <div class="relative inline-block">
                                <img
                                    src="{{ $previewUrl }}"
                                    alt="{{ __('Featured image preview') }}"
                                    class="h-40 w-40 rounded-[var(--hs-radius-card)] object-cover border border-[var(--hs-neutral-200)]"
                                />
                                <button
                                    type="button"
                                    wire:click="clearFeaturedImage"
                                    aria-label="{{ __('Remove image') }}"
                                    class="absolute top-1.5 right-1.5 flex size-6 items-center justify-center rounded-full bg-[var(--hs-danger-500)] text-white transition sm:opacity-0 sm:group-hover:opacity-100 hover:bg-[var(--hs-danger-700)]"
                                >
                                    <flux:icon name="x-mark" variant="mini" class="size-3.5" />
                                </button>
                            </div>
                            <p class="mt-1.5 text-xs text-[var(--hs-neutral-500)]">
                                {{ __('Click ✕ to remove and replace.') }}
                            </p>
                        </div>
                    @else
                        {{-- Dropzone state --}}
                        <x-hs-dropzone
                            input-id="featured-image-input"
                            wire-model="featured_image"
                            accept="image/jpeg,image/png,image/webp"
                            :multiple="false"
                            hint="{{ __('JPEG, PNG, WebP — up to 5MB') }}"
                            loading-target="featured_image"
                        >
                            @error('featured_image') <p class="mt-2 text-sm text-[var(--hs-danger-700)]">{{ $message }}</p> @enderror
                        </x-hs-dropzone>
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
                                    >
                                        <span class="sr-only">{{ __('Remove') }}</span>
                                    </flux:button>
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
        message="{{ __('This will replace all product options with the selected categories defaults. Continue?') }}"
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

    {{-- Subscription Required --}}
    <flux:modal name="subscription-required" class="w-full sm:w-auto md:w-[420px] !p-0">
        <div class="flex flex-col">
            <div class="px-4 sm:px-6 py-5">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 flex items-center justify-center size-10 rounded-full text-yellow-600 bg-yellow-50">
                        <flux:icon name="exclamation-triangle" variant="mini" class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-[var(--hs-neutral-900)]">{{ __('Active Subscription Required') }}</h3>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">
                            {{ __('You need an active subscription to publish products and make them visible to buyers on the marketplace.') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="shrink-0 border-t border-[var(--hs-neutral-100)] px-4 sm:px-6 py-4">
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" href="{{ route('hub.subscription') }}" wire:navigate>
                        {{ __('Go to Subscription') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>
