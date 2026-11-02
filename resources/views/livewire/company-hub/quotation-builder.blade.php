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
                                @php
                                    $fieldClasses = 'w-full border block appearance-none text-sm rounded-md py-1.5 h-8 leading-[1.125rem] ps-3 pe-3 bg-white text-zinc-700 placeholder-zinc-400 shadow-xs border-zinc-200 border-b-zinc-300/80 focus:outline-none focus:border-[var(--hs-green-400)] focus:ring-1 focus:ring-[var(--hs-green-400)] transition dark:bg-white/10 dark:text-zinc-300 dark:border-white/10 dark:placeholder-zinc-400';
                                    $invalidClasses = '!border-red-500 !ring-1 !ring-red-500 !shadow-none';
                                    $labelStyle = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
                                    // Selects keep the native dropdown arrow (override the shared appearance-none).
                                    $selectClasses = $fieldClasses.' [appearance:auto]';
                                @endphp

                                {{-- Row header: item number + variant picker + remove --}}
                                <div class="flex items-center gap-3">
                                    <span class="shrink-0 flex items-center justify-center size-5 rounded-full bg-[var(--hs-neutral-200)] text-[10px] font-semibold text-[var(--hs-neutral-600)]">
                                        {{ $index + 1 }}
                                    </span>

                                    {{-- Variant picker. A native <select> shows its first <option> when --}}
                                    {{-- the bound value is null and no empty option exists, which made the --}}
                                    {{-- first variant *look* selected on load while the fields/payout stayed --}}
                                    {{-- blank (selectVariant only runs on change). The explicit empty option --}}
                                    {{-- keeps null showing the placeholder; x-init re-fires selectVariant when --}}
                                    {{-- a variant is genuinely preselected but the row hasn't been populated. --}}
                                    <div
                                        class="flex-1"
                                        wire:key="variant-picker-{{ $index }}"
                                        x-data="{
                                            variantId: @js((string) ($item['variant_id'] ?? '')),
                                            prefillIfNeeded() {
                                                const noDescription = !($wire.get('items.{{ $index }}.description'));
                                                if (this.variantId && noDescription) {
                                                    $wire.selectVariant({{ $index }}, parseInt(this.variantId));
                                                }
                                            },
                                        }"
                                        x-init="prefillIfNeeded()"
                                    >
                                        <select
                                            x-model="variantId"
                                            x-on:change="$wire.selectVariant({{ $index }}, $event.target.value ? parseInt($event.target.value) : null)"
                                            class="{{ $selectClasses }}"
                                        >
                                            <option value="">{{ __('Select a product variant…') }}</option>
                                            @foreach ($this->shopVariants as $v)
                                                <option value="{{ $v['id'] }}">
                                                    {{ $v['label'] }} · ₱{{ number_format((float) $v['price'], 2) }}/{{ $v['unit'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    @if (count($items) > 1)
                                        <flux:button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            wire:click="confirmRemoveItem({{ $index }})"
                                            class="shrink-0 text-[var(--hs-danger-500)] hover:bg-[var(--hs-danger-50)]"
                                        >
                                            <span class="sr-only">{{ __('Remove item') }}</span>
                                        </flux:button>
                                    @endif
                                </div>

                                {{-- Item fields --}}
                                {{-- The row runs 4 fields normally; when the unit is "Other" a 5th --}}
                                {{-- cell (the custom-unit input) appears beside the select and the --}}
                                {{-- grid widens from 12 to 15 tracks so no field shifts down. --}}
                                <div
                                    wire:key="fields-{{ $index }}"
                                    x-data="{
                                        knownUnits: @js(collect(config('units', []))->pluck('value')->all()),
                                        selected: '',
                                        custom: '',
                                        description: '',
                                        {{-- Error flags mirrored from the server, since these fields sit --}}
                                        {{-- inside wire:ignore and can't receive the morphed red ring. --}}
                                        descriptionError: @js((bool) ($itemFieldErrors[$index]['description'] ?? false)),
                                        unitError: @js((bool) ($itemFieldErrors[$index]['unit'] ?? false)),
                                        get isOther() { return this.selected === 'other'; },
                                        hydrateUnit(val) {
                                            val = val ?? '';
                                            const isKnown = this.knownUnits.includes(val) && val !== 'other';
                                            this.selected = (val && !isKnown) ? 'other' : (val || '');
                                            this.custom = (!isKnown && val !== '' && val !== 'other') ? val : '';
                                        },
                                        syncUnit() {
                                            const val = this.selected === 'other' ? this.custom : this.selected;
                                            $wire.set('items.{{ $index }}.unit', val);
                                        },
                                    }"
                                    x-init="
                                        hydrateUnit($wire.get('items.{{ $index }}.unit'));
                                        description = $wire.get('items.{{ $index }}.description') ?? '';
                                        $watch('selected', () => syncUnit());
                                        $watch('custom', () => { if (selected === 'other') syncUnit(); });
                                        $watch('description', (val) => $wire.set('items.{{ $index }}.description', val));
                                        {{-- Keep the client in sync when the server changes these (e.g. selectVariant). --}}
                                        {{-- Bracket notation is required here: Alpine evaluates this string as a --}}
                                        {{-- literal JS path expression, and "items.0.unit" is invalid JS syntax --}}
                                        {{-- (a numeric literal can't be followed by dot-access like that). --}}
                                        $watch('$wire.items[{{ $index }}].unit', (val) => hydrateUnit(val));
                                        $watch('$wire.items[{{ $index }}].description', (val) => { if ((val ?? '') !== description) description = val ?? ''; });
                                        {{-- Re-read the mirrored error flags after each validation round-trip. --}}
                                        $watch('$wire.itemFieldErrors', (bag) => {
                                            descriptionError = !!(bag?.[{{ $index }}]?.description);
                                            unitError = !!(bag?.[{{ $index }}]?.unit);
                                        });
                                    "
                                    :class="isOther ? 'sm:grid-cols-[repeat(15,minmax(0,1fr))]' : 'sm:grid-cols-12'"
                                    class="grid grid-cols-1 gap-3"
                                >
                                    {{-- Alpine-controlled (wire:ignore) so a variant selection prefills --}}
                                    {{-- it live, mirroring how the unit reacts to selectVariant. Typing --}}
                                    {{-- flows back to Livewire via the description watcher above. --}}
                                    <div class="sm:col-span-5" wire:ignore>
                                        <label class="{{ $labelStyle }}">{{ __('Description') }} <span class="text-red-500">*</span></label>
                                        <input
                                            x-model.debounce.300ms="description"
                                            type="text"
                                            placeholder="{{ __('e.g. Halal Coconut Flour 25 kg') }}"
                                            class="{{ $fieldClasses }}"
                                            :class="descriptionError ? '{{ $invalidClasses }}' : ''"
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
                                    {{-- Unit select --}}
                                    <div class="sm:col-span-2" wire:ignore>
                                        <label class="{{ $labelStyle }}">{{ __('Unit') }} <span class="text-red-500">*</span></label>
                                        <select
                                            x-model="selected"
                                            class="{{ $selectClasses }}"
                                            :class="unitError ? '{{ $invalidClasses }}' : ''"
                                        >
                                            <option value="">{{ __('Select…') }}</option>
                                            @foreach (config('units', []) as $unit)
                                                <option value="{{ $unit['value'] }}">{{ $unit['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    {{-- Custom-unit cell: only present when "Other" is chosen, sitting --}}
                                    {{-- beside the select as its own column (row widens to 15 tracks). --}}
                                    <div class="sm:col-span-3" x-show="isOther" x-cloak wire:ignore>
                                        <label class="{{ $labelStyle }}">{{ __('Custom Unit') }} <span class="text-red-500">*</span></label>
                                        <input
                                            x-model.debounce.300ms="custom"
                                            type="text"
                                            placeholder="{{ __('e.g. pallet') }}"
                                            class="{{ $fieldClasses }}"
                                            :class="unitError ? '{{ $invalidClasses }}' : ''"
                                        />
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
                    label="{{ __('Notes / Terms (optional)') }}"
                    placeholder="{{ __('Pricing valid for monthly contract. MOQ applies. Lead time: 10 business days.') }}"
                    rows="3"
                />

                {{-- Actions — right-aligned with the primary action last, matching --}}
                {{-- the other hub forms (shop profile, payout, order builder). The --}}
                {{-- destructive Withdraw is pushed to the far left. --}}
                <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-[var(--hs-neutral-100)]">
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

                    <flux:button type="submit" variant="filled" @class(['ml-auto' => ! $this->quotation?->isSubmitted()]) wire:loading.attr="disabled">
                        {{ __('Save Draft') }}
                    </flux:button>
                    <flux:button type="button" variant="primary" wire:click="confirmSubmit" wire:loading.attr="disabled">
                        {{ __('Submit Quotation') }}
                    </flux:button>
                </div>

            </form>
        </div>
    </div>

    {{-- Submit Confirmation --}}
    <x-hs-confirm
        name="submit-quotation-confirm"
        title="{{ __('Submit Quotation') }}"
        message="{{ __('Once submitted, this quotation cannot be edited. The buyer will see it immediately. You can withdraw it later if needed.') }}"
        variant="warning"
        confirmText="{{ __('Confirm & Submit') }}"
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
        title="{{ __('Withdraw Quotation') }}"
        message="{{ __('Withdraw this quotation? The buyer will no longer be able to award it.') }}"
        variant="danger"
        confirmText="{{ __('Withdraw') }}"
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
        title="{{ __('Remove Item') }}"
        message="{{ __('Remove this quotation item?') }}"
        variant="danger"
        confirmText="{{ __('Remove') }}"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="removeItem" wire:loading.attr="disabled">
                {{ __('Remove') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>