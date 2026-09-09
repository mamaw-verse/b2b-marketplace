<div>
<div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
    <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Subscriptions') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Track plan usage, trialing merchants, and renewals.') }}</p>
        </div>
        <flux:button variant="ghost" icon="cog-6-tooth" wire:click="openSettings">
            {{ __('Settings') }}
        </flux:button>
    </div>

    <div class="px-6 pt-5 grid gap-4 md:grid-cols-2">
        <flux:select wire:model.live="statusFilter" label="{{ __('Status') }}">
            <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="pending_payment">{{ __('Pending payment') }}</flux:select.option>
            <flux:select.option value="trialing">{{ __('Trialing') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="lapsed">{{ __('Lapsed') }}</flux:select.option>
            <flux:select.option value="cancelled">{{ __('Cancelled') }}</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="planFilter" label="{{ __('Plan') }}">
            <flux:select.option value="all">{{ __('All plans') }}</flux:select.option>
            @foreach ($this->plans as $plan)
                <flux:select.option value="{{ $plan->id }}">{{ $plan->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="mt-5 overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Business') }}</flux:table.column>
                <flux:table.column>{{ __('Plan') }}</flux:table.column>
                <flux:table.column>{{ __('Country') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Period') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->subscriptions as $subscription)
                    <flux:table.row :key="$subscription->id" class="hover:bg-[var(--hs-neutral-50)] transition">
                        <flux:table.cell variant="strong">
                            {{ $subscription->shop->registered_business_name }}
                            <flux:text size="sm">{{ $subscription->shop->user?->email }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>{{ $subscription->plan->name }}</flux:table.cell>
                        <flux:table.cell>{{ $subscription->shop->country_code }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$subscription->status === 'active' ? 'green' : ($subscription->status === 'trialing' ? 'blue' : 'zinc')">
                                {{ str($subscription->status)->headline() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-[var(--hs-neutral-600)]">
                            @if ($subscription->current_period_end)
                                {{ $subscription->current_period_end->format('M d, Y') }}
                            @else
                                {{ __('Not set') }}
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center">
                            {{ __('No subscriptions found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    @if ($this->subscriptions->hasPages())
        <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">
            {{ $this->subscriptions->links() }}
        </div>
    @endif
</div>

{{-- Subscription Settings Modal --}}
<flux:modal wire:model="showSettingsModal" class="md:w-[32rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Subscription Settings') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Configure subscription plan pricing and availability.') }}</flux:text>
        </div>

        @foreach ($settingsPlans as $index => $plan)
            <div class="space-y-4 {{ $index > 0 ? 'pt-4 border-t border-[var(--hs-neutral-100)]' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-[var(--hs-neutral-900)]">{{ $plan['name'] }}</p>
                        <p class="text-xs text-[var(--hs-neutral-500)]">
                            {{ strtoupper($plan['currency']) }} · {{ str($plan['billing_period'])->headline() }}
                        </p>
                    </div>
                    <flux:switch
                        wire:model="settingsPlans.{{ $index }}.is_active"
                        label="{{ __('Active') }}"
                    />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <flux:input
                            wire:model.blur="settingsPlans.{{ $index }}.price"
                            label="{{ __('Price') }} ({{ strtoupper($plan['currency']) }})"
                            type="number"
                            step="0.01"
                            min="0"
                            description="{{ __('Annual fee. 0.00 for free.') }}"
                            required
                        />
                        @error("settingsPlans.{$index}.price")
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:input
                            wire:model.blur="settingsPlans.{{ $index }}.trial_days"
                            label="{{ __('Trial Days') }}"
                            type="number"
                            min="0"
                            max="9999"
                            description="{{ __('Leave blank to disable.') }}"
                            placeholder="{{ __('No trial') }}"
                        />
                        @error("settingsPlans.{$index}.trial_days")
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        @endforeach

        <div class="flex justify-end gap-3 pt-2">
            <flux:button variant="ghost" wire:click="$set('showSettingsModal', false)">
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" wire:click="saveSettings">
                {{ __('Save') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
</div>
