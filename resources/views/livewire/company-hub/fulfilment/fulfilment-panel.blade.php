@php
    $inputClasses = 'w-full border block appearance-none text-sm rounded-md py-1.5 h-8 leading-[1.125rem] ps-3 pe-3 bg-white text-zinc-700 placeholder-zinc-400 shadow-xs border-zinc-200 border-b-zinc-300/80 focus:outline-none focus:border-[var(--hs-green-400)] focus:ring-1 focus:ring-[var(--hs-green-400)] transition dark:bg-white/10 dark:text-zinc-300 dark:border-white/10 dark:placeholder-zinc-400';
    $inputInvalidClasses = '!border-red-500 !ring-1 !ring-red-500 !shadow-none';
    $labelClasses = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
@endphp

<div class="p-4 rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)]">
    <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-3">Fulfilment</h3>

    @if (! $fulfilment)
        <div class="space-y-3">
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="{{ $labelClasses }}">{{ __('Carrier') }} <span class="text-red-500">*</span></label>
                    <input
                        wire:model="carrier"
                        type="text"
                        placeholder="e.g. JRS Express"
                        class="{{ $inputClasses }} {{ $errors->has('carrier') ? $inputInvalidClasses : '' }}"
                    />
                    <p class="mt-1 text-xs text-[var(--hs-danger-500)] min-h-[1rem]">@error('carrier') {{ $message }} @enderror</p>
                </div>
                <div>
                    <label class="{{ $labelClasses }}">{{ __('Tracking Code') }} <span class="text-red-500">*</span></label>
                    <input
                        wire:model="trackingCode"
                        type="text"
                        class="{{ $inputClasses }} {{ $errors->has('trackingCode') ? $inputInvalidClasses : '' }}"
                    />
                    <p class="mt-1 text-xs text-[var(--hs-danger-500)] min-h-[1rem]">@error('trackingCode') {{ $message }} @enderror</p>
                </div>
                <div>
                    <label class="{{ $labelClasses }}">{{ __('Tracking URL') }} <span class="text-red-500">*</span></label>
                    <input
                        wire:model="trackingUrl"
                        type="url"
                        placeholder="https://..."
                        class="{{ $inputClasses }} {{ $errors->has('trackingUrl') ? $inputInvalidClasses : '' }}"
                    />
                    <p class="mt-1 text-xs text-[var(--hs-danger-500)] min-h-[1rem]">@error('trackingUrl') {{ $message }} @enderror</p>
                </div>
            </div>
            <flux:button size="sm" wire:click="createFulfilment">Create Fulfilment</flux:button>
        </div>
    @else
        <div class="flex items-center gap-2 mb-3">
            <x-hs-status-pill :status="$fulfilment->status" />
            @if ($fulfilment->carrier)
                <span class="text-sm text-[var(--hs-neutral-600)]">{{ $fulfilment->carrier }} &mdash; {{ $fulfilment->tracking_code }}</span>
            @endif
        </div>

        @if ($fulfilment->status === 'pending')
            <div class="space-y-3">
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="{{ $labelClasses }}">{{ __('Carrier') }} <span class="text-red-500">*</span></label>
                        <input
                            wire:model="carrier"
                            type="text"
                            class="{{ $inputClasses }} {{ $errors->has('carrier') ? $inputInvalidClasses : '' }}"
                        />
                        <p class="mt-1 text-xs text-[var(--hs-danger-500)] min-h-[1rem]">@error('carrier') {{ $message }} @enderror</p>
                    </div>
                    <div>
                        <label class="{{ $labelClasses }}">{{ __('Tracking Code') }} <span class="text-red-500">*</span></label>
                        <input
                            wire:model="trackingCode"
                            type="text"
                            class="{{ $inputClasses }} {{ $errors->has('trackingCode') ? $inputInvalidClasses : '' }}"
                        />
                        <p class="mt-1 text-xs text-[var(--hs-danger-500)] min-h-[1rem]">@error('trackingCode') {{ $message }} @enderror</p>
                    </div>
                    <div>
                        <label class="{{ $labelClasses }}">{{ __('Tracking URL') }} <span class="text-red-500">*</span></label>
                        <input
                            wire:model="trackingUrl"
                            type="url"
                            placeholder="https://..."
                            class="{{ $inputClasses }} {{ $errors->has('trackingUrl') ? $inputInvalidClasses : '' }}"
                        />
                        <p class="mt-1 text-xs text-[var(--hs-danger-500)] min-h-[1rem]">@error('trackingUrl') {{ $message }} @enderror</p>
                    </div>
                </div>
                <flux:modal.trigger name="confirm-mark-shipped">
                    <flux:button size="sm" variant="primary">Mark Shipped</flux:button>
                </flux:modal.trigger>
            </div>
        @elseif ($fulfilment->status === 'shipped')
            <div class="flex gap-2">
                <flux:modal.trigger name="confirm-mark-delivered">
                    <flux:button size="sm" variant="primary">Mark Delivered</flux:button>
                </flux:modal.trigger>
                @if (! $hasReturnDisputeInProgress)
                    <flux:modal.trigger name="confirm-mark-returned">
                        <flux:button size="sm" variant="ghost">Mark Returned</flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        @endif
    @endif

    <x-hs-confirm
        name="confirm-mark-shipped"
        title="Mark as Shipped"
        message="Mark this order as shipped?"
        variant="primary"
        confirmText="Mark Shipped"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="markShipped" wire:loading.attr="disabled">
                Mark Shipped
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    <x-hs-confirm
        name="confirm-mark-delivered"
        title="Mark as Delivered"
        message="Mark this order as delivered?"
        variant="primary"
        confirmText="Mark Delivered"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="markDelivered" wire:loading.attr="disabled">
                Mark Delivered
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    @if (! $hasReturnDisputeInProgress)
        <flux:modal name="confirm-mark-returned" class="md:w-[440px] !p-0">
            <div class="flex flex-col">
                {{-- Header --}}
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 flex items-center justify-center size-10 rounded-full text-[var(--hs-warning-500)] bg-[var(--hs-warning-50)]">
                            <flux:icon name="arrow-uturn-left" variant="mini" class="size-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-[var(--hs-neutral-900)]">{{ __('Mark as Returned') }}</h3>
                            <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">{{ __('Confirm the goods were returned. You can optionally issue a refund to the buyer now.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5 space-y-4">
                    <flux:select
                        wire:model.live="returnRefundType"
                        :label="__('Refund')"
                    >
                        <flux:select.option value="no_refund">{{ __('No refund') }}</flux:select.option>
                        <flux:select.option value="full_refund">{{ __('Full refund') }} ({{ $order->currency }} {{ number_format((float) $order->order_total, 2) }})</flux:select.option>
                        <flux:select.option value="partial_refund">{{ __('Partial refund') }}</flux:select.option>
                    </flux:select>

                    @if ($returnRefundType === 'partial_refund')
                        <div>
                            <flux:input
                                wire:model="returnRefundAmount"
                                :label="__('Refund Amount') . ' *'"
                                type="number"
                                step="0.01"
                                min="0.01"
                                max="{{ (float) $order->order_total }}"
                                :placeholder="'0.00'"
                            />
                            <p class="mt-1 text-xs text-[var(--hs-neutral-500)]">{{ __('Max:') }} {{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</p>
                            @error('returnRefundAmount')
                                <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="shrink-0 border-t border-[var(--hs-neutral-100)] px-6 py-4">
                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>
                        <flux:button
                            variant="filled"
                            wire:click="markReturned"
                            wire:loading.attr="disabled"
                        >
                            {{ __('Confirm Return') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
