<div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6">
    <div>
        <flux:heading size="xl">{{ __('Team') }}</flux:heading>
        <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">{{ __('Manage staff access for :shop.', ['shop' => $this->shop->storefront_name]) }}</p>
    </div>

    <flux:card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="lg">{{ __('Team seats') }}</flux:heading>
                <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">{{ __(':used of :limit seats used', ['used' => $this->shop->staffSeatsUsed(), 'limit' => config('marketplace.limits.staff_per_shop', 2)]) }}</p>
            </div>
            <flux:badge color="{{ $this->shop->staffSeatsUsed() >= (int) config('marketplace.limits.staff_per_shop', 2) ? 'amber' : 'green' }}">
                {{ max(0, (int) config('marketplace.limits.staff_per_shop', 2) - $this->shop->staffSeatsUsed()) }} {{ __('available') }}
            </flux:badge>
        </div>

        <form wire:submit="invite" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
            <flux:input wire:model="email" type="email" :label="__('Staff email')" placeholder="name@example.com" class="flex-1 w-full" />
            <flux:button type="submit" variant="primary" :disabled="$this->shop->staffSeatsUsed() >= (int) config('marketplace.limits.staff_per_shop', 2)" wire:loading.attr="disabled" wire:target="invite">{{ __('Send invitation') }}</flux:button>
        </form>
        @error('email') <p class="mt-2 text-sm text-[var(--hs-danger-700)]">{{ $message }}</p> @enderror
    </flux:card>

    <flux:card>
        <flux:heading size="lg">{{ __('Active staff') }}</flux:heading>
        <div class="mt-4 divide-y divide-[var(--hs-neutral-200)]">
            @forelse ($this->activeStaff as $member)
                <div wire:key="active-staff-{{ $member->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div><p class="font-medium text-[var(--hs-neutral-900)]">{{ $member->user?->name ?? $member->email }}</p><p class="text-sm text-[var(--hs-neutral-600)]">{{ $member->email }}</p></div>
                    <flux:button size="sm" variant="danger" wire:click="confirmRevoke({{ $member->id }})">{{ __('Remove access') }}</flux:button>
                </div>
            @empty
                <p class="py-4 text-sm text-[var(--hs-neutral-600)]">{{ __('No active staff members yet.') }}</p>
            @endforelse
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">{{ __('Pending invitations') }}</flux:heading>
        <div class="mt-4 divide-y divide-[var(--hs-neutral-200)]">
            @forelse ($this->pendingInvitations as $invitation)
                <div wire:key="pending-invitation-{{ $invitation->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div><p class="font-medium text-[var(--hs-neutral-900)]">{{ $invitation->email }}</p><p class="text-sm text-[var(--hs-neutral-600)]">{{ __('Expires :date', ['date' => $invitation->expires_at?->toDateString() ?? __('expired')]) }}</p></div>
                    <div class="flex gap-2">
                        <flux:button size="sm" variant="subtle" wire:click="resend({{ $invitation->id }})">{{ __('Resend') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="confirmRevoke({{ $invitation->id }})">{{ __('Revoke') }}</flux:button>
                    </div>
                </div>
            @empty
                <p class="py-4 text-sm text-[var(--hs-neutral-600)]">{{ __('No pending invitations.') }}</p>
            @endforelse
        </div>
    </flux:card>

    <x-hs-confirm name="team-revoke-confirm" :title="__('Remove team access')" :message="__('This person will immediately lose access to the shop.')" variant="danger">
        <x-slot name="action"><flux:button variant="danger" wire:click="revoke" wire:loading.attr="disabled">{{ __('Confirm removal') }}</flux:button></x-slot>
    </x-hs-confirm>
</div>
