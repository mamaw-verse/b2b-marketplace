<div>
    <div class="max-w-lg mx-auto bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Dispute — Order #{{ $order->id }}</h1>
            <x-hs-status-pill :status="$dispute->status" />
        </div>

        @if (session('success'))
            <div class="mx-6 mt-4 p-3 bg-[var(--hs-success-50)] border border-[var(--hs-success-200)] rounded-lg text-sm text-[var(--hs-success-700)]">{{ session('success') }}</div>
        @endif

        <div class="p-6 space-y-4">
            <div>
                <p class="text-sm text-[var(--hs-neutral-500)]">Type</p>
                <p class="text-sm font-medium text-[var(--hs-neutral-900)]">{{ str_replace('_', ' ', ucfirst($dispute->type)) }}</p>
            </div>
            <div>
                <p class="text-sm text-[var(--hs-neutral-500)]">Reason</p>
                <p class="text-sm text-[var(--hs-neutral-900)]">{{ $dispute->reason }}</p>
            </div>

            @if ($dispute->resolution_type)
                <div class="p-3 bg-[var(--hs-neutral-50)] rounded-lg">
                    <p class="text-sm text-[var(--hs-neutral-500)]">Resolution: {{ str_replace('_', ' ', $dispute->resolution_type) }}</p>
                    @if ($dispute->resolution_amount)
                        <p class="text-sm font-medium text-[var(--hs-neutral-900)]">PHP {{ number_format((float) $dispute->resolution_amount, 2) }}</p>
                    @endif
                    @if ($dispute->resolution_notes)
                        <p class="text-sm text-[var(--hs-neutral-700)] mt-1">{{ $dispute->resolution_notes }}</p>
                    @endif
                </div>
            @endif

            @unless (in_array($dispute->status, ['resolved_refunded', 'resolved_denied', 'withdrawn']))
                <div class="flex gap-2 pt-4 border-t border-[var(--hs-neutral-100)]">
                    @if ($dispute->status !== 'escalated')
                        <flux:modal.trigger name="confirm-escalate-dispute">
                            <flux:button size="sm">Escalate to Admin</flux:button>
                        </flux:modal.trigger>
                    @endif
                    <flux:modal.trigger name="confirm-withdraw-dispute">
                        <flux:button size="sm" variant="ghost">Withdraw</flux:button>
                    </flux:modal.trigger>
                </div>
            @endunless
        </div>
    </div>

    <x-hs-confirm
        name="confirm-escalate-dispute"
        title="Escalate to Admin"
        message="Escalate to admin? This cannot be undone."
        variant="warning"
        confirmText="Escalate"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="escalate" wire:loading.attr="disabled">
                Escalate
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    <x-hs-confirm
        name="confirm-withdraw-dispute"
        title="Withdraw Dispute"
        message="Withdraw this dispute?"
        variant="danger"
        confirmText="Withdraw"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="withdraw" wire:loading.attr="disabled">
                Withdraw
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
