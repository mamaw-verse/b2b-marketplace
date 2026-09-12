<div>
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

        @if ($errorMessage)
            <div class="p-4 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-sm text-[var(--hs-danger-700)]">
                {{ $errorMessage }}
            </div>
        @endif

        {{-- Step: Form --}}
        @if ($step === 'form')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-6">

                {{-- Instructions --}}
                <div class="flex items-start gap-3 p-4 bg-[var(--hs-info-50)] border border-[var(--hs-info-200)] rounded-lg mb-6">
                    <flux:icon name="information-circle" class="size-5 text-[var(--hs-info-700)] flex-shrink-0 mt-0.5" />
                    <div class="text-sm text-[var(--hs-info-700)]">
                        <p class="font-semibold mb-1">{{ __('How to complete your payment') }}</p>
                        <ol class="list-decimal list-inside space-y-1">
                            <li>{{ __('Transfer the exact amount to our bank account.') }}</li>
                            <li>{{ __('Enter the bank reference or transaction number below.') }}</li>
                            <li>{{ __('Upload a screenshot or photo of your payment receipt.') }}</li>
                            <li>{{ __('Our team will verify and activate your subscription within 1 business day.') }}</li>
                        </ol>
                    </div>
                </div>

                <div class="space-y-5">
                    {{-- Reference number --}}
                    <div>
                        <flux:input
                            wire:model="referenceNumber"
                            label="{{ __('Bank / Transaction Reference Number') }}"
                            placeholder="{{ __('e.g. 1234567890') }}"
                            required
                        />
                        @error('referenceNumber')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Receipt upload --}}
                    <div>
                        <p class="text-sm font-medium text-[var(--hs-neutral-700)] mb-1.5">
                            {{ __('Payment Receipt') }}
                            <span class="text-[var(--hs-danger-700)]">*</span>
                        </p>
                        <x-hs-dropzone
                            input-id="receipt-upload"
                            wire-model="receipt"
                            accept="image/jpeg,image/png,application/pdf"
                            :multiple="false"
                            hint="{{ __('JPEG, PNG, or PDF — max 5 MB') }}"
                            loading-target="receipt"
                        >
                            @error('receipt')
                                <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                            @enderror

                            @if ($receipt)
                                <div class="mt-3 flex items-center gap-2 text-sm text-[var(--hs-neutral-700)]">
                                    <flux:icon name="document-check" class="size-4 text-[var(--hs-success-700)]" />
                                    <span>{{ $receipt->getClientOriginalName() }}</span>
                                </div>
                            @endif
                        </x-hs-dropzone>
                    </div>

                    <flux:button
                        variant="primary"
                        class="w-full"
                        wire:click="submit"
                        wire:loading.attr="disabled"
                        wire:target="submit,receipt"
                    >
                        <span wire:loading.remove wire:target="submit">
                            {{ __('Submit Payment') }}
                        </span>
                        <span wire:loading wire:target="submit">{{ __('Submitting...') }}</span>
                    </flux:button>
                </div>
            </div>

        {{-- Step: Submitted --}}
        @elseif ($step === 'submitted')
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-[var(--hs-info-50)] flex items-center justify-center mb-4">
                    <flux:icon name="clock" class="size-8 text-[var(--hs-info-700)]" />
                </div>
                <h2 class="text-xl font-bold text-[var(--hs-neutral-900)]">{{ __('Payment Submitted') }}</h2>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-2 max-w-sm mx-auto">
                    {{ __("We've received your payment details and receipt. Our team will verify and activate your subscription within 1 business day.") }}
                </p>
                <p class="text-xs text-[var(--hs-neutral-500)] mt-3">
                    {{ __("You'll receive an email once your subscription is activated.") }}
                </p>
                <flux:button variant="ghost" class="mt-6" href="{{ route('hub.subscription') }}" wire:navigate>
                    {{ __('Back to Subscription') }}
                </flux:button>
            </div>
        @endif

    </div>
</div>
