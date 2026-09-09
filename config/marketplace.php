<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Instance Country
    |--------------------------------------------------------------------------
    |
    | ISO 3166-1 alpha-2 code identifying which country this app instance
    | serves. Each country runs its own independent deployment. Set via
    | MARKETPLACE_INSTANCE_COUNTRY in .env.
    |
    */
    'instance_country' => mb_strtoupper((string) env('MARKETPLACE_INSTANCE_COUNTRY', 'PH')),

    /*
    |--------------------------------------------------------------------------
    | Country Portals Registry
    |--------------------------------------------------------------------------
    |
    | Static list of all known country instances. Each entry contains:
    |   country_code — ISO 3166-1 alpha-2
    |   display_name — Human-readable country name
    |   flag         — Unicode flag emoji
    |   locale       — Default app locale for this country
    |   url          — External URL for that country's instance (null = coming soon)
    |
    | Cross-portal links on the homepage use these URLs. Configure per
    | environment via PORTAL_{CC}_URL (e.g. PORTAL_MY_URL=https://my.1halalstore.com).
    |
    */
    'portals' => [
        ['country_code' => 'PH', 'display_name' => 'Philippines',          'card_heading' => 'PHILIPPINES',             'flag' => '🇵🇭', 'locale' => 'en', 'url' => env('PORTAL_PH_URL', null), 'landmark_image' => '/images/countries/ph.jpg'],
        ['country_code' => 'MY', 'display_name' => 'Malaysia',              'card_heading' => 'MALAYSIA',                'flag' => '🇲🇾', 'locale' => 'ms', 'url' => env('PORTAL_MY_URL', null), 'landmark_image' => '/images/countries/my.jpg'],
        ['country_code' => 'SA', 'display_name' => 'Saudi Arabia',          'card_heading' => 'KINGDOM OF SAUDI ARABIA', 'flag' => '🇸🇦', 'locale' => 'ar', 'url' => env('PORTAL_SA_URL', null), 'landmark_image' => '/images/countries/sa.jpg'],
        ['country_code' => 'BN', 'display_name' => 'Brunei',                'card_heading' => 'BRUNEI DARUSSALAM',       'flag' => '🇧🇳', 'locale' => 'ms', 'url' => env('PORTAL_BN_URL', null), 'landmark_image' => '/images/countries/bn.jpg'],
        ['country_code' => 'ID', 'display_name' => 'Indonesia',             'card_heading' => 'INDONESIA',               'flag' => '🇮🇩', 'locale' => 'id', 'url' => env('PORTAL_ID_URL', null), 'landmark_image' => '/images/countries/id.jpg'],
        ['country_code' => 'TR', 'display_name' => 'Turkey',                'card_heading' => 'TÜRKIYE',                 'flag' => '🇹🇷', 'locale' => 'tr', 'url' => env('PORTAL_TR_URL', null), 'landmark_image' => '/images/countries/tr.jpg'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Free-Trial Eligible Country
    |--------------------------------------------------------------------------
    |
    | The country code whose merchants get the PH-style free subscription
    | trial. Defaults to PH but can be overridden per instance if needed.
    |
    */
    'trial_eligible_country' => mb_strtoupper((string) env('MARKETPLACE_TRIAL_ELIGIBLE_COUNTRY', 'PH')),

    /*
    |--------------------------------------------------------------------------
    | Platform Commission Rate
    |--------------------------------------------------------------------------
    |
    | Flat commission rate applied to completed orders for platform revenue.
    | 0.01 = 1%. Used in payout calculations and reports.
    |
    */
    'commission_rate' => (float) env('MARKETPLACE_COMMISSION_RATE', 0.01),

    /*
    |--------------------------------------------------------------------------
    | Auto-Accept Days
    |--------------------------------------------------------------------------
    |
    | Number of days after delivery before the system auto-confirms receipt
    | if the buyer takes no action (and no dispute is open).
    |
    */
    'auto_accept_days' => (int) env('MARKETPLACE_AUTO_ACCEPT_DAYS', 7),

    'initial_subscription_trial_days' => (int) env('MARKETPLACE_INITIAL_SUBSCRIPTION_TRIAL_DAYS', 180),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature flags to enable/disable optional functionality.
    |
    */
    'features' => [
        // Phase 2-4 Capabilities (now gated, toggled false by default in Phase 6)
        'payouts_enabled' => (bool) env('MARKETPLACE_PAYOUTS_ENABLED', false),
        'orders_enabled' => (bool) env('MARKETPLACE_ORDERS_ENABLED', false),
        'escrow_payments_enabled' => (bool) env('MARKETPLACE_ESCROW_PAYMENTS_ENABLED', false),
        'fulfilment_enabled' => (bool) env('MARKETPLACE_FULFILMENT_ENABLED', false),
        'disputes_enabled' => (bool) env('MARKETPLACE_DISPUTES_ENABLED', false),
        'reviews_enabled' => (bool) env('MARKETPLACE_REVIEWS_ENABLED', false),
        'chat_enabled' => (bool) env('MARKETPLACE_CHAT_ENABLED', false),
        'quotations_enabled' => (bool) env('MARKETPLACE_QUOTATIONS_ENABLED', false),
        'seller_rfq_queue_enabled' => (bool) env('MARKETPLACE_SELLER_RFQ_QUEUE_ENABLED', false),
        // Note: chat is deliberately NOT in this list — it stays enabled in Phase 6
        // (retained per requirements.md §4.5/§5), just decoupled from RFQ auto-threading.

        // Phase 6 net-new capabilities, on by default
        'subscriptions_enabled' => (bool) env('MARKETPLACE_SUBSCRIPTIONS_ENABLED', true),
        'certification_apply_enabled' => (bool) env('MARKETPLACE_CERTIFICATION_APPLY_ENABLED', true),
        'sponsored_content_enabled' => (bool) env('MARKETPLACE_SPONSORED_CONTENT_ENABLED', true),
        'admin_rfq_management_enabled' => (bool) env('MARKETPLACE_ADMIN_RFQ_MANAGEMENT_ENABLED', true),
    ],

];
