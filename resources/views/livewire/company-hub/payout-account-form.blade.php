<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                {{ __('Payout Account') }}
            </h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                {{ __('Your bank account details for receiving payouts. Changes will require re-verification by an admin.') }}
            </p>
        </div>

        {{-- Status Badge --}}
        @if ($verification_status)
            <div class="px-6 pt-5">
                <div @class([
                    'inline-flex items-center gap-2 px-3 py-1.5 rounded-[var(--hs-radius-pill)] text-sm font-medium',
                    'bg-amber-50 text-amber-700 ring-1 ring-amber-200' => $verification_status === 'pending',
                    'bg-green-50 text-green-700 ring-1 ring-green-200' => $verification_status === 'verified',
                    'bg-red-50 text-red-700 ring-1 ring-red-200' => $verification_status === 'rejected',
                ])>
                    @if ($verification_status === 'pending')
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        {{ __('Pending Verification') }}
                    @elseif ($verification_status === 'verified')
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        {{ __('Verified') }}
                        @if ($verified_at)
                            <span class="text-xs text-green-600">{{ $verified_at }}</span>
                        @endif
                    @elseif ($verification_status === 'rejected')
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        {{ __('Rejected') }}
                    @endif
                </div>

                @if ($verification_status === 'rejected' && $rejection_reason)
                    <div class="mt-3 p-3 bg-red-50 border border-red-100 rounded-lg">
                        <p class="text-sm font-medium text-red-800">{{ __('Rejection Reason:') }}</p>
                        <p class="mt-1 text-sm text-red-700">{{ $rejection_reason }}</p>
                    </div>
                @endif
            </div>
        @endif

        <div class="p-6">
            @if (session()->has('success'))
                <div class="mb-6 p-3 bg-green-50 border border-green-100 rounded-lg">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            <form wire:submit="save" class="space-y-6">
                {{-- Bank Name --}}
                <div>
                    <flux:select
                        wire:model="bank_name"
                        label="Bank Name"
                        placeholder="Select your bank"
                        required
                    >
                        @foreach ($this->bankList as $bank)
                            <flux:select.option value="{{ $bank }}">{{ $bank }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('bank_name') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                {{-- Bank Code --}}
                <div>
                    <flux:input
                        wire:model="bank_code"
                        label="Bank Code"
                        placeholder="e.g. BDO, BPI, MBTC"
                        required
                    />
                    @error('bank_code') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                {{-- Account Holder Name --}}
                <div>
                    <flux:input
                        wire:model="account_holder_name"
                        label="Account Holder Name"
                        placeholder="Full name as shown on bank account"
                        required
                    />
                    @error('account_holder_name') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                {{-- Account Number --}}
                <div>
                    <flux:input
                        wire:model="account_number"
                        label="Account Number"
                        placeholder="Enter your bank account number"
                        required
                    />
                    @error('account_number') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                {{-- Branch --}}
                <div>
                    <flux:input
                        wire:model="branch"
                        label="Branch (Optional)"
                        placeholder="Branch name or location"
                    />
                    @error('branch') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                {{-- Submit --}}
                <div class="flex items-center gap-3 pt-4 border-t border-[var(--hs-neutral-100)]">
                    <flux:button type="submit" variant="primary">
                        {{ $hasExistingAccount ? __('Update Account') : __('Save Account') }}
                    </flux:button>

                    @if ($hasExistingAccount && $verification_status === 'verified')
                        <p class="text-sm text-[var(--hs-neutral-500)]">
                            {{ __('Updating will reset verification status to pending.') }}
                        </p>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
