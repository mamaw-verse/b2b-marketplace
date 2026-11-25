<?php

declare(strict_types=1);

namespace App\Support\Seo;

/**
 * The FAQ as shared data (T5.4): the visible page and its FAQPage structured data are both
 * built from this one list, so they cannot disagree.
 *
 * Entries about orders are dropped while orders are switched off, and the trust entries
 * come from TrustFacts, so a disabled capability is never described.
 */
final class FaqEntries
{
    /**
     * Entries that only make sense while orders are enabled (wrong product, damage, cancellation).
     *
     * @var list<string>
     */
    private const array ORDER_ENTRIES = ['faq-24', 'faq-25', 'faq-26'];

    public function __construct(private readonly TrustFacts $facts) {}

    /**
     * @return list<array{id: string, icon: string, title: string, items: list<array{id: string, q: string, a: string}>}>
     */
    public function groups(): array
    {
        $groups = [
            ['id' => 'getting-started', 'icon' => 'rocket-launch', 'title' => __('Getting Started'), 'items' => [
                ['id' => 'faq-1', 'q' => __('What is 1HalalStore.com?'), 'a' => __('1HalalStore.com is a Halal Supply-Chain Marketplace. It connects farmers, manufacturers, suppliers, distributors, logistics providers, merchants, businesses and consumers through a digital platform. Our vision is to connect the Halal ecosystem from Farm to Plate.')],
                ['id' => 'faq-2', 'q' => __('Is 1HalalStore.com just an online store?'), 'a' => __('No. It supports not only retail transactions but also B2B sourcing, wholesale, distribution, logistics, warehousing, product discovery and international trade.')],
                ['id' => 'faq-3', 'q' => __('Who can join?'), 'a' => __('Farmers, MSMEs, manufacturers, Halal-certified businesses, suppliers, importers, exporters, wholesalers, distributors, retailers, restaurants, logistics providers, warehouses, service providers, B2B buyers and consumers — subject to verification and approval.')],
                ['id' => 'faq-4', 'q' => __('Do I need to be Muslim to become a merchant?'), 'a' => __('No. Businesses of any affiliation are welcome, provided they comply with applicable laws, marketplace requirements and Halal requirements for the products or services they offer.')],
            ]],
            ['id' => 'halal', 'icon' => 'shield-check', 'title' => __('Halal & Certification'), 'items' => [
                ['id' => 'faq-5', 'q' => __('Does every product need to be Halal-certified?'), 'a' => __('Where certification is legally or commercially required, the merchant must provide it. For other products, the Platform identifies the applicable Halal status. Merchants must never falsely claim certification.')],
                ['id' => 'faq-6', 'q' => __('Who issues Halal certification?'), 'a' => __('A competent Halal certification body. 1HalalStore.com is a marketplace and does not become the certifying body merely because a product appears on the Platform. Listings should identify the certification body and details where applicable.')],
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
                ['id' => 'faq-11', 'q' => __('Does 1HalalStore.com provide logistics?'), 'a' => __('The Platform facilitates connections with logistics, warehousing and fulfillment partners. Actual transportation is performed by independent providers.')],
                ['id' => 'faq-12', 'q' => __('Can I become a logistics partner?'), 'a' => __('Yes, subject to the partner program and verification — freight forwarders, trucking, cold-chain, warehouses, fulfillment centers, last-mile and international providers.')],
                ['id' => 'faq-13', 'q' => __('Can I become a country or franchise partner?'), 'a' => __('Potentially. Franchise, country or territorial operator and strategic partnership models are governed by a separate agreement — not automatically created by merchant registration.')],
                ['id' => 'faq-14', 'q' => __('Merchant vs. franchise partner?'), 'a' => __('A merchant lists and sells products. A franchise or country partner may hold broader territorial rights and operational responsibilities under a separate Franchise Partnership Agreement.')],
                ['id' => 'faq-31', 'q' => __('Can government and industry groups participate?'), 'a' => __('Yes — government agencies, chambers, industry and Halal associations, farmer and MSME groups, logistics and export organizations, tourism bodies and trade partners, subject to agreements and law.')],
                ['id' => 'faq-32', 'q' => __('Does the Platform support MSMEs?'), 'a' => __('Yes — a major objective is giving MSMEs and Halal micro-entrepreneurs digital market access and supply-chain connections, locally and internationally.')],
                ['id' => 'faq-35', 'q' => __('How can I become a partner?'), 'a' => __('Contact the partnerships team via the support email admin@1halalstore.com. Opportunities include merchant, logistics, supply-chain, country operator, franchise, technology and strategic partnerships.')],
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
                ['id' => 'faq-29', 'q' => __('Can I request deletion or correction?'), 'a' => __('Subject to law, you may request access, correction, deletion, blocking or objection. Contact the Data Protection Officer at admin@1halalstore.com.')],
            ]],
            ['id' => 'ecosystem', 'icon' => 'globe-alt', 'title' => __('Ecosystem & Contact'), 'items' => [
                ['id' => 'faq-33', 'q' => __('What does “Farm to Plate” mean?'), 'a' => __('Connecting the entire Halal value chain — from agricultural production and ingredients through processing, manufacturing, logistics, distribution, retail and final consumption.')],
                ['id' => 'faq-34', 'q' => __('What countries can participate?'), 'a' => __('The Platform may expand internationally. Availability depends on operational capability, regulations, partners, payments, logistics and Halal requirements.')],
                ['id' => 'faq-36', 'q' => __('Where can I read the legal policies?'), 'a' => __('The latest Terms of Service, Privacy Policy, Merchant Terms, Refund and Cancellation Policy, Shipping and Delivery Policy, Prohibited Products Policy, Halal Integrity Policy and Franchise/Partner Agreements will be available on the Platform.')],
                ['id' => 'faq-37', 'q' => __('How can I contact 1HalalStore.com?'), 'a' => __('Customer, merchant, partnership and privacy/DPO support email: admin@1halalstore.com. Contact us first so concerns can be investigated promptly.')],
            ]],
        ];
        $groups = $this->withTrustEntries($groups);

        return $this->withoutDisabledEntries($groups);
    }

