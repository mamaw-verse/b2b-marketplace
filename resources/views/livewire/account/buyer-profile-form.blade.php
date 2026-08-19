<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                {{ __('Buyer Profile') }}
            </h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                {{ __('Your business details used for RFQs and quotations. This information is shared with sellers when you send a request for quotation.') }}
            </p>
        </div>

        <div class="p-6">
            <form wire:submit="save" class="space-y-8">
                {{-- Business Name --}}
                <div>
                    <flux:input
                        wire:model="business_name"
                        label="Business Name"
                        placeholder="Enter your business or company name"
                        required
                    />
                    @error('business_name') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                {{-- Billing Address Section --}}
                <div class="space-y-4">
                    <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Billing Address') }}</h2>

                    <flux:input
                        wire:model="billing_address_1"
                        label="Address Line 1"
                        placeholder="Street address, P.O. box, company name"
                    />
                    @error('billing_address_1') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror

                    <flux:input
                        wire:model="billing_address_2"
                        label="Address Line 2"
                        placeholder="Apartment, suite, unit, building, floor, etc."
                    />
                    @error('billing_address_2') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <flux:input
                                wire:model="billing_city"
                                label="City"
                                placeholder="City / Municipality"
                            />
                            @error('billing_city') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <flux:input
                                wire:model="billing_province"
                                label="Province"
                                placeholder="Province / State"
                            />
                            @error('billing_province') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <flux:input
                                wire:model="billing_zip_code"
                                label="Zip Code"
                                placeholder="Postal / ZIP code"
                            />
                            @error('billing_zip_code') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <flux:input
                                wire:model="billing_country_code"
                                label="Country Code"
                                placeholder="PH"
                                maxlength="2"
                            />
                            @error('billing_country_code') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Shipping Address Section --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Shipping Address') }}</h2>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model.live="useShippingSameAsBilling"
                            class="rounded border-[var(--hs-neutral-300)] text-[var(--hs-green-600)] focus:ring-[var(--hs-green-500)]"
                        />
                        <span class="text-sm text-[var(--hs-neutral-600)]">{{ __('Same as billing address') }}</span>
                    </label>

                    @unless ($useShippingSameAsBilling)
                        <flux:input
                            wire:model="shipping_address_1"
                            label="Address Line 1"
                            placeholder="Street address, P.O. box, company name"
                        />
                        @error('shipping_address_1') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror

                        <flux:input
                            wire:model="shipping_address_2"
                            label="Address Line 2"
                            placeholder="Apartment, suite, unit, building, floor, etc."
                        />
                        @error('shipping_address_2') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <flux:input
                                    wire:model="shipping_city"
                                    label="City"
                                    placeholder="City / Municipality"
                                />
                                @error('shipping_city') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:input
                                    wire:model="shipping_province"
                                    label="Province"
                                    placeholder="Province / State"
                                />
                                @error('shipping_province') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <flux:input
                                    wire:model="shipping_zip_code"
                                    label="Zip Code"
                                    placeholder="Postal / ZIP code"
                                />
                                @error('shipping_zip_code') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:input
                                    wire:model="shipping_country_code"
                                    label="Country Code"
                                    placeholder="PH"
                                    maxlength="2"
                                />
                                @error('shipping_country_code') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endunless
                </div>

                {{-- Contact Override Section --}}
                <div class="space-y-4">
                    <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Contact Information') }}</h2>
                    <p class="text-sm text-[var(--hs-neutral-500)]">{{ __('Optional — override your account phone/email for business inquiries.') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <flux:input
                                wire:model="contact_phone"
                                label="Phone"
                                placeholder="+63 917 123 4567"
                                type="tel"
                            />
                            @error('contact_phone') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <flux:input
                                wire:model="contact_email"
                                label="Email"
                                placeholder="purchasing@yourcompany.ph"
                                type="email"
                            />
                            @error('contact_email') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center gap-3 pt-4 border-t border-[var(--hs-neutral-100)]">
                    <flux:button type="submit" variant="primary">
                        {{ __('Save Profile') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
