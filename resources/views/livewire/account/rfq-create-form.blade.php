<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    {{-- Breadcrumb --}}
    <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-[var(--hs-neutral-500)]">
        <a href="{{ route('storefront.products') }}" class="hover:text-[var(--hs-green-600)] transition" wire:navigate>{{ __('Products') }}</a>
        <flux:icon name="chevron-right" variant="mini" class="size-4 text-[var(--hs-neutral-300)]" />
        @if ($shop)
            <a href="{{ route('storefront.company', $shop) }}" class="hover:text-[var(--hs-green-600)] transition" wire:navigate>{{ $shop->storefront_name }}</a>
            <flux:icon name="chevron-right" variant="mini" class="size-4 text-[var(--hs-neutral-300)]" />
        @endif
        <span class="font-medium text-[var(--hs-neutral-700)]">{{ __('Request Quote') }}</span>
    </nav>

    {{-- Page Heading --}}
    <header class="mb-6">
        <h1 class="font-[var(--hs-font-display)] text-2xl font-bold text-[var(--hs-neutral-900)] sm:text-3xl">
            {{ $submittedRfqId ? __('Request sent') : __('Request for Quotation') }}
        </h1>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-500)]">
            {{ $submittedRfqId
                ? __('Your request has been submitted. The admin will review it and you can monitor its status.')
                : __('Submit your requirements and receive a tailored quotation') }}
        </p>
    </header>

    @if ($submittedRfqId)
        {{-- In-place confirmation: no redirect, the buyer keeps their place. --}}
        <div class="mx-auto max-w-3xl rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-10 text-center shadow-[var(--hs-shadow-sm)]">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-[var(--hs-success-50)]">
                <flux:icon name="check-circle" class="size-8 text-[var(--hs-success-700)]" />
            </div>
            <h2 class="mt-4 text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('RFQ submitted') }}</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-[var(--hs-neutral-500)]">
                {{ __('You can track the status of this request under My RFQs.') }}
            </p>

            @if ($submittedTitle !== '')
                <div class="mx-auto mt-5 flex max-w-md items-center gap-2.5 rounded-[var(--hs-radius-md)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] px-4 py-3 text-left">
                    <flux:icon name="document-text" variant="mini" class="size-4 shrink-0 text-[var(--hs-neutral-400)]" />
                    <span class="min-w-0 flex-1 truncate text-sm text-[var(--hs-neutral-700)]">{{ $submittedTitle }}</span>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <flux:button variant="primary" icon="inbox-stack" href="{{ route('account.rfqs.index') }}" wire:navigate>
                    {{ __('Go to My RFQs') }}
                </flux:button>
                <flux:button variant="ghost" href="{{ route('account.rfqs.show', $submittedRfqId) }}" wire:navigate>
                    {{ __('View this RFQ') }}
                </flux:button>
            </div>
        </div>
    @elseif (! $shop)
        {{-- No supplier selected: the form needs a seller to send to. --}}
        <div class="mx-auto max-w-3xl rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-10 text-center shadow-[var(--hs-shadow-sm)]">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-[var(--hs-neutral-100)]">
                <flux:icon name="building-storefront" class="size-7 text-[var(--hs-neutral-400)]" />
            </div>
            <h2 class="mt-4 text-lg font-semibold text-[var(--hs-neutral-800)]">{{ __('Choose a supplier first') }}</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-[var(--hs-neutral-500)]">
                {{ __('An RFQ is always addressed to one verified supplier. Open a business or product page and use the Request Quote button there.') }}
            </p>
            @error('shop_id')
                <p class="mt-3 text-sm text-[var(--hs-danger-700)]">{{ $message }}</p>
            @enderror
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <flux:button variant="primary" icon="magnifying-glass" href="{{ route('storefront.companies') }}" wire:navigate>
                    {{ __('Browse businesses') }}
                </flux:button>
                <flux:button variant="ghost" href="{{ route('storefront.products') }}" wire:navigate>
                    {{ __('Browse products') }}
                </flux:button>
            </div>
        </div>
    @elseif ($step === 1)
        {{-- ------------------------------------------------------------------ --}}
        {{-- STEP 1 — Buyer profile setup                                       --}}
        {{-- ------------------------------------------------------------------ --}}

        {{-- Step indicator --}}
        <div class="mx-auto max-w-3xl">
        <div class="mb-6 flex items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="flex size-7 items-center justify-center rounded-full bg-[var(--hs-green-600)] text-xs font-bold text-white">1</span>
                <span class="text-sm font-semibold text-[var(--hs-neutral-800)]">{{ __('Your Profile') }}</span>
            </div>
            <div class="h-px flex-1 bg-[var(--hs-neutral-200)]"></div>
            <div class="flex items-center gap-2">
                <span class="flex size-7 items-center justify-center rounded-full border border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-100)] text-xs font-bold text-[var(--hs-neutral-500)]">2</span>
                <span class="text-sm font-medium text-[var(--hs-neutral-400)]">{{ __('Your Request') }}</span>
            </div>
        </div>

        <div class="overflow-hidden rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)]">
            {{-- Card header --}}
            <div class="border-b border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-[var(--hs-warning-100)]">
                        <flux:icon name="user-circle" variant="mini" class="size-5 text-[var(--hs-warning-700)]" />
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-[var(--hs-neutral-900)]">{{ __('Set up your buyer profile') }}</h2>
                        <p class="mt-0.5 text-sm text-[var(--hs-neutral-500)]">
                            {{ __('Suppliers need your business details to send you an accurate quotation. This information is shared when you send an RFQ.') }}
                        </p>
                    </div>
                </div>
            </div>

            <form wire:submit="saveProfile">
                <div class="space-y-6 px-6 py-6">
                    {{-- Business Name --}}
                    <flux:input
                        wire:model="business_name"
                        label="{{ __('Business Name') }}"
                        placeholder="{{ __('Your business name') }}"
                        badge="{{ __('Required') }}"
                        required
                    />

                    {{-- Billing Address --}}
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-[var(--hs-neutral-800)]">{{ __('Billing Address') }}</h3>

                        <flux:input wire:model="billing_address_1" label="{{ __('Address Line 1') }}" placeholder="{{ __('Street address, P.O. box, business name') }}" />
                        <flux:input wire:model="billing_address_2" label="{{ __('Address Line 2') }}" placeholder="{{ __('Apartment, suite, unit, floor, etc.') }}" />

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:input wire:model="billing_city" label="{{ __('City') }}" placeholder="{{ __('City / Municipality') }}" />
                            <flux:input wire:model="billing_province" label="{{ __('Province') }}" placeholder="{{ __('Province / State') }}" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:input wire:model="billing_zip_code" label="{{ __('Zip Code') }}" placeholder="{{ __('Postal / ZIP code') }}" />
                            <flux:input wire:model="billing_country_code" label="{{ __('Country Code') }}" placeholder="PH" maxlength="2" />
                        </div>
                    </div>

                    {{-- Shipping Address --}}
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-[var(--hs-neutral-800)]">{{ __('Shipping Address') }}</h3>

                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="checkbox"
                                wire:model.live="useShippingSameAsBilling"
                                class="rounded border-[var(--hs-neutral-300)] text-[var(--hs-green-600)] focus:ring-[var(--hs-green-500)]"
                            />
                            <span class="text-sm text-[var(--hs-neutral-600)]">{{ __('Same as billing address') }}</span>
                        </label>

                        @unless ($useShippingSameAsBilling)
                            <flux:input wire:model="shipping_address_1" label="{{ __('Address Line 1') }}" placeholder="{{ __('Street address, P.O. box, business name') }}" />
                            <flux:input wire:model="shipping_address_2" label="{{ __('Address Line 2') }}" placeholder="{{ __('Apartment, suite, unit, floor, etc.') }}" />

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <flux:input wire:model="shipping_city" label="{{ __('City') }}" placeholder="{{ __('City / Municipality') }}" />
                                <flux:input wire:model="shipping_province" label="{{ __('Province') }}" placeholder="{{ __('Province / State') }}" />
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <flux:input wire:model="shipping_zip_code" label="{{ __('Zip Code') }}" placeholder="{{ __('Postal / ZIP code') }}" />
                                <flux:input wire:model="shipping_country_code" label="{{ __('Country Code') }}" placeholder="PH" maxlength="2" />
                            </div>
                        @endunless
                    </div>

                    {{-- Contact Information --}}
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-semibold text-[var(--hs-neutral-800)]">{{ __('Contact Information') }}</h3>
                            <p class="mt-0.5 text-xs text-[var(--hs-neutral-500)]">{{ __('Optional — override your account phone/email for business inquiries.') }}</p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:input wire:model="contact_phone" label="{{ __('Phone') }}" placeholder="+63 917 123 4567" type="tel" />
                            <flux:input wire:model="contact_email" label="{{ __('Email') }}" placeholder="purchasing@yourbusiness.ph" type="email" />
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col-reverse gap-3 border-t border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] px-6 py-4 sm:flex-row sm:items-center sm:justify-end">
                    <flux:button variant="ghost" href="{{ route('account.rfqs.index') }}" wire:navigate class="w-full sm:w-auto">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button
                        type="submit"
                        variant="primary"
                        icon="arrow-right"
                        class="w-full sm:w-auto"
                        wire:loading.attr="disabled"
                        wire:target="saveProfile"
                    >
                        {{ __('Continue to RFQ') }}
                    </flux:button>
                </div>
            </form>
        </div>
        </div>
    @else
        {{-- ------------------------------------------------------------------ --}}
        {{-- STEP 2 — RFQ form                                                  --}}
        {{-- ------------------------------------------------------------------ --}}

        @php
            $productPrice = $product?->variants->min('price');
            $productCurrency = $product?->variants->first()->currency ?? 'PHP';
            $productUnit = $product?->variants->first()->unit ?? '';
            $galleryImages = collect();
            if ($product?->featured_image_url) {
                $galleryImages->push($product->featured_image_url);
            }
            if ($product?->gallery->count() > 0) {
                $featured = $product->featured_image_url ?? null;
                $product->gallery->each(function ($img) use ($galleryImages, $featured) {
                    if ($img->image_url !== $featured) {
                        $galleryImages->push($img->image_url);
                    }
                });
            }
            $galleryImages = $galleryImages->values();
        @endphp

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-5 lg:items-start">
            {{-- ============ LEFT: Product reference (sticky) ============ --}}
            @if ($product)
                <div class="lg:col-span-2 lg:sticky lg:top-6 space-y-4">
                    {{-- Product image --}}
                    <div class="rounded-[var(--hs-radius-card)] overflow-hidden border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)] p-3">
                        <div class="rounded-[var(--hs-radius-md)] overflow-hidden bg-[var(--hs-neutral-50)]">
                            @if ($galleryImages->isNotEmpty())
                                <img
                                    src="{{ $galleryImages->first() }}"
                                    alt="{{ $product->name }}"
                                    class="w-full aspect-square object-cover"
                                    loading="lazy"
                                />
                            @else
                                <div class="flex aspect-square items-center justify-center">
                                    <flux:icon name="photo" class="size-16 text-[var(--hs-neutral-300)]" />
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Product info card --}}
                    <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)] p-5 space-y-3">
                        {{-- Reference label --}}
                        <p class="text-[0.65rem] font-semibold uppercase tracking-widest text-[var(--hs-neutral-500)]">{{ __('Reference product') }}</p>
                        {{-- Categories --}}
                        @if ($product->categories->count())
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($product->categories->take(3) as $category)
                                    <span class="inline-flex items-center rounded-full bg-[var(--hs-neutral-100)] px-2.5 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--hs-neutral-600)]">
                                        {{ $category->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        {{-- Name --}}
                        <h2 class="text-lg font-bold text-[var(--hs-neutral-900)] leading-snug">
                            {{ $product->name }}
                        </h2>

                        {{-- Supplier --}}
                        @if ($product->shop)
                            <div class="flex items-center gap-2">
                                <div class="size-6 shrink-0 rounded-[var(--hs-radius-sm)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] flex items-center justify-center text-[0.6rem] font-bold text-[var(--hs-green-700)]">
                                    {{ strtoupper(mb_substr($product->shop->storefront_name, 0, 1)) }}
                                </div>
                                <span class="text-sm font-medium text-[var(--hs-neutral-600)]">{{ $product->shop->storefront_name }}</span>
                            </div>
                        @endif

                        {{-- Price --}}
                        @if ($productPrice)
                            <div class="pt-2 border-t border-[var(--hs-neutral-100)]">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-widest text-[var(--hs-neutral-500)]">{{ __('Starting from') }}</p>
                                <p class="mt-0.5 text-xl font-bold text-[var(--hs-neutral-900)] tabular-nums">
                                    {{ $productCurrency }} {{ number_format((float) $productPrice, 2) }}
                                    @if ($productUnit)
                                        <span class="text-sm font-medium text-[var(--hs-neutral-500)]">/ {{ $productUnit }}</span>
                                    @endif
                                </p>
                            </div>
                        @endif

                        {{-- Description --}}
                        @if ($product->description)
                            <div class="pt-2 border-t border-[var(--hs-neutral-100)]">
                                <p class="text-sm text-[var(--hs-neutral-500)] leading-relaxed line-clamp-4">
                                    {{ $product->description }}
                                </p>
                            </div>
                        @endif

                        {{-- View full product link --}}
                        <a href="{{ route('storefront.product', $product) }}" wire:navigate
                           class="inline-flex items-center gap-1.5 text-sm font-semibold text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] hover:underline pt-1">
                            {{ __('View full product details') }}
                            <flux:icon name="arrow-right" variant="mini" class="size-3.5" />
                        </a>
                    </div>
                </div>
            @endif

            {{-- ============ RIGHT: RFQ form ============ --}}
            <div class="{{ $product ? 'lg:col-span-3' : 'lg:col-span-5' }}">
                {{-- Step indicator --}}
                <div class="mb-6 flex items-center gap-3">
                    <a href="{{ route('account.profile') }}" wire:navigate class="flex items-center gap-2 group">
                        <span class="flex size-7 items-center justify-center rounded-full border border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-100)] text-xs font-bold text-[var(--hs-neutral-500)] group-hover:border-[var(--hs-green-600)] group-hover:bg-[var(--hs-green-50)] transition">1</span>
                        <span class="text-sm font-medium text-[var(--hs-neutral-400)] group-hover:text-[var(--hs-green-700)] transition">{{ __('Edit profile') }}</span>
                    </a>
                    <div class="h-px flex-1 bg-[var(--hs-neutral-200)]"></div>
                    <div class="flex items-center gap-2">
                        <span class="flex size-7 items-center justify-center rounded-full bg-[var(--hs-green-600)] text-xs font-bold text-white">2</span>
                        <span class="text-sm font-semibold text-[var(--hs-neutral-800)]">{{ __('Your Request') }}</span>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)]">
                    @error('shop_id')
                        <div class="flex items-start gap-2.5 border-b border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)] px-6 py-4">
                            <flux:icon name="exclamation-circle" variant="mini" class="mt-0.5 size-4 shrink-0 text-[var(--hs-danger-700)]" />
                            <p class="text-sm text-[var(--hs-danger-800)]">{{ $message }}</p>
                        </div>
                    @enderror

                    <form wire:submit="submit">
                        <div class="space-y-6 px-6 py-6">
                            <flux:input
                                wire:model="title"
                                label="{{ __('What are you sourcing?') }}"
                                placeholder="{{ __('e.g. 500kg Halal Coconut Flour — bulk pricing inquiry') }}"
                                badge="{{ __('Required') }}"
                            />

                            <flux:textarea
                                wire:model="description"
                                label="{{ __('Requirements') }}"
                                placeholder="{{ __('Quantity and packaging, specifications, delivery location and timeline, certifications you require...') }}"
                                rows="7"
                                badge="{{ __('Required') }}"
                                description="{{ __('The more detail you give, the more accurate the quotation.') }}"
                            />

                            {{-- Attachment --}}
                            <div>
                                <div class="mb-1.5 flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-[var(--hs-neutral-800)]">{{ __('Attachment') }}</span>
                                    <span class="text-xs text-[var(--hs-neutral-400)]">{{ __('Optional') }}</span>
                                </div>

                                @if ($attachment)
                                    {{-- Staged file — show filename with a Remove button --}}
                                    <div wire:loading.remove wire:target="attachment"
                                         class="flex items-center gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-4 py-3">
                                        <flux:icon name="paper-clip" class="size-5 shrink-0 text-[var(--hs-neutral-400)]" />
                                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-[var(--hs-neutral-900)]">
                                            {{ $attachment->getClientOriginalName() }}
                                        </span>
                                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearAttachment" type="button">
                                            {{ __('Remove') }}
                                        </flux:button>
                                    </div>
                                @else
                                    {{-- Dropzone --}}
                                    <div x-data="{ dragging: false }">
                                        <label
                                            for="rfq-attachment"
                                            x-on:dragover.prevent="dragging = true"
                                            x-on:dragleave.prevent="dragging = false"
                                            x-on:drop.prevent="
                                                dragging = false;
                                                if ($event.dataTransfer?.files?.length) {
                                                    const input = document.getElementById('rfq-attachment');
                                                    input.files = $event.dataTransfer.files;
                                                    input.dispatchEvent(new Event('change', { bubbles: true }));
                                                }
                                            "
                                            x-bind:class="dragging
                                                ? 'border-[var(--hs-green-600)] bg-[var(--hs-green-50)]'
                                                : 'border-[var(--hs-neutral-200)] hover:border-[var(--hs-green-600)] hover:bg-[var(--hs-green-50)]'"
                                            class="flex flex-col items-center justify-center gap-2 rounded-[var(--hs-radius-card)] border-2 border-dashed p-6 text-center cursor-pointer transition"
                                        >
                                            <flux:icon name="cloud-arrow-up" class="size-7 text-[var(--hs-neutral-500)]" />
                                            <p class="text-sm font-medium text-[var(--hs-neutral-900)]">
                                                {{ __('Drag & drop, or click to browse') }}
                                            </p>
                                            <p class="text-xs text-[var(--hs-neutral-500)]">
                                                {{ __('Spec sheet, artwork or reference document — PDF, Word or image, up to 10MB') }}
                                            </p>
                                            <input
                                                id="rfq-attachment"
                                                type="file"
                                                wire:model="attachment"
                                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp"
                                                class="hidden"
                                            />
                                        </label>
                                    </div>
                                @endif

                                <div wire:loading wire:target="attachment" class="mt-2 flex items-center gap-2 text-sm text-[var(--hs-neutral-500)]">
                                    <flux:icon name="arrow-path" class="size-4 animate-spin" />
                                    {{ __('Uploading file…') }}
                                </div>

                                @error('attachment')
                                    <p class="mt-2 flex items-center gap-1.5 text-xs text-[var(--hs-danger-700)]">
                                        <flux:icon name="exclamation-circle" class="size-4 shrink-0" />
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- Info --}}
                            <div class="flex items-start gap-2.5 rounded-[var(--hs-radius-md)] border border-[var(--hs-info-200)] bg-[var(--hs-info-50)] p-4">
                                <flux:icon name="information-circle" variant="mini" class="mt-0.5 size-4 shrink-0 text-[var(--hs-info-700)]" />
                                <p class="text-xs leading-relaxed text-[var(--hs-info-800)]">
                                    {{ __('Once sent, this RFQ cannot be edited. Your business name and address are shared with the supplier as part of the inquiry.') }}
                                </p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-col-reverse gap-3 border-t border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] px-6 py-4 sm:flex-row sm:items-center sm:justify-end">
                            <flux:button variant="ghost" href="{{ route('account.rfqs.index') }}" wire:navigate class="w-full sm:w-auto">
                                {{ __('Cancel') }}
                            </flux:button>
                            <flux:button
                                type="submit"
                                variant="primary"
                                icon="paper-airplane"
                                class="w-full sm:w-auto"
                                wire:loading.attr="disabled"
                                wire:target="submit"
                            >
                                {{ __('Send RFQ') }}
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
