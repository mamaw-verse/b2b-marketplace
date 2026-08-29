<div class="space-y-6">
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Shop Profile') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Update your company details shown on the storefront.') }}</p>
        </div>

        <form wire:submit="save" class="p-6 space-y-8">

            {{-- ── Business Identity ─────────────────────────────────────── --}}
            <section class="space-y-5">
                <flux:heading size="md">{{ __('Business Identity') }}</flux:heading>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <flux:input wire:model="registered_business_name" :label="__('Registered Business Name')" required />
                        @error('registered_business_name') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <flux:input wire:model="storefront_name" :label="__('Storefront Name')" required />
                        @error('storefront_name') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <flux:select wire:model="business_structure" :label="__('Business Structure')">
                        <flux:select.option value="sole_proprietorship">{{ __('Sole Proprietorship') }}</flux:select.option>
                        <flux:select.option value="partnership">{{ __('Partnership') }}</flux:select.option>
                        <flux:select.option value="corporation">{{ __('Corporation') }}</flux:select.option>
                    </flux:select>
                    @error('business_structure') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>
            </section>

            {{-- ── Contact ───────────────────────────────────────────────── --}}
            <section class="space-y-5">
                <flux:heading size="md">{{ __('Contact') }}</flux:heading>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <flux:input wire:model="phone" :label="__('Contact Phone')" />
                        @error('phone') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <flux:input wire:model="email" type="email" :label="__('Contact Email')" />
                        @error('email') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <flux:input wire:model="address_1" :label="__('Address Line 1')" required />
                    @error('address_1') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <flux:input wire:model="address_2" :label="__('Address Line 2')" />
                    @error('address_2') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <flux:input wire:model="city" :label="__('City')" required />
                        @error('city') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <flux:input wire:model="province" :label="__('Province')" required />
                        @error('province') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <flux:input wire:model="zip_code" :label="__('ZIP Code')" required />
                        @error('zip_code') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- ── Operations ────────────────────────────────────────────── --}}
            <section class="space-y-5">
                <flux:heading size="md">{{ __('Operations') }}</flux:heading>

                <div>
                    <flux:label>{{ __('Product Categories') }}</flux:label>
                    <flux:text class="mt-1 mb-3">{{ __('Select the product categories you sell or manufacture.') }}</flux:text>

                    @if ($this->categories->isEmpty())
                        <p class="text-sm text-[var(--hs-neutral-500)]">{{ __('No categories are available yet.') }}</p>
                    @else
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ($this->categories as $category)
                                <label
                                    wire:key="category-{{ $category->id }}"
                                    class="flex items-center gap-2 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-3 py-2 text-sm text-[var(--hs-neutral-800)] cursor-pointer hover:border-[var(--hs-green-400)] transition"
                                >
                                    <flux:checkbox wire:model="product_categories" value="{{ $category->id }}" />
                                    <span>{{ $category->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('product_categories') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <flux:input wire:model="fda_lto_number" :label="__('FDA LTO Number')" />
                        @error('fda_lto_number') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <flux:input wire:model="average_production_lead_time" type="number" min="0" :label="__('Avg. Production Lead Time (days)')" />
                        @error('average_production_lead_time') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <flux:select wire:model="export_readiness_status" :label="__('Export Readiness')">
                        <flux:select.option value="never_exported">{{ __('Never Exported') }}</flux:select.option>
                        <flux:select.option value="exporting_regionally">{{ __('Exporting Regionally') }}</flux:select.option>
                        <flux:select.option value="exporting_globally">{{ __('Exporting Globally') }}</flux:select.option>
                    </flux:select>
                    @error('export_readiness_status') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
                </div>
            </section>

            {{-- ── Value Chain & Capabilities ────────────────────────────── --}}
            <section class="space-y-5">
                <flux:heading size="md">{{ __('Value Chain & Capabilities') }}</flux:heading>

                <flux:checkbox.group wire:model="value_chain_positions" :label="__('Value Chain Positions')" :description="__('Select all that apply to your business.')">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <flux:checkbox value="farm" :label="__('Farm / Raw Materials')" />
                        <flux:checkbox value="processing" :label="__('Processing')" />
                        <flux:checkbox value="manufacturing" :label="__('Manufacturing')" />
                        <flux:checkbox value="distribution" :label="__('Distribution')" />
                        <flux:checkbox value="retail" :label="__('Retail')" />
                        <flux:checkbox value="food_service" :label="__('Food Service')" />
                    </div>
                </flux:checkbox.group>
                @error('value_chain_positions') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror

                <flux:checkbox.group wire:model="capabilities" :label="__('Capabilities')" :description="__('Select all capabilities your company offers.')">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <flux:checkbox value="processing" :label="__('Processing')" />
                        <flux:checkbox value="contract_processing" :label="__('Contract Processing')" />
                        <flux:checkbox value="packaging" :label="__('Packaging')" />
                        <flux:checkbox value="contract_manufacturing" :label="__('Contract Manufacturing')" />
                        <flux:checkbox value="oem_odm" :label="__('OEM / ODM')" />
                        <flux:checkbox value="private_label" :label="__('Private Label')" />
                        <flux:checkbox value="cold_storage" :label="__('Cold Storage')" />
                        <flux:checkbox value="warehousing" :label="__('Warehousing')" />
                        <flux:checkbox value="distribution" :label="__('Distribution')" />
                        <flux:checkbox value="wholesale" :label="__('Wholesale')" />
                        <flux:checkbox value="importing" :label="__('Importing')" />
                        <flux:checkbox value="exporting" :label="__('Exporting')" />
                    </div>
                </flux:checkbox.group>
                @error('capabilities') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
            </section>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-[var(--hs-neutral-100)]">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    {{ __('Save Changes') }}
                </flux:button>
            </div>
        </form>
    </div>

    {{-- ── Verified Compliance Details (read-only) ───────────────────────
         These were verified during onboarding review. They're shown here for
         reference but can't be edited from the profile, since changing them
         would invalidate the verification. --}}
    @php $verified = $this->verifiedDetails; @endphp
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <div class="flex items-center gap-2">
                <flux:icon name="shield-check" class="size-5 text-[var(--hs-green-600)]" />
                <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Verified Compliance Details') }}</h2>
            </div>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                {{ __('These details were verified during onboarding and are read-only. Contact support if any need to change.') }}
            </p>
        </div>

        <div class="p-6 space-y-8">
            {{-- Legal identifiers --}}
            <section>
                <flux:heading size="sm">{{ __('Legal Identifiers') }}</flux:heading>
                <dl class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('DTI/SEC Number') }}</dt>
                        <dd class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $verified['dti_sec_number'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('TIN') }}</dt>
                        <dd class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $verified['tax_identification_number'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Country') }}</dt>
                        <dd class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $verified['country_code'] ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Halal certificates --}}
            <section>
                <flux:heading size="sm">{{ __('Halal Certificates') }}</flux:heading>
                @if ($verified['certificates']->isEmpty())
                    <p class="mt-3 text-sm text-[var(--hs-neutral-500)]">{{ __('No certificates on file.') }}</p>
                @else
                    @php
                        $bodyLabels = [
                            'IDCP' => __('IDCP'),
                            'HDIP' => __('HDIP'),
                            'PRIME_CERTIFICATION' => __('Prime Certification'),
                            'OTHER_NCMF' => __('Other (NCMF)'),
                        ];
                    @endphp
                    <ul class="mt-3 space-y-3">
                        @foreach ($verified['certificates'] as $certificate)
                            <li class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] p-4" wire:key="shop-cert-{{ $certificate->id }}">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-medium text-[var(--hs-neutral-900)]">
                                        {{ $certificate->certification_body === 'OTHER_NCMF' && $certificate->certification_body_other
                                            ? $certificate->certification_body_other
                                            : ($bodyLabels[$certificate->certification_body] ?? $certificate->certification_body) }}
                                    </span>
                                    @if ($certificate->is_verified)
                                        <flux:badge color="green" size="sm" icon="check-circle">{{ __('Verified') }}</flux:badge>
                                    @endif
                                </div>
                                <dl class="mt-2 grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
                                    <div>
                                        <dt class="text-xs text-[var(--hs-neutral-500)]">{{ __('Registration No.') }}</dt>
                                        <dd class="text-[var(--hs-neutral-900)]">{{ $certificate->registration_number ?: '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-[var(--hs-neutral-500)]">{{ __('Expiry Date') }}</dt>
                                        <dd class="text-[var(--hs-neutral-900)]">{{ $certificate->expiry_date?->format('M j, Y') ?? '—' }}</dd>
                                    </div>
                                    <div class="flex items-end">
                                        @if ($certificate->media_id)
                                            <flux:button
                                                :href="route('media.view', $certificate->media_id)"
                                                target="_blank"
                                                variant="ghost"
                                                size="sm"
                                                icon="arrow-top-right-on-square"
                                            >
                                                {{ __('View file') }}
                                            </flux:button>
                                        @endif
                                    </div>
                                </dl>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Uploaded documents --}}
            <section>
                <flux:heading size="sm">{{ __('Submitted Documents') }}</flux:heading>
                @if ($verified['documents']->isEmpty())
                    <p class="mt-3 text-sm text-[var(--hs-neutral-500)]">{{ __('No documents on file.') }}</p>
                @else
                    @php
                        $documentLabels = \App\Livewire\CompanyHub\OnboardingWizard::REQUIRED_DOCUMENTS;
                    @endphp
                    <ul class="mt-3 divide-y divide-[var(--hs-neutral-100)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)]">
                        @foreach ($verified['documents'] as $document)
                            <li class="flex items-center gap-3 px-4 py-3" wire:key="shop-doc-{{ $document->id }}">
                                <flux:icon name="document" class="size-5 shrink-0 text-[var(--hs-neutral-500)]" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-[var(--hs-neutral-900)]">
                                        {{ __($documentLabels[$document->description] ?? ($document->description ?: __('Document'))) }}
                                    </p>
                                    <p class="truncate text-xs text-[var(--hs-neutral-500)]">{{ $document->original_filename }}</p>
                                </div>
                                @if ($document->pivot->is_verified)
                                    <flux:badge color="green" size="sm" icon="check-circle">{{ __('Verified') }}</flux:badge>
                                @endif
                                <flux:button
                                    :href="route('media.view', $document->id)"
                                    target="_blank"
                                    variant="ghost"
                                    size="sm"
                                    icon="arrow-top-right-on-square"
                                >
                                    {{ __('View') }}
                                </flux:button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</div>
