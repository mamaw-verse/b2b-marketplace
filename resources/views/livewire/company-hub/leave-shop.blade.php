<div class="mx-auto max-w-xl px-4 py-10 sm:px-6">
    <flux:card>
        <flux:heading size="lg">{{ __('Leave shop team') }}</flux:heading>
        <p class="mt-2 text-sm text-[var(--hs-neutral-600)]">{{ __('You will lose access to this shop immediately. The owner can invite you again later.') }}</p>
        <flux:modal.trigger name="leave-shop-confirm">
            <flux:button class="mt-5" variant="danger">{{ __('Leave shop') }}</flux:button>
        </flux:modal.trigger>
        <x-hs-confirm name="leave-shop-confirm" :title="__('Leave shop team')" :message="__('You will immediately lose access to this shop.')" variant="danger">
            <x-slot name="action"><flux:button variant="danger" wire:click="leave" wire:loading.attr="disabled">{{ __('Confirm leave') }}</flux:button></x-slot>
        </x-hs-confirm>
    </flux:card>
</div>
