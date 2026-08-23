<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                {{ $quotation ? __('Edit Quotation') : __('New Quotation') }}
            </h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                {{ __('RFQ:') }} <span class="font-medium text-[var(--hs-neutral-700)]">{{ $rfqRecipient->rfq->title }}</span>
            </p>
        </div>

        <div class="p-6">
            <form wire:submit="saveDraft" class="space-y-6">

                {{-- Line Items --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Line Items') }}</h2>
                        <flux:button type="button" variant="ghost" size="sm" icon="plus" wire:click="addItem">
                            {{ __('Add Item') }}
                        </flux:button>
                    </div>

                    <div class="space-y-3">
                        @foreach ($items as $index => $item)
                            <div
                                wire:key="item-{{ $index }}"
                                class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-4 space-y-3"
                            >
                                {{-- Row header: item number + variant picker + remove --}}
                                <div class="flex items-center gap-3">
                                    <span class="shrink-0 flex items-center justify-center size-5 rounded-full bg-[var(--hs-neutral-200)] text-[10px] font-semibold text-[var(--hs-neutral-600)]">
                                        {{ $index + 1 }}
                                    </span>

                                    {{-- Variant picker --}}
                                    <div class="flex-1" x-data>
                                        <flux:select
                                            wire:model="items.{{ $index }}.variant_id"
                                            x-on:change="$wire.selectVariant({{ $index }}, $event.target.value ? parseInt($event.target.value) : null)"
                                            placeholder="From catalogue (optional)…"
                                        >
                                            <flux:select.option value="">— Freeform item —</flux:select.option>
                                            @foreach ($this->shopVariants as $v)
                                                <flux:select.option value="{{ $v['id'] }}">
                                                    {{ $v['label'] }} · ₱{{ number_format((float) $v['price'], 2) }}/{{ $v['unit'] }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>

                                    @if (count($items) > 1)
                                        <flux:button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            wire:click="removeItem({{ $index }})"
                                            class="shrink-0 text-[var(--hs-danger-500)] hover:bg-[var(--hs-danger-50)]"
                                        />
                                    @endif
                                </div>

                                {{-- Item fields --}}
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                    <div class="sm:col-span-5">
                                        <flux:input
                                            wire:model="items.{{ $index }}.description"
                                            label="Description"
                                            placeholder="e.g. Halal Coconut Flour 25 kg"
                                            size="sm"
                                        />
                                        @error("items.{$index}.description")
                                            <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <flux:input
                                            wire:model="items.{{ $index }}.quantity"
                                            label="Qty"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            size="sm"
                                        />
                                        @error("items.{$index}.quantity")
                                            <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <x-hs-unit-select
                                            wire-model="items.{{ $index }}.unit"
                                            :value="$item['unit'] ?? ''"
                                            label="Unit"
                                            size="sm"
                                        />
                                        @error("items.{$index}.unit")
                                            <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-3">
                                        <flux:input
                                            wire:model="items.{{ $index }}.unit_price"
                                            label="Unit Price (₱)"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            size="sm"
                                        />
                                        @error("items.{$index}.unit_price")
                                            <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Subtotal + variant hint --}}
                                <div class="flex items-center justify-between pt-1">
                                    @if (! empty($item['variant_id']))
                                        <p class="flex items-center gap-1 text-xs text-[var(--hs-neutral-400)]">
                                            <flux:icon name="link" variant="mini" class="size-3" />
                                            {{ __('Linked to catalogue SKU — fields are editable, changes apply to this quotation only.') }}
                                        </p>
                                    @else
                                        <span></span>
                                    @endif

                                    @php
                                        $subtotal = is_numeric($item['quantity'] ?? null) && is_numeric($item['unit_price'] ?? null)
                                            ? number_format((float) $item['quantity'] * (float) $item['unit_price'], 2)
                                            : '0.00';
                                    @endphp
                                    <p class="text-xs font-medium text-[var(--hs-neutral-600)] whitespace-nowrap">
                                        {{ __('Subtotal:') }} <span class="font-semibold text-[var(--hs-neutral-900)]">₱{{ $subtotal }}</span>
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('items')
                        <p class="mt-2 text-sm text-[var(--hs-danger-500)]">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Running total --}}
                @php
                    $runningTotal = collect($items)->sum(fn ($i) =>
                        is_numeric($i['quantity'] ?? null) && is_numeric($i['unit_price'] ?? null)
                            ? (float) $i['quantity'] * (float) $i['unit_price']
                            : 0
                    );
                @endphp
                @if ($runningTotal > 0)
                    <div class="flex justify-end border-t border-[var(--hs-neutral-100)] pt-3">
                        <p class="text-sm text-[var(--hs-neutral-600)]">
                            {{ __('Total:') }}
                            <span class="ml-2 text-lg font-bold text-[var(--hs-neutral-900)]">
                                ₱{{ number_format($runningTotal, 2) }}
                            </span>
                        </p>
                    </div>
                @endif

                {{-- Notes --}}
                <flux:textarea
                    wire:model="notes"
                    label="Notes / Terms (optional)"
                    placeholder="Pricing valid for monthly contract. MOQ applies. Lead time: 10 business days."
                    rows="3"
                />

                {{-- Actions --}}
                <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-[var(--hs-neutral-100)]">
                    <flux:button type="submit" variant="ghost" wire:loading.attr="disabled">
                        {{ __('Save Draft') }}
                    </flux:button>
                    <flux:button type="button" variant="primary" wire:click="confirmSubmit" wire:loading.attr="disabled">
                        {{ __('Submit Quotation') }}
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('hub.rfqs.show', $rfqRecipient) }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>

                    @if ($quotation?->isSubmitted())
                        <flux:button
                            type="button"
                            variant="ghost"
                            wire:click="withdrawQuotation"
                            wire:confirm="Withdraw this quotation? The buyer will no longer be able to award it."
                            class="ml-auto text-[var(--hs-danger-500)] hover:bg-[var(--hs-danger-50)]"
                        >
                            {{ __('Withdraw') }}
                        </flux:button>
                    @endif
                </div>

            </form>
        </div>
    </div>

    {{-- Submit Confirmation Modal --}}
    <x-hs-modal model="showSubmitConfirmation" title="Submit Quotation" size="sm">
        <div class="flex items-start gap-2.5 rounded-[var(--hs-radius-card)] bg-[var(--hs-warning-50)] border border-[var(--hs-warning-200)] px-4 py-3">
            <flux:icon name="exclamation-triangle" variant="mini" class="mt-0.5 shrink-0 text-[var(--hs-warning-500)]" />
            <p class="text-sm text-[var(--hs-warning-700)]">
                <strong>{{ __('Important:') }}</strong>
                {{ __('Once submitted, this quotation cannot be edited. The buyer will see it immediately. You can withdraw it later if needed.') }}
            </p>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-[var(--hs-neutral-100)] mt-4">
            <flux:button variant="ghost" wire:click="$set('showSubmitConfirmation', false)">
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" wire:click="submitQuotation" wire:loading.attr="disabled">
                {{ __('Confirm & Submit') }}
            </flux:button>
        </div>
    </x-hs-modal>
</div>