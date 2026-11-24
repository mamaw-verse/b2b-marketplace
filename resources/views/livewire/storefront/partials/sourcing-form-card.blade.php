@if ($submitted)
    <div class="flex flex-col items-center py-10 text-center">
        <div class="flex size-14 items-center justify-center rounded-full bg-[var(--hs-green-50)] text-[var(--hs-green-700)]">
            <flux:icon.check-circle class="size-8" />
        </div>
        <h2 class="mt-5 text-2xl font-bold tracking-tight text-[var(--hs-neutral-900)]">{{ __('Request Submitted') }}</h2>
        <p class="mt-2 max-w-md text-sm text-[var(--hs-neutral-600)]">
            {{ __('We have received your sourcing request and will review it shortly.') }}
            @if ($submittedEmail)
                {{ __('A confirmation email is on its way to') }} <span class="font-semibold text-[var(--hs-neutral-900)]">{{ $submittedEmail }}</span>.
            @endif
        </p>
    </div>
@else
    <form wire:submit="submit" novalidate>
        {{-- Product details --}}
        <div class="rounded-xl sm:border sm:border-[var(--hs-neutral-200)] sm:bg-[var(--hs-neutral-50)] p-0 sm:p-6">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Product Details') }}</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>{{ __('Product Name / Type') }}</flux:label>
                        <flux:input type="text" wire:model="product_name" placeholder="{{ __('Product name') }}" />
                        <flux:error name="product_name" />
                    </flux:field>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Quantity') }} <span class="font-normal text-[var(--hs-neutral-600)]">({{ __('optional') }})</span></flux:label>
                            <flux:input type="text" wire:model="quantity" placeholder="{{ __('e.g. 500 kg') }}" />
                            <flux:error name="quantity" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Target Budget') }} <span class="font-normal text-[var(--hs-neutral-600)]">({{ __('optional') }})</span></flux:label>
                            <flux:input type="text" wire:model="target_budget" placeholder="{{ __('e.g. PHP 50,000') }}" />
                            <flux:error name="target_budget" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Product Description & Specifications') }}</flux:label>
                        <flux:textarea wire:model="product_description" rows="5" placeholder="{{ __('Tell us about the size, material, color, features, technical specifications, or any other requirements.') }}" />
                        <flux:error name="product_description" />
                    </flux:field>
                </div>

                <flux:field class="lg:self-center">
                    <flux:label>{{ __('Reference Image / File') }}</flux:label>
                    <x-hs-dropzone
                        input-id="sourcing-reference-file"
                        wire-model="reference_file"
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx"
                        :multiple="false"
                        loading-target="reference_file"
                        hint="{{ __('Upload a product photo, specification sheet, catalog, or other reference. Max file size: 10 MB') }}"
                    >
                        @if ($reference_file)
                            <p class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-[var(--hs-green-700)]">
                                <flux:icon name="document-check" class="size-4" />
                                {{ $reference_file->getClientOriginalName() }}
                            </p>
                        @endif
                        @error('reference_file')
                            <p class="mt-2 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p>
                        @enderror
                    </x-hs-dropzone>
                </flux:field>
            </div>
        </div>

        {{-- Contact details --}}
        <div class="mt-6 rounded-xl sm:border sm:border-[var(--hs-neutral-200)] sm:bg-[var(--hs-neutral-50)] p-0 sm:p-6">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Your Details') }}</h3>
                @auth
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-[var(--hs-neutral-700)]">
                        <input
                            type="checkbox"
                            wire:model.live="use_registered_details"
                            class="size-4 shrink-0 rounded border-[var(--hs-neutral-400)] accent-[var(--hs-green-600)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:ring-offset-2"
                        />
                        {{ __('Use my registered details') }}
                    </label>
                @endauth
                @if ($use_registered_details && auth()->user() !== null)
                    <p class="text-sm text-[var(--hs-neutral-600)]">
                        {{ __('We will use the contact details on your account:') }}
                        <span class="font-medium text-[var(--hs-neutral-900)]">{{ $name }} ({{ $email }})</span>
                    </p>
                @endif
            </div>

            @if (! ($use_registered_details && auth()->user() !== null))
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Name') }}</flux:label>
                        <flux:input type="text" wire:model="name" placeholder="{{ __('John Smith') }}" />
                        <flux:error name="name" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Email Address') }}</flux:label>
                        <flux:input type="email" wire:model="email" placeholder="{{ __('contact@example.com') }}" />
                        <flux:error name="email" />
                    </flux:field>
                </div>
            @endif

            <div class="mt-4">
                <flux:error name="human_check" />
            </div>
        </div>

        {{-- Honeypot --}}
        <div class="absolute left-[-9999px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
            <label for="sourcing-honeypot">{{ __('Leave this field empty') }}</label>
            <input type="text" id="sourcing-honeypot" tabindex="-1" autocomplete="off" wire:model="honeypot" />
        </div>

        <div class="mt-6 flex justify-end">
            <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled" class="w-full sm:w-auto">
                {{ __('Submit Sourcing Request') }}
            </flux:button>
        </div>
    </form>
@endif
