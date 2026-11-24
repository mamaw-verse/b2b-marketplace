<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card header (same pattern as every other seller page) --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Team') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">{{ __('Manage staff access for :shop.', ['shop' => $this->shop->storefront_name]) }}</p>
        </div>

        <div class="divide-y divide-[var(--hs-neutral-100)]">
            {{-- Seats + invite --}}
            <section class="p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-[var(--hs-neutral-900)]">{{ __('Team seats') }}</h2>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">{{ __(':used of :limit seats used', ['used' => $this->shop->staffSeatsUsed(), 'limit' => config('marketplace.limits.staff_per_shop', 2)]) }}</p>
                    </div>
                    <flux:badge color="{{ $this->shop->staffSeatsUsed() >= (int) config('marketplace.limits.staff_per_shop', 2) ? 'amber' : 'green' }}">
                        {{ max(0, (int) config('marketplace.limits.staff_per_shop', 2) - $this->shop->staffSeatsUsed()) }} {{ __('available') }}
                    </flux:badge>
                </div>

                <form wire:submit="invite" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                    <flux:input wire:model="email" type="email" :label="__('Staff email')" placeholder="name@example.com" class="flex-1 w-full" />
                    <flux:button type="submit" variant="primary" class="w-full sm:w-auto" :disabled="$this->shop->staffSeatsUsed() >= (int) config('marketplace.limits.staff_per_shop', 2)" wire:loading.attr="disabled" wire:target="invite">{{ __('Send invitation') }}</flux:button>
                </form>
                @error('email') <p class="mt-2 text-sm text-[var(--hs-danger-700)]">{{ $message }}</p> @enderror
            </section>

            {{-- Active staff --}}
            <section class="p-6">
                <h2 class="text-base font-semibold text-[var(--hs-neutral-900)]">{{ __('Active staff') }}</h2>
                <div class="mt-4 divide-y divide-[var(--hs-neutral-100)]">
                    @forelse ($this->activeStaff as $member)
                        <div wire:key="active-staff-{{ $member->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-[var(--hs-neutral-900)]">{{ $member->user?->name ?? $member->email }}</p>
                                <p class="truncate text-sm text-[var(--hs-neutral-600)]">{{ $member->email }}</p>
                            </div>
                            <flux:button size="sm" variant="danger" wire:click="confirmRevoke({{ $member->id }})">{{ __('Remove access') }}</flux:button>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-[var(--hs-neutral-600)]">{{ __('No active staff members yet.') }}</p>
                    @endforelse
                </div>
            </section>

            {{-- Pending invitations --}}
            <section class="p-6">
                <h2 class="text-base font-semibold text-[var(--hs-neutral-900)]">{{ __('Pending invitations') }}</h2>
                <div class="mt-4 divide-y divide-[var(--hs-neutral-100)]">
                    @forelse ($this->pendingInvitations as $invitation)
                        <div wire:key="pending-invitation-{{ $invitation->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-[var(--hs-neutral-900)]">{{ $invitation->email }}</p>
                                <p class="text-sm text-[var(--hs-neutral-600)]">{{ __('Expires :date', ['date' => $invitation->expires_at?->toDateString() ?? __('expired')]) }}</p>
                            </div>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="subtle" wire:click="resend({{ $invitation->id }})">{{ __('Resend') }}</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="confirmRevoke({{ $invitation->id }})">{{ __('Revoke') }}</flux:button>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-[var(--hs-neutral-600)]">{{ __('No pending invitations.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <x-hs-confirm name="team-revoke-confirm" :title="__('Remove team access')" :message="__('This person will immediately lose access to the shop.')" variant="danger">
        <x-slot name="action"><flux:button variant="danger" wire:click="revoke" wire:loading.attr="disabled">{{ __('Confirm removal') }}</flux:button></x-slot>
    </x-hs-confirm>
</div>
