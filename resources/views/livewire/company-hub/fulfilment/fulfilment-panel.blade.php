<div class="p-4 rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)]">
    <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-3">Fulfilment</h3>

    @if (! $fulfilment)
        <div class="space-y-3">
            <div class="grid grid-cols-3 gap-2">
                <flux:input wire:model="carrier" label="Carrier" placeholder="e.g. JRS Express" />
                <flux:input wire:model="trackingCode" label="Tracking Code" />
                <flux:input wire:model="trackingUrl" label="Tracking URL" />
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
                    <flux:input wire:model="carrier" label="Carrier" />
                    <flux:input wire:model="trackingCode" label="Tracking Code" />
                    <flux:input wire:model="trackingUrl" label="Tracking URL" />
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
