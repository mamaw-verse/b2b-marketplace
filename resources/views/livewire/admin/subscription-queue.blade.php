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

    {{-- Tab switcher --}}
    <div class="px-6 pt-4 flex gap-1 border-b border-[var(--hs-neutral-100)]">
        <button
            wire:click="switchTab('subscriptions')"
            class="px-4 py-2 text-sm font-medium rounded-t transition-colors
                {{ $tab === 'subscriptions'
                    ? 'text-[var(--hs-neutral-900)] border-b-2 border-[var(--hs-green-700)] -mb-px'
                    : 'text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)]' }}"
        >
            {{ __('All Subscriptions') }}
        </button>
        <button
            wire:click="switchTab('pending_payments')"
            class="px-4 py-2 text-sm font-medium rounded-t transition-colors
                {{ $tab === 'pending_payments'
                    ? 'text-[var(--hs-neutral-900)] border-b-2 border-[var(--hs-warning-600)] -mb-px'
                    : 'text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)]' }}"
        >
            {{ __('Pending Payments') }}
            @if ($this->pendingPaymentSubscriptions->total() > 0)
                <span class="ml-1.5 inline-flex items-center justify-center rounded-full bg-[var(--hs-warning-100)] text-[var(--hs-warning-700)] text-xs font-semibold px-2 py-0.5">
                    {{ $this->pendingPaymentSubscriptions->total() }}
                </span>
            @endif
        </button>
    </div>

    @if ($tab === 'subscriptions')
        {{-- Filters --}}
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
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Payment Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Period') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->subscriptions as $subscription)
                        @php $attempt = $subscription->paymentAttempts->first(); @endphp
                        <flux:table.row :key="$subscription->id" class="hover:bg-[var(--hs-neutral-50)] transition">
                            <flux:table.cell variant="strong">
                                {{ $subscription->shop->registered_business_name }}
                                <flux:text size="sm">{{ $subscription->shop->user?->email }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>{{ $subscription->plan->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="match($subscription->status) {
                                    'active' => 'green',
                                    'trialing' => 'blue',
                                    'pending_payment' => 'yellow',
                                    default => 'zinc',
                                }">
                                    {{ str($subscription->status)->headline() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-[var(--hs-neutral-600)]">
                                @if ($attempt?->created_at)
                                    {{ $attempt->created_at->format('M d, Y') }}
                                @else
                                    {{ __('—') }}
                                @endif
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
                            <flux:table.cell colspan="6" class="text-center">
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

    @else
        {{-- Pending Payments reconciliation tab --}}
        <div class="px-6 py-4 flex items-start gap-3 bg-[var(--hs-warning-50)] border-b border-[var(--hs-warning-200)]">
            <flux:icon name="exclamation-triangle" class="size-5 text-[var(--hs-warning-600)] flex-shrink-0 mt-0.5" />
            <p class="text-sm text-[var(--hs-warning-700)]">
                {{ __('These subscriptions are stuck in a pending payment state. Use the actions below to manually resolve them after verifying the payment status.') }}
            </p>
        </div>

        <div class="mt-1 overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Business') }}</flux:table.column>
                    <flux:table.column>{{ __('Plan') }}</flux:table.column>
                    <flux:table.column>{{ __('Payment') }}</flux:table.column>
                    <flux:table.column>{{ __('Sync Attempts') }}</flux:table.column>
                    <flux:table.column>{{ __('Initiated') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->pendingPaymentSubscriptions as $subscription)
                        @php $attempt = $subscription->paymentAttempts->first(); @endphp
                        <flux:table.row :key="$subscription->id" class="hover:bg-[var(--hs-neutral-50)] transition">
                            <flux:table.cell variant="strong">
                                {{ $subscription->shop->registered_business_name }}
                                <flux:text size="sm">{{ $subscription->shop->user?->email }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $subscription->plan->name }}
                                <flux:text size="sm">
                                    {{ $subscription->plan->currency }} {{ number_format((float) $subscription->plan->price, 2) }}
                                </flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($attempt)
                                    @if ($attempt->isManual())
                                        {{-- Manual payment: show reference number + receipt link --}}
                                        <flux:badge size="sm" color="blue" class="mb-1">{{ __('Manual') }}</flux:badge>
                                        @if ($attempt->manual_reference_number)
                                            <div>
                                                <code class="text-xs bg-[var(--hs-neutral-100)] px-1.5 py-0.5 rounded text-[var(--hs-neutral-700)]">
                                                    {{ $attempt->manual_reference_number }}
                                                </code>
                                            </div>
                                        @endif
                                        @if ($attempt->manual_receipt_path)
                                            <a
                                                href="{{ \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($attempt->manual_receipt_path, now()->addMinutes(30)) }}"
                                                target="_blank"
                                                class="mt-1 inline-flex items-center gap-1 text-xs text-[var(--hs-info-700)] hover:underline"
                                            >
                                                <flux:icon name="document-arrow-down" class="size-3" />
                                                {{ __('View Receipt') }}
                                            </a>
                                        @endif
                                    @else
                                        {{-- DLT payment: show transaction reference + status --}}
                                        <code class="text-xs bg-[var(--hs-neutral-100)] px-1.5 py-0.5 rounded text-[var(--hs-neutral-700)]">
                                            {{ $attempt->dlt_merchant_transaction_id }}
                                        </code>
                                        @if ($attempt->dlt_payment_status)
                                            <flux:badge size="sm" color="{{ $attempt->dlt_payment_status === 'PENDING' ? 'yellow' : 'zinc' }}" class="ml-1">
                                                {{ $attempt->dlt_payment_status }}
                                            </flux:badge>
                                        @endif
                                    @endif
                                @else
                                    <span class="text-[var(--hs-neutral-400)] text-sm">{{ __('No attempt yet') }}</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-[var(--hs-neutral-600)]">
                                {{ $attempt?->sync_attempts ?? 0 }}
                            </flux:table.cell>
                            <flux:table.cell class="text-[var(--hs-neutral-600)] text-sm">
                                {{ $attempt?->created_at?->format('M d, Y H:i') ?? $subscription->created_at->format('M d, Y H:i') }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:modal.trigger name="confirm-activate">
                                        <flux:button
                                            size="sm"
                                            variant="primary"
                                            wire:click="confirmActivate({{ $subscription->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="confirmActivate({{ $subscription->id }})"
                                        >
                                            <span wire:loading.remove wire:target="confirmActivate({{ $subscription->id }})">{{ __('Activate') }}</span>
                                            <span wire:loading wire:target="confirmActivate({{ $subscription->id }})">
                                                <svg class="animate-spin size-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            </span>
                                        </flux:button>
                                    </flux:modal.trigger>

                                    <flux:modal.trigger name="confirm-cancel">
                                        <flux:button
                                            size="sm"
                                            variant="danger"
                                            wire:click="confirmCancel({{ $subscription->id }})"
                                        >
                                            {{ __('Cancel') }}
                                        </flux:button>
                                    </flux:modal.trigger>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-10">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon name="check-circle" class="size-8 text-[var(--hs-success-700)]" />
                                    <p class="text-sm text-[var(--hs-neutral-500)]">{{ __('No pending payment subscriptions. All clear!') }}</p>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($this->pendingPaymentSubscriptions->hasPages())
            <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">
                {{ $this->pendingPaymentSubscriptions->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Force Activate confirmation --}}
<x-hs-confirm
    name="confirm-activate"
    title="{{ __('Force Activate Subscription') }}"
    message="{{ __('This will manually mark the subscription as active and the payment attempt as succeeded. Only do this after confirming payment was received in the DLT merchant portal.') }}"
    variant="primary"
>
    <x-slot name="action">
        <flux:button variant="primary" wire:click="forceActivate" wire:loading.attr="disabled">
            {{ __('Yes, Activate') }}
        </flux:button>
    </x-slot>
</x-hs-confirm>

{{-- Activate confirmation when DLT /sync still returns PENDING --}}
<x-hs-confirm
    name="confirm-activate-pending"
    title="{{ __('Payment Still Pending on DLT') }}"
    message="{{ __('The DLT gateway still reports this transaction as PENDING — it has not been confirmed as paid.') }}"
    variant="primary"
>
    <div class="mt-3 space-y-2 text-sm text-[var(--hs-neutral-700)]">
        <div class="flex items-start gap-2 p-3 bg-[var(--hs-warning-50)] border border-[var(--hs-warning-200)] rounded-lg">
            <flux:icon name="exclamation-triangle" class="size-4 text-[var(--hs-warning-600)] flex-shrink-0 mt-0.5" />
            <p class="text-[var(--hs-warning-700)] text-xs">
                {{ __('Only activate if you have independently verified payment was received in the DLT merchant portal or your bank statement. Activating without confirmed payment may result in a free subscription.') }}
            </p>
        </div>
    </div>
    <x-slot name="action">
        <flux:button variant="primary" wire:click="forceActivateOverride" wire:loading.attr="disabled">
            {{ __('Yes, Activate Anyway') }}
        </flux:button>
    </x-slot>
</x-hs-confirm>

{{-- Force Cancel confirmation --}}
<x-hs-confirm
    name="confirm-cancel"
    title="{{ __('Force Cancel Subscription') }}"
    message="{{ __('This will mark the subscription as cancelled and the payment attempt as failed. The merchant will need to restart the subscription process.') }}"
    variant="danger"
>
    <x-slot name="action">
        <flux:button variant="danger" wire:click="forceCancel" wire:loading.attr="disabled">
            {{ __('Yes, Cancel') }}
        </flux:button>
    </x-slot>
</x-hs-confirm>

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
