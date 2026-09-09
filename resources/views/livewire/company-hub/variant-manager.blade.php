<div>
    @include('livewire.company-hub.partials.product-tabs', ['product' => $this->product])

    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Variants') }}</h1>
                <p class="mt-0.5 text-sm text-[var(--hs-neutral-500)]">
                    {{ __('Each variant is a unique SKU — a specific combination of options and price.') }}
                </p>
            </div>
            <flux:button variant="primary" icon="plus" wire:click="openAddVariant" class="shrink-0">
                {{ __('Add Variant') }}
            </flux:button>
        </div>

        <div class="p-6">

            {{-- No-schema notice --}}
            @if (empty($this->mergedAttributeSchema))
                <div class="mb-5 flex items-start gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] px-4 py-3 text-sm text-[var(--hs-neutral-600)]">
                    <flux:icon name="information-circle" variant="mini" class="mt-0.5 shrink-0 text-[var(--hs-neutral-400)]" />
                    <span>
                        {{ __('No product options are defined yet.') }}
                        <a href="{{ route('hub.products.edit', $this->product) }}" wire:navigate class="underline font-medium text-[var(--hs-neutral-700)]">
                            {{ __('Set them on the Basic Info tab') }}
                        </a>
                        {{ __('so each variant can carry the right attributes (e.g. Size, Cut, Weight).') }}
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

                        <x-hs-status-pill :status="$variant['status']" />

                        <div class="ml-auto flex items-center gap-0.5">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="banknotes"
                                wire:click="openQuantityBreaks({{ $variant['id'] }})"
                            >
                                <span class="hidden sm:inline">{{ __('Qty Breaks') }}</span>
                                @if (count($variant['quantity_breaks'] ?? []) > 0)
                                    <flux:badge size="sm" color="green" class="ml-1">{{ count($variant['quantity_breaks']) }}</flux:badge>
                                @endif
                            </flux:button>

                            <div class="w-px h-5 bg-[var(--hs-neutral-200)] mx-1"></div>

                            {{-- Publish / archive toggle as an explicit icon button (mirrors
                                 product-list): the status pill is now display-only. --}}
                            <flux:button
                                variant="ghost"
                                size="sm"
                                :icon="$variant['status'] === 'published' ? 'archive-box' : 'arrow-up-circle'"
                                wire:click="toggleStatus({{ $variant['id'] }})"
                                tooltip="{{ $variant['status'] === 'published' ? __('Archive') : __('Publish') }}"
                            />

                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="pencil-square"
                                wire:click="openEditVariant({{ $variant['id'] }})"
                                tooltip="{{ __('Edit') }}"
                            />
                        </div>
                    </div>

                    {{-- Card body --}}
                    <div class="px-4 py-3.5 text-sm">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-8 gap-y-3">
                            <div>
                                <p class="text-xs text-[var(--hs-neutral-500)] mb-0.5">{{ __('Price') }}</p>
                                <p class="text-base font-bold text-[var(--hs-neutral-900)]">
                                    ₱{{ number_format((float) $variant['price'], 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-[var(--hs-neutral-500)] mb-0.5">{{ __('Min. Order') }}</p>
                                <p class="font-medium text-[var(--hs-neutral-800)]">
                                    {{ $variant['minimum_order_quantity'] }}
                                    <span class="text-[var(--hs-neutral-500)] font-normal">{{ $variant['unit'] }}</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-[var(--hs-neutral-500)] mb-0.5">{{ __('Lead Time') }}</p>
                                <p class="font-medium text-[var(--hs-neutral-800)]">
                                    {{ $variant['lead_time_days'] !== '' ? __(':days days', ['days' => $variant['lead_time_days']]) : '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-[var(--hs-neutral-500)] mb-0.5">{{ __('Pack Size') }}</p>
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
                    title="{{ __('No variants yet') }}"
                    :subtitle='__("Click Add Variant to define the first SKU, price, and options for this product.")'
                />
            @endforelse

        </div>
    </div>

    {{-- ── Add / Edit Variant Modal ─────────────────────────────────────── --}}
    <x-hs-modal
        model="showVariantModal"
        :title="$form->editingVariantId ? __('Edit Variant') : __('Add Variant')"
        subtitle="{{ __('Fill in the details below. The variant is saved immediately when you click Save.') }}"
        size="xl"
        :dismissible="false"
    >
        <div class="space-y-4">

            {{-- SKU + Status row --}}
            <div class="flex gap-3">
                <div class="flex-1">
                    <flux:input
                        wire:model="form.sku"
                        label="{{ __('SKU') }}"
                        placeholder="{{ __('e.g. PROD-001-S') }}"
                        required
                    />
                    @error('form.sku') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>
                <div class="w-36">
                    <flux:select wire:model="form.status" label="{{ __('Status') }}">
                        <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                        <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
                    </flux:select>
                </div>
            </div>

            {{-- Price + Unit + MOQ. hs-unit-select uses layout="grid": its select
                 and custom-unit inputs render as two sibling grid cells (root is
                 display:contents). The row is 3 columns normally and expands to 4
                 only when "Other" is chosen, so the custom-unit input gets its own
                 column without pushing MOQ down. The column count is driven by the
                 live unit value: a value that isn't a known unit means custom.
                 @js(...) reuses the same config('units') source as the select. --}}
            <div
                x-data="{
                    knownUnits: @js(collect(config('units', []))->pluck('value')->all()),
                    get isCustomUnit() {
                        const v = $wire.get('form.unit');
                        return !!v && !this.knownUnits.includes(v);
                    },
                }"
                :class="isCustomUnit ? 'grid-cols-4' : 'grid-cols-3'"
                class="grid gap-3"
            >
                <div>
                    <flux:input
                        wire:model="form.price"
                        label="{{ __('Price (₱)') }}"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        required
                    />
                    @error('form.price') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>

                <x-hs-unit-select
                    wire-model="form.unit"
                    :value="$form->unit"
                    label="{{ __('Unit') }}"
                    layout="grid"
                    required
                />

                <div>
                    <flux:input
                        wire:model="form.moq"
                        label="{{ __('Min. Order Qty') }}"
                        type="number"
                        min="1"
                        placeholder="1"
                        required
                    />
                    @error('form.moq') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>
            </div>
            @error('form.unit') <p class="-mt-2 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror

            {{-- Lead time + Pack size --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <flux:input
                        wire:model="form.leadTime"
                        label="{{ __('Lead Time (days)') }}"
                        type="number"
                        min="0"
                        placeholder="{{ __('e.g. 7') }}"
                    />
                    @error('form.leadTime') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <flux:input
                        wire:model="form.packSize"
                        label="{{ __('Pack Size') }}"
                        placeholder="{{ __('e.g. 12 × 1 kg') }}"
                    />
                    @error('form.packSize') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Product Options (from schema) --}}
            @if (! empty($this->mergedAttributeSchema))
                <div class="border-t border-[var(--hs-neutral-100)] pt-4">
                    <p class="mb-3 text-sm font-semibold text-[var(--hs-neutral-700)]">{{ __('Product Options') }}</p>
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
                                    <flux:select wire:model="form.attributes.{{ $attrKey }}">
                                        <flux:select.option value="">{{ __('Select…') }}</flux:select.option>
                                        @foreach (array_map('trim', explode(',', $attrConfig['options'])) as $option)
                                            <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @elseif (($attrConfig['type'] ?? '') === 'number')
                                    <flux:input
                                        wire:model="form.attributes.{{ $attrKey }}"
                                        type="number"
                                        step="any"
                                        placeholder="{{ __('Enter value') }}"
                                    />
                                @else
                                    <flux:input
                                        wire:model="form.attributes.{{ $attrKey }}"
                                        placeholder="{{ __('Enter value') }}"
                                    />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showVariantModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="saveVariant" wire:loading.attr="disabled">
                    {{ $form->editingVariantId ? __('Update Variant') : __('Save Variant') }}
                </flux:button>
            </div>
        </x-slot>
    </x-hs-modal>

    {{-- ── Quantity Breaks Modal ────────────────────────────────────────── --}}
    <x-hs-modal
        model="showQuantityBreaksModal"
        title="{{ __('Quantity Breaks') }}"
        subtitle="{{ __('Define price tiers — buyers ordering more pay less per unit.') }}"
        size="md"
    >
        <div class="space-y-2">
            @if (! empty($quantityBreaks))
                <div class="grid grid-cols-[80px_1fr_32px] gap-2 px-1">
                    <span class="text-xs font-medium text-[var(--hs-neutral-500)]">{{ __('Min. Qty') }}</span>
                    <span class="text-xs font-medium text-[var(--hs-neutral-500)]">{{ __('Price') }}</span>
                    <span></span>
                </div>
            @endif

            @foreach ($quantityBreaks as $breakIndex => $break)
                <div class="grid grid-cols-[80px_1fr_32px] gap-2 items-center">
                    <flux:input
                        wire:model="quantityBreaks.{{ $breakIndex }}.min_qty"
                        type="number"
                        min="1"
                        placeholder="{{ __('Qty') }}"
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
                        wire:click="confirmRemoveQuantityBreak({{ $breakIndex }})"
                        class="text-[var(--hs-danger-500)] hover:bg-[var(--hs-danger-50)] shrink-0"
                    />
                </div>
            @endforeach

            <flux:button variant="ghost" size="sm" icon="plus" wire:click="addQuantityBreak" class="mt-1">
                {{ __('Add tier') }}
            </flux:button>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showQuantityBreaksModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="saveQuantityBreaks" wire:loading.attr="disabled">
                    {{ __('Save Breaks') }}
                </flux:button>
            </div>
        </x-slot>
    </x-hs-modal>

    <x-hs-confirm
        name="confirm-remove-quantity-break"
        title="{{ __('Remove Quantity Break') }}"
        message="{{ __('Remove this quantity break?') }}"
        variant="danger"
        confirmText="{{ __('Remove') }}"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="removeQuantityBreak" wire:loading.attr="disabled">
                {{ __('Remove') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>