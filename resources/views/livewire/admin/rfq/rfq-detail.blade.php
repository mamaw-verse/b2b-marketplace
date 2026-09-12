<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">{{ $rfq->title }}</flux:heading>
            <flux:subheading>{{ __('Manage RFQ from :buyer', ['buyer' => $rfq->buyer?->buyerProfile?->business_name ?? $rfq->buyer_snapshot['business_name'] ?? __('Unknown Buyer')]) }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Left Column: Product, RFQ Info, Buyer & Seller --}}
        <div class="space-y-6 md:col-span-2">

            {{-- Product Detail --}}
            @if($rfq->product)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">{{ __('Product for Quotation') }}</flux:heading>
                    @php
                        $galleryImages = collect();
                        if ($rfq->product->featured_image_url) {
                            $galleryImages->push($rfq->product->featured_image_url);
                        }
                        if ($rfq->product->gallery->count() > 0) {
                            $featured = $rfq->product->featured_image_url ?? null;
                            $rfq->product->gallery->each(function ($img) use ($galleryImages, $featured) {
                                if ($img->image_url !== $featured) {
                                    $galleryImages->push($img->image_url);
                                }
                            });
                        }
                        $galleryImages = $galleryImages->values();
                    @endphp
                    <div class="flex items-center gap-4">
                        {{-- Image --}}
                        <div class="shrink-0 size-16 rounded-lg overflow-hidden border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)]">
                            @if($galleryImages->isNotEmpty())
                                <img
                                    src="{{ $galleryImages->first() }}"
                                    alt="{{ $rfq->product->name }}"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                />
                            @else
                                <div class="flex w-full h-full items-center justify-center">
                                    <flux:icon name="photo" class="size-8 text-[var(--hs-neutral-300)]" />
                                </div>
                            @endif
                        </div>

                        {{-- Details --}}
                        <div class="min-w-0 space-y-1.5">
                            <a href="{{ route('storefront.product', $rfq->product) }}" target="_blank"
                               class="block font-bold text-[var(--hs-neutral-900)] hover:text-[var(--hs-green-700)] transition leading-snug">
                                {{ $rfq->product->name }}
                            </a>

                            @if($rfq->product->categories->count())
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($rfq->product->categories->take(3) as $category)
                                        <span class="inline-flex items-center rounded-full bg-[var(--hs-neutral-100)] px-2.5 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--hs-neutral-600)]">
                                            {{ $category->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @php $cheapestVariant = $rfq->product->variants->sortBy('price')->first(); @endphp
                            @if($cheapestVariant)
                                <p class="text-sm font-semibold text-[var(--hs-neutral-900)] tabular-nums">
                                    {{ $cheapestVariant->currency ?? 'PHP' }} {{ number_format((float)$cheapestVariant->price, 2) }}
                                    @if($cheapestVariant->unit)
                                        <span class="text-sm font-medium text-[var(--hs-neutral-500)]">/ {{ $cheapestVariant->unit }}</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- RFQ Details --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('RFQ Details') }}</flux:heading>
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-zinc-500">{{ __('Requirements') }}</div>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white whitespace-pre-wrap">{{ $rfq->description ?: __('No requirements provided.') }}</div>
                    </div>
                </div>

                @if($rfq->attachments->isNotEmpty())
                    <div class="mt-6">
                        <div class="text-sm font-medium text-zinc-500 mb-2">{{ __('Attachments') }}</div>
                        <div class="flex flex-col gap-2">
                            @foreach($rfq->attachments as $attachment)
                                <a href="{{ route('media.view', $attachment) }}" target="_blank" class="flex items-center gap-2 p-3 rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] hover:bg-[var(--hs-neutral-100)] transition group">
                                    <flux:icon name="paper-clip" variant="mini" class="size-5 text-[var(--hs-neutral-400)] group-hover:text-[var(--hs-green-600)]" />
                                    <span class="text-sm font-medium text-[var(--hs-neutral-700)]">{{ $attachment->original_filename }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </flux:card>

            {{-- Buyer Information --}}
            @php
                $buyerProfile = $rfq->buyer?->buyerProfile;
                $snapshot = $rfq->buyer_snapshot;
                // Prefer live buyer profile, fall back to snapshot
                $displayName = $buyerProfile?->business_name ?? $snapshot['business_name'] ?? null;
                $displayEmail = $buyerProfile?->contact_email ?? null;
                $displayPhone = $buyerProfile?->contact_phone ?? null;
                $displayBilling = $buyerProfile ? collect([
                    $buyerProfile->billing_address_1,
                    $buyerProfile->billing_address_2,
                ])->filter()->implode(', ') : ($snapshot['billing_address']['address_1'] ?? null);
                $displayBillingCity = $buyerProfile?->billing_city ?? $snapshot['billing_address']['city'] ?? null;
                $displayBillingCountry = $buyerProfile?->billing_country_code ?? $snapshot['billing_address']['country_code'] ?? null;
                $displayShipping = $buyerProfile ? collect([
                    $buyerProfile->shipping_address_1,
                    $buyerProfile->shipping_address_2,
                ])->filter()->implode(', ') : ($snapshot['shipping_address']['address_1'] ?? null);
                $displayShippingCity = $buyerProfile?->shipping_city ?? $snapshot['shipping_address']['city'] ?? null;
                $displayShippingCountry = $buyerProfile?->shipping_country_code ?? $snapshot['shipping_address']['country_code'] ?? null;
            @endphp
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Buyer Information') }}</flux:heading>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                    @if($displayName)
                        <div>
                            <dt class="text-sm font-medium text-zinc-500">{{ __('Business Name') }}</dt>
                            <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $displayName }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Contact Email') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $displayEmail ?: __('N/A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Contact Phone') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $displayPhone ?: __('N/A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Billing Address') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">
                            @if($displayBilling)
                                {{ $displayBilling }}<br />
                            @endif
                            {{ $displayBillingCity }}{{ $displayBillingCity && $displayBillingCountry ? ', ' : '' }}{{ $displayBillingCountry }}
                            @if(!$displayBilling && !$displayBillingCity && !$displayBillingCountry)
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Shipping Address') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">
                            @if($displayShipping)
                                {{ $displayShipping }}<br />
                            @endif
                            {{ $displayShippingCity }}{{ $displayShippingCity && $displayShippingCountry ? ', ' : '' }}{{ $displayShippingCountry }}
                            @if(!$displayShipping && !$displayShippingCity && !$displayShippingCountry)
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
                @if ($rfq->buyer?->shop)
                    <div class="mt-5 pt-4 border-t border-[var(--hs-neutral-100)]">
                        <flux:button
                            href="{{ route('admin.merchants.show', $rfq->buyer->shop) }}"
                            variant="ghost"
                            size="sm"
                            icon="arrow-top-right-on-square"
                            wire:navigate
                        >
                            {{ __('View more info') }}
                        </flux:button>
                    </div>
                @endif
            </flux:card>

            {{-- Supplier Information --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Supplier Information') }}</flux:heading>
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-zinc-500">{{ __('Target Shop') }}</div>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">
                            <a href="{{ route('admin.merchants.show', $recipient->shop_id) }}" class="text-[var(--hs-green-700)] hover:underline" wire:navigate>
                                {{ $recipient->shop->registered_business_name }}
                            </a>
                        </div>
                    </div>
                </div>

                @if($rfq->product?->shop)
                    @php $sellerShop = $rfq->product->shop; @endphp
                    <div class="mt-6 pt-6 border-t border-[var(--hs-neutral-100)]">
                        <div class="text-sm font-medium text-zinc-500 mb-3">{{ __('Business Information') }}</div>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            @if($sellerShop->email)
                                <div>
                                    <dt class="font-medium text-zinc-500">{{ __('Email') }}</dt>
                                    <dd class="mt-0.5 text-zinc-900 dark:text-white">{{ $sellerShop->email }}</dd>
                                </div>
                            @endif
                            @if($sellerShop->phone)
                                <div>
                                    <dt class="font-medium text-zinc-500">{{ __('Phone') }}</dt>
                                    <dd class="mt-0.5 text-zinc-900 dark:text-white">{{ $sellerShop->phone }}</dd>
                                </div>
                            @endif
                            <div class="sm:col-span-2">
                                <dt class="font-medium text-zinc-500">{{ __('Address') }}</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-white">
                                    @if($sellerShop->address_1)
                                        {{ $sellerShop->address_1 }}<br />
                                    @endif
                                    @if($sellerShop->address_2)
                                        {{ $sellerShop->address_2 }}<br />
                                    @endif
                                    {{ collect([$sellerShop->city, $sellerShop->province, $sellerShop->zip_code])->filter()->implode(', ') }}
                                    {{ $sellerShop->country_code }}
                                    @if(!$sellerShop->address_1 && !$sellerShop->city && !$sellerShop->country_code)
                                        —
                                    @endif
                                </dd>
                            </div>
                        </dl>
                        <div class="mt-4">
                            <flux:button
                                href="{{ route('admin.merchants.show', $sellerShop) }}"
                                variant="ghost"
                                size="sm"
                                icon="arrow-top-right-on-square"
                                wire:navigate
                            >
                                {{ __('View Shop') }}
                            </flux:button>
                        </div>
                    </div>
                @endif
            </flux:card>
        </div>

        {{-- Right Column: Admin Management --}}
        <div class="space-y-6">
            <flux:card>
                <form wire:submit="saveAdminDetails" class="space-y-5">
                    <flux:heading size="lg" class="mb-2">{{ __('Admin Management') }}</flux:heading>

                    <flux:select wire:model="adminStatus" label="{{ __('Admin Status') }}">
                        <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                        <flux:select.option value="in_progress">{{ __('In Progress') }}</flux:select.option>
                        <flux:select.option value="closed">{{ __('Closed') }}</flux:select.option>
                    </flux:select>

                    <flux:textarea wire:model="adminNotes" label="{{ __('Internal Notes') }}" rows="5" placeholder="{{ __('Add private notes for admins...') }}" />

                    <div class="pt-2">
                        <flux:button type="submit" variant="primary" class="w-full">{{ __('Save Changes') }}</flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    </div>
</div>
