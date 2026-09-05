<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Arbitrate Dispute — :order', ['order' => $dispute->order->order_number]) }}</h1>
        </div>

        @if (session('error'))
            <div class="mx-6 mt-4 p-3 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-sm text-[var(--hs-danger-700)]">{{ session('error') }}</div>
        @endif

        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Order Context --}}
            <div class="space-y-4">
                <h2 class="text-sm font-medium text-[var(--hs-neutral-500)]">{{ __('Order Details') }}</h2>
                <div class="text-sm space-y-1">
                    <p><span class="text-[var(--hs-neutral-500)]">{{ __('Shop:') }}</span> {{ $dispute->order->shop->storefront_name }}</p>
                    <p><span class="text-[var(--hs-neutral-500)]">{{ __('Buyer:') }}</span> {{ $dispute->openedBy->first_name }} {{ $dispute->openedBy->last_name }}</p>
                    <p><span class="text-[var(--hs-neutral-500)]">{{ __('Total:') }}</span> {{ $dispute->order->currency }} {{ number_format((float) $dispute->order->order_total, 2) }}</p>
                </div>

                <h2 class="text-sm font-medium text-[var(--hs-neutral-500)] mt-4">{{ __('Dispute Reason') }}</h2>
                <p class="text-sm text-[var(--hs-neutral-900)]">{{ $dispute->reason }}</p>
            </div>

            {{-- Arbitration Form --}}
            <div class="space-y-4">
                <h2 class="text-sm font-medium text-[var(--hs-neutral-500)]">{{ __('Arbitration Decision') }}</h2>

                <flux:select wire:model="resolutionType" label="{{ __('Resolution Type') }}">
                    <option value="full_refund">{{ __('Full Refund') }}</option>
                    <option value="partial_refund">{{ __('Partial Refund') }}</option>
                    <option value="denied">{{ __('Deny (Order Stands)') }}</option>
                </flux:select>

                @if ($resolutionType === 'partial_refund')
                    <flux:input wire:model="resolutionAmount" label="{{ __('Refund Amount') }}" type="number" step="0.01" max="{{ $dispute->order->order_total }}" />
                @endif

                <flux:textarea wire:model="resolutionNotes" label="{{ __('Resolution Notes (required)') }}" rows="4" />

                <flux:modal.trigger name="confirm-submit-arbitration">
                    <flux:button variant="primary">{{ __('Submit Decision') }}</flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </div>

    <x-hs-confirm
        name="confirm-submit-arbitration"
        title="{{ __('Submit Arbitration') }}"
        message="{{ __('Submit arbitration decision? This is final.') }}"
        variant="warning"
        confirmText="{{ __('Submit Decision') }}"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="arbitrate" wire:loading.attr="disabled">
                {{ __('Submit Decision') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
