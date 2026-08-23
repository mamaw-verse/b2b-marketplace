<div>
    <div class="max-w-lg mx-auto bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Open Dispute — Order #{{ $order->id }}</h1>
        </div>
        <div class="p-6 space-y-4">
            <flux:select wire:model="type" label="Dispute Type">
                <option value="refund_only">Refund Only</option>
                <option value="return_refund">Return & Refund</option>
            </flux:select>

            <flux:textarea wire:model="reason" label="Reason" rows="5" placeholder="Describe the issue..." />
            @error('reason') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" href="{{ route('account.orders.show', $order) }}" wire:navigate>Cancel</flux:button>
                <flux:button variant="primary" wire:click="submit">Submit Dispute</flux:button>
            </div>
        </div>
    </div>
</div>
