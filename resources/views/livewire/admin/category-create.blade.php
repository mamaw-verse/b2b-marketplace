<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Create Category') }}</h1>
        <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Define a new category and its variant attribute schema.') }}</p>
    </div>

    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <form wire:submit="save" class="space-y-6 p-6">
            <div>
                <flux:input
                    wire:model.live.debounce.300ms="name"
                    label="{{ __('Name') }}"
                    placeholder="{{ __('e.g. Bakery Ingredients') }}"
                    required
                />
                @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            @if ($name)
                <div>
                    <p class="text-xs text-[var(--hs-neutral-500)] mb-1">{{ __('Slug preview:') }}</p>
                    <code class="rounded-[var(--hs-radius-tag)] bg-[var(--hs-neutral-100)] px-2 py-0.5 text-xs font-mono text-[var(--hs-neutral-700)]">{{ \Illuminate\Support\Str::slug($name) }}</code>
                </div>
            @endif

            <div>
                <flux:textarea
                    wire:model="description"
                    label="{{ __('Description') }}"
                    rows="3"
                    placeholder="{{ __('Optional description...') }}"
                />
                @error('description') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <flux:select wire:model.live="parent_id" label="{{ __('Parent Group') }}">
                    <flux:select.option value="">{{ __('None — this is a top-level group') }}</flux:select.option>
                    @foreach ($this->parents as $parent)
                        <flux:select.option value="{{ $parent->id }}">{{ $parent->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                @error('parent_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-[var(--hs-neutral-500)]">
                    {{ __('Leave empty to create a top-level group, or pick a group to create a subcategory sellers can select.') }}
                </p>
            </div>

            <div class="max-w-40">
                <flux:input
                    wire:model="sort_order"
                    type="number"
                    min="0"
                    label="{{ __('Sort Order') }}"
                    placeholder="0"
                />
                @error('sort_order') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-[var(--hs-neutral-500)]">
                    {{ __('Lower numbers appear first among siblings.') }}
                </p>
            </div>

            {{-- Product Options (Variant Attribute Schema) --}}
            <div class="border-t border-[var(--hs-neutral-100)] pt-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)]">{{ __('Product Options') }}</h3>
                        <p class="mt-0.5 text-sm text-[var(--hs-neutral-500)]">
                            {{ __('Define what makes each variant unique (e.g. Cut, Weight, Packaging).') }}
                        </p>
                    </div>
                    @if ($parent_id)
                        <flux:button type="button" variant="outline" size="sm" icon="plus" wire:click="addAttribute">
                            {{ __('Add Option') }}
                        </flux:button>
                    @endif
                </div>

                @if (! $parent_id)
                    <div class="flex items-center gap-2.5 px-4 py-4 rounded-[var(--hs-radius-card)] border border-dashed border-[var(--hs-neutral-200)] text-sm text-[var(--hs-neutral-400)]">
                        <flux:icon name="information-circle" variant="mini" class="shrink-0 text-[var(--hs-neutral-300)]" />
                        {{ __('Product options only apply to subcategories. Select a parent group above to define them.') }}
                    </div>
                @elseif (! empty($variantAttributesSchema))
                    <div class="space-y-2">
                        @foreach ($variantAttributesSchema as $key => $attr)
                            <div
                                x-data="{ type: '{{ $attr['type'] ?? 'select' }}' }"
                                class="flex flex-wrap lg:flex-nowrap items-end gap-3 p-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)]"
                            >
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

                                {{-- Choices --}}
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

                                {{-- Required --}}
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
                                    wire:click="removeAttribute('{{ $key }}')"
                                    class="text-[var(--hs-danger-500)] hover:bg-[var(--hs-danger-50)] shrink-0"
                                />
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center gap-2.5 px-4 py-4 rounded-[var(--hs-radius-card)] border border-dashed border-[var(--hs-neutral-200)] text-sm text-[var(--hs-neutral-400)]">
                        <flux:icon name="squares-2x2" variant="mini" class="shrink-0 text-[var(--hs-neutral-300)]" />
                        {{ __('No options defined yet. Click') }} <strong class="text-[var(--hs-neutral-600)] mx-1">{{ __('Add Option') }}</strong> {{ __('to get started.') }}
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 border-t border-[var(--hs-neutral-100)] pt-4">
                <flux:button variant="ghost" href="{{ route('admin.categories.index') }}" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit">
                    {{ __('Create Category') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
