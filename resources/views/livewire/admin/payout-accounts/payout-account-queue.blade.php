<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Payout Accounts</h1>
        </div>

        {{-- Status filter tabs --}}
        <div class="px-4 sm:px-6 pt-5 pb-4 flex flex-wrap items-center gap-3 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            <div class="flex flex-wrap gap-1.5">
                @foreach (['pending', 'verified', 'rejected'] as $status)
                    <button
                        wire:click="filterByStatus('{{ $status }}')"
                        @class([
                            'px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition',
                            'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm ring-1 ring-[var(--hs-neutral-200)]' => $statusFilter === $status,
                            'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)] hover:bg-[var(--hs-neutral-0)]' => $statusFilter !== $status,
                        ])
                    >
                        {{ ucfirst($status) }}
                    </button>
                @endforeach
            </div>

            <div class="ml-auto w-64">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by shop name..."
                    icon="magnifying-glass"
                    size="sm"
                />
            </div>
        </div>

        {{-- Accounts table --}}
        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Shop</flux:table.column>
                    <flux:table.column>Bank</flux:table.column>
                    <flux:table.column>Account Holder</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Submitted</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->accounts as $account)
                        <flux:table.row :key="$account->id">
                            <flux:table.cell variant="strong">
                                {{ $account->shop?->storefront_name }}
                            </flux:table.cell>

                            <flux:table.cell>
                                {{ $account->bank_name }}
                            </flux:table.cell>

                            <flux:table.cell>
                                {{ $account->account_holder_name }}
                            </flux:table.cell>

                            <flux:table.cell class="py-0">
                                <span @class([
                                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                    'bg-amber-50 text-amber-700' => $account->verification_status === 'pending',
                                    'bg-green-50 text-green-700' => $account->verification_status === 'verified',
                                    'bg-red-50 text-red-700' => $account->verification_status === 'rejected',
                                ])>
                                    {{ ucfirst($account->verification_status) }}
                                </span>
                            </flux:table.cell>

                            <flux:table.cell>
                                {{ $account->created_at?->format('M d, Y') }}
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($account->verification_status === 'pending')
                                    <div class="flex gap-2">
                                        <flux:modal.trigger name="confirm-verify-account-{{ $account->id }}">
                                            <flux:button variant="primary" size="sm">
                                                Verify
                                            </flux:button>
                                        </flux:modal.trigger>
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            wire:click="openRejectModal({{ $account->id }})"
                                        >
                                            Reject
                                        </flux:button>
                                    </div>
                                @elseif ($account->verification_status === 'rejected')
                                    <span class="text-xs text-[var(--hs-neutral-500)]" title="{{ $account->rejection_reason }}">
                                        {{ Str::limit($account->rejection_reason, 30) }}
                                    </span>
                                @else
                                    <span class="text-xs text-[var(--hs-neutral-400)]">—</span>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center">
                                <x-hs-empty-state
                                    icon="banknotes"
                                    title="No payout accounts"
                                    subtitle="No payout accounts found for this status."
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($this->accounts->hasPages())
                <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">
                    {{ $this->accounts->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Rejection Modal --}}
    <flux:modal name="reject-payout-account">
        <div class="space-y-4">
            <flux:heading size="lg">Reject Payout Account</flux:heading>
            <p class="text-sm text-[var(--hs-neutral-600)]">
                Please provide a reason for rejecting this payout account. The seller will be notified.
            </p>

            <flux:textarea
                wire:model="rejectionReason"
                label="Rejection Reason"
                placeholder="Explain why this account is being rejected..."
                rows="3"
                required
            />
            @error('rejectionReason') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

            <div class="flex justify-end gap-3 pt-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('reject-payout-account').close()">
                    Cancel
                </flux:button>
                <flux:button variant="danger" wire:click="rejectAccount">
                    Reject Account
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Verify Confirmation Modals --}}
    @foreach ($this->accounts->filter(fn ($a) => $a->verification_status === 'pending') as $account)
        <x-hs-confirm
            name="confirm-verify-account-{{ $account->id }}"
            title="Verify Payout Account"
            message="Are you sure you want to verify the payout account for {{ $account->shop?->storefront_name }}? This will allow the seller to receive payouts."
            variant="primary"
            confirmText="Verify Account"
        >
            <x-slot name="action">
                <flux:button variant="primary" wire:click="verifyAccount({{ $account->id }})" wire:loading.attr="disabled">
                    Verify Account
                </flux:button>
            </x-slot>
        </x-hs-confirm>
    @endforeach
</div>
