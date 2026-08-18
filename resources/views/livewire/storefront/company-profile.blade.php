<div>
    {{-- Dark Header Band --}}
    <section class="bg-[var(--hs-green-950)] py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-start gap-5">
                <x-hs-avatar :initials="strtoupper(mb_substr($shop->storefront_name, 0, 2))" size="lg" />
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold text-white font-[var(--hs-font-display)]">{{ $shop->storefront_name }}</h1>
                        @if($shop->certificates->contains(fn($c) => $c->status === 'active'))
                            <x-hs-badge variant="verified" label="Verified" />
                        @endif
                    </div>
                    <p class="mt-1 text-[var(--hs-neutral-300)]">{{ $shop->registered_business_name }}</p>
                    <p class="mt-1 text-sm text-[var(--hs-neutral-400)]">
                        {{ $shop->city }}{{ $shop->city && $shop->province ? ', ' : '' }}{{ $shop->province }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Content Card --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 -mt-6 relative z-10 pb-12">
        <div class="rounded-[var(--hs-radius-panel)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-md)] p-8">
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
                {{-- Main Content (2/3) --}}
                <div class="lg:col-span-2 space-y-8">
                    {{-- Value Chain Stepper --}}
                    @if (!empty($shop->value_chain_positions))
                        <section>
                            <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)] mb-4">Value Chain Positions</h2>
                            <x-hs-value-chain :steps="$shop->value_chain_positions" />
                        </section>
                    @endif

                    {{-- Capabilities --}}
                    @if (!empty($shop->capabilities))
                        <section>
                            <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)] mb-4">Capabilities</h2>
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
                            <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)] mb-4">Products</h2>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @foreach ($shop->products as $product)
                                    <x-hs-product-card :product="$product" />
                                @endforeach
                            </div>
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
                            <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-3">Categories</h3>
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
                            <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-3">Certifications</h3>
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
                        <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-3">Export Readiness</h3>
                        <x-hs-badge variant="gold" :label="ucfirst(str_replace('_', ' ', $shop->export_readiness_status))" />
                    </section>

                    {{-- Lead Time --}}
                    @if($shop->average_production_lead_time)
                        <section class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] p-4">
                            <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-3">Production Lead Time</h3>
                            <p class="text-2xl font-bold text-[var(--hs-green-700)]">{{ $shop->average_production_lead_time }} <span class="text-sm font-normal text-[var(--hs-neutral-500)]">days avg.</span></p>
                        </section>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</div>
