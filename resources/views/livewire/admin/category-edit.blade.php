<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-[var(--hs-neutral-900)]">Edit Category</h1>
        <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">Update category details and variant attribute schema.</p>
    </div>

    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <form wire:submit="save" class="space-y-6 p-6">
            <div>
                <flux:input
                    wire:model.live.debounce.300ms="name"
                    label="Name"
                    placeholder="e.g. Bakery Ingredients"
                    required
                />
                @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            @if ($name)
                <div>
                    <p class="text-xs text-[var(--hs-neutral-500)] mb-1">Slug preview:</p>
                    <code class="rounded-[var(--hs-radius-tag)] bg-[var(--hs-neutral-100)] px-2 py-0.5 text-xs font-mono text-[var(--hs-neutral-700)]">{{ \Illuminate\Support\Str::slug($name) }}</code>
                </div>
            @endif

            <div>
                <flux:textarea
                    wire:model="description"
                    label="Description"
                    rows="3"
                    placeholder="Optional description..."
                />
                @error('description') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Product Options (Variant Attribute Schema) --}}
            <div class="border-t border-[var(--hs-neutral-100)] pt-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)]">Product Options</h3>
                        <p class="mt-0.5 text-sm text-[var(--hs-neutral-500)]">
                            Define what makes each variant unique (e.g. Cut, Weight, Packaging).
                        </p>
                    </div>
                    <flux:button type="button" variant="outline" size="sm" icon="plus" wire:click="addAttribute">
                        Add Option
                    </flux:button>
                </div>

                @if (! empty($variantAttributesSchema))
                    <div class="space-y-2">
                        @foreach ($variantAttributesSchema as $key => $attr)
                            <div
                                x-data="{ type: '{{ $attr['type'] ?? 'select' }}' }"
                                class="flex flex-wrap lg:flex-nowrap items-end gap-3 p-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)]"
                            >
                                {{-- Label --}}
                                <div class="w-64 shrink-0">
                                    @if ($loop->first)
                                        <label class="block text-xs font-medium text-[var(--hs-neutral-500)] mb-1">Label</label>
                                    @endif
                                    <flux:input
                                        wire:model="variantAttributesSchema.{{ $key }}.label"
                                        placeholder="e.g. Cut"
                                        size="sm"
                                    />
                                </div>

                                {{-- Type --}}
                                <div class="w-32 shrink-0">
                                    @if ($loop->first)
                                        <label class="block text-xs font-medium text-[var(--hs-neutral-500)] mb-1">Type</label>
                                    @endif
                                    <flux:select
                                        wire:model="variantAttributesSchema.{{ $key }}.type"
                                        x-model="type"
                                        size="sm"
                                    >
                                        <flux:select.option value="select">Select</flux:select.option>
                                        <flux:select.option value="text">Text</flux:select.option>
                                        <flux:select.option value="number">Number</flux:select.option>
                                    </flux:select>
                                </div>

                                {{-- Choices --}}
                                <div class="flex-1 min-w-[180px]">
                                    @if ($loop->first)
                                        <label class="block text-xs font-medium text-[var(--hs-neutral-500)] mb-1">Choices (comma-separated)</label>
                                    @endif
                                    <flux:input
                                        wire:model="variantAttributesSchema.{{ $key }}.options"
                                        placeholder="e.g. Whole, Half, Quarter"
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
                                    <span class="text-xs text-[var(--hs-neutral-600)]">Req.</span>
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
                        No options defined yet. Click <strong class="text-[var(--hs-neutral-600)] mx-1">Add Option</strong> to get started.
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 border-t border-[var(--hs-neutral-100)] pt-4">
                <flux:button variant="ghost" href="{{ route('admin.categories.index') }}" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button variant="primary" type="submit">
                    Update Category
                </flux:button>
            </div>
        </form>
    </div>
</div>
