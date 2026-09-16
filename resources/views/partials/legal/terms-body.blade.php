{{-- Shared Terms of Service body. Rendered on /terms and inside the registration consent modal. --}}
<p class="text-sm leading-relaxed text-[var(--hs-neutral-700)]">
    {{ __('Welcome to 1HALALSTORE.COM, a Halal Supply-Chain Marketplace designed to connect Halal-certified and Halal-compliant businesses, farmers, manufacturers, suppliers, distributors, logistics providers, merchants, service providers and consumers across the Philippines and international markets.') }}
</p>
<p class="mt-3 text-sm leading-relaxed text-[var(--hs-neutral-700)]">
    {{ __('These Terms of Service (“Terms”) govern your access to and use of the 1HALALSTORE.COM website, marketplace, mobile applications, digital services, merchant portals and related services (collectively, the “Platform”). By accessing, registering with, purchasing through, selling through, or otherwise using the Platform, you agree to these Terms. If you do not agree with these Terms, please do not use the Platform.') }}
</p>

<div class="mt-6 space-y-0 divide-y divide-[var(--hs-neutral-100)] border-t border-[var(--hs-neutral-100)]">
    <section id="section-1" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('1. About 1HALALSTORE.COM') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('1HALALSTORE.COM is intended to operate as a Halal Supply-Chain Marketplace, connecting participants throughout the Halal value chain:') }}</p>
        <ul class="mt-3 space-y-2">
            @foreach ([__('Farmers and producers'), __('Food and non-food manufacturers'), __('Halal-certified suppliers'), __('Importers and exporters'), __('Wholesalers and distributors'), __('Logistics and warehousing providers'), __('Retailers and merchants'), __('Restaurants and food-service businesses'), __('Halal service providers'), __('Business-to-business buyers'), __('Consumers'), __('Other approved ecosystem partners')] as $item)
                <li class="flex items-start gap-2.5 text-sm leading-relaxed text-[var(--hs-neutral-700)]">
                    <flux:icon.check-circle class="mt-0.5 size-4 shrink-0 text-[var(--hs-green-700)]" />
                    {{ $item }}
                </li>
            @endforeach
        </ul>
        <p class="mt-3 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('The Platform may facilitate product discovery, business matching, ordering, payment, logistics coordination, merchant services, supply-chain information and other digital marketplace functions. Unless expressly stated otherwise, 1HALALSTORE.COM is not the manufacturer, seller, importer, exporter, owner, warehouse operator or logistics provider of products listed by independent merchants.') }}</p>
    </section>

    <section id="section-2" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('2. Definitions') }}</h2>
        <dl class="mt-3 space-y-3">
            @foreach ([[__('Platform'), __('1HALALSTORE.COM and its related websites, applications, systems and services.')], [__('Operator'), __('The legal entity authorized to own, manage or operate the Platform.')], [__('Merchant'), __('An approved seller, supplier, manufacturer, farmer, distributor, service provider or business listing products or services through the Platform.')], [__('Buyer'), __('An individual or business purchasing or attempting to purchase products or services through the Platform.')], [__('Supply-Chain Partner'), __('An approved logistics provider, warehouse, distributor, fulfillment provider, importer, exporter or other supply-chain participant.')], [__('Halal Certification'), __('Certification issued by a competent Halal certification body recognized or accepted under applicable laws, regulations or industry requirements.')], [__('Halal-Certified Product'), __('A product represented as certified by an identified Halal certification body and whose certification is valid for the applicable product, facility or scope.')], [__('Transaction'), __('An order, sale, purchase, service engagement, payment, delivery or other commercial transaction facilitated through the Platform.')]] as [$term, $def])
                <div class="rounded-xl bg-[var(--hs-neutral-50)] border border-[var(--hs-neutral-100)] p-4">
                    <dt class="text-sm font-semibold text-[var(--hs-neutral-900)]">{{ $term }}</dt>
                    <dd class="mt-1 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ $def }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section id="section-3" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('3. Eligibility') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('You may use the Platform only if you are legally capable of entering into contracts, the information you provide is accurate and current, you comply with applicable laws, you have authority to act for any business you represent, and your use does not violate these Terms.') }}</p>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Businesses may be required to provide registration documents, permits, licenses, tax information, identification documents, Halal certificates and other verification information.') }}</p>
    </section>

    <section id="section-4" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('4. Account Registration') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Certain Platform functions require registration. You agree to provide truthful and complete information, maintain accurate account information, keep credentials confidential, notify the Platform of unauthorized activity, never impersonate another person or business, never create accounts using false identities, and accept responsibility for activities conducted through your account.') }}</p>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('The Platform may suspend or terminate accounts that contain false, misleading, fraudulent or incomplete information.') }}</p>
    </section>

    <section id="section-5" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('5. Merchant Requirements') }}</h2>
        <ul class="mt-3 space-y-2">
            @foreach ([__('Provide accurate product and service descriptions'), __('Provide accurate prices, quantities and availability'), __('Identify applicable certifications and permits'), __('Maintain all licenses required by law'), __('Comply with food, product safety, labeling, consumer protection, tax, import/export and Halal requirements'), __('Fulfill accepted orders within the stated period'), __('Provide accurate shipping and fulfillment information'), __('Honor applicable warranties and statutory consumer rights'), __('Maintain appropriate business records'), __('Cooperate with investigations concerning complaints, counterfeit goods, prohibited products, fraud or Halal-integrity concerns')] as $item)
                <li class="flex items-start gap-2.5 text-sm leading-relaxed text-[var(--hs-neutral-700)]">
                    <flux:icon.check-circle class="mt-0.5 size-4 shrink-0 text-[var(--hs-green-700)]" />
                    {{ $item }}
                </li>
            @endforeach
        </ul>
        <p class="mt-3 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Merchants are solely responsible for the legality, quality, safety, authenticity and accuracy of their products and services, except to the extent the Platform expressly assumes responsibility under a separate agreement.') }}</p>
    </section>

    <section id="section-6" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('6. Halal Integrity') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('1HALALSTORE.COM is designed to support the Halal ecosystem and promote transparency across the Halal supply chain. Where applicable, merchants may be required to provide Halal certificates, certification numbers and validity dates, product and ingredient lists, facility and traceability information, supplier and manufacturing information, relevant permits, and other supporting documents.') }}</p>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('The Platform may verify, display, suspend or remove Halal-related information. A marketplace listing, badge, label or reference to “Halal” does not replace a formal Halal certification where certification is legally or commercially required. Users must not falsely represent a product, facility, ingredient, service or business as Halal-certified. The Platform may suspend listings or accounts where Halal integrity is reasonably questioned.') }}</p>
    </section>

    <section id="section-7" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('7. Product Listings') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Merchants are responsible for ensuring that listings are accurate, current, lawful, not misleading, properly labeled, supported by required documentation, and consistent with applicable certification claims. The Platform may edit, reject, suspend or remove listings that violate these Terms, applicable law, Platform policies or Halal-integrity requirements.') }}</p>
    </section>

    <section id="section-8" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('8. Prices and Payments') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Prices are established by merchants unless otherwise indicated, and may be displayed in Philippine Pesos, US Dollars or other supported currencies. The final transaction amount may include product price, taxes, shipping, handling and marketplace fees, payment processing charges, customs duties, import charges, or other applicable charges. Payment providers have their own terms and privacy policies. 1HALALSTORE.COM may use third-party payment providers and is not responsible for interruptions caused by payment networks outside its reasonable control.') }}</p>
    </section>

    <section id="section-9" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('9. Orders') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Submitting an order constitutes an offer to purchase. An order may be subject to merchant acceptance, payment authorization, inventory availability, fraud screening and other verification. The Platform or merchant may cancel an order where the product is unavailable, pricing contains a material error, payment cannot be verified, fraud is suspected, the merchant cannot fulfill the order, the product is prohibited, or required regulatory or Halal documentation is unavailable. Amounts paid for cancelled orders are handled according to the applicable refund policy.') }}</p>
    </section>

    <section id="section-10" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('10. Shipping, Logistics and Delivery') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('1HALALSTORE.COM may connect buyers and merchants with logistics, warehousing and fulfillment providers. Delivery times are estimates unless expressly guaranteed. Responsibility for shipment may depend on the selected Incoterm, delivery arrangement, merchant agreement or applicable law. International transactions may involve customs clearance, import/export permits, duties and taxes, product registration, quarantine and food-safety requirements. The party responsible for importation must ensure compliance with the laws of the destination country.') }}</p>
    </section>

    <section id="section-11" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('11. Returns, Refunds and Cancellations') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Returns, refunds and cancellations are governed by the applicable product listing, merchant policy, Platform refund policy and mandatory consumer-protection laws. Nothing in these Terms removes or restricts a consumer right that cannot lawfully be waived. Perishable, customized, opened, consumed, or misused products may be subject to special return conditions.') }}</p>
    </section>

    <section id="section-12" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('12. Prohibited Products and Activities') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Users may not use the Platform for illegal or counterfeit goods, fraudulent products, products falsely represented as Halal, products prohibited by law, unauthorized pharmaceuticals, weapons, stolen goods, money laundering, fraud, pyramid schemes, misleading advertisements, intellectual-property infringement, unauthorized collection of personal information, cyber abuse, or any activity that threatens Halal integrity or consumer safety. The Platform may immediately suspend or remove prohibited listings.') }}</p>
    </section>

    <section id="section-13" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('13. Intellectual Property') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('All Platform software, designs, logos, trademarks, graphics, text, databases, systems and other proprietary materials belong to the Operator or its licensors unless otherwise stated. Merchants retain ownership of their product photographs, trademarks and materials, subject to a non-exclusive, worldwide, royalty-free license granted to 1HALALSTORE.COM to use, reproduce, display, format, distribute and promote that content solely for operating, marketing and improving the Platform.') }}</p>
    </section>

    <section id="section-14" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('14. User-Generated Content') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Users must not upload content that is false, deceptive, defamatory, illegal, infringing, malicious, fraudulent, or designed to manipulate marketplace rankings or reviews. The Platform may remove content that violates these Terms.') }}</p>
    </section>

    <section id="section-15" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('15. Ratings and Reviews') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Reviews must reflect genuine experiences. Users may not purchase fake reviews, manipulate ratings, threaten others to obtain reviews, post reviews for transactions that did not occur, or use reviews to promote unrelated businesses. The Platform may moderate or remove fraudulent or unlawful reviews.') }}</p>
    </section>

    <section id="section-16" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('16. Marketplace Role') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __("The Platform acts primarily as a digital marketplace and supply-chain facilitator. Unless stated otherwise in a separate written agreement, the merchant is responsible for the product, the buyer for accurate order and delivery information, the logistics provider for transportation, the payment provider for processing, and the Platform facilitates the digital connection. The Platform does not guarantee every merchant's performance, product quality, delivery time, certification status or solvency.") }}</p>
    </section>

    <section id="section-17" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('17. Business-to-Business Transactions') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('1HALALSTORE.COM may facilitate wholesale and B2B transactions. B2B buyers and sellers may enter separate commercial agreements covering minimum order quantities, pricing, credit terms, Incoterms, shipping, warehousing, specifications, quality, Halal certification, inspection, payment, dispute resolution, and other commercial terms. Where a separate agreement exists, its terms may supplement these Terms.') }}</p>
    </section>

    <section id="section-18" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('18. International Transactions') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Users in international transactions are responsible for complying with applicable laws in origin, transit and destination countries. The Platform does not guarantee that a product permitted in one country is permitted in another, and Halal certification recognition may differ between jurisdictions.') }}</p>
    </section>

    <section id="section-19" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('19. Suspension and Termination') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('The Platform may suspend or terminate access where a user violates these Terms, provides false information, engages in fraud, fails to comply with law, misrepresents Halal status, repeatedly fails to fulfill transactions, threatens Platform security, abuses other users, or creates material risk to the Platform or its users. Termination does not eliminate obligations that accrued before termination.') }}</p>
    </section>

    <section id="section-20" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('20. Disclaimers') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('To the extent permitted by law, the Platform is provided on an “as available” basis. The Platform does not guarantee it will always be available, listings will always be accurate, products will always be available, every merchant will perform, delivery will always be on schedule, third-party services will remain uninterrupted, or every Halal certification claim will remain valid after the date displayed. Nothing in these Terms excludes liability that cannot lawfully be excluded.') }}</p>
    </section>

    <section id="section-21" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('21. Limitation of Liability') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('To the maximum extent permitted by law, the Platform and its officers, employees, agents and affiliates shall not be liable for indirect, incidental, special or consequential losses arising from transactions between independent users, except where such liability cannot legally be excluded. This does not limit statutory consumer rights or liability arising from fraud, gross negligence, willful misconduct or other matters that cannot legally be limited.') }}</p>
    </section>

    <section id="section-22" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('22. Indemnification') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('To the extent permitted by law, users agree to indemnify the Platform against claims, losses, liabilities and reasonable expenses arising from violation of these Terms or applicable law, fraudulent conduct, intellectual-property infringement, false product or Halal claims, or breach of obligations to another user.') }}</p>
    </section>

    <section id="section-23" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('23. Privacy') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Personal information is processed according to the 1HALALSTORE.COM Privacy Policy. The Platform intends to process personal information in accordance with applicable privacy laws, including the Philippine Data Privacy Act of 2012 where applicable.') }} <a href="{{ route('legal.privacy') }}" wire:navigate class="font-semibold text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)]">{{ __('Read the Privacy Policy.') }}</a></p>
    </section>

    <section id="section-24" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('24. Changes to These Terms') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('The Platform may update these Terms when necessary. Material changes will be communicated through reasonable means. Continued use of the Platform after the effective date of updated Terms constitutes acceptance, subject to applicable law.') }}</p>
    </section>

    <section id="section-25" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('25. Governing Law') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('For transactions governed by Philippine law, these Terms shall be interpreted in accordance with the laws of the Republic of the Philippines. Mandatory consumer, privacy, e-commerce and other applicable laws prevail over conflicting provisions where required. For international transactions, the parties may agree to additional governing-law and dispute-resolution provisions in a separate commercial agreement.') }}</p>
    </section>

    <section id="section-26" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('26. Dispute Resolution') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __("Users should first attempt to resolve complaints directly through the Platform's customer-support and dispute-resolution process. Nothing in this section prevents a consumer from exercising rights or remedies available under applicable law or filing a complaint with the appropriate government authority.") }}</p>
    </section>

    <section id="section-27" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('27. Contact') }}</h2>
        <div class="mt-3 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5">
            <p class="text-sm font-semibold text-[var(--hs-neutral-900)]">1HALALSTORE.COM</p>
            <ul class="mt-2 space-y-1 text-sm text-[var(--hs-neutral-600)]">
                <li>{{ __('Operator: To be confirmed') }}</li>
                <li>{{ __('Address: To be confirmed') }}</li>
                <li>{{ __('Support email: To be confirmed') }}</li>
                <li>{{ __('Customer support: To be confirmed') }}</li>
            </ul>
        </div>
        <p class="mt-3 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('By clicking “I Agree,” registering an account, placing an order, listing products or otherwise using the Platform, you acknowledge that you have read and agreed to these Terms of Service.') }}</p>
    </section>
</div>
