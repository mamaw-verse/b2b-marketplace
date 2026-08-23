<div class="p-4 rounded-lg border border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)]">
    <h3 class="text-sm font-semibold text-[var(--hs-danger-700)] mb-3">Active Dispute</h3>

    @if ($dispute)
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <x-hs-status-pill :status="$dispute->status" />
                <span class="text-sm text-[var(--hs-neutral-700)]">{{ str_replace('_', ' ', ucfirst($dispute->type)) }}</span>
            </div>
            <p class="text-sm text-[var(--hs-neutral-700)]">{{ $dispute->reason }}</p>

            @if ($dispute->type === 'return_refund' && in_array($dispute->status, ['open', 'awaiting_return']))
                <flux:modal.trigger name="confirm-return">
                    <flux:button size="sm">Confirm Return Received</flux:button>
                </flux:modal.trigger>
            @endif

            @if (($dispute->type === 'refund_only' && $dispute->status === 'open') || ($dispute->type === 'return_refund' && $dispute->status === 'return_received'))
                <div class="border-t border-[var(--hs-neutral-200)] pt-3 space-y-2">
                    <flux:select wire:model="resolutionType" label="Resolution">
                        <option value="full_refund">Full Refund</option>
                        <option value="partial_refund">Partial Refund</option>
                        <option value="denied">Deny</option>
                    </flux:select>
                    @if ($resolutionType === 'partial_refund')
                        <flux:input wire:model="resolutionAmount" label="Amount" type="number" step="0.01" />
                    @endif
                    <flux:input wire:model="resolutionNotes" label="Notes" />
                    <flux:modal.trigger name="confirm-resolve-dispute">
                        <flux:button size="sm" variant="primary">Resolve</flux:button>
                    </flux:modal.trigger>
                </div>
            @endif

            {{-- Escalate to admin (available for both parties on non-terminal disputes) --}}
            @if (! in_array($dispute->status, ['escalated', 'resolved_refunded', 'resolved_denied', 'withdrawn']))
                <div class="border-t border-[var(--hs-neutral-200)] pt-3">
                    <flux:modal.trigger name="confirm-escalate-dispute">
                        <flux:button size="sm" variant="ghost">Escalate to Admin</flux:button>
                    </flux:modal.trigger>
                </div>
            @endif
        </div>
    @else
        <p class="text-sm text-[var(--hs-neutral-500)]">No active dispute.</p>
    @endif

    <x-hs-confirm
        name="confirm-return"
        title="Confirm Return"
        message="Confirm you received the returned item?"
        variant="primary"
        confirmText="Confirm"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="confirmReturn" wire:loading.attr="disabled">
                Confirm
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    <x-hs-confirm
        name="confirm-resolve-dispute"
        title="Resolve Dispute"
        message="Resolve this dispute?"
        variant="primary"
        confirmText="Resolve"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="resolve" wire:loading.attr="disabled">
                Resolve
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    <x-hs-confirm
        name="confirm-escalate-dispute"
        title="Escalate to Admin"
        message="Escalate this dispute to admin? This cannot be undone."
        variant="warning"
        confirmText="Escalate"
    >
        <x-slot name="action">
            <flux:button variant="filled" wire:click="escalate" wire:loading.attr="disabled">
                Escalate
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
