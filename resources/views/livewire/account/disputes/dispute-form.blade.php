<div>
    <x-hs-modal
        name="open-dispute-modal"
        model="show"
        :title="__('Open Dispute') . ' — ' . $order->order_number"
        size="lg"
    >
        <div class="space-y-4">
            {{-- Advisory banner --}}
            <div class="flex items-start gap-2.5 rounded-[var(--hs-radius-card)] bg-[var(--hs-warning-50)] border border-[var(--hs-warning-200)] p-4">
                <flux:icon name="chat-bubble-left-ellipsis" variant="mini" class="mt-0.5 shrink-0 text-[var(--hs-warning-700)]" />
                <p class="text-sm text-[var(--hs-warning-800)]">
                    <strong class="font-semibold">{{ __('Before you proceed:') }}</strong>
                    {{ __('We recommend chatting with the seller to resolve the issue before initiating a dispute.') }}
                </p>
            </div>

            <flux:select wire:model="type" label="Dispute Type">
                <option value="refund_only">Refund Only</option>
                <option value="return_refund">Return &amp; Refund</option>
            </flux:select>

            <flux:textarea wire:model="reason" label="Reason" rows="5" placeholder="Describe the issue..." />
            @error('reason') <p class="text-sm text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror

            {{-- Item selection: buyer picks which items and how many to dispute/return --}}
            <div>
                <label class="block text-sm font-medium text-[var(--hs-neutral-700)] mb-1.5">
                    {{ __('Items to Dispute') }} <span class="text-red-500">*</span>
                </label>
                <p class="text-xs text-[var(--hs-neutral-500)] mb-2">{{ __('Select the affected items and the quantity for each.') }}</p>

                <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] divide-y divide-[var(--hs-neutral-100)]">
                    @foreach ($order->items as $item)
                        @php $maxQty = (int) $item->quantity; @endphp
                        {{-- Alpine mirrors the checkbox so the qty input enables/disables and --}}
                        {{-- clamps live (1..ordered) without waiting for a server round-trip; --}}
                        {{-- both fields still bind to Livewire via wire:model.live. --}}
                        <div
                            class="flex items-center gap-3 px-3 py-2.5"
                            x-data="{ selected: @js((bool) ($selectedItems[$item->id]['selected'] ?? false)), max: {{ $maxQty }} }"
                        >
                            <flux:checkbox wire:model.live="selectedItems.{{ $item->id }}.selected" x-model="selected" />

                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-[var(--hs-neutral-900)] truncate">{{ $item->description }}</p>
                                <p class="text-xs text-[var(--hs-neutral-500)]">
                                    {{ __('Ordered:') }} {{ $maxQty }} {{ $item->unit }}
                                </p>
                            </div>

                            <div class="w-24 shrink-0">
                                <input
                                    type="number"
                                    min="1"
                                    max="{{ $maxQty }}"
                                    step="1"
                                    inputmode="numeric"
                                    wire:model.live="selectedItems.{{ $item->id }}.quantity"
                                    x-bind:disabled="! selected"
                                    x-on:input="
                                        if ($el.value !== '') {
                                            let v = Math.trunc(Number($el.value));
                                            if (v < 1) v = 1;
                                            if (v > max) v = max;
                                            $el.value = v;
                                        }
                                    "
                                    class="w-full rounded-md border border-[var(--hs-neutral-200)] px-2.5 py-1.5 text-sm text-right tabular-nums bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)] disabled:bg-[var(--hs-neutral-50)] disabled:text-[var(--hs-neutral-400)] {{ $errors->has("selectedItems.{$item->id}.quantity") ? '!border-red-500 !ring-1 !ring-red-500' : '' }}"
                                />
                            </div>
                        </div>
                        @error("selectedItems.{$item->id}.quantity")
                            <p class="px-3 pb-2 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p>
                        @enderror
                    @endforeach
                </div>

                @error('selectedItems') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
            </div>

            {{-- Photo upload (required) --}}
            <div>
                <label class="block text-sm font-medium text-[var(--hs-neutral-700)] mb-1.5">
                    {{ __('Supporting Photos') }} <span class="text-red-500">*</span>
                </label>
                <p class="text-xs text-[var(--hs-neutral-500)] mb-2">{{ __('Attach photos of the issue. 1 to 5 files (JPG, PNG, PDF). Max 10MB each.') }}</p>
                <div class="rounded-[var(--hs-radius-card)] border border-dashed border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-50)] px-4 py-4">
                    <input
                        type="file"
                        wire:model="disputePhotos"
                        multiple
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                        class="block w-full text-sm text-[var(--hs-neutral-500)] file:mr-4 file:rounded-[var(--hs-radius-pill)] file:border-0 file:bg-[var(--hs-green-50)] file:px-4 file:py-2 file:text-sm file:font-medium file:text-[var(--hs-green-700)] hover:file:bg-[var(--hs-green-100)]"
                    />
                </div>
                <div wire:loading wire:target="disputePhotos" class="mt-1.5 text-xs text-[var(--hs-neutral-500)]">{{ __('Uploading…') }}</div>

                @if (! empty($disputePhotos))
                    <ul class="mt-2 space-y-1">
                        @foreach ($disputePhotos as $photo)
                            <li class="flex items-center gap-1.5 text-xs text-[var(--hs-neutral-600)]">
                                <flux:icon name="paper-clip" variant="mini" class="size-3.5 text-[var(--hs-neutral-400)]" />
                                {{ $photo->getClientOriginalName() }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                @error('disputePhotos') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                @error('disputePhotos.*') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="submit" wire:loading.attr="disabled" wire:target="submit">
                    {{ __('Submit Dispute') }}
                </flux:button>
            </div>
        </x-slot>
    </x-hs-modal>
</div>
