<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SEO configuration — single source of truth
|--------------------------------------------------------------------------
|
| robots.txt, the `X-Robots-Tag` header and (from Phase 1) the <meta name="robots">
| tag are all driven from the lists below. Never duplicate these lists elsewhere.
|
*/

return [

    /*
    | Force `noindex` + `Disallow: /` even in production (e.g. a pre-launch deploy).
    | Non-production environments are always noindex regardless of this flag.
    */
    'force_noindex' => (bool) env('SEO_FORCE_NOINDEX', false),

    'site_name' => env('APP_NAME', '1Halal Store'),

    /*
    | Public asset paths (relative to public/). OG default is 1200x630; the logo is square.
    */
    'default_og_image' => 'og-default.jpg',
    'logo_path' => 'logo-512.png',

    /*
    | og:locale per application locale.
    */
    'og_locales' => [
        'en' => 'en_US',
        'ms' => 'ms_MY',
        'ar' => 'ar_SA',
        'id' => 'id_ID',
        'tr' => 'tr_TR',
    ],

    /*
    | Path prefixes (no leading slash) that must never be indexed. A prefix also
    | matches everything beneath it (`admin` matches `admin` and `admin/...`).
    | Verified against routes/web.php, routes/settings.php, routes/api.php and Fortify.
    */
    'private_path_patterns' => [
        'admin',
        'hub',
        'account',
        'portal',
        'api',
        'auth',
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password',
        'two-factor',
        'two-factor-challenge',
        'email/verify',
        'user/confirm-password',
        'confirm-password',
        'staff/invitations',
        'settings',
        'media',
        'users',
        'notifications',
        'rfqs',
        'orders',
        'messages',
        'payments',
        'onboarding',
        'profile',
        'redirect',
        'ads',
        'human-check',
        'locale',
    ],

    /*
    | Query parameters whose presence marks a listing URL as `noindex, follow`
    | and canonicalises it to the clean listing URL (applied in Phase 3, T3.2).
    */
    'noindex_follow_query_params' => [],

    /*
    | Advertise the sitemap in robots.txt. Flipped to true when the sitemap
    | routes ship (Phase 3, T3.3) so robots.txt never points at a 404.
    */
    'sitemap_enabled' => (bool) env('SEO_SITEMAP_ENABLED', false),

    /*
    | AI crawler policy (Phase 5, T5.1).
    */
    'ai_crawlers' => [],

    /*
    | Public social profiles for Organization.sameAs. Comma-separated absolute
    | URLs in SEO_SOCIAL_PROFILES; empty means no `sameAs` is emitted.
    */
    'social_profiles' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SEO_SOCIAL_PROFILES', '')),
    ))),

    /*
    | Public contact details for Organization.contactPoint. A `contactPoint` is
    | only emitted when a public support email is configured.
    */
    'contact' => [
        'email' => env('SEO_CONTACT_EMAIL'),
    ],

    /*
    | Real "last modified" dates (YYYY-MM-DD) for static content pages, read as
    | `seo.content_modified.legal.terms`. Must match the date the page itself
    | shows to visitors; pages without a real date are omitted rather than
    | stamped with "now".
    */
    'content_modified' => [
        'legal' => [
            'terms' => '2026-09-16',
            'privacy' => '2026-09-16',
        ],
    ],
];
