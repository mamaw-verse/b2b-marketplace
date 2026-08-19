<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                {{ $quotation ? __('Edit Quotation') : __('New Quotation') }}
            </h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                {{ __('For RFQ:') }} {{ $rfqRecipient->rfq->title }}
            </p>
        </div>

        <div class="p-6">
            <form wire:submit="saveDraft" class="space-y-6">
                {{-- Line Items --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Line Items') }}</h2>
                        <button type="button" wire:click="addItem"
                            class="text-sm text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] font-medium">
                            + {{ __('Add Item') }}
                        </button>
                    </div>

                    <div class="space-y-4">
                        @foreach ($items as $index => $item)
                            <div class="rounded-lg border border-[var(--hs-neutral-200)] p-4 relative" wire:key="item-{{ $index }}">
                                @if (count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})"
                                        class="absolute top-2 right-2 text-[var(--hs-neutral-400)] hover:text-red-500 transition">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                    </button>
                                @endif

                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                    <div class="sm:col-span-5">
                                        <flux:input
                                            wire:model="items.{{ $index }}.description"
                                            label="Description"
                                            placeholder="e.g., Halal Coconut Flour 25kg"
                                            size="sm"
                                        />
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
                                    </div>
                                    <div class="sm:col-span-2">
                                        <flux:input
                                            wire:model="items.{{ $index }}.unit"
                                            label="Unit"
                                            placeholder="kg, pcs"
                                            size="sm"
                                        />
                                    </div>
                                    <div class="sm:col-span-3">
                                        <flux:input
                                            wire:model="items.{{ $index }}.unit_price"
                                            label="Unit Price (PHP)"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            size="sm"
                                        />
                                    </div>
                                </div>

                                @php
                                    $subtotal = is_numeric($item['quantity'] ?? null) && is_numeric($item['unit_price'] ?? null)
                                        ? number_format((float) $item['quantity'] * (float) $item['unit_price'], 2)
                                        : '0.00';
                                @endphp
                                <p class="mt-2 text-xs text-[var(--hs-neutral-500)] text-right">
                                    {{ __('Subtotal:') }} PHP {{ $subtotal }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    @error('items') <p class="mt-2 text-red-500 text-sm">{{ $message }}</p> @enderror
                    @error('items.*') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                {{-- Notes --}}
                <flux:textarea
                    wire:model="notes"
                    label="Notes / Terms (optional)"
                    placeholder="Pricing valid for monthly contract. MOQ applies. Lead time: 10 business days."
                    rows="3"
                />

                {{-- Actions --}}
                <div class="flex items-center gap-3 pt-4 border-t border-[var(--hs-neutral-100)]">
                    <flux:button type="submit" variant="ghost">
                        {{ __('Save Draft') }}
                    </flux:button>
                    <flux:button type="button" variant="primary" wire:click="confirmSubmit">
                        {{ __('Submit Quotation') }}
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('hub.rfqs.show', $rfqRecipient) }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>

    {{-- Submit Confirmation Modal --}}
    <flux:modal name="submit-confirm" wire:model="showSubmitConfirmation">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Submit Quotation') }}</flux:heading>
            <div class="rounded-lg bg-[var(--hs-warning-50)] border border-[var(--hs-warning-200)] p-3">
                <p class="text-sm text-[var(--hs-warning-700)]">
                    <strong>{{ __('Important:') }}</strong> {{ __('Once submitted, this quotation cannot be edited. The buyer will be able to see it immediately. You can withdraw it later if needed.') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="primary" wire:click="submitQuotation">
                    {{ __('Confirm & Submit') }}
                </flux:button>
                <flux:button variant="ghost" wire:click="$set('showSubmitConfirmation', false)">
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
