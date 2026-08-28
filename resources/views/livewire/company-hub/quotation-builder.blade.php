<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                {{ $this->quotation ? __('Edit Quotation') : __('New Quotation') }}
            </h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                {{ __('RFQ:') }} <span class="font-medium text-[var(--hs-neutral-700)]">{{ $this->rfqRecipient->rfq->title }}</span>
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
                                            placeholder="Select a product variant…"
                                        >
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
                                            wire:click="confirmRemoveItem({{ $index }})"
                                            class="shrink-0 text-[var(--hs-danger-500)] hover:bg-[var(--hs-danger-50)]"
                                        />
                                    @endif
                                </div>

                                {{-- Item fields --}}
                                @php
                                    $fieldClasses = 'w-full border block appearance-none text-sm rounded-md py-1.5 h-8 leading-[1.125rem] ps-3 pe-3 bg-white text-zinc-700 placeholder-zinc-400 shadow-xs border-zinc-200 border-b-zinc-300/80 focus:outline-none focus:border-[var(--hs-green-400)] focus:ring-1 focus:ring-[var(--hs-green-400)] transition dark:bg-white/10 dark:text-zinc-300 dark:border-white/10 dark:placeholder-zinc-400';
                                    $invalidClasses = '!border-red-500 !ring-1 !ring-red-500 !shadow-none';
                                    $labelStyle = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
                                @endphp
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                    <div class="sm:col-span-5">
                                        <label class="{{ $labelStyle }}">{{ __('Description') }} <span class="text-red-500">*</span></label>
                                        <input
                                            wire:model="items.{{ $index }}.description"
                                            type="text"
                                            placeholder="e.g. Halal Coconut Flour 25 kg"
                                            class="{{ $fieldClasses }} {{ $errors->has("items.{$index}.description") ? $invalidClasses : '' }}"
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="{{ $labelStyle }}">{{ __('Qty') }} <span class="text-red-500">*</span></label>
                                        <input
                                            wire:model.live.debounce.300ms="items.{{ $index }}.quantity"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            class="{{ $fieldClasses }} {{ $errors->has("items.{$index}.quantity") ? $invalidClasses : '' }}"
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="{{ $labelStyle }}">{{ __('Unit') }} <span class="text-red-500">*</span></label>
                                        <select
                                            wire:model="items.{{ $index }}.unit"
                                            class="{{ $fieldClasses }} {{ $errors->has("items.{$index}.unit") ? $invalidClasses : '' }}"
                                        >
                                            <option value="">{{ __('Select…') }}</option>
                                            @foreach (config('units', []) as $unit)
                                                <option value="{{ $unit['value'] }}">{{ $unit['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="{{ $labelStyle }}">{{ __('Unit Price (₱)') }} <span class="text-red-500">*</span></label>
                                        <input
                                            wire:model.live.debounce.300ms="items.{{ $index }}.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            class="{{ $fieldClasses }} {{ $errors->has("items.{$index}.unit_price") ? $invalidClasses : '' }}"
                                        />
                                    </div>
                                </div>
                                @if ($errors->hasAny(["items.{$index}.description", "items.{$index}.quantity", "items.{$index}.unit", "items.{$index}.unit_price"]))
                                    <div class="text-xs text-[var(--hs-danger-500)] space-y-0.5">
                                        @error("items.{$index}.description") <p>{{ $message }}</p> @enderror
                                        @error("items.{$index}.quantity") <p>{{ $message }}</p> @enderror
                                        @error("items.{$index}.unit") <p>{{ $message }}</p> @enderror
                                        @error("items.{$index}.unit_price") <p>{{ $message }}</p> @enderror
                                    </div>
                                @endif

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

                    {{-- Payout Simulator --}}
                    @php
                        $commissionRate = \App\Models\PayoutSetting::getCommissionFeeRate();
                        $transactionFee = \App\Models\PayoutSetting::getTransactionFeeAmount();
                        $commissionAmount = round($runningTotal * $commissionRate, 2);
                        $totalFees = round($transactionFee + $commissionAmount, 2);
                        $netPayout = round($runningTotal - $totalFees, 2);
                    @endphp
                    <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <flux:icon name="calculator" variant="mini" class="size-4 text-[var(--hs-neutral-500)]" />
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)]">{{ __('Payout Estimate') }}</h3>
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-sm">
                                <span class="text-[var(--hs-neutral-600)]">{{ __('Quotation Total') }}</span>
                                <span class="font-medium text-[var(--hs-neutral-900)] tabular-nums">₱{{ number_format($runningTotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-[var(--hs-neutral-600)]">{{ __('Commission') }} ({{ number_format($commissionRate * 100, 1) }}%)</span>
                                <span class="font-medium text-[var(--hs-neutral-700)] tabular-nums">−₱{{ number_format($commissionAmount, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-[var(--hs-neutral-600)]">{{ __('Transaction Fee') }}</span>
                                <span class="font-medium text-[var(--hs-neutral-700)] tabular-nums">−₱{{ number_format($transactionFee, 2) }}</span>
                            </div>
                            <div class="pt-2 mt-1 border-t border-[var(--hs-neutral-200)] flex justify-between text-sm">
                                <span class="font-semibold text-[var(--hs-neutral-900)]">{{ __('Estimated Payout') }}</span>
                                <span class="font-bold text-[var(--hs-green-700)] tabular-nums">₱{{ number_format(max(0, $netPayout), 2) }}</span>
                            </div>
                        </div>
                        <p class="mt-2.5 text-xs text-[var(--hs-neutral-400)]">{{ __('Based on current platform rates. Actual payout calculated after order completion.') }}</p>
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
                    <flux:button variant="ghost" href="{{ route('hub.rfqs.show', $this->rfqRecipient) }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>

                    @if ($this->quotation?->isSubmitted())
                        <flux:button
                            type="button"
                            variant="ghost"
                            wire:click="confirmWithdraw"
                            class="ml-auto text-[var(--hs-danger-500)] hover:bg-[var(--hs-danger-50)]"
                        >
                            {{ __('Withdraw') }}
                        </flux:button>
                    @endif
                </div>

            </form>
        </div>
    </div>

    {{-- Submit Confirmation --}}
    <x-hs-confirm
        name="submit-quotation-confirm"
        title="Submit Quotation"
        message="Once submitted, this quotation cannot be edited. The buyer will see it immediately. You can withdraw it later if needed."
        variant="warning"
        confirmText="Confirm & Submit"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="submitQuotation" wire:loading.attr="disabled">
                {{ __('Confirm & Submit') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    {{-- Withdraw Confirmation --}}
    <x-hs-confirm
        name="withdraw-quotation-confirm"
        title="Withdraw Quotation"
        message="Withdraw this quotation? The buyer will no longer be able to award it."
        variant="danger"
        confirmText="Withdraw"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="withdrawQuotation" wire:loading.attr="disabled">
                {{ __('Withdraw') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    {{-- Remove Item Confirmation --}}
    <x-hs-confirm
        name="confirm-remove-quotation-item"
        title="Remove Item"
        message="Remove this quotation item?"
        variant="danger"
        confirmText="Remove"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="removeItem" wire:loading.attr="disabled">
                {{ __('Remove') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>