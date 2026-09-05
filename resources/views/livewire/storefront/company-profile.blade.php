<div>
    {{-- Dark Header Band --}}
    <section class="bg-[var(--hs-green-950)] py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex flex-col sm:flex-row items-start gap-4 sm:gap-5">
                <x-hs-avatar :initials="strtoupper(mb_substr($shop->storefront_name, 0, 2))" size="lg" class="shrink-0" />
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl sm:text-3xl font-bold text-white font-[var(--hs-font-display)] break-words">{{ $shop->storefront_name }}</h1>
                        @if($shop->country_code)
                            <x-hs-country-flag :country-code="$shop->country_code" class="text-2xl sm:text-3xl" />
                        @endif
                        @if($shop->certificates->contains(fn($c) => $c->status === 'active'))
                            <x-hs-badge variant="verified" label="{{ __('Verified') }}" />
                        @endif
                    </div>
                    <p class="mt-1 text-[var(--hs-neutral-300)]">{{ $shop->registered_business_name }}</p>
                    <p class="mt-1 text-sm text-[var(--hs-neutral-400)]">
                        {{ $shop->city }}{{ $shop->city && $shop->province ? ', ' : '' }}{{ $shop->province }}
                    </p>
                    @if($shop->average_rating)
                        <div class="mt-2 flex items-center gap-1">
                            <div class="flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="text-sm {{ $shop->average_rating >= $i ? 'text-yellow-400' : 'text-[var(--hs-neutral-300)]' }}">★</span>
                                @endfor
                            </div>
                            <span class="text-sm text-[var(--hs-neutral-200)]">{{ number_format((float)$shop->average_rating, 1) }}</span>
                            <span class="text-sm text-[var(--hs-neutral-400)]">({{ $shop->total_reviews }} {{ Str::plural('review', $shop->total_reviews) }})</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Owner-only: shortcut to edit the shop profile in the Seller Hub --}}
            @auth
                @if ($shop->user_id === auth()->id())
                    <a
                        href="{{ route('hub.shop.profile') }}"
                        wire:navigate
                        class="inline-flex shrink-0 items-center gap-2 rounded-[var(--hs-radius-pill)] bg-[var(--hs-neutral-0)]/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-[var(--hs-neutral-0)]/25 transition hover:bg-[var(--hs-neutral-0)]/20"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                        {{ __('Edit shop profile') }}
                    </a>
                @elseif (config('marketplace.features.chat_enabled'))
                    <button
                        wire:click="messageCompany"
                        class="inline-flex shrink-0 items-center gap-2 rounded-[var(--hs-radius-pill)] bg-[var(--hs-neutral-0)]/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-[var(--hs-neutral-0)]/25 transition hover:bg-[var(--hs-neutral-0)]/20"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.068.157 2.148.279 3.238.364.466.037.893.281 1.153.671L12 21l2.652-3.978c.26-.39.687-.634 1.153-.67 1.09-.086 2.17-.208 3.238-.365 1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                        {{ __('Message this company') }}
                    </button>
                @endif
            @else
                @if (config('marketplace.features.chat_enabled'))
                    <button
                        wire:click="messageCompany"
                        class="inline-flex shrink-0 items-center gap-2 rounded-[var(--hs-radius-pill)] bg-[var(--hs-neutral-0)]/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-[var(--hs-neutral-0)]/25 transition hover:bg-[var(--hs-neutral-0)]/20"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.068.157 2.148.279 3.238.364.466.037.893.281 1.153.671L12 21l2.652-3.978c.26-.39.687-.634 1.153-.67 1.09-.086 2.17-.208 3.238-.365 1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                        {{ __('Message this company') }}
                    </button>
                @endif
            @endauth
            </div>
        </div>
    </section>

    {{-- Main Content Card --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 -mt-6 relative z-10 pb-12">
        <div class="rounded-[var(--hs-radius-panel)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-md)] p-4 sm:p-8">
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
                {{-- Main Content (2/3) --}}
                <div class="lg:col-span-2 space-y-8">
                    {{-- Value Chain Stepper --}}
                    @if (!empty($shop->value_chain_positions))
                        <section>
                            <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)] mb-4">{{ __('Value Chain Positions') }}</h2>
                            <x-hs-value-chain :steps="$shop->value_chain_positions" />
                        </section>
                    @endif

                    {{-- Capabilities --}}
                    @if (!empty($shop->capabilities))
                        <section>
                            <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)] mb-4">{{ __('Capabilities') }}</h2>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($shop->capabilities as $capability)
                                    <x-hs-badge variant="tag" :label="ucfirst(str_replace('_', ' ', $capability))" />
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Products Grid --}}
                    @if ($shop->products->count() > 0)
                        <section>
                            <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)] mb-4">{{ __('Products') }}</h2>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @foreach ($shop->products as $product)
                                    <x-hs-product-card :product="$product" />
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Reviews --}}
                    @if ($shop->total_reviews > 0)
                        <section>
                            <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)] mb-4">{{ __('Reviews') }}</h2>
                            <livewire:storefront.review-list reviewable-type="shop" :reviewable-id="$shop->id" />
                        </section>
                    @endif
                </div>

                {{-- Sidebar (1/3) --}}
                <aside class="space-y-6">
                    {{-- Categories --}}
                    @php
                        $activeCategories = $shop->products->pluck('categories')->flatten()->unique('id');
                    @endphp
                    @if ($activeCategories->count() > 0)
                        <section class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] p-4">
                            <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-3">{{ __('Categories') }}</h3>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($activeCategories as $category)
                                    <x-hs-badge variant="tag" :label="$category->name" />
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Certifications --}}
                    @if ($shop->certificates->count() > 0)
                        <section class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] p-4">
                            <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-3">{{ __('Certifications') }}</h3>
                            <ul class="space-y-2">
                                @foreach ($shop->certificates as $cert)
                                    <li class="flex items-center justify-between">
                                        <span class="text-sm text-[var(--hs-neutral-700)]">{{ $cert->certification_body }}</span>
                                        <x-hs-cert-badge :status="$cert->status" />
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    {{-- Export Readiness --}}
                    <section class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] p-4">
                        <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-3">{{ __('Export Readiness') }}</h3>
                        <x-hs-badge variant="gold" :label="ucfirst(str_replace('_', ' ', $shop->export_readiness_status))" />
                    </section>

                    {{-- Lead Time --}}
                    @if($shop->average_production_lead_time)
                        <section class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] p-4">
                            <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-3">{{ __('Production Lead Time') }}</h3>
                            <p class="text-2xl font-bold text-[var(--hs-green-700)]">{{ $shop->average_production_lead_time }} <span class="text-sm font-normal text-[var(--hs-neutral-500)]">{{ __('days avg.') }}</span></p>
                        </section>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</div>
