<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Payout Accounts') }}</h1>
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
                    placeholder="{{ __('Search by shop name...') }}"
                    icon="magnifying-glass"
                    size="sm"
                />
            </div>
        </div>

        {{-- Mobile: stacked card list --}}
        <div class="lg:hidden divide-y divide-[var(--hs-neutral-100)]">
            @forelse ($this->accounts as $account)
                <div class="px-6 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-[var(--hs-neutral-900)]">{{ $account->shop?->storefront_name }}</p>
                            <p class="text-sm text-[var(--hs-neutral-500)]">{{ $account->bank_name }}</p>
                        </div>
                        <span @class([
                            'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                            'bg-[var(--hs-warning-50)] text-[var(--hs-warning-700)]' => $account->verification_status === 'pending',
                            'bg-[var(--hs-success-50)] text-[var(--hs-success-700)]' => $account->verification_status === 'verified',
                            'bg-[var(--hs-danger-50)] text-[var(--hs-danger-700)]' => $account->verification_status === 'rejected',
                        ])>
                            {{ ucfirst($account->verification_status) }}
                        </span>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <div>
                            <dt class="text-xs text-[var(--hs-neutral-500)]">{{ __('Account Holder') }}</dt>
                            <dd class="text-[var(--hs-neutral-900)]">{{ $account->account_holder_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-[var(--hs-neutral-500)]">{{ __('Submitted') }}</dt>
                            <dd class="text-[var(--hs-neutral-600)]">{{ $account->created_at?->format('M d, Y') }}</dd>
                        </div>
                    </dl>
                    @if ($account->verification_status === 'pending')
                        <div class="mt-3 flex gap-2">
                            <flux:modal.trigger name="confirm-verify-account-{{ $account->id }}">
                                <flux:button variant="primary" size="sm">
                                    {{ __('Verify') }}
                                </flux:button>
                            </flux:modal.trigger>
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="openRejectModal({{ $account->id }})"
                            >
                                {{ __('Reject') }}
                            </flux:button>
                        </div>
                    @elseif ($account->verification_status === 'rejected')
                        <p class="mt-2 text-xs text-[var(--hs-neutral-500)]" title="{{ $account->rejection_reason }}">
                            {{ Str::limit($account->rejection_reason, 30) }}
                        </p>
                    @endif
                </div>
            @empty
                <div class="px-6 py-10 text-center">
                    <x-hs-empty-state
                        icon="banknotes"
                        title="{{ __('No payout accounts') }}"
                        subtitle="{{ __('No payout accounts found for this status.') }}"
                    />
                </div>
            @endforelse
        </div>

        {{-- Desktop: table --}}
        <div class="hidden overflow-hidden lg:block">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Shop') }}</flux:table.column>
                    <flux:table.column>{{ __('Bank') }}</flux:table.column>
                    <flux:table.column>{{ __('Account Holder') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Submitted') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
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
                                    'bg-[var(--hs-warning-50)] text-[var(--hs-warning-700)]' => $account->verification_status === 'pending',
                                    'bg-[var(--hs-success-50)] text-[var(--hs-success-700)]' => $account->verification_status === 'verified',
                                    'bg-[var(--hs-danger-50)] text-[var(--hs-danger-700)]' => $account->verification_status === 'rejected',
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
                                                {{ __('Verify') }}
                                            </flux:button>
                                        </flux:modal.trigger>
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            wire:click="openRejectModal({{ $account->id }})"
                                        >
                                            {{ __('Reject') }}
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
                                    title="{{ __('No payout accounts') }}"
                                    subtitle="{{ __('No payout accounts found for this status.') }}"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($this->accounts->hasPages())
            <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">
                {{ $this->accounts->links() }}
            </div>
        @endif
    </div>

    {{-- Rejection Modal --}}
    <flux:modal name="reject-payout-account" class="w-full sm:w-auto md:w-[440px]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Reject Payout Account') }}</flux:heading>
            <p class="text-sm text-[var(--hs-neutral-600)]">
                {{ __('Please provide a reason for rejecting this payout account. The seller will be notified.') }}
            </p>

            <flux:textarea
                wire:model="rejectionReason"
                label="{{ __('Rejection Reason') }}"
                placeholder="{{ __('Explain why this account is being rejected...') }}"
                rows="3"
                required
            />
            @error('rejectionReason') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 sm:gap-3 pt-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('reject-payout-account').close()" class="w-full sm:w-auto">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="rejectAccount" class="w-full sm:w-auto">
                    {{ __('Reject Account') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Verify Confirmation Modals --}}
    @foreach ($this->accounts->filter(fn ($a) => $a->verification_status === 'pending') as $account)
        <x-hs-confirm
            name="confirm-verify-account-{{ $account->id }}"
            title="{{ __('Verify Payout Account') }}"
            message="{{ __('Are you sure you want to verify the payout account for :shop? This will allow the seller to receive payouts.', ['shop' => $account->shop?->storefront_name]) }}"
            variant="primary"
            confirmText="{{ __('Verify Account') }}"
        >
            <x-slot name="action">
                <flux:button variant="primary" wire:click="verifyAccount({{ $account->id }})" wire:loading.attr="disabled">
                    {{ __('Verify Account') }}
                </flux:button>
            </x-slot>
        </x-hs-confirm>
    @endforeach
</div>
