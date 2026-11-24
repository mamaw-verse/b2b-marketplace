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
    | Browser chrome colour (<meta name="theme-color"> and the web manifest). Defaults to
    | --hs-green-600 from resources/css/tokens.css.
    */
    'theme_color' => env('SEO_THEME_COLOR', '#00312B'),

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
    | and canonicalises it to the clean listing URL (T3.2). `category` is the
    | live Livewire #[Url] binding on /products; the rest are the names a
    | search, filter or sort link would use, so an inbound link carrying one
    | never creates an indexable duplicate. Tracking parameters (utm_*, fbclid,
    | gclid, ref) are not facets: they are stripped from the canonical only.
    */
    'noindex_follow_query_params' => [
        'search',
        'q',
        'query',
        'category',
        'categoryId',
        'sort',
        'sortBy',
        'order',
        'country',
        'certification',
    ],

    /*
    | XML sitemaps (T3.3): /sitemap.xml plus child files, generated on demand
    | and cached; also advertised in robots.txt. `SEO_SITEMAP_ENABLED=false`
    | turns the routes into 404s and drops the robots.txt line.
    */
    'sitemap_enabled' => (bool) env('SEO_SITEMAP_ENABLED', true),

    'sitemap' => [
        // Sitemap protocol limit is 50 000 URLs / 50 MB per file.
        'max_urls_per_file' => 50000,

        // Safety net for mass updates that bypass model events (seconds).
        'cache_ttl' => (int) env('SEO_SITEMAP_CACHE_TTL', 3600),

        // Public, indexable, non-entity routes (no meaningful lastmod, so none is sent).
        'static_routes' => [
            'home',
            'storefront.companies',
            'storefront.products',
            'about',
            'help.faq',
            'legal.terms',
            'legal.privacy',
            'apply.certificate',
            'sourcing-request.create',
        ],
    ],

    /*
    | Cross-country hreflang (T3.4). Alternates come from the portals registry
    | in config/marketplace.php and are only emitted on the home page, and only
    | when this instance and at least one other portal have a URL configured
    | (PORTAL_xx_URL), so the set is reciprocal across deployments.
    | `x_default_country` (SEO_HREFLANG_X_DEFAULT_COUNTRY, e.g. PH) names the
    | portal used for `x-default`; leave empty to omit it.
    */
    'hreflang' => [
        'x_default_country' => env('SEO_HREFLANG_X_DEFAULT_COUNTRY'),
    ],

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
