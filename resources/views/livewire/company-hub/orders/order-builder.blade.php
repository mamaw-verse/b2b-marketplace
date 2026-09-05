@php
    // Curated unit list (same source as <x-hs-unit-select>) for the per-row
    // Alpine-bound select in the items table.
    $unitGroups = collect(config('units', []))->groupBy('group');
    // Read-only platform fee rates shown to the seller for transparency.
    $commissionRate = \App\Models\PayoutSetting::getCommissionFeeRate();
    $transactionFee = \App\Models\PayoutSetting::getTransactionFeeAmount();
@endphp

<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ $orderId ? __('Edit :order', ['order' => $this->order?->order_number ?? __('Order')]) : __('New Order') }}</h1>
        </div>

        @if (session('error'))
            <div class="mx-6 mt-4 p-3 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-sm text-[var(--hs-danger-700)]">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="mx-6 mt-4 p-3 bg-[var(--hs-success-50)] border border-[var(--hs-success-200)] rounded-lg text-sm text-[var(--hs-success-700)]">{{ session('success') }}</div>
        @endif

        <div class="p-6">

            {{-- Customer Details --}}
            <section class="pb-6 border-b border-[var(--hs-neutral-100)]">
                <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)] mb-4">{{ __('Customer Details') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model="customerBusinessName" label="{{ __('Business Name') }}" />
                    <flux:input wire:model="customerContactName" label="{{ __('Contact Name') }}" />
                    <flux:input wire:model="customerContactPhone" label="{{ __('Phone') }}" />
                    <flux:input wire:model="customerContactEmail" label="{{ __('Email') }}" type="email" />
                </div>
            </section>

            {{-- Billing Address --}}
            <section class="py-6 border-b border-[var(--hs-neutral-100)]">
                <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)] mb-4">{{ __('Billing Address') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input wire:model="billingAddress1" label="{{ __('Billing Address') }}" class="md:col-span-3" />
                    <flux:input wire:model="billingCity" label="{{ __('City') }}" />
                    <flux:input wire:model="billingProvince" label="{{ __('Province') }}" />
                    <flux:input wire:model="billingZipCode" label="{{ __('Zip Code') }}" />
                </div>
            </section>

            {{-- Shipping Address --}}
            <section class="py-6 border-b border-[var(--hs-neutral-100)]">
                <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)] mb-4">{{ __('Shipping Address') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input wire:model="shippingAddress1" label="{{ __('Shipping Address') }}" class="md:col-span-3" />
                    <flux:input wire:model="shippingCity" label="{{ __('City') }}" />
                    <flux:input wire:model="shippingProvince" label="{{ __('Province') }}" />
                    <flux:input wire:model="shippingZipCode" label="{{ __('Zip Code') }}" />
                </div>
            </section>

            {{-- Items, Fees & Totals share one Alpine scope so the total (and the --}}
            {{-- payout estimate derived from it) can update live as the seller types. --}}
            <div x-data="{
                    items: @entangle('items'),
                    fees: @entangle('fees'),
                    itemsTotal: 0,
                    feesTotal: 0,
                    grandTotal: 0,
                    commissionRate: {{ (float) $commissionRate }},
                    transactionFee: {{ (float) $transactionFee }},
                    commissionAmount: 0,
                    netPayout: 0,
                    recalcTotal() {
                        this.itemsTotal = this.items.reduce((sum, i) => sum + (parseFloat(i.quantity || 0) * parseFloat(i.unit_price || 0)), 0);
                        this.feesTotal = this.fees.reduce((sum, f) => sum + parseFloat(f.amount || 0), 0);
                        this.grandTotal = this.itemsTotal + this.feesTotal;
                        this.commissionAmount = this.grandTotal * this.commissionRate;
                        this.netPayout = Math.max(0, this.grandTotal - this.commissionAmount - this.transactionFee);
                    },
                    money(v) { return (parseFloat(v || 0)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                    rowSubtotal(item) { return parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0); },
                 }"
                 x-init="
                    recalcTotal();
                    $watch('items', () => recalcTotal());
                    $watch('fees', () => recalcTotal());
                 "
                 x-effect="recalcTotal()">

                {{-- Product / variant search — only available for standalone (non-quotation) orders --}}
                @if (! $this->fromQuotation)
                <section class="py-6 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)] mb-4">{{ __('Add from Catalog') }}</h2>
                    <div class="relative max-w-md">
                        <flux:input
                            wire:model.live.debounce.300ms="productSearch"
                            placeholder="{{ __('Search products by name or SKU…') }}"
                            icon="magnifying-glass"
                        />
                        @if (mb_strlen(trim($productSearch)) >= 2)
                            <div class="mt-2 border border-[var(--hs-neutral-200)] rounded-md divide-y divide-[var(--hs-neutral-100)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)] max-h-64 overflow-auto">
                                @forelse ($this->searchResults as $variant)
                                    <button
                                        type="button"
                                        wire:key="search-{{ $variant->id }}"
                                        @click="
                                            items.push({
                                                description: @js(($variant->product->name ?? '') . ' — ' . ($variant->description ?? $variant->sku)),
                                                quantity: {{ (int) ($variant->minimum_order_quantity ?: 1) }},
                                                unit: @js($variant->unit ?? 'pcs'),
                                                unit_price: {{ (float) $variant->price }},
                                                variant_id: {{ $variant->id }},
                                                variant_snapshot: null
                                            });
                                            $wire.set('productSearch', '');
                                        "
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-[var(--hs-neutral-50)] flex items-center justify-between"
                                    >
                                        <span class="text-[var(--hs-neutral-900)]">{{ $variant->product->name ?? '—' }}
                                            <span class="text-[var(--hs-neutral-500)]">· {{ $variant->sku }}</span>
                                        </span>
                                        <span class="text-[var(--hs-neutral-600)] tabular-nums">{{ number_format((float) $variant->price, 2) }} / {{ $variant->unit }}</span>
                                    </button>
                                @empty
                                    <p class="px-3 py-2 text-sm text-[var(--hs-neutral-500)]">{{ __('No matching products.') }}</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </section>
                @endif

                {{-- Order Items --}}
                <section class="py-6 border-b border-[var(--hs-neutral-100)]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)]">{{ __('Items') }}</h2>
                            @if ($this->fromQuotation)
                                <span class="inline-flex items-center gap-1 text-[11px] text-[var(--hs-neutral-400)] font-medium">
                                    <flux:icon name="lock-closed" variant="mini" class="size-3" />
                                    {{ __('From quotation · prices locked') }}
                                </span>
                            @endif
                        </div>
                        @if (! $this->fromQuotation)
                            <button type="button" @click="items.push({description:'', quantity:1, unit:'pcs', unit_price:0, variant_id:null, variant_snapshot:null})" class="text-sm text-[var(--hs-green-700)] hover:underline">+ {{ __('Add Item') }}</button>
                        @endif
                    </div>

                    @if ($this->fromQuotation)
                        {{-- Read-only item table for quotation-sourced orders --}}
                        <template x-if="items.length">
                            <div class="rounded-lg border border-[var(--hs-neutral-200)] overflow-hidden">
                                <table class="w-full text-[13.5px]">
                                    <thead>
                                        <tr class="bg-[var(--hs-neutral-50)]">
                                            <th class="text-left text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold px-4 py-2.5 w-8">#</th>
                                            <th class="text-left text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold px-4 py-2.5">{{ __('Description') }}</th>
                                            <th class="text-left text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold px-4 py-2.5 whitespace-nowrap">{{ __('Qty') }}</th>
                                            <th class="text-left text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold px-4 py-2.5 whitespace-nowrap">{{ __('Unit') }}</th>
                                            <th class="text-left text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold px-4 py-2.5 whitespace-nowrap">{{ __('Unit Price') }}</th>
                                            <th class="text-right text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold px-4 py-2.5 whitespace-nowrap">{{ __('Subtotal') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(item, index) in items" :key="index">
                                            <tr class="border-t border-[var(--hs-neutral-100)]">
                                                <td class="px-4 py-3 text-[var(--hs-neutral-400)] text-[12px] tabular-nums" x-text="index + 1"></td>
                                                <td class="px-4 py-3 text-[var(--hs-neutral-900)] font-medium" x-text="item.description || '—'"></td>
                                                <td class="px-4 py-3 text-[var(--hs-neutral-600)] tabular-nums whitespace-nowrap" x-text="parseInt(item.quantity)"></td>
                                                <td class="px-4 py-3 text-[var(--hs-neutral-600)] whitespace-nowrap" x-text="item.unit"></td>
                                                <td class="px-4 py-3 text-[var(--hs-neutral-600)] tabular-nums whitespace-nowrap" x-text="money(item.unit_price)"></td>
                                                <td class="px-4 py-3 text-right text-[var(--hs-neutral-900)] font-semibold tabular-nums whitespace-nowrap" x-text="money(rowSubtotal(item))"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                        <template x-if="!items.length">
                            <p class="text-sm text-[var(--hs-neutral-500)] py-2">{{ __('No items found.') }}</p>
                        </template>
                    @else
                        {{-- Editable items for standalone orders --}}
                        <template x-if="items.length">
                            <div class="grid grid-cols-12 gap-2 mb-1.5 px-0.5 text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold">
                                <div class="col-span-4">{{ __('Description') }}</div>
                                <div class="col-span-2">{{ __('Qty') }}</div>
                                <div class="col-span-2">{{ __('Unit') }}</div>
                                <div class="col-span-2">{{ __('Unit price') }}</div>
                                <div class="col-span-1 text-right">{{ __('Subtotal') }}</div>
                                <div class="col-span-1"></div>
                            </div>
                        </template>

                        <template x-for="(item, index) in items" :key="index">
                            <div class="grid grid-cols-12 gap-2 mb-2 items-start">
                                <div class="col-span-4"><input x-model="item.description" placeholder="{{ __('Description') }}" class="w-full rounded-md border border-[var(--hs-neutral-200)] px-3 py-2 text-sm bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)]" /></div>
                                <div class="col-span-2">
                                    <input
                                        x-model.number="item.quantity"
                                        type="number"
                                        step="1"
                                        min="1"
                                        inputmode="numeric"
                                        placeholder="{{ __('Qty') }}"
                                        @input="item.quantity = item.quantity === '' ? '' : Math.max(1, Math.trunc(item.quantity))"
                                        :class="(item.quantity === '' || item.quantity < 1) ? '!border-red-500 !ring-1 !ring-red-500' : ''"
                                        class="w-full rounded-md border border-[var(--hs-neutral-200)] px-3 py-2 text-sm bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)]"
                                    />
                                    <p x-show="item.quantity === '' || item.quantity < 1" x-cloak class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ __('Min 1') }}</p>
                                </div>
                                <div class="col-span-2">
                                    {{-- Unit dropdown from curated units (S4.T15) --}}
                                    <select x-model="item.unit" class="w-full rounded-md border border-[var(--hs-neutral-200)] px-3 py-2 text-sm bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)]">
                                        @foreach ($unitGroups as $groupName => $groupUnits)
                                            <optgroup label="{{ $groupName }}">
                                                @foreach ($groupUnits as $unit)
                                                    <option value="{{ $unit['value'] }}">{{ $unit['label'] }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-2"><input x-model.number="item.unit_price" type="number" min="0" step="0.01" placeholder="{{ __('Unit Price') }}" class="w-full rounded-md border border-[var(--hs-neutral-200)] px-3 py-2 text-sm bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)]" /></div>
                                <div class="col-span-1 text-right text-sm text-[var(--hs-neutral-700)] tabular-nums pt-2" x-text="money(rowSubtotal(item))"></div>
                                <div class="col-span-1 flex justify-end">
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        @click="items.splice(index, 1)"
                                        aria-label="{{ __('Remove item') }}"
                                        class="text-[var(--hs-danger-500)] hover:bg-[var(--hs-danger-50)]"
                                    />
                                </div>
                            </div>
                        </template>

                        <template x-if="!items.length">
                            <p class="text-sm text-[var(--hs-neutral-500)] py-2">{{ __('No items yet — add one to get started.') }}</p>
                        </template>
                    @endif
                </section>

                {{-- Fees --}}
                <section class="py-6 border-b border-[var(--hs-neutral-100)]">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-[13px] font-[650] uppercase tracking-[.05em] text-[var(--hs-neutral-500)]">{{ __('Fees') }}</h2>
                        <button type="button" @click="fees.push({label:'', amount:0})" class="text-sm text-[var(--hs-green-700)] hover:underline">+ {{ __('Add Fee') }}</button>
                    </div>

                    <template x-if="fees.length">
                        <div class="grid grid-cols-12 gap-2 mb-1.5 px-0.5 text-[11px] uppercase tracking-[.04em] text-[var(--hs-neutral-500)] font-semibold">
                            <div class="col-span-6">{{ __('Fee label') }}</div>
                            <div class="col-span-4">{{ __('Amount') }}</div>
                            <div class="col-span-2"></div>
                        </div>
                    </template>

                    <template x-for="(fee, index) in fees" :key="index">
                        <div class="grid grid-cols-12 gap-2 mb-2 items-center">
                            <div class="col-span-6"><input x-model="fee.label" placeholder="{{ __('Fee Label') }}" class="w-full rounded-md border border-[var(--hs-neutral-200)] px-3 py-2 text-sm bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)]" /></div>
                            <div class="col-span-4"><input x-model.number="fee.amount" type="number" min="0" step="0.01" placeholder="{{ __('Amount') }}" class="w-full rounded-md border border-[var(--hs-neutral-200)] px-3 py-2 text-sm bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)]" /></div>
                            <div class="col-span-2 flex justify-end">
                                <flux:button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    @click="fees.splice(index, 1)"
                                    aria-label="{{ __('Remove fee') }}"
                                    class="text-[var(--hs-danger-500)] hover:bg-[var(--hs-danger-50)]"
                                />
                            </div>
                        </div>
                    </template>

                    <template x-if="!fees.length">
                        <p class="text-sm text-[var(--hs-neutral-500)] py-2">{{ __('No fees yet.') }}</p>
                    </template>
                </section>

                {{-- Payout Estimate — mirrors the quotation builder's simulator so sellers --}}
                {{-- see the same breakdown when building an order. Values are computed --}}
                {{-- live in Alpine (recalcTotal) so they track items/fees as they're edited. --}}
                <section class="py-6 border-b border-[var(--hs-neutral-100)]" x-show="grandTotal > 0" x-cloak>
                    <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <flux:icon name="calculator" variant="mini" class="size-4 text-[var(--hs-neutral-500)]" />
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)]">{{ __('Payout Estimate') }}</h3>
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-sm">
                                <span class="text-[var(--hs-neutral-600)]">{{ __('Order Total') }}</span>
                                <span class="font-medium text-[var(--hs-neutral-900)] tabular-nums" x-text="money(grandTotal)"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-[var(--hs-neutral-600)]">{{ __('Commission (:rate%)', ['rate' => number_format($commissionRate * 100, 1)]) }}</span>
                                <span class="font-medium text-[var(--hs-neutral-700)] tabular-nums" x-text="'−' + money(commissionAmount)"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-[var(--hs-neutral-600)]">{{ __('Transaction Fee') }}</span>
                                <span class="font-medium text-[var(--hs-neutral-700)] tabular-nums" x-text="'−' + money(transactionFee)"></span>
                            </div>
                            <div class="pt-2 mt-1 border-t border-[var(--hs-neutral-200)] flex justify-between text-sm">
                                <span class="font-semibold text-[var(--hs-neutral-900)]">{{ __('Estimated Payout') }}</span>
                                <span class="font-bold text-[var(--hs-green-700)] tabular-nums" x-text="money(netPayout)"></span>
                            </div>
                        </div>
                        <p class="mt-2.5 text-xs text-[var(--hs-neutral-400)]">{{ __('Based on current platform rates. Actual payout calculated after order completion.') }}</p>
                    </div>
                </section>

                {{-- Totals: always driven by the live Alpine figures so they update as --}}
                {{-- items/fees are edited, without waiting for a server round-trip. The --}}
                {{-- entangled items/fees arrays are seeded from the order on load, so --}}
                {{-- this stays accurate immediately after save too. --}}
                <section class="pt-6 text-right space-y-1">
                    <p class="text-sm text-[var(--hs-neutral-600)]">{{ __('Items: :currency', ['currency' => $this->order?->currency ?? 'PHP']) }} <span x-text="money(itemsTotal)"></span></p>
                    <p class="text-sm text-[var(--hs-neutral-600)]">{{ __('Fees: :currency', ['currency' => $this->order?->currency ?? 'PHP']) }} <span x-text="money(feesTotal)"></span></p>
                    <p class="text-lg font-bold text-[var(--hs-neutral-900)]">{{ __('Total: :currency', ['currency' => $this->order?->currency ?? 'PHP']) }} <span x-text="money(grandTotal)"></span></p>
                </section>
            </div>

            {{-- Actions — right-aligned with the primary action last, matching --}}
            {{-- the other hub forms (quotation builder, shop profile, payout). --}}
            <div class="flex flex-wrap items-center gap-3 pt-6 mt-6 border-t border-[var(--hs-neutral-100)]">
                <flux:button variant="ghost" href="{{ route('hub.orders.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button class="ml-auto" wire:click="save">{{ __('Save Draft') }}</flux:button>
                <flux:button variant="primary" wire:click="confirmSubmit">{{ __('Submit for Payment') }}</flux:button>
            </div>
        </div>
    </div>

    {{-- Submit Confirmation --}}
    <x-hs-confirm
        name="submit-order-confirm"
        title="{{ __('Submit Order for Payment?') }}"
        message='{{ __("The buyer will be notified and prompted to pay. You won\'t be able to edit the order after submission.") }}'
        variant="primary"
        confirmText="{{ __('Submit') }}"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="submit" wire:loading.attr="disabled">
                {{ __('Submit') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
