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
                <flux:modal.trigger name="confirm-mark-returned">
                    <flux:button size="sm" variant="ghost">Mark Returned</flux:button>
                </flux:modal.trigger>
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

    <x-hs-confirm
        name="confirm-mark-returned"
        title="Mark as Returned"
        message="Mark this order as returned to seller?"
        variant="warning"
        confirmText="Mark Returned"
    >
        <x-slot name="action">
            <flux:button variant="filled" wire:click="markReturned" wire:loading.attr="disabled">
                Mark Returned
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
