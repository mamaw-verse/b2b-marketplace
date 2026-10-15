<style>
    html { scroll-behavior: smooth; }
    @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
</style>
<div id="top" class="bg-[var(--hs-neutral-50)]" x-data="{ open: 'faq-1', query: '' }">
    {{-- Hero band --}}
    <div class="bg-[var(--hs-green-950)]">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 pt-10 sm:pt-14 pb-8 sm:pb-10">
            <nav class="flex items-center gap-2 text-xs text-white/50" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" wire:navigate class="hover:text-[var(--hs-gold-300)] transition">{{ __('Home') }}</a>
                <span aria-hidden="true">/</span>
                <span class="font-semibold text-white/90">{{ __('FAQs') }}</span>
            </nav>
            <h1 class="mt-4 text-2xl sm:text-3xl font-bold tracking-tight text-white">{{ __('Frequently Asked Questions') }}</h1>
            <p class="mt-2 max-w-2xl text-sm sm:text-base text-white/70 leading-relaxed">
                {{ __('Halal Supply-Chain Marketplace — from Farm to Plate. Answers for buyers, merchants, logistics partners and country operators.') }}
            </p>
            {{-- Search --}}
            <div class="relative mt-5 max-w-xl">
                <flux:icon.magnifying-glass class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-white/40 pointer-events-none" />
                <input
                    x-model="query"
                    type="search"
                    placeholder="{{ __('Search questions — e.g. Halal, logistics, merchant…') }}"
                    class="w-full pl-12 pr-4 py-3 bg-white/10 border border-white/20 rounded-xl text-sm text-white placeholder:text-white/40 focus:bg-white/15 focus:border-[var(--hs-gold-400)] focus:ring-2 focus:ring-[var(--hs-gold-400)]/20 outline-none transition"
                />
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        @php
            $groups = [
                ['id' => 'getting-started', 'icon' => 'rocket-launch', 'title' => __('Getting Started'), 'items' => [
                    ['id' => 'faq-1', 'q' => __('What is 1HALALSTORE.COM?'), 'a' => __('1HALALSTORE.COM is a Halal Supply-Chain Marketplace. It connects farmers, manufacturers, suppliers, distributors, logistics providers, merchants, businesses and consumers through a digital platform. Our vision is to connect the Halal ecosystem from Farm to Plate.')],
                    ['id' => 'faq-2', 'q' => __('Is 1HALALSTORE.COM just an online store?'), 'a' => __('No. It supports not only retail transactions but also B2B sourcing, wholesale, distribution, logistics, warehousing, product discovery and international trade.')],
                    ['id' => 'faq-3', 'q' => __('Who can join?'), 'a' => __('Farmers, MSMEs, manufacturers, Halal-certified businesses, suppliers, importers, exporters, wholesalers, distributors, retailers, restaurants, logistics providers, warehouses, service providers, B2B buyers and consumers — subject to verification and approval.')],
                    ['id' => 'faq-4', 'q' => __('Do I need to be Muslim to become a merchant?'), 'a' => __('No. Businesses of any affiliation are welcome, provided they comply with applicable laws, marketplace requirements and Halal requirements for the products or services they offer.')],
                ]],
                ['id' => 'halal', 'icon' => 'shield-check', 'title' => __('Halal & Certification'), 'items' => [
                    ['id' => 'faq-5', 'q' => __('Does every product need to be Halal-certified?'), 'a' => __('Where certification is legally or commercially required, the merchant must provide it. For other products, the Platform identifies the applicable Halal status. Merchants must never falsely claim certification.')],
                    ['id' => 'faq-6', 'q' => __('Who issues Halal certification?'), 'a' => __('A competent Halal certification body. 1HALALSTORE.COM is a marketplace and does not become the certifying body merely because a product appears on the Platform. Listings should identify the certification body and details where applicable.')],
                    ['id' => 'faq-7', 'q' => __('Can non-food products be listed?'), 'a' => __('Yes — food and beverages, cosmetics, personal care, permitted pharma and health products, fashion, tourism, hospitality, logistics, manufacturing, agriculture, ingredients, packaging, professional services and other Halal-related offerings.')],
                    ['id' => 'faq-21', 'q' => __('Can I report a questionable Halal claim?'), 'a' => __('Yes — immediately report suspected false or expired certification, misleading claims, contamination, unauthorized logo use, or ingredient misrepresentation. The Platform may investigate and take action.')],
                    ['id' => 'faq-22', 'q' => __('How does traceability work?'), 'a' => __('The Platform may record and share supply-chain information across Farm → Processor → Manufacturer → Warehouse → Distributor → Logistics → Merchant → Consumer. The level of traceability depends on data provided by participants and Platform technology.')],
                ]],
                ['id' => 'selling', 'icon' => 'building-storefront', 'title' => __('Selling & Sourcing'), 'items' => [
                    ['id' => 'faq-8', 'q' => __('Can farmers join?'), 'a' => __('Yes. Farmers and agricultural producers can participate subject to applicable requirements — connecting them with manufacturers, distributors, merchants and consumers is a core objective.')],
                    ['id' => 'faq-9', 'q' => __('Can manufacturers sell wholesale?'), 'a' => __('Yes. The marketplace supports B2B. Manufacturers may offer wholesale quantities, MOQs and business pricing subject to Platform rules and commercial agreements.')],
                    ['id' => 'faq-10', 'q' => __('Can businesses source internationally?'), 'a' => __('Yes, subject to law — including import/export, customs, food and product regulations, Halal, registration, labeling, taxes and destination-country rules.')],
                    ['id' => 'faq-15', 'q' => __('How do I become a merchant?'), 'a' => __('Register an account, complete the merchant application, submit business information, permits and Halal certification where applicable, complete verification, accept the Merchant Terms, upload products, and begin selling after approval.')],
                    ['id' => 'faq-16', 'q' => __('Is merchant registration free?'), 'a' => __('Fees depend on the merchant program. Promotional, introductory or country-specific packages may apply — fees are disclosed before enrollment.')],
                    ['id' => 'faq-17', 'q' => __('Can I sell internationally?'), 'a' => __('Subject to approval and law — depending on product eligibility, destination rules, logistics, import/export, payments, Halal recognition, customs and merchant capability.')],
                    ['id' => 'faq-30', 'q' => __('Can I advertise my business?'), 'a' => __('Subject to Platform rules: product promotion, featured listings, sponsored campaigns, business profiles, B2B and trade campaigns, country campaigns and Halal ecosystem events. Advertising must be truthful and lawful.')],
                ]],
                ['id' => 'logistics', 'icon' => 'truck', 'title' => __('Logistics & Partnerships'), 'items' => [
                    ['id' => 'faq-11', 'q' => __('Does 1HALALSTORE.COM provide logistics?'), 'a' => __('The Platform facilitates connections with logistics, warehousing and fulfillment partners. Actual transportation is performed by independent providers.')],
                    ['id' => 'faq-12', 'q' => __('Can I become a logistics partner?'), 'a' => __('Yes, subject to the partner program and verification — freight forwarders, trucking, cold-chain, warehouses, fulfillment centers, last-mile and international providers.')],
                    ['id' => 'faq-13', 'q' => __('Can I become a country or franchise partner?'), 'a' => __('Potentially. Franchise, country or territorial operator and strategic partnership models are governed by a separate agreement — not automatically created by merchant registration.')],
                    ['id' => 'faq-14', 'q' => __('Merchant vs. franchise partner?'), 'a' => __('A merchant lists and sells products. A franchise or country partner may hold broader territorial rights and operational responsibilities under a separate Franchise Partnership Agreement.')],
                    ['id' => 'faq-31', 'q' => __('Can government and industry groups participate?'), 'a' => __('Yes — government agencies, chambers, industry and Halal associations, farmer and MSME groups, logistics and export organizations, tourism bodies and trade partners, subject to agreements and law.')],
                    ['id' => 'faq-32', 'q' => __('Does the Platform support MSMEs?'), 'a' => __('Yes — a major objective is giving MSMEs and Halal micro-entrepreneurs digital market access and supply-chain connections, locally and internationally.')],
                    ['id' => 'faq-35', 'q' => __('How can I become a partner?'), 'a' => __('Contact the partnerships team via the support email admin@gmail.com. Opportunities include merchant, logistics, supply-chain, country operator, franchise, technology and strategic partnerships.')],
                ]],
                ['id' => 'orders', 'icon' => 'shopping-bag', 'title' => __('Orders, Payments & Trust'), 'items' => [
                    ['id' => 'faq-18', 'q' => __('How are payments made?'), 'a' => __('Through approved payment providers; methods vary by country and transaction type. Additional processing, currency or transaction charges may apply.')],
                    ['id' => 'faq-19', 'q' => __('Who sets the product price?'), 'a' => __('Generally the merchant, unless a separate commercial arrangement applies. Marketplace, subscription, transaction or service fees may also apply.')],
                    ['id' => 'faq-20', 'q' => __('Who is responsible for the product?'), 'a' => __('Generally the merchant or supplier — quality, authenticity, description, safety, compliance, permits, labeling and Halal claims.')],
                    ['id' => 'faq-23', 'q' => __('Is every merchant guaranteed legitimate?'), 'a' => __('The Platform may verify merchants, but users should exercise reasonable care — verification is not an unconditional guarantee of future performance.')],
                    ['id' => 'faq-24', 'q' => __('What if I receive the wrong product?'), 'a' => __('Contact the merchant and Platform support as soon as possible. Rights and remedies depend on applicable law and the return/refund policy.')],
                    ['id' => 'faq-25', 'q' => __('What if my order is damaged?'), 'a' => __('Report promptly with photos, delivery information and evidence. The Platform may coordinate with the merchant and logistics provider.')],
                    ['id' => 'faq-26', 'q' => __('Can I cancel an order?'), 'a' => __('Depends on order status, merchant policy, product type and consumer law. Some products have special cancellation restrictions.')],
                    ['id' => 'faq-27', 'q' => __('Is my personal information protected?'), 'a' => __('Yes — the Platform implements appropriate privacy and security measures. Processing follows the Privacy Policy and applicable law.')],
                    ['id' => 'faq-28', 'q' => __('Will my information be shared with merchants?'), 'a' => __('Where necessary to complete a transaction (e.g. delivery details with merchant, logistics and payment providers). Only reasonably necessary information is shared.')],
                    ['id' => 'faq-29', 'q' => __('Can I request deletion or correction?'), 'a' => __('Subject to law, you may request access, correction, deletion, blocking or objection. Contact the Data Protection Officer at admin@gmail.com.')],
                ]],
                ['id' => 'ecosystem', 'icon' => 'globe-alt', 'title' => __('Ecosystem & Contact'), 'items' => [
                    ['id' => 'faq-33', 'q' => __('What does “Farm to Plate” mean?'), 'a' => __('Connecting the entire Halal value chain — from agricultural production and ingredients through processing, manufacturing, logistics, distribution, retail and final consumption.')],
                    ['id' => 'faq-34', 'q' => __('What countries can participate?'), 'a' => __('The Platform may expand internationally. Availability depends on operational capability, regulations, partners, payments, logistics and Halal requirements.')],
                    ['id' => 'faq-36', 'q' => __('Where can I read the legal policies?'), 'a' => __('The latest Terms of Service, Privacy Policy, Merchant Terms, Refund and Cancellation Policy, Shipping and Delivery Policy, Prohibited Products Policy, Halal Integrity Policy and Franchise/Partner Agreements will be available on the Platform.')],
                    ['id' => 'faq-37', 'q' => __('How can I contact 1HALALSTORE.COM?'), 'a' => __('Customer, merchant, partnership and privacy/DPO support email: admin@gmail.com. Contact us first so concerns can be investigated promptly.')],
                ]],
            ];
        @endphp

        {{-- Mobile topics --}}
        <details class="mb-6 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] lg:hidden">
            <summary class="cursor-pointer px-5 py-3.5 text-sm font-semibold text-[var(--hs-neutral-800)]">{{ __('Browse topics') }}</summary>
            <ul class="space-y-0.5 border-t border-[var(--hs-neutral-100)] px-3 py-3 text-sm">
                @foreach ($groups as $group)
                    <li>
                        <a href="#group-{{ $group['id'] }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-[0.85rem] font-medium text-[var(--hs-neutral-600)] hover:bg-[var(--hs-green-50)] hover:text-[var(--hs-green-700)] transition">
                            {{ $group['title'] }}
                            <span class="rounded-full bg-[var(--hs-neutral-100)] px-2 py-0.5 text-[0.7rem] font-bold text-[var(--hs-neutral-500)]">{{ count($group['items']) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </details>

        <div
            x-data="{ active: null }"
            x-init="() => { const obs = new IntersectionObserver((entries) => { entries.forEach((e) => { if (e.isIntersecting) active = e.target.id; }); }, { rootMargin: '-10% 0px -75% 0px' }); $el.querySelectorAll('section[id]').forEach((s) => obs.observe(s)); }"
            class="grid grid-cols-1 gap-6 lg:grid-cols-[280px_minmax(0,1fr)] lg:items-start"
        >
            {{-- Sidebar --}}
            <aside class="hidden lg:block lg:sticky lg:top-6 space-y-6">
                @include('partials.legal.doc-nav', ['active' => 'faq'])

                <div class="rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-4">
                    <h2 class="px-1 text-sm font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Browse topics') }}</h2>
                    <ul class="mt-3 space-y-0.5 text-sm">
                        @foreach ($groups as $group)
                            <li>
                                <a
                                    href="#group-{{ $group['id'] }}"
                                    :class="active === 'group-{{ $group['id'] }}' ? 'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] font-semibold' : 'text-[var(--hs-neutral-600)]'"
                                    class="flex items-center justify-between rounded-lg px-3 py-2 text-[0.85rem] font-medium hover:bg-[var(--hs-green-50)] hover:text-[var(--hs-green-700)] transition"
                                >
                                    {{ $group['title'] }}
                                    <span class="rounded-full bg-[var(--hs-neutral-100)] px-2 py-0.5 text-[0.7rem] font-bold text-[var(--hs-neutral-500)]">{{ count($group['items']) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-green-50)] p-5">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-[var(--hs-neutral-900)]">
                        <flux:icon.lifebuoy class="size-4 text-[var(--hs-green-700)]" />
                        {{ __('Still stuck?') }}
                    </h2>
                    <p class="mt-2 text-[0.8rem] leading-relaxed text-[var(--hs-neutral-600)]">{{ __('Contact customer support with your order or business details and we will help.') }}</p>
                </div>
            </aside>

            {{-- Topic groups --}}
            <div class="min-w-0 space-y-6">
                @foreach ($groups as $group)
                    <section id="group-{{ $group['id'] }}" class="rounded-2xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-6 sm:p-8 shadow-[var(--hs-shadow-sm)] scroll-mt-24">
                        <h2 class="flex items-center gap-3 text-lg font-bold text-[var(--hs-neutral-900)]">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[var(--hs-green-50)] text-[var(--hs-green-700)]">
                                <flux:icon :icon="$group['icon']" class="size-5" />
                            </span>
                            {{ $group['title'] }}
                            <span class="rounded-full bg-[var(--hs-neutral-100)] px-2.5 py-0.5 text-[0.72rem] font-bold text-[var(--hs-neutral-500)]">{{ count($group['items']) }}</span>
                        </h2>
                        <div class="mt-5 space-y-3">
                            @foreach ($group['items'] as $item)
                                <div
                                    x-show="!query || '{{ mb_strtolower($item['q']) }}'.includes(query.toLowerCase())"
                                    class="overflow-hidden rounded-xl border transition"
                                    :class="open === '{{ $item['id'] }}' ? 'border-[var(--hs-green-200)] bg-[var(--hs-green-50)]/40' : 'border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]'"
                                >
                                    <button
                                        type="button"
                                        @click="open = open === '{{ $item['id'] }}' ? null : '{{ $item['id'] }}'"
                                        :aria-expanded="open === '{{ $item['id'] }}'"
                                        class="flex w-full items-center justify-between gap-3 bg-transparent border-none cursor-pointer px-4 sm:px-5 py-4 text-left"
                                    >
                                        <span class="text-sm sm:text-[0.92rem] font-semibold text-[var(--hs-neutral-900)]">{{ $item['q'] }}</span>
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-[var(--hs-neutral-100)] text-[var(--hs-neutral-600)] transition-transform" :class="open === '{{ $item['id'] }}' && 'rotate-180'">
                                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                        </span>
                                    </button>
                                    <div x-show="open === '{{ $item['id'] }}'" x-collapse>
                                        <p class="px-4 sm:px-5 pb-4 sm:pb-5 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ $item['a'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                {{-- Back to documents --}}
                <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('legal.privacy') }}" wire:navigate class="group flex items-center justify-between gap-4 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-5 py-4 no-underline transition hover:border-[var(--hs-green-200)] hover:bg-[var(--hs-green-50)]/50 sm:min-w-64">
                        <span class="text-[var(--hs-neutral-400)] group-hover:text-[var(--hs-green-700)] transition" aria-hidden="true">&larr;</span>
                        <span class="text-right">
                            <span class="block text-xs font-medium uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Previous') }}</span>
                            <span class="mt-0.5 block text-sm font-bold text-[var(--hs-neutral-900)] group-hover:text-[var(--hs-green-700)]">{{ __('Privacy Policy') }}</span>
                        </span>
                    </a>
                    <a href="#top" class="text-xs font-semibold text-[var(--hs-neutral-500)] hover:text-[var(--hs-green-700)] transition sm:text-right">{{ __('Back to top') }} &uarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>
