<div x-data x-on:redirect-to-payment.window="window.location.href = $event.detail.url">
    <div class="space-y-4">
        {{-- Order Summary Header --}}
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Pay for :order', ['order' => $order->order_number]) }}</h1>
                    <p class="text-sm text-[var(--hs-neutral-500)]">{{ $order->shop->storefront_name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)]">{{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</p>
                    <p class="text-xs text-[var(--hs-neutral-500)]">{{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}</p>
                </div>
            </div>
        </div>

        {{-- Error Message --}}
        @if ($errorMessage)
            <div class="p-4 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-sm text-[var(--hs-danger-700)]">
                {{ $errorMessage }}
                @if ($step === 'failed')
                    <flux:button size="sm" class="mt-2" wire:click="retry">{{ __('Try Again') }}</flux:button>
                @endif
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
                    {{-- Card Brands --}}
                    @if (count($cardBrands) > 0)
                        <h2 class="text-sm font-semibold text-[var(--hs-neutral-700)] uppercase tracking-wider mb-3">{{ __('Credit / Debit Card') }}</h2>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mb-6">
                            @foreach ($cardBrands as $index => $brand)
                                <button
                                    wire:click="selectBrand({{ $index }}, true)"
                                    class="flex flex-col items-center gap-2 p-3 rounded-lg border border-[var(--hs-neutral-200)] hover:border-[var(--hs-green-400)] hover:bg-[var(--hs-green-50)] transition cursor-pointer"
                                >
                                    @if ($brand['image'])
                                        <img src="{{ $brand['image'] }}" alt="{{ $brand['value'] }}" class="h-8 object-contain" />
                                    @else
                                        <div class="h-8 w-8 rounded bg-[var(--hs-neutral-100)] flex items-center justify-center">
                                            <svg class="size-4 text-[var(--hs-neutral-500)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                                        </div>
                                    @endif
                                    <span class="text-xs text-[var(--hs-neutral-700)] text-center">{{ $brand['value'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- E-wallet Brands --}}
                    @if (count($ewalletBrands) > 0)
                        <h2 class="text-sm font-semibold text-[var(--hs-neutral-700)] uppercase tracking-wider mb-3">{{ __('E-Wallet / Digital Payment') }}</h2>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            @foreach ($ewalletBrands as $index => $brand)
                                <button
                                    wire:click="selectBrand({{ $index }}, false)"
                                    class="flex flex-col items-center gap-2 p-3 rounded-lg border border-[var(--hs-neutral-200)] hover:border-[var(--hs-green-400)] hover:bg-[var(--hs-green-50)] transition cursor-pointer"
                                >
                                    @if ($brand['image'])
                                        <img src="{{ $brand['image'] }}" alt="{{ $brand['value'] }}" class="h-8 object-contain" />
                                    @else
                                        <div class="h-8 w-8 rounded bg-[var(--hs-neutral-100)] flex items-center justify-center">
                                            <svg class="size-4 text-[var(--hs-neutral-500)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
                                        </div>
                                    @endif
                                    <span class="text-xs text-[var(--hs-neutral-700)] text-center">{{ $brand['value'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if (count($brands) === 0 && !$errorMessage)
                        <p class="text-center text-[var(--hs-neutral-500)] py-8">{{ __('No payment options available.') }}</p>
                    @endif
                @endif
            </div>

        {{-- Step: Card Payment Form --}}
        @elseif ($step === 'card_form')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-6">
                <div class="flex items-center gap-3 mb-5">
                    <button wire:click="goBack" class="text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-900)]">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    </button>
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Pay with :brand', ['brand' => $selectedBrand['value'] ?? __('Card')]) }}</h2>
                </div>

                <div class="space-y-4">
                    {{-- Card Details --}}
                    <div class="space-y-3">
                        <h3 class="text-sm font-medium text-[var(--hs-neutral-500)]">{{ __('Card Details') }}</h3>
                        <flux:input wire:model="cardNumber" label="{{ __('Card Number') }}" placeholder="4111 1111 1111 1111" maxlength="19" />
                        <flux:input wire:model="cardholderName" label="{{ __('Cardholder Name') }}" placeholder="JOHN DOE" />
                        <div class="grid grid-cols-3 gap-3">
                            <flux:select wire:model="expiryMonth" label="{{ __('Month') }}">
                                <option value="">MM</option>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}</option>
                                @endfor
                            </flux:select>
                            <flux:select wire:model="expiryYear" label="{{ __('Year') }}">
                                <option value="">YY</option>
                                @for ($y = now()->year; $y <= now()->year + 10; $y++)
                                    <option value="{{ $y }}">{{ substr((string) $y, -2) }}</option>
                                @endfor
                            </flux:select>
                            <flux:input wire:model="ccv" label="{{ __('CVV') }}" type="password" placeholder="•••" maxlength="4" />
                        </div>
                    </div>

                    {{-- Billing Info --}}
                    <div class="space-y-3 pt-4 border-t border-[var(--hs-neutral-100)]">
                        <h3 class="text-sm font-medium text-[var(--hs-neutral-500)]">{{ __('Billing Information') }}</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <flux:input wire:model="firstName" label="{{ __('First Name') }}" />
                            <flux:input wire:model="lastName" label="{{ __('Last Name') }}" />
                        </div>
                        <flux:input wire:model="email" label="{{ __('Email') }}" type="email" />
                        <flux:input wire:model="phone" label="{{ __('Phone') }}" placeholder="+63 917 XXX XXXX" />
                        <flux:input wire:model="addressLine1" label="{{ __('Address Line 1') }}" />
                        <flux:input wire:model="addressLine2" label="{{ __('Address Line 2') }}" />
                        <div class="grid grid-cols-2 gap-3">
                            <flux:input wire:model="city" label="{{ __('City') }}" />
                            <flux:input wire:model="province" label="{{ __('Province') }}" />
                        </div>
                        <flux:input wire:model="postalCode" label="{{ __('Postal Code') }}" placeholder="1000" />
                    </div>

                    <flux:button variant="primary" class="w-full" wire:click="submitCardPayment" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitCardPayment">{{ __('Pay :currency :amount', ['currency' => $order->currency, 'amount' => number_format((float) $order->order_total, 2)]) }}</span>
                        <span wire:loading wire:target="submitCardPayment">{{ __('Processing...') }}</span>
                    </flux:button>

                    <p class="text-xs text-center text-[var(--hs-neutral-500)]">
                        <svg class="inline size-3 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                        {{ __('Your card details are sent securely to the payment processor.') }}
                    </p>
                </div>
            </div>

        {{-- Step: E-wallet Payment Form --}}
        @elseif ($step === 'ewallet_form')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-6">
                <div class="flex items-center gap-3 mb-5">
                    <button wire:click="goBack" class="text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-900)]">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    </button>
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Pay with :brand', ['brand' => $selectedBrand['value'] ?? __('E-Wallet')]) }}</h2>
                </div>

                <div class="space-y-4">
                    <p class="text-sm text-[var(--hs-neutral-600)]">{{ __("You'll be redirected to :brand to complete your payment.", ['brand' => $selectedBrand['value'] ?? __('the payment provider')]) }}</p>

                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <flux:input wire:model="firstName" label="{{ __('First Name') }}" />
                            <flux:input wire:model="lastName" label="{{ __('Last Name') }}" />
                        </div>
                        <flux:input wire:model="email" label="{{ __('Email') }}" type="email" />
                        <flux:input wire:model="phone" label="{{ __('Phone') }}" placeholder="+63 917 XXX XXXX" />
                        <flux:input wire:model="addressLine1" label="{{ __('Address Line 1') }}" />
                        <flux:input wire:model="addressLine2" label="{{ __('Address Line 2') }}" />
                        <div class="grid grid-cols-2 gap-3">
                            <flux:input wire:model="city" label="{{ __('City') }}" />
                            <flux:input wire:model="province" label="{{ __('Province') }}" />
                        </div>
                        <flux:input wire:model="postalCode" label="{{ __('Postal Code') }}" placeholder="1000" />
                    </div>

                    <flux:button variant="primary" class="w-full" wire:click="submitEwalletPayment" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitEwalletPayment">{{ __('Continue to :brand', ['brand' => $selectedBrand['value'] ?? __('Payment')]) }}</span>
                        <span wire:loading wire:target="submitEwalletPayment">{{ __('Redirecting...') }}</span>
                    </flux:button>
                </div>
            </div>

        {{-- Step: Processing --}}
        @elseif ($step === 'processing')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-8 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[var(--hs-green-700)] mx-auto"></div>
                <h2 class="mt-4 text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Processing Payment') }}</h2>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-1">{{ __('Please wait while we process your payment...') }}</p>
            </div>

        {{-- Step: Success --}}
        @elseif ($step === 'success')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-green-200)] shadow-[var(--hs-shadow-sm)] p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-[var(--hs-green-50)] flex items-center justify-center mb-4">
                    <svg class="size-8 text-[var(--hs-green-700)]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </div>
                <h2 class="text-xl font-bold text-[var(--hs-neutral-900)]">{{ __('Payment Successful!') }}</h2>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-2">{{ __('Your order has been paid and the seller has been notified.') }}</p>
                <flux:button variant="primary" class="mt-6" href="{{ route('account.orders.show', $order) }}" wire:navigate>
                    {{ __('View Order') }}
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
                <flux:button variant="primary" class="mt-6" wire:click="retry">{{ __('Try Again') }}</flux:button>
            </div>
        @endif
    </div>
</div>