    /**
     * Every entry in display order, for structured data.
     *
     * @return list<array{id: string, q: string, a: string}>
     */
    public function flat(): array
    {
        return array_merge(...array_map(static fn (array $group): array => $group['items'], $this->groups()));
    }

    /**
     * Adds entries whose answers are TrustFacts sentences (and only the enabled ones).
     *
     * @param  list<array<string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    private function withTrustEntries(array $groups): array
    {
        $extra = [
            'halal' => [
                ['id' => 'faq-38', 'q' => __('How are suppliers verified?'), 'a' => $this->answer(['onboarding_approval'])],
                ['id' => 'faq-39', 'q' => __('How are halal certificates tracked?'), 'a' => $this->answer(['certificate_tracking'])],
            ],
            'selling' => [
                ['id' => 'faq-41', 'q' => __('Can buyers ask suppliers for quotations or message them?'), 'a' => $this->answer(['quotations', 'chat'])],
            ],
            'orders' => [
                ['id' => 'faq-40', 'q' => __('What protections apply to orders?'), 'a' => $this->answer(['escrow', 'disputes', 'reviews'])],
            ],
        ];

        foreach ($groups as &$group) {
            foreach ($extra[$group['id']] ?? [] as $entry) {
                if ($entry['a'] !== '') {
                    $group['items'][] = $entry;
                }
            }
        }

        return $groups;
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    private function withoutDisabledEntries(array $groups): array
    {
        if ((bool) config('marketplace.features.orders_enabled', false)) {
            return $groups;
        }

        foreach ($groups as &$group) {
            $group['items'] = array_values(array_filter(
                $group['items'],
                static fn (array $item): bool => ! in_array($item['id'], self::ORDER_ENTRIES, true),
            ));
        }

        return $groups;
    }

    /**
     * The enabled facts among $keys as one answer; empty when none is enabled.
     *
     * @param  list<string>  $keys
     */
    private function answer(array $keys): string
    {
        return implode(' ', array_values(array_intersect_key($this->facts->all(), array_flip($keys))));
    }
}
