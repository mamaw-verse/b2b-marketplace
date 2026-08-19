<div>
    @include('livewire.company-hub.partials.product-tabs')

    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Variants</h1>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ $product->name }}</p>
            </div>
            <div class="flex gap-2">
                <flux:button variant="primary" icon="plus" wire:click="addVariant">
                    Add Variant
                </flux:button>
            </div>
        </div>

        <div class="p-6">

            {{-- Variants table --}}
            <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>SKU</flux:table.column>
                        <flux:table.column>Price</flux:table.column>
                        <flux:table.column>MOQ</flux:table.column>
                        <flux:table.column>Unit</flux:table.column>
                        <flux:table.column>Lead Time</flux:table.column>
                        <flux:table.column>Pack Size</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($variants as $index => $variant)
                            <flux:table.row :key="$variant['id'] ?? 'new-' . $index" class="hover:bg-[var(--hs-neutral-50)] transition">
                                <flux:table.cell>
                                    <flux:input wire:model="variants.{{ $index }}.sku" size="sm" placeholder="SKU" />
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:input wire:model="variants.{{ $index }}.price" size="sm" type="number" step="0.01" min="0" placeholder="0.00" />
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:input wire:model="variants.{{ $index }}.minimum_order_quantity" size="sm" type="number" min="1" placeholder="1" />
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:input wire:model="variants.{{ $index }}.unit" size="sm" placeholder="kg, pcs" />
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:input wire:model="variants.{{ $index }}.lead_time_days" size="sm" type="number" min="0" placeholder="Days" />
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:input wire:model="variants.{{ $index }}.pack_size" size="sm" placeholder="e.g. 12x1kg" />
                                </flux:table.cell>
                                <flux:table.cell class="py-0">
                                    <button wire:click="toggleVariantStatus({{ $index }})" class="cursor-pointer">
                                        <x-hs-status-pill :status="$variant['status'] ?? 'archived'" />
                                    </button>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-1">
                                        <flux:button variant="ghost" size="sm" icon="currency-dollar" wire:click="openQuantityBreaks({{ $index }})" />
                                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="removeVariant({{ $index }})" wire:confirm="Remove this variant?" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="8" class="text-center">
                                    <x-hs-empty-state icon="package" title="No variants yet" subtitle="Add your first variant to define pricing." />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            @if (count($variants) > 0)
                <div class="mt-4 flex justify-end">
                    <flux:button variant="primary" wire:click="saveAll">
                        Save All Variants
                    </flux:button>
                </div>
            @endif
        </div>
    </div>

    {{-- Quantity Breaks Modal --}}
    <flux:modal wire:model.self="showQuantityBreaksModal" class="md:w-[480px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Quantity Breaks</flux:heading>
                <flux:text class="mt-1">Define price tiers based on order quantity.</flux:text>
            </div>

            @foreach ($quantityBreaks as $breakIndex => $break)
                <div class="flex items-center gap-3">
                    <flux:input wire:model="quantityBreaks.{{ $breakIndex }}.min_qty" label="{{ $breakIndex === 0 ? 'Min Qty' : '' }}" type="number" min="1" placeholder="Qty" />
                    <flux:input wire:model="quantityBreaks.{{ $breakIndex }}.price" label="{{ $breakIndex === 0 ? 'Price' : '' }}" type="number" step="0.01" min="0" placeholder="0.00" />
                    <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="removeQuantityBreak({{ $breakIndex }})" class="{{ $breakIndex === 0 ? 'mt-6' : '' }}" />
                </div>
            @endforeach

            <flux:button variant="ghost" size="sm" icon="plus" wire:click="addQuantityBreak">
                Add Tier
            </flux:button>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showQuantityBreaksModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveQuantityBreaks">Save Breaks</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
