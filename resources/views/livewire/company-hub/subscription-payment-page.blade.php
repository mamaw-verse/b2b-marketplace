<div x-data x-on:redirect-to-payment.window="window.location.href = $event.detail.url">
    <div class="space-y-4">

        {{-- Header --}}
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Subscription Payment') }}</h1>
                    <p class="text-sm text-[var(--hs-neutral-500)]">{{ $subscription->plan->name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)]">
                        {{ $subscription->plan->currency }} {{ number_format((float) $subscription->plan->price, 2) }}
                    </p>
                    <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('per year') }}</p>
                </div>
            </div>
        </div>

        {{-- Inline error (non-failed steps) --}}
        @if ($errorMessage && $step === 'payment_form')
            <div class="p-4 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-sm text-[var(--hs-danger-700)]">
                {{ $errorMessage }}
            </div>
        @endif

        {{-- Step: Brand Selection --}}
        @if ($step === 'brands')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-6">
                @if ($loadingBrands)
                    <div class="flex flex-col items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[var(--hs-green-700)]"></div>
                        <p class="mt-3 text-sm text-[var(--hs-neutral-600)]">{{ __('Loading payment options...') }}</p>
                    </div>
                @else
                    @if (count($cardBrands) > 0)
                        <h2 class="text-sm font-semibold text-[var(--hs-neutral-700)] uppercase tracking-wider mb-3">{{ __('Credit / Debit Card') }}</h2>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mb-6">
                            @foreach ($cardBrands as $index => $brand)
                                <button wire:click="selectBrand({{ $index }}, true)" class="flex flex-col items-center gap-2 p-3 rounded-lg border border-[var(--hs-neutral-200)] hover:border-[var(--hs-green-400)] hover:bg-[var(--hs-green-50)] transition cursor-pointer">
                                    @if ($brand['image'])
                                        <img src="{{ $brand['image'] }}" alt="{{ $brand['value'] }}" class="h-8 object-contain" />
                                    @else
                                        <div class="h-8 w-8 rounded bg-[var(--hs-neutral-100)] flex items-center justify-center">
                                            <flux:icon name="credit-card" class="size-4 text-[var(--hs-neutral-500)]" />
                                        </div>
                                    @endif
                                    <span class="text-xs text-[var(--hs-neutral-700)] text-center">{{ $brand['value'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if (count($ewalletBrands) > 0)
                        <h2 class="text-sm font-semibold text-[var(--hs-neutral-700)] uppercase tracking-wider mb-3">{{ __('E-Wallet / Digital Payment') }}</h2>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            @foreach ($ewalletBrands as $index => $brand)
                                <button wire:click="selectBrand({{ $index }}, false)" class="flex flex-col items-center gap-2 p-3 rounded-lg border border-[var(--hs-neutral-200)] hover:border-[var(--hs-green-400)] hover:bg-[var(--hs-green-50)] transition cursor-pointer">
                                    @if ($brand['image'])
                                        <img src="{{ $brand['image'] }}" alt="{{ $brand['value'] }}" class="h-8 object-contain" />
                                    @else
                                        <div class="h-8 w-8 rounded bg-[var(--hs-neutral-100)] flex items-center justify-center">
                                            <flux:icon name="device-phone-mobile" class="size-4 text-[var(--hs-neutral-500)]" />
                                        </div>
                                    @endif
                                    <span class="text-xs text-[var(--hs-neutral-700)] text-center">{{ $brand['value'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if (count($brands) === 0 && ! $errorMessage)
                        <p class="text-center text-[var(--hs-neutral-500)] py-8">{{ __('No payment options available.') }}</p>
                    @endif
                @endif
            </div>

        {{-- Step: Billing / Payor Details --}}
        @elseif ($step === 'payment_form')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-6">
                <div class="flex items-center gap-3 mb-5">
                    <button wire:click="goBack" class="text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-900)]">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    </button>
                    <div class="flex items-center gap-2">
                        @if ($selectedBrand['image'] ?? null)
                            <img src="{{ $selectedBrand['image'] }}" alt="{{ $selectedBrand['value'] }}" class="h-6 object-contain" />
                        @endif
                        <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Pay with :brand', ['brand' => $selectedBrand['value'] ?? '']) }}</h2>
                    </div>
                </div>

                <p class="mb-5 text-sm text-[var(--hs-neutral-600)]">
                    @if ($selectedBrand['is_card'] ?? false)
                        {{ __("Enter your billing details. You'll enter your card number on the secure payment page.") }}
                    @else
                        {{ __("Enter your billing details. You'll be redirected to :brand to complete payment.", ['brand' => $selectedBrand['value'] ?? __('the payment provider')]) }}
                    @endif
                </p>

                <div class="space-y-4">
                    <div class="space-y-3">
                        <h3 class="text-sm font-medium text-[var(--hs-neutral-500)]">{{ __('Contact Details') }}</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <flux:input wire:model="firstName" label="{{ __('First Name') }}" required />
                            <flux:input wire:model="lastName" label="{{ __('Last Name') }}" required />
                        </div>
                        <flux:input wire:model="email" label="{{ __('Email') }}" type="email" required />
                        <flux:input wire:model="phone" label="{{ __('Phone') }}" placeholder="+63 917 XXX XXXX" />
                    </div>

                    <div class="space-y-3 pt-4 border-t border-[var(--hs-neutral-100)]">
                        <h3 class="text-sm font-medium text-[var(--hs-neutral-500)]">{{ __('Billing Address') }}</h3>
                        <flux:input wire:model="addressLine1" label="{{ __('Address Line 1') }}" required />
                        <flux:input wire:model="addressLine2" label="{{ __('Address Line 2') }}" />
                        <div class="grid grid-cols-2 gap-3">
                            <flux:input wire:model="city" label="{{ __('City') }}" required />
                            <flux:input wire:model="province" label="{{ __('Province') }}" required />
                        </div>
                        <flux:input wire:model="postalCode" label="{{ __('Postal Code') }}" placeholder="1000" required />
                    </div>

                    <flux:button variant="primary" class="w-full" wire:click="submitPayment" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitPayment">
                            {{ __('Continue to Payment') }} —
                            {{ $subscription->plan->currency }} {{ number_format((float) $subscription->plan->price, 2) }}
                        </span>
                        <span wire:loading wire:target="submitPayment">{{ __('Preparing payment...') }}</span>
                    </flux:button>

                    <p class="text-xs text-center text-[var(--hs-neutral-500)]">
                        <flux:icon name="lock-closed" class="inline size-3 mr-1" />
                        {{ __('You will enter payment details on the secure DLT-hosted checkout page.') }}
                    </p>
                </div>
            </div>

        {{-- Step: Processing (waiting for redirect) --}}
        @elseif ($step === 'processing')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-8 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[var(--hs-green-700)] mx-auto"></div>
                <h2 class="mt-4 text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Preparing Payment') }}</h2>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-1">{{ __('Redirecting you to the secure checkout page...') }}</p>
            </div>

        {{-- Step: Verifying (returned from DLT, syncing status) --}}
        @elseif ($step === 'verifying')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-8 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[var(--hs-green-700)] mx-auto"></div>
                <h2 class="mt-4 text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Verifying Payment') }}</h2>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-1">{{ __('Confirming your payment status, please wait...') }}</p>
            </div>

        {{-- Step: Success --}}
        @elseif ($step === 'success')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-green-200)] shadow-[var(--hs-shadow-sm)] p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-[var(--hs-green-50)] flex items-center justify-center mb-4">
                    <svg class="size-8 text-[var(--hs-green-700)]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </div>
                <h2 class="text-xl font-bold text-[var(--hs-neutral-900)]">{{ __('Payment Successful!') }}</h2>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-2">{{ __('Your subscription is now active.') }}</p>
                <flux:button variant="primary" class="mt-6" href="{{ route('hub.subscription') }}" wire:navigate>
                    {{ __('View Subscription') }}
                </flux:button>
            </div>

        {{-- Step: Pending (payment not yet settled) --}}
        @elseif ($step === 'pending')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-yellow-200 shadow-[var(--hs-shadow-sm)] p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-yellow-50 flex items-center justify-center mb-4">
                    <flux:icon name="clock" class="size-8 text-yellow-600" />
                </div>
                <h2 class="text-xl font-bold text-[var(--hs-neutral-900)]">{{ __('Payment Pending') }}</h2>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-2">{{ __('Your payment is still being processed. We\'ll update your subscription once it\'s confirmed.') }}</p>
                <flux:button variant="ghost" class="mt-6" href="{{ route('hub.subscription') }}" wire:navigate>
                    {{ __('Back to Subscription') }}
                </flux:button>
            </div>

        {{-- Step: Failed --}}
        @elseif ($step === 'failed')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-danger-200)] shadow-[var(--hs-shadow-sm)] p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-[var(--hs-danger-50)] flex items-center justify-center mb-4">
                    <svg class="size-8 text-[var(--hs-danger-700)]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </div>
                <h2 class="text-xl font-bold text-[var(--hs-neutral-900)]">{{ __('Payment Failed') }}</h2>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-2">{{ $errorMessage ?? __('Your payment could not be processed.') }}</p>
                <flux:button variant="primary" class="mt-6" href="{{ route('hub.subscription') }}" wire:navigate>
                    {{ __('Back to Subscription') }}
                </flux:button>
            </div>
        @endif

    </div>
</div>
