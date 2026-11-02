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
        <div class="rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5 sm:p-6">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Product Details') }}</h3>
            <div class="mt-4 space-y-4">
                <flux:field>
                    <flux:label>{{ __('Product Name / Type') }}</flux:label>
                    <flux:input type="text" wire:model="product_name" placeholder="{{ __('What product are you looking for?') }}" />
                    <flux:error name="product_name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Product Description & Specifications') }}</flux:label>
                    <flux:textarea wire:model="product_description" rows="4" placeholder="{{ __('Tell us about the size, material, color, features, technical specifications, or any other requirements. How many units are you looking to purchase?') }}" />
                    <flux:error name="product_description" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Reference Image / File') }}</flux:label>
                    <x-hs-dropzone
                        input-id="sourcing-reference-file"
                        wire-model="reference_file"
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx"
                        :multiple="false"
                        loading-target="reference_file"
                        hint="{{ __('Upload a product photo, specification sheet, catalog, or other reference. Max file size: 10MB') }}"
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
        <div class="mt-6 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5 sm:p-6">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Your Details') }}</h3>
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

            <div class="mt-4">
                <flux:error name="human_check" />
            </div>
        </div>

        {{-- Honeypot --}}
        <div class="absolute left-[-9999px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
            <label for="sourcing-honeypot">{{ __('Leave this field empty') }}</label>
            <input type="text" id="sourcing-honeypot" tabindex="-1" autocomplete="off" wire:model="honeypot" />
        </div>

        <div class="mt-6">
            <flux:button type="submit" variant="primary" class="!w-full" icon="paper-airplane" wire:loading.attr="disabled">
                {{ __('Submit Sourcing Request') }}
            </flux:button>
        </div>
    </form>
@endif
