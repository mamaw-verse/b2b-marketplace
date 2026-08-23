<div>
    @include('livewire.company-hub.partials.product-tabs')

    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Variants</h1>
                <p class="mt-0.5 text-sm text-[var(--hs-neutral-500)]">
                    Each variant is a unique SKU — a specific combination of options and price.
                </p>
            </div>
            <flux:button variant="primary" icon="plus" wire:click="openAddVariant" class="shrink-0">
                Add Variant
            </flux:button>
        </div>

        <div class="p-6">

            {{-- No-schema notice --}}
            @if (empty($this->mergedAttributeSchema))
                <div class="mb-5 flex items-start gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] px-4 py-3 text-sm text-[var(--hs-neutral-600)]">
                    <flux:icon name="information-circle" variant="mini" class="mt-0.5 shrink-0 text-[var(--hs-neutral-400)]" />
                    <span>
                        No product options are defined yet.
                        <a href="{{ route('hub.products.edit', $product) }}" wire:navigate class="underline font-medium text-[var(--hs-neutral-700)]">
                            Set them on the Basic Info tab
                        </a>
                        so each variant can carry the right attributes (e.g. Size, Cut, Weight).
                    </span>
                </div>
            @endif

            {{-- Variant cards --}}
            @forelse ($variants as $variant)
                <div
                    wire:key="variant-{{ $variant['id'] }}"
                    class="group mb-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] transition hover:border-[var(--hs-neutral-300)] hover:shadow-[var(--hs-shadow-sm)]"
                >
                    {{-- Card top bar --}}
                    <div class="flex flex-wrap items-center gap-3 px-4 py-3 border-b border-[var(--hs-neutral-100)]">
                        <span class="font-mono text-sm font-semibold tracking-tight text-[var(--hs-neutral-900)]">{{ $variant['sku'] }}</span>

                        <button wire:click="toggleStatus({{ $variant['id'] }})" class="cursor-pointer">
                            <x-hs-status-pill :status="$variant['status']" />
                        </button>

                        <div class="ml-auto flex items-center gap-0.5">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="banknotes"
                                wire:click="openQuantityBreaks({{ $variant['id'] }})"
                            >
                                <span class="hidden sm:inline">Qty Breaks</span>
                                @if (count($variant['quantity_breaks'] ?? []) > 0)
                                    <flux:badge size="sm" color="green" class="ml-1">{{ count($variant['quantity_breaks']) }}</flux:badge>
                                @endif
                            </flux:button>

                            <div class="w-px h-5 bg-[var(--hs-neutral-200)] mx-1"></div>

                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="pencil-square"
                                wire:click="openEditVariant({{ $variant['id'] }})"
                            />
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                wire:click="removeVariant({{ $variant['id'] }})"
                                wire:confirm="Remove this variant permanently?"
                                class="text-[var(--hs-danger-500)] hover:bg-[var(--hs-danger-50)]"
                            />
                        </div>
                    </div>

                    {{-- Card body --}}
                    <div class="px-4 py-3.5 text-sm">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-8 gap-y-3">
                            <div>
                                <p class="text-xs text-[var(--hs-neutral-500)] mb-0.5">Price</p>
                                <p class="text-base font-bold text-[var(--hs-neutral-900)]">
                                    ₱{{ number_format((float) $variant['price'], 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-[var(--hs-neutral-500)] mb-0.5">Min. Order</p>
                                <p class="font-medium text-[var(--hs-neutral-800)]">
                                    {{ $variant['minimum_order_quantity'] }}
                                    <span class="text-[var(--hs-neutral-500)] font-normal">{{ $variant['unit'] }}</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-[var(--hs-neutral-500)] mb-0.5">Lead Time</p>
                                <p class="font-medium text-[var(--hs-neutral-800)]">
                                    {{ $variant['lead_time_days'] !== '' ? $variant['lead_time_days'] . ' days' : '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-[var(--hs-neutral-500)] mb-0.5">Pack Size</p>
                                <p class="font-medium text-[var(--hs-neutral-800)]">{{ $variant['pack_size'] ?: '—' }}</p>
                            </div>
                        </div>

                        {{-- Product options summary --}}
                        @if (! empty($this->mergedAttributeSchema) && ! empty($variant['attributes']))
                            <div class="flex flex-wrap gap-1.5 mt-3.5 pt-3 border-t border-[var(--hs-neutral-100)]">
                                @foreach ($this->mergedAttributeSchema as $attrKey => $attrConfig)
                                    @php $val = $variant['attributes'][$attrKey] ?? ''; @endphp
                                    @if ($val !== '')
                                        <span class="inline-flex items-center gap-1 rounded-full border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] px-2.5 py-1 text-xs text-[var(--hs-neutral-700)]">
                                            <span class="text-[var(--hs-neutral-500)]">{{ $attrConfig['label'] ?? $attrKey }}</span>
                                            <span class="font-medium">{{ $val }}</span>
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <x-hs-empty-state
                    icon="squares-2x2"
                    title="No variants yet"
                    subtitle="Click 'Add Variant' to define the first SKU, price, and options for this product."
                />
            @endforelse

        </div>
    </div>

    {{-- ── Add / Edit Variant Modal ─────────────────────────────────────── --}}
    <x-hs-modal
        model="showVariantModal"
        :title="$editingVariantId ? 'Edit Variant' : 'Add Variant'"
        subtitle="Fill in the details below. The variant is saved immediately when you click Save."
        size="lg"
    >
        <div class="space-y-4">

            {{-- SKU + Status row --}}
            <div class="flex gap-3">
                <div class="flex-1">
                    <flux:input
                        wire:model="draftSku"
                        label="SKU"
                        placeholder="e.g. PROD-001-S"
                        required
                    />
                    @error('draftSku') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>
                <div class="w-36">
                    <flux:select wire:model="draftStatus" label="Status">
                        <flux:select.option value="archived">Archived</flux:select.option>
                        <flux:select.option value="published">Published</flux:select.option>
                    </flux:select>
                </div>
            </div>

            {{-- Price + MOQ + Unit --}}
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <flux:input
                        wire:model="draftPrice"
                        label="Price (₱)"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        required
                    />
                    @error('draftPrice') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <flux:input
                        wire:model="draftMoq"
                        label="Min. Order Qty"
                        type="number"
                        min="1"
                        placeholder="1"
                        required
                    />
                    @error('draftMoq') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-hs-unit-select
                        wire-model="draftUnit"
                        :value="$draftUnit"
                        label="Unit"
                        required
                    />
                    @error('draftUnit') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Lead time + Pack size --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <flux:input
                        wire:model="draftLeadTime"
                        label="Lead Time (days)"
                        type="number"
                        min="0"
                        placeholder="e.g. 7"
                    />
                    @error('draftLeadTime') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <flux:input
                        wire:model="draftPackSize"
                        label="Pack Size"
                        placeholder="e.g. 12 × 1 kg"
                    />
                    @error('draftPackSize') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Product Options (from schema) --}}
            @if (! empty($this->mergedAttributeSchema))
                <div class="border-t border-[var(--hs-neutral-100)] pt-4">
                    <p class="mb-3 text-sm font-semibold text-[var(--hs-neutral-700)]">Product Options</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach ($this->mergedAttributeSchema as $attrKey => $attrConfig)
                            <div>
                                <label class="block mb-1 text-sm font-medium text-[var(--hs-neutral-700)]">
                                    {{ $attrConfig['label'] ?? ucfirst($attrKey) }}
                                    @if (! empty($attrConfig['required']))
                                        <span class="text-[var(--hs-danger-500)]">*</span>
                                    @endif
                                </label>
                                @if (($attrConfig['type'] ?? 'text') === 'select' && ! empty($attrConfig['options']))
                                    <flux:select wire:model="draftAttributes.{{ $attrKey }}">
                                        <flux:select.option value="">Select…</flux:select.option>
                                        @foreach (array_map('trim', explode(',', $attrConfig['options'])) as $option)
                                            <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @elseif (($attrConfig['type'] ?? '') === 'number')
                                    <flux:input
                                        wire:model="draftAttributes.{{ $attrKey }}"
                                        type="number"
                                        step="any"
                                        placeholder="Enter value"
                                    />
                                @else
                                    <flux:input
                                        wire:model="draftAttributes.{{ $attrKey }}"
                                        placeholder="Enter value"
                                    />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

        {{-- Footer actions --}}
        <div class="flex justify-end gap-2 border-t border-[var(--hs-neutral-100)] pt-4 mt-4">
            <flux:button variant="ghost" wire:click="$set('showVariantModal', false)">
                Cancel
            </flux:button>
            <flux:button variant="primary" wire:click="saveVariant" wire:loading.attr="disabled">
                {{ $editingVariantId ? 'Update Variant' : 'Save Variant' }}
            </flux:button>
        </div>
    </x-hs-modal>

    {{-- ── Quantity Breaks Modal ────────────────────────────────────────── --}}
    <x-hs-modal
        model="showQuantityBreaksModal"
        title="Quantity Breaks"
        subtitle="Define price tiers — buyers ordering more pay less per unit."
        size="md"
    >
        <div class="space-y-2">
            @if (! empty($quantityBreaks))
                <div class="grid grid-cols-[80px_1fr_32px] gap-2 px-1">
                    <span class="text-xs font-medium text-[var(--hs-neutral-500)]">Min. Qty</span>
                    <span class="text-xs font-medium text-[var(--hs-neutral-500)]">Price</span>
                    <span></span>
                </div>
            @endif

            @foreach ($quantityBreaks as $breakIndex => $break)
                <div class="grid grid-cols-[80px_1fr_32px] gap-2 items-center">
                    <flux:input
                        wire:model="quantityBreaks.{{ $breakIndex }}.min_qty"
                        type="number"
                        min="1"
                        placeholder="Qty"
                    />
                    <flux:input
                        wire:model="quantityBreaks.{{ $breakIndex }}.price"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                    >
                        <x-slot name="iconLeading">
                            <span class="text-[var(--hs-neutral-400)] text-sm">₱</span>
                        </x-slot>
                    </flux:input>
                    <flux:button
                        variant="ghost"
                        size="sm"
                        icon="trash"
                        wire:click="removeQuantityBreak({{ $breakIndex }})"
                        class="text-[var(--hs-danger-500)] hover:bg-[var(--hs-danger-50)] shrink-0"
                    />
                </div>
            @endforeach

            <flux:button variant="ghost" size="sm" icon="plus" wire:click="addQuantityBreak" class="mt-1">
                Add tier
            </flux:button>
        </div>

        <div class="flex justify-end gap-2 border-t border-[var(--hs-neutral-100)] pt-4 mt-4">
            <flux:button variant="ghost" wire:click="$set('showQuantityBreaksModal', false)">
                Cancel
            </flux:button>
            <flux:button variant="primary" wire:click="saveQuantityBreaks" wire:loading.attr="disabled">
                Save Breaks
            </flux:button>
        </div>
    </x-hs-modal>
</div>