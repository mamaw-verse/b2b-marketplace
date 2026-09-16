{{-- Shared Privacy Policy body. Rendered on /privacy and inside the registration consent modal. --}}
{{-- $adminPhone is shared via view composer; the line below stays hidden until a phone is set. --}}
<p class="text-sm leading-relaxed text-[var(--hs-neutral-700)]">
    {{ __('1HALALSTORE.COM (“1HALALSTORE,” “we,” “our,” or “us”) respects your privacy. This Privacy Policy explains how we collect, use, store, disclose and protect personal information when you use our website, marketplace, applications, merchant portal, customer-support channels and related services.') }}
</p>
<p class="mt-3 text-sm leading-relaxed text-[var(--hs-neutral-700)]">
    {{ __('We aim to process personal information according to applicable data-protection laws, including Republic Act No. 10173, the Philippine Data Privacy Act of 2012, its implementing rules and regulations, and applicable National Privacy Commission issuances, where applicable.') }}
</p>

<div class="mt-6 space-y-0 divide-y divide-[var(--hs-neutral-100)] border-t border-[var(--hs-neutral-100)]">
    <section id="section-1" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('1. Who Controls Your Personal Information?') }}</h2>
        <div class="mt-3 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5">
            <ul class="space-y-1 text-sm text-[var(--hs-neutral-600)]">
                <li>{{ __('Controller: Ismail Abaya') }}</li>
                <li>{{ __('Address: BO. Taytay, El Salvador City, Misamis Oriental 9017') }}</li>
                <li>{{ __('Privacy email: admin@gmail.com') }}</li>
                @if ($adminPhone)
                    <li>{{ __('Phone: :phone', ['phone' => $adminPhone]) }}</li>
                @endif
                <li>{{ __('Data Protection Officer: Ismail Abaya') }}</li>
            </ul>
        </div>
        <p class="mt-3 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Where another organization independently determines how it processes your information, that organization may be a separate Personal Information Controller.') }}</p>
    </section>

    <section id="section-2" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('2. Information We May Collect') }}</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            @foreach ([[__('Account Information'), __('Full name, business name, username, email, mobile number, password credentials, business / billing / shipping addresses.')], [__('Merchant Information'), __('Business registration and tax information, permits, authorized representative details, product and Halal certificate data, facility and supplier information, payout details where required.')], [__('Transaction Information'), __('Orders, purchases, products, payment status, delivery details, refunds, returns, complaints and support communications.')], [__('Technical Information'), __('IP address, device and browser information, OS, login information, approximate location, usage data, cookies and similar technologies.')]] as [$title, $body])
                <div class="rounded-xl border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] p-4">
                    <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)]">{{ $title }}</h3>
                    <p class="mt-1 text-[0.83rem] leading-relaxed text-[var(--hs-neutral-700)]">{{ $body }}</p>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('We may also collect information you provide when you contact support, submit complaints, participate in surveys, send inquiries, submit merchant applications, or communicate through social-media or messaging channels.') }}</p>
    </section>

    <section id="section-3" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('3. Why We Collect Personal Information') }}</h2>
        <ul class="mt-3 space-y-2">
            @foreach ([__('Create and manage accounts'), __('Verify merchants'), __('Process orders and facilitate payments'), __('Coordinate delivery and customer support'), __('Facilitate B2B transactions'), __('Verify business and certification information'), __('Support Halal supply-chain traceability'), __('Prevent fraud and protect Platform security'), __('Resolve disputes'), __('Improve functionality, analytics and service communications'), __('Send marketing communications where permitted'), __('Comply with legal obligations and lawful government requests'), __('Protect our legal rights and those of our users')] as $item)
                <li class="flex items-start gap-2.5 text-sm leading-relaxed text-[var(--hs-neutral-700)]">
                    <flux:icon.check-circle class="mt-0.5 size-4 shrink-0 text-[var(--hs-green-700)]" />
                    {{ $item }}
                </li>
            @endforeach
        </ul>
        <p class="mt-3 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Personal information should be processed only for specified and legitimate purposes and should be adequate and not excessive in relation to those purposes.') }}</p>
    </section>

    <section id="section-4" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('4. Legal Bases for Processing') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Depending on the circumstances, processing may be based on your consent, performance of a contract or steps taken before entering into a contract, compliance with a legal obligation, protection of lawful rights and interests, or other lawful bases recognized by applicable law. Where consent is required, you may withdraw it subject to legal limitations and circumstances where another lawful basis permits continued processing.') }}</p>
    </section>

    <section id="section-5" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('5. Sharing of Personal Information') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('We may share information, when necessary and lawful, with merchants, buyers, logistics companies, warehouses, payment processors, technology and support providers, verification providers, professional advisers, auditors, government authorities where legally required, franchise or country operators where appropriate, and other service providers necessary to operate the marketplace.') }}</p>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('We do not intend to sell personal information merely for third-party advertising. Where applicable, data-sharing arrangements will include appropriate safeguards.') }}</p>
    </section>

    <section id="section-6" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('6. Merchant-to-Buyer Information Sharing') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Because 1HALALSTORE.COM is a supply-chain marketplace, certain information may need to be shared between transaction participants — for example, a merchant may need the buyer’s name, delivery address, contact number, order details and delivery instructions to fulfill an order. A logistics provider may likewise receive information necessary to deliver the order. Only information reasonably necessary for the transaction should be shared.') }}</p>
    </section>

    <section id="section-7" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('7. Halal Supply-Chain Information') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Certain business and product information may be displayed publicly or shared within the marketplace to support Halal transparency and traceability: business and product names, Halal certification status, certification body, certificate number and validity, product category, facility information, origin and supply-chain information. Personal information contained in certification or business documents will be handled according to applicable privacy requirements.') }}</p>
    </section>

    <section id="section-8" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('8. International Data Transfers') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('1HALALSTORE.COM may operate internationally, so personal information may be processed or stored in countries outside the Philippines. Where cross-border transfers occur, we will seek to implement safeguards required by applicable privacy laws. Privacy laws may differ between countries.') }}</p>
    </section>

    <section id="section-9" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('9. Cookies') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('We may use cookies and similar technologies for login authentication, security, website functionality, preferences, analytics, performance and marketing where permitted. You may control cookies through your browser or applicable Platform settings; disabling certain cookies may affect functionality.') }}</p>
    </section>

    <section id="section-10" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('10. Data Security') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('We intend to implement reasonable and appropriate organizational, physical and technical measures designed to protect personal information against unauthorized access or disclosure, loss, destruction, alteration, misuse and other unlawful processing. No electronic system can guarantee absolute security.') }}</p>
    </section>

    <section id="section-11" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('11. Data Retention') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('We retain personal information only for as long as reasonably necessary for the purpose collected, completion of transactions, customer support, legal, accounting and tax obligations, fraud prevention, dispute resolution, establishment or defense of legal claims, and legitimate business purposes. When no longer required, information may be deleted, anonymized, archived or securely disposed of.') }}</p>
    </section>

    <section id="section-12" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('12. Your Privacy Rights') }}</h2>
        <ul class="mt-3 space-y-2">
            @foreach ([__('Right to be informed'), __('Right to access'), __('Right to correct inaccurate information'), __('Right to object to certain processing'), __('Right to withdraw consent where consent is the basis'), __('Right to request deletion or blocking where legally applicable'), __('Right to data portability where applicable'), __('Right to lodge a complaint with the appropriate authority')] as $item)
                <li class="flex items-start gap-2.5 text-sm leading-relaxed text-[var(--hs-neutral-700)]">
                    <flux:icon.check-circle class="mt-0.5 size-4 shrink-0 text-[var(--hs-green-700)]" />
                    {{ $item }}
                </li>
            @endforeach
        </ul>
    </section>

    <section id="section-13" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('13. How to Exercise Your Rights') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('Send a request to the Data Protection Officer at admin@gmail.com with sufficient information to verify your identity and understand your request. We may need to retain certain information where required by law or necessary to establish, exercise or defend legal claims.') }}</p>
    </section>

    <section id="section-14" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('14. Children’s Privacy') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('The Platform is not intended for children below the minimum age required to independently enter into online transactions under applicable law. Where a transaction involves a minor, the appropriate parent, guardian or legally authorized person should supervise or provide any required authorization.') }}</p>
    </section>

    <section id="section-15" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('15. Third-Party Websites') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('The Platform may contain links to third-party websites. We are not responsible for their privacy practices — please review the privacy policies of those third parties.') }}</p>
    </section>

    <section id="section-16" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('16. Marketing Communications') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('We may send service-related communications necessary to operate your account or transactions. Marketing communications will be handled in accordance with applicable law, and where required you may unsubscribe or object to direct marketing.') }}</p>
    </section>

    <section id="section-17" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('17. Data Breaches and Security Incidents') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('If a personal-data breach or security incident occurs, we will take appropriate measures in accordance with applicable law and regulatory requirements, including notifications where legally required.') }}</p>
    </section>

    <section id="section-18" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('18. Changes to This Privacy Policy') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('We may update this Privacy Policy from time to time. Material changes may be communicated through the Platform or other reasonable means, and the latest version will always be posted on 1HALALSTORE.COM.') }}</p>
    </section>

    <section id="section-19" class="py-6 scroll-mt-24">
        <h2 class="text-[1.05rem] font-bold text-[var(--hs-neutral-900)]">{{ __('19. Contact Us') }}</h2>
        <div class="mt-3 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5">
            <p class="text-sm font-semibold text-[var(--hs-neutral-900)]">1HALALSTORE.COM</p>
            <ul class="mt-2 space-y-1 text-sm text-[var(--hs-neutral-600)]">
                <li>{{ __('Operator: Ismail Abaya') }}</li>
                <li>{{ __('Address: BO. Taytay, El Salvador City, Misamis Oriental 9017') }}</li>
                <li>{{ __('Privacy email: admin@gmail.com') }}</li>
                <li>{{ __('DPO: Ismail Abaya') }}</li>
            </ul>
        </div>
        <p class="mt-3 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ __('For privacy concerns, please use the subject “1HALALSTORE.COM – Privacy Request”.') }}</p>
    </section>
</div>
