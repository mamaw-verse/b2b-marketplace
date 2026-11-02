<div x-data x-on:redirect-to-payment.window="window.location.href = $event.detail.url">
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Subscription') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                @if (config('marketplace.features.subscriptions_enabled'))
                    {{ __('Choose a yearly plan to keep your products visible on the marketplace.') }}
                @else
                    {{ __('The marketplace is currently free. Subscription plans are not required right now.') }}
                @endif
            </p>
        </div>

        <div class="p-6 space-y-6">
            {{-- Pending payment banner --}}
            @if ($this->pendingSubscription)
                <div class="rounded-[var(--hs-radius-card)] border border-yellow-200 bg-yellow-50 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex gap-3">
                            <flux:icon name="clock" class="size-5 mt-0.5 shrink-0 text-yellow-600" />
                            <div>
                                <p class="text-sm font-semibold text-yellow-900">{{ __('Payment in progress') }}</p>
                                <p class="mt-0.5 text-sm text-yellow-700">
                                    {{ __('You have an incomplete payment for :plan. Complete it or dismiss to start over.', ['plan' => $this->pendingSubscription->plan->name]) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <flux:button variant="primary" size="sm" wire:click="resumePayment" wire:loading.attr="disabled">
                                {{ __('Complete Payment') }}
                            </flux:button>
                            <flux:button variant="ghost" size="sm" wire:click="dismissPendingPayment" wire:loading.attr="disabled">
                                {{ __('Dismiss') }}
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Trial expiry banner --}}
            @if ($this->trialDaysRemaining !== null && ! $this->pendingSubscription)
                @php
                    $urgent = $this->trialDaysRemaining <= 7;
                    $warning = $this->trialDaysRemaining <= 30;
                @endphp
                <div @class([
                    'rounded-[var(--hs-radius-card)] border p-5',
                    'border-red-200 bg-red-50' => $urgent,
                    'border-yellow-200 bg-yellow-50' => $warning && ! $urgent,
                    'border-blue-200 bg-blue-50' => ! $warning,
                ])>
                    <div class="flex items-start gap-3">
                        <flux:icon
                            :name="$urgent ? 'exclamation-triangle' : 'information-circle'"
                            @class([
                                'size-5 mt-0.5 shrink-0',
                                'text-red-600' => $urgent,
                                'text-yellow-600' => $warning && ! $urgent,
                                'text-blue-600' => ! $warning,
                            ])
                        />
                        <div>
                            @if ($this->trialDaysRemaining === 0)
                                <p class="text-sm font-semibold text-red-900">{{ __('Your trial expires today') }}</p>
                                <p class="mt-0.5 text-sm text-red-700">{{ __('Pay now to keep your products visible and avoid any interruption.') }}</p>
                            @elseif ($urgent)
                                <p class="text-sm font-semibold text-red-900">{{ __('Trial ends in :days days', ['days' => $this->trialDaysRemaining]) }}</p>
                                <p class="mt-0.5 text-sm text-red-700">{{ __('Pay now to continue uninterrupted after your trial ends.') }}</p>
                            @elseif ($warning)
                                <p class="text-sm font-semibold text-yellow-900">{{ __('Trial ends in :days days', ['days' => $this->trialDaysRemaining]) }}</p>
                                <p class="mt-0.5 text-sm text-yellow-700">{{ __('Subscribe before your trial ends so there\'s no gap in your marketplace visibility.') }}</p>
                            @else
                                <p class="text-sm font-semibold text-blue-900">{{ __('Trial ends in :days days', ['days' => $this->trialDaysRemaining]) }}</p>
                                <p class="mt-0.5 text-sm text-blue-700">{{ __('You can pay now to lock in your subscription — it will start when your trial ends.') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Current status card --}}
            <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.08em] text-[var(--hs-neutral-500)]">{{ __('Current status') }}</p>
                @if ($this->currentSubscription)
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="text-base font-semibold text-[var(--hs-neutral-900)]">{{ $this->currentSubscription->plan->name }}</span>
                        <flux:badge :color="match($this->currentSubscription->status) {
                            'active' => 'green',
                            'trialing' => 'blue',
                            'pending_payment' => 'yellow',
                            default => 'zinc',
                        }">
                            {{ str($this->currentSubscription->status)->headline() }}
                        </flux:badge>
                    </div>
                    <p class="mt-2 text-sm text-[var(--hs-neutral-600)]">
                        @if ($this->currentSubscription->status === 'trialing' && $this->currentSubscription->trial_ends_at)
                            {{ __('Free trial — ends :date', ['date' => $this->currentSubscription->trial_ends_at->format('M d, Y')]) }}
                        @elseif ($this->currentSubscription->status === 'pending_payment')
                            {{ __('Awaiting payment completion.') }}
                        @elseif ($this->currentSubscription->current_period_end)
                            {{ __('Renews :date', ['date' => $this->currentSubscription->current_period_end->format('M d, Y')]) }}
                        @else
                            {{ __('No renewal date set.') }}
                        @endif
                    </p>
                @else
                    <p class="mt-2 text-sm text-[var(--hs-neutral-600)]">{{ __('You do not have an active subscription yet.') }}</p>
                @endif
            </div>

            {{-- Plan cards — hidden only while a payment is pending --}}
            @unless ($this->pendingSubscription)
                {{-- Don't show plan cards if already active (not trialing, not lapsed) --}}
                @if (! $this->currentSubscription || in_array($this->currentSubscription->status, ['trialing', 'lapsed', 'cancelled']))
                    <div class="grid gap-4 lg:grid-cols-2">
                        @forelse ($this->plans as $plan)
                            <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-5 shadow-[var(--hs-shadow-sm)]">
                                <div class="flex items-start justify-between gap-4">
                                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ $plan->name }}</h2>
                                    @if ($this->canStartTrial($plan->id))
                                        <flux:badge color="blue">{{ __('Trial available') }}</flux:badge>
                                    @endif
                                </div>

                                <div class="mt-5">
                                    <p class="text-3xl font-semibold text-[var(--hs-neutral-900)]">
                                        {{ $plan->currency }} {{ number_format((float) $plan->price, 2) }}
                                    </p>
                                    <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('per year') }}</p>
                                </div>

                                @if ($this->currentSubscription?->status === 'trialing')
                                    <p class="mt-3 text-xs text-[var(--hs-neutral-500)]">
                                        {{ __('Your paid subscription will start when your trial ends on :date.', [
                                            'date' => $this->currentSubscription->trial_ends_at?->format('M d, Y'),
                                        ]) }}
                                    </p>
                                @endif

                                <div class="mt-5">
                                    <flux:button
                                        variant="primary"
                                        wire:click="subscribe({{ $plan->id }})"
                                        wire:loading.attr="disabled"
                                    >
                                        @if ($this->canStartTrial($plan->id))
                                            {{ __('Start Free Trial') }}
                                        @elseif ($this->currentSubscription?->status === 'trialing')
                                            {{ __('Pay Now — Start After Trial') }}
                                        @else
                                            {{ __('Pay Annual Fee') }}
                                        @endif
                                    </flux:button>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-[var(--hs-radius-card)] border border-dashed border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-8 text-center lg:col-span-2">
                                <p class="text-sm text-[var(--hs-neutral-600)]">{{ __('No active subscription plans are available right now.') }}</p>
                            </div>
                        @endforelse
                    </div>
                @endif
            @endunless
        </div>
    </div>
</div>
