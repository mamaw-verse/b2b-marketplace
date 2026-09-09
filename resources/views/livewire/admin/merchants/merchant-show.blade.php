<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">{{ $shop->storefront_name }}</flux:heading>
            <flux:subheading>{{ __('Manage business details and status.') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if($shop->status === 'active')
                <flux:modal.trigger name="confirm-suspend">
                    <flux:button variant="danger">{{ __('Suspend Business') }}</flux:button>
                </flux:modal.trigger>
            @elseif($shop->status === 'suspended')
                <flux:modal.trigger name="confirm-reactivate">
                    <flux:button variant="primary">{{ __('Reactivate Business') }}</flux:button>
                </flux:modal.trigger>
            @endif
        </div>
    </div>

    <x-hs-confirm
        name="confirm-suspend"
        title="{{ __('Suspend Business') }}"
        message="{{ __('Are you sure you want to suspend this business? Their products will be hidden from search.') }}"
        variant="danger"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="suspend" wire:loading.attr="disabled">{{ __('Suspend') }}</flux:button>
        </x-slot>
    </x-hs-confirm>

    <x-hs-confirm
        name="confirm-reactivate"
        title="{{ __('Reactivate Business') }}"
        message="{{ __('Are you sure you want to reactivate this business?') }}"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="reactivate" wire:loading.attr="disabled">{{ __('Reactivate') }}</flux:button>
        </x-slot>
    </x-hs-confirm>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="space-y-6 md:col-span-2">
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Overview') }}</flux:heading>

                {{-- ── Business Identity ──────────────────────────────────────── --}}
                <flux:heading size="sm" class="mb-3">{{ __('Business Identity') }}</flux:heading>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-5 mb-6">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Registered Business Name') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $shop->registered_business_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Business Structure') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">
                            {{ match ($shop->business_structure) {
                                'sole_proprietorship' => __('Sole Proprietorship'),
                                'partnership'         => __('Partnership'),
                                'corporation'         => __('Corporation'),
                                default               => ucfirst((string) $shop->business_structure),
                            } }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Account Owner') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $shop->user->name }} ({{ $shop->user->email }})</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            <flux:badge color="{{ $shop->status === 'active' ? 'green' : ($shop->status === 'suspended' ? 'red' : 'zinc') }}">
                                {{ ucfirst($shop->status) }}
                            </flux:badge>
                        </dd>
                    </div>
                </dl>

                <div class="border-t border-[var(--hs-neutral-100)] my-5"></div>

                {{-- ── Contact ─────────────────────────────────────────────────── --}}
                <flux:heading size="sm" class="mb-3">{{ __('Contact') }}</flux:heading>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-5 mb-6">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Phone') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $shop->phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Email') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $shop->email ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Address') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">
                            {{ implode(', ', array_filter([
                                $shop->address_1,
                                $shop->address_2,
                                $shop->city,
                                $shop->province,
                                $shop->zip_code,
                                $shop->country_code,
                            ])) ?: '—' }}
                        </dd>
                    </div>
                </dl>

                <div class="border-t border-[var(--hs-neutral-100)] my-5"></div>

                {{-- ── Legal Identifiers ───────────────────────────────────────── --}}
                <flux:heading size="sm" class="mb-3">{{ __('Legal Identifiers') }}</flux:heading>
                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-5 mb-6">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('DTI/SEC Number') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $shop->dti_sec_number ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('TIN') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $shop->tax_identification_number ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('FDA LTO Number') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $shop->fda_lto_number ?: '—' }}</dd>
                    </div>
                </dl>

                <div class="border-t border-[var(--hs-neutral-100)] my-5"></div>

                {{-- ── Operations ──────────────────────────────────────────────── --}}
                <flux:heading size="sm" class="mb-3">{{ __('Operations') }}</flux:heading>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-5 mb-6">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Product Categories') }}</dt>
                        <dd class="mt-1">
                            @if ($shop->categories->isEmpty())
                                <span class="text-sm text-zinc-500">—</span>
                            @else
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($shop->categories as $category)
                                        <flux:badge color="zinc" size="sm">{{ $category->name }}</flux:badge>
                                    @endforeach
                                </div>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Export Readiness') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">
                            {{ match ($shop->export_readiness_status) {
                                'never_exported'       => __('Never Exported'),
                                'exporting_regionally' => __('Exporting Regionally'),
                                'exporting_globally'   => __('Exporting Globally'),
                                default                => ucfirst((string) $shop->export_readiness_status),
                            } }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Avg. Production Lead Time') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">
                            {{ $shop->average_production_lead_time ? __(':days days', ['days' => $shop->average_production_lead_time]) : '—' }}
                        </dd>
                    </div>
                </dl>

                <div class="border-t border-[var(--hs-neutral-100)] my-5"></div>

                {{-- ── Value Chain & Capabilities ──────────────────────────────── --}}
                <flux:heading size="sm" class="mb-3">{{ __('Value Chain & Capabilities') }}</flux:heading>
                @php
                    $vcLabels = [
                        'farm'         => __('Farm / Raw Materials'),
                        'processing'   => __('Processing'),
                        'manufacturing'=> __('Manufacturing'),
                        'distribution' => __('Distribution'),
                        'retail'       => __('Retail'),
                        'food_service' => __('Food Service'),
                    ];
                    $capLabels = [
                        'processing'            => __('Processing'),
                        'contract_processing'   => __('Contract Processing'),
                        'packaging'             => __('Packaging'),
                        'contract_manufacturing'=> __('Contract Manufacturing'),
                        'oem_odm'               => __('OEM / ODM'),
                        'private_label'         => __('Private Label'),
                        'cold_storage'          => __('Cold Storage'),
                        'warehousing'           => __('Warehousing'),
                        'distribution'          => __('Distribution'),
                        'wholesale'             => __('Wholesale'),
                        'importing'             => __('Importing'),
                        'exporting'             => __('Exporting'),
                    ];
                @endphp
                <dl class="grid grid-cols-1 gap-x-4 gap-y-5">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Value Chain Positions') }}</dt>
                        <dd class="mt-1">
                            @if (!empty($shop->value_chain_positions))
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($shop->value_chain_positions as $pos)
                                        <flux:badge color="zinc" size="sm">{{ $vcLabels[$pos] ?? ucfirst($pos) }}</flux:badge>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-sm text-zinc-500">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Capabilities') }}</dt>
                        <dd class="mt-1">
                            @if (!empty($shop->capabilities))
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($shop->capabilities as $cap)
                                        <flux:badge color="zinc" size="sm">{{ $capLabels[$cap] ?? ucfirst($cap) }}</flux:badge>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-sm text-zinc-500">—</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">{{ __('Products') }}</flux:heading>
                    <span class="text-sm text-zinc-500">{{ __(':count total', ['count' => $this->products->count()]) }}</span>
                </div>

                @if($this->products->isEmpty())
                    <p class="text-sm text-zinc-500 py-4 text-center">{{ __('This business has no products yet.') }}</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($this->products as $product)
                            @php
                                $isPublished = $product->status === 'published' && ! $product->deleted_at;
                                $isTrashed   = (bool) $product->deleted_at;
                                $cheapest    = $product->variants->sortBy('price')->first();
                            @endphp

                            <div class="relative flex gap-3 rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-3 shadow-[var(--hs-shadow-sm)]">

                                {{-- Thumbnail --}}
                                <div class="shrink-0 size-16 rounded-md overflow-hidden bg-[var(--hs-neutral-50)] flex items-center justify-center">
                                    @if($product->featured_image_path)
                                        <img src="{{ $product->featured_image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover" />
                                    @else
                                        <svg class="size-7 text-[var(--hs-neutral-300)]" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 0 0 2.25-2.25V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
                                    @endif
                                </div>

                                {{-- Body --}}
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <button
                                            wire:click="openPreview({{ $product->id }})"
                                            class="text-sm font-semibold text-[var(--hs-neutral-900)] hover:text-[var(--hs-green-700)] text-left line-clamp-1 transition cursor-pointer"
                                        >{{ $product->name }}</button>
                                        {{-- Status badge --}}
                                        @if($isTrashed)
                                            <flux:badge color="red" size="sm" class="shrink-0">{{ __('Deleted') }}</flux:badge>
                                        @elseif($product->moderation_status === 'admin_unpublished')
                                            <flux:badge color="orange" size="sm" class="shrink-0">{{ __('Unpublished') }}</flux:badge>
                                        @elseif($product->moderation_status === 'flagged')
                                            <flux:badge color="yellow" size="sm" class="shrink-0">{{ __('Flagged') }}</flux:badge>
                                        @elseif($isPublished)
                                            <flux:badge color="green" size="sm" class="shrink-0">{{ __('Published') }}</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm" class="shrink-0">{{ ucfirst($product->status) }}</flux:badge>
                                        @endif
                                    </div>

                                    {{-- Categories --}}
                                    @if($product->categories->isNotEmpty())
                                        <p class="mt-0.5 text-xs text-[var(--hs-neutral-500)] truncate">
                                            {{ $product->categories->pluck('name')->join(', ') }}
                                        </p>
                                    @endif

                                    {{-- Price --}}
                                    @if($cheapest)
                                        <p class="mt-1 text-xs font-semibold text-[var(--hs-green-700)]">
                                            {{ $cheapest->currency ?? 'PHP' }} {{ number_format((float) $cheapest->price, 2) }}
                                            <span class="font-normal text-[var(--hs-neutral-500)]">{{ __('· MOQ :minimum_order_quantity :unit', ['minimum_order_quantity' => $cheapest->minimum_order_quantity, 'unit' => $cheapest->unit ?? 'pcs']) }}</span>
                                        </p>
                                    @endif

                                    {{-- Actions row --}}
                                    <div class="mt-2 flex items-center gap-3">
                                        {{-- Live storefront link (only when genuinely live) --}}
                                        @if($isPublished && $product->moderation_status === 'active' && $shop->status === 'active')
                                            <a href="{{ route('storefront.product', $product) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1 text-xs font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] transition">
                                                <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                                {{ __('Live') }}
                                            </a>
                                        @else
                                            <span class="text-xs text-[var(--hs-neutral-400)]">{{ __('Not live') }}</span>
                                        @endif

                                        {{-- Moderation link --}}
                                        <a href="{{ route('admin.products.moderation') }}?product_id={{ $product->id }}"
                                           wire:navigate
                                           class="text-xs text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)] transition">
                                            {{ __('Moderate') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>
        </div>

        <div class="space-y-6">
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Subscription Status') }}</flux:heading>
                @if($shop->latestSubscription)
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-zinc-500">{{ __('Current Status') }}</div>
                            <flux:badge color="{{ in_array($shop->latestSubscription->status, ['active', 'trialing']) ? 'green' : 'zinc' }}" class="mt-1">
                                {{ ucfirst($shop->latestSubscription->status) }}
                            </flux:badge>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-zinc-500">{{ __('Started') }}</div>
                            <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $shop->latestSubscription->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-sm text-zinc-500">{{ __('No subscription record found.') }}</div>
                @endif
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Halal Certificates') }}</flux:heading>

                @if ($shop->certificates->isEmpty())
                    <p class="text-sm text-zinc-500">{{ __('No certificates on file.') }}</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($shop->certificates as $certificate)
                            @php
                                $certStatusColor = match ($certificate->status) {
                                    'active'        => 'green',
                                    'expiring_soon' => 'yellow',
                                    'expired'       => 'red',
                                    default         => 'zinc',
                                };
                                $certStatusLabel = match ($certificate->status) {
                                    'active'        => __('Active'),
                                    'expiring_soon' => __('Expiring Soon'),
                                    'expired'       => __('Expired'),
                                    default         => ucfirst($certificate->status),
                                };
                            @endphp
                            <li class="flex items-center justify-between gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] px-4 py-3" wire:key="cert-{{ $certificate->id }}">
                                <div class="flex items-center gap-3">
                                    <flux:badge color="{{ $certStatusColor }}" size="sm">{{ $certStatusLabel }}</flux:badge>
                                    @if ($certificate->expiry_date)
                                        <span class="text-xs text-zinc-500">
                                            {{ __('Expires :date', ['date' => $certificate->expiry_date->format('M d, Y')]) }}
                                        </span>
                                    @endif
                                </div>
                                @if ($certificate->media_id)
                                    <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewDocument({{ $certificate->media_id }})">
                                        {{ __('View') }}
                                    </flux:button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </flux:card>
            
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Submitted Documents') }}</flux:heading>

                @if ($shop->documents->isEmpty())
                    <p class="text-sm text-zinc-500">{{ __('No documents on file.') }}</p>
                @else
                    @php
                        $documentLabels = \App\Livewire\CompanyHub\OnboardingWizard::REQUIRED_DOCUMENTS;
                    @endphp
                    <ul class="divide-y divide-[var(--hs-neutral-100)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)]">
                        @foreach ($shop->documents as $document)
                            <li class="flex items-center gap-3 px-4 py-3" wire:key="doc-{{ $document->id }}">
                                <flux:icon name="document" class="size-5 shrink-0 text-[var(--hs-neutral-500)]" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ __($documentLabels[$document->description] ?? ($document->description ?: __('Document'))) }}
                                    </p>
                                    <p class="truncate text-xs text-zinc-500">{{ $document->original_filename }}</p>
                                </div>
                                @if ($document->pivot->is_verified)
                                    <flux:badge color="green" size="sm" icon="check-circle">{{ __('Verified') }}</flux:badge>
                                @endif
                                <flux:button
                                    wire:click="viewDocument({{ $document->id }})"
                                    variant="ghost"
                                    size="sm"
                                    icon="eye"
                                >
                                    {{ __('View') }}
                                </flux:button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Onboarding History') }}</flux:heading>
                @if($shop->onboarding)
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-zinc-500">{{ __('Submitted') }}</div>
                            <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $shop->onboarding->submitted_at?->format('M d, Y') ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-zinc-500">{{ __('Approved') }}</div>
                            <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $shop->onboarding->approved_at?->format('M d, Y') ?? 'N/A' }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-sm text-zinc-500">{{ __('No onboarding record found.') }}</div>
                @endif
            </flux:card>
        </div>
    </div>

    {{-- ── Product Preview Modal ────────────────────────────────────── --}}
    <flux:modal name="product-preview" class="w-full max-w-2xl">
        @if($this->previewProduct)
            @php
                $preview = $this->previewProduct;
                $galleryImages = collect();
                if ($preview->featured_image_path) {
                    $galleryImages->push($preview->featured_image_url);
                }
                foreach ($preview->gallery as $img) {
                    if ($img->image_url !== $preview->featured_image_url) {
                        $galleryImages->push($img->image_url);
                    }
                }
            @endphp

            <flux:heading size="lg">{{ $preview->name }}</flux:heading>

            <div class="mt-1 flex items-center gap-2 flex-wrap">
                <span class="text-sm font-medium text-[var(--hs-neutral-600)]">{{ $preview->shop->storefront_name }}</span>
                <flux:badge color="{{ $preview->status === 'published' ? 'green' : 'zinc' }}" size="sm">
                    {{ ucfirst($preview->status) }}
                </flux:badge>
                <flux:badge
                    color="{{ $preview->moderation_status === 'active' ? 'green' : ($preview->moderation_status === 'flagged' ? 'yellow' : 'red') }}"
                    size="sm">
                    {{ str_replace('_', ' ', ucfirst($preview->moderation_status)) }}
                </flux:badge>
            </div>

            {{-- Gallery --}}
            @if($galleryImages->isNotEmpty())
                <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                    @foreach($galleryImages as $imgUrl)
                        <img src="{{ $imgUrl }}"
                             alt="{{ $preview->name }}"
                             class="h-36 w-36 shrink-0 rounded-lg object-cover border border-[var(--hs-neutral-200)]" />
                    @endforeach
                </div>
            @else
                <div class="mt-4 flex h-36 items-center justify-center rounded-lg bg-[var(--hs-neutral-50)] border border-[var(--hs-neutral-200)]">
                    <svg class="size-10 text-[var(--hs-neutral-300)]" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 0 0 2.25-2.25V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                    </svg>
                </div>
            @endif

            {{-- Categories --}}
            @if($preview->categories->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach($preview->categories as $cat)
                        <span class="inline-flex items-center rounded-full bg-[var(--hs-neutral-100)] px-2.5 py-0.5 text-xs font-medium text-[var(--hs-neutral-700)]">
                            {{ $cat->name }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Description --}}
            @if($preview->description)
                <p class="mt-4 text-sm text-[var(--hs-neutral-700)] leading-relaxed">{{ $preview->description }}</p>
            @endif

            {{-- Variants --}}
            @if($preview->variants->isNotEmpty())
                <div class="mt-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)] mb-2">{{ __('Variants') }}</p>
                    <div class="divide-y divide-[var(--hs-neutral-100)] rounded-lg border border-[var(--hs-neutral-200)] overflow-hidden">
                        @foreach($preview->variants as $variant)
                            <div class="flex items-center justify-between px-4 py-3 bg-[var(--hs-neutral-0)] text-sm">
                                <div class="min-w-0">
                                    <span class="font-medium text-[var(--hs-neutral-900)]">{{ $variant->sku }}</span>
                                    @if($variant->attributes)
                                        <span class="ml-2 text-[var(--hs-neutral-500)]">
                                            {{ collect($variant->attributes)->map(fn ($v, $k) => "$k: $v")->join(' · ') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="shrink-0 text-right ml-4">
                                    <span class="font-semibold text-[var(--hs-green-700)]">
                                        {{ $variant->currency ?? 'PHP' }} {{ number_format((float) $variant->price, 2) }}
                                    </span>
                                    <span class="text-[var(--hs-neutral-500)] ml-1">
                                        {{ __('· MOQ :minimum_order_quantity :unit', ['minimum_order_quantity' => $variant->minimum_order_quantity, 'unit' => $variant->unit]) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Close') }}</flux:button>
                </flux:modal.close>
            </div>

        @else
            <div class="py-12 text-center text-sm text-[var(--hs-neutral-500)]">{{ __('Loading…') }}</div>
        @endif
    </flux:modal>

    {{-- ── Certificate / Document Viewer Modal ─────────────────────── --}}
    @include('livewire.admin.document-viewer')

</div>
