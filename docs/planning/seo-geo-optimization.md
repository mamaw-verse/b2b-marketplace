# SEO & GEO Optimization — Implementation Plan (B2B Marketplace)

**Repo:** `mamaw-verse/b2b-marketplace` (1Halal Store — B2B halal supply-chain marketplace)
**Branch audited:** `main` @ `a8d67d9`
**Stack:** Laravel 13 / PHP ^8.4.27, Livewire full-page components + Blade, Alpine, Tailwind 4, Flux, Pest 5, PostgreSQL, Redis
**Scope:** Schema.org JSON-LD · Open Graph / social previews · crawl control (robots, sitemaps, hreflang) · GEO (Generative Engine Optimization)
**Source plan adapted from:** `mamaw-verse/b2c` → `docs/planning/14-seo-geo-optimization.md`

> **Status: planning only. Nothing in this document is implemented yet.** Every finding below was read from the code in this repo; items marked **(verify)** could not be confirmed from source alone and need a check on a running/deployed environment.

---

## 0. Why this is not a copy of the B2C plan

The B2C plan targets Laravel + Inertia/React with SSR, a consumer shop checkout and a `production` default branch. This repo is different in ways that change the work:

| Topic | B2C (source plan) | B2B (this repo) | Effect on the plan |
| --- | --- | --- | --- |
| Rendering | Inertia/React; SSR needed (T0.7) and a major risk | Livewire full-page components render HTML on the server | **SSR task dropped.** Replaced by a "no crawler-hidden content" audit (lazy/deferred Livewire parts, Alpine-only content). |
| Head management | Single `SeoData` + `app.blade.php` | Every view does its own `@push('meta')` (duplicated across ~6 views); no shared SEO object | Need a **shared head component first** (Phase 1) before schema work. |
| Commerce | Cart, checkout, escrow, returns, shipping | **Discovery + RFQ.** Orders/escrow/payments/reviews behind flags that default **off** | `Offer` shipping/returns, merchant-listing eligibility and review markup are **mostly not applicable**. Schema must follow what the page really shows. |
| Pricing | Single price + sale price | Variants with `price`, `currency`, `minimum_order_quantity`, `quantity_breaks`, `unit`, `pack_size`, `lead_time_days` | Offer markup needs MOQ / unit semantics, not retail semantics. |
| Entities | Shop = `Store` | Shop = verified **company/supplier** with certificates, value-chain position, capabilities, export readiness | Use `Organization` (not `Store`) with halal certification markup. |
| Categories | Public category pages | Categories have slugs, but only filter `/products?category={id}` — **no public category URL** | New indexable category landing route needed. |
| Geography | One country | **One independent deployment per country** (`MARKETPLACE_INSTANCE_COUNTRY`, `portals` registry, locales en/ms/ar/id/tr) | `areaServed`, `inLanguage`, hreflang and `sameAs` must come from per-instance config, never hard-coded to PH. |
| Default branch / CI | CI listed `main`, repo default was `production` | `main` is the default and CI runs on `main` | No branch mismatch to fix. |

---

## 1. Current state (audit)

### 1.1 What exists

| Area | Where | Notes |
| --- | --- | --- |
| Open Graph + Twitter tags | `@push('meta')` in `hero-search`, `company-directory`, `company-profile`, `product-directory`, `product-detail`, `about-page` blade views | Hand-copied per view; inconsistent field coverage. |
| Canonical tag | `product-detail`, `company-profile` only | Others fall back to `url()->current()` in `og:url` and have no `<link rel="canonical">`. |
| Canonical slug redirects | `EnsureCanonicalSlug` middleware (301 to the slug URL, keeps query string) | Good; its `isVisible()` rules are the right definition of "publicly visible" and should be reused. |
| Per-page `<title>` | `#[Title]` on Livewire classes; `ProductDetail` sets `->title($product->name)` | Layout fallback is `'1Halal Store'`. Several titles are generic (`Business Directory`, `Product Directory`). |
| Product og:image | `route('products.featured-image')` | Public product images are served with `Cache-Control: public, max-age=31536000, immutable` on an **un-versioned URL**. |
| Visibility rules | `Product::scopeMarketplaceVisible()`, `Shop::scopeActive()`, subscription gating | Single source of truth to reuse for sitemaps/llms/schema. |
| Stored ratings | `products.average_rating/total_reviews`, `shops.average_rating/total_reviews` | Cheaper than B2C (no per-render aggregate query), but reviews UI is behind `reviews_enabled`. |
| Scheduler | `routes/console.php` (`Schedule::job(...)`) | Place to add the weekly SEO audit. |
| Analytics | GA4 snippet in `layouts/storefront.blade.php` | Out of scope. |

### 1.2 Gaps

| # | Finding | Evidence | Severity |
| --- | --- | --- | --- |
| **B1** | **No structured data at all.** No JSON-LD anywhere in `app/`, `resources/`. | grep for `ld+json` / `schema.org` returns nothing | High |
| **B2** | **`public/robots.txt` allows everything** (`User-agent: *` / `Disallow:`). `/admin`, `/hub`, `/account`, `/portal`, `/api`, auth and staff-invitation URLs are crawlable by rule. It is also a static file, so a future dynamic route would be shadowed. | `public/robots.txt` | **Critical** |
| **B3** | **No sitemap** of any kind and no `Sitemap:` line. | no route/file | High |
| **B4** | **No `robots` meta / `X-Robots-Tag`** anywhere. Private areas, filtered listings, auth pages and the staff-invitation token URL rely on nothing but auth redirects. | no matches | High |
| **B5** | **`/favicon.svg` is referenced by the layout but the file lives at `public/images/favicon.svg`** (and is 1.8 MB). The link 404s. | `layouts/storefront.blade.php`, `public/` | Medium |
| **B6** | **Default OG image is the wordmark** `images/brand-logo.png` (1459×355). Not 1.91:1 and not square, so previews crop or letterbox it. `Organization.logo` would need a square asset (`apple-touch-icon.png` is 180×180; `halal_store_main_logo.png` is 960×1020). | `file public/images/*` | Medium |
| **B7** | **No public category pages.** Categories have `slug` and `description` but are only reachable as `?category={id}` on `/products`. Facet URLs have no canonical or `noindex`. | `product-directory.blade.php`, `routes/web.php` | High |
| **B8** | **Filter/search/pagination URLs on `/products` and `/companies`** have no canonical strategy. | Livewire query-string state, no canonical | Medium |
| **B9** | **No hreflang, no `og:locale`, no `inLanguage`.** The app serves 5 locales but locale is chosen by **session** (`SetLocale`), so one URL can return different languages. | `SetLocale.php`, `config/marketplace.php` `portals` | Medium (strategy decision) |
| **B10** | **No AI-crawler policy, no `llms.txt`.** | n/a | Medium |
| **B11** | **Shop OG image is the generic logo** for every company; company pages have no `Organization` entity markup, no certification markup. | `company-profile.blade.php` | Medium |
| **B12** | **FAQ content is inline Alpine markup** (`x-data open/query`) in `legal/faq-page.blade.php`, with no shared data source, so `FAQPage` markup cannot be guaranteed to equal visible text. | `faq-page.blade.php` | Low–Medium |
| **B13** | **Title/description quality is uneven**; several pages have no `<meta name="description">` (home, about, directories use only OG). | per-view `@push('meta')` | Medium |
| **B14** | **Private/PII fields exist on `Shop`** (`phone`, `email`, `address_1/2`, `zip_code`, `dti_sec_number`, `tax_identification_number`, `fda_lto_number`, `geo_location`). Any schema builder that reads the model wholesale would leak them. | `Shop::$fillable` | **Guardrail** (see §3) |
| **B15** | **CI pins PHP 8.3** while `composer.json` requires `^8.4.27`. Confirm CI is currently green before adding more gates. | `.github/workflows/tests.yml`, `composer.json` | Low (verify) |

---

## 2. Goals and non-goals

**Goals**

1. Public pages emit valid, entity-linked JSON-LD (`@context` + `@graph` + stable `@id`), only with data the page actually shows.
2. Crawl control is explicit and generated from one config: private areas blocked, facets handled, sitemaps discoverable.
3. Reliable social/messaging previews (Facebook, WhatsApp, LinkedIn, X, Telegram) with correct images.
4. Buyers' and AI answer engines can discover and cite **verified halal suppliers, certificates and products** per country.
5. Regression guardrails: tests and a scheduled audit.

**Non-goals**

- Merchant Center / Google Shopping feeds (B2B has no checkout).
- Paid acquisition, backlinks, GA4 changes.
- A full multilingual URL strategy (`/ar/...`). Only a decision and the minimum safe behaviour (see Phase 3).
- Rewriting page UI or copy beyond adding unique, factual intro text.

---

## 3. Target architecture and guardrails

Follow the repo's existing conventions (final classes, `declare(strict_types=1)`, `app/Actions`, `app/Support`, `app/ValueObjects`, Pest tests). Mirror the shape of the B2C solution but built for Blade/Livewire:

```
config/seo.php                         // single source of truth (see T0.3)
app/Support/Seo/
    SeoData.php                        // value object: title, description, canonical, robots, og, twitter, jsonLd, hreflang
    SeoResolver.php                    // set()/get() per request; fallback for pages that set nothing
    Visibility.php                     // shared "publicly visible shop/product" rules (extracted from EnsureCanonicalSlug)
app/Support/Schema/
    SchemaGraph.php                    // @context once, @graph, de-dupe by @id, strip null/empty
    SchemaId.php                       // stable @id helpers
app/Actions/Seo/
    Build{Home,Product,Company,CompanyDirectory,ProductDirectory,Category,Content}SeoData.php
    Schema/Build{Organization,WebSite,WebPage,Breadcrumb,Product,Supplier,ItemList,Faq}Node.php
    BuildRobotsTxt.php  BuildSitemap*.php  BuildLlmsTxt.php
resources/views/components/seo/head.blade.php   // the ONE place that renders meta + JSON-LD
```

**Rules**

- Pages call `Seo::set(...)` (Livewire `render()`/`mount()`); the layout renders `<x-seo.head />`. The per-view `@push('meta')` blocks are removed as each page migrates.
- One `<script type="application/ld+json">` per page: `{"@context":"https://schema.org","@graph":[...]}`.
- Stable ids: `{base}/#organization`, `{base}/#website`, `{base}/companies/{slug}#organization`, `{base}/products/{slug}#product`, plus `#breadcrumb` / `#webpage` per URL. `{base}` is the instance's `APP_URL`, never a hard-coded domain.
- Encode with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`. Company names, product names and descriptions are user-supplied (product/company descriptions come from a WYSIWYG, `hs-wysiwyg`/Trix), so output must be sanitised plain text, never raw HTML.
- Only emit properties backed by real, **publicly displayed** data. No invented ratings, prices, dates or empty strings.
- **Public-data allow-list for companies (B14):** the only `Shop` fields schema/meta/llms may read are `storefront_name`, `slug`, `company_description`, `products_description`, `city`, `province`, `country_code`, `value_chain_positions`, `capabilities`, `export_readiness_status`, `average_rating`/`total_reviews` (flag-gated), and verified, active certificate **body + status**. Never `phone`, `email`, `address_1/2`, `zip_code`, `dti_sec_number`, `tax_identification_number`, `fda_lto_number`, `registered_business_name`, or `geo_location`. Builders take a dedicated DTO, not the model, and a test asserts none of those values appear in head, JSON-LD, sitemap or `llms.txt`.

---

## 4. Phases

| Phase | Theme | Outcome | Est. |
| --- | --- | --- | --- |
| **0** | Stabilise and stop the leaks | Private areas blocked, static robots removed, broken assets fixed, one SEO config | 1–2 d |
| **1** | Shared head layer | One component renders meta; per-view duplication removed; unique titles/descriptions/canonicals | 2–3 d |
| **2** | Schema graph | Organization/WebSite/WebPage/Breadcrumb everywhere; Product and Supplier entities | 3–4 d |
| **3** | Discovery | Public category pages, facet policy, sitemaps, hreflang decision | 2–3 d |
| **4** | Social and AI previews | Correct OG images and tags, hardened image delivery | 1–2 d |
| **5** | GEO layer | AI-crawler policy, `llms.txt`, trust facts, FAQ, crawlable-content audit | 2–3 d |
| **6** | Validation and rollout | CI gate, scheduled audit, manual checklist, staged release | 1–2 d |

Phase 0 → 1 are sequential. Phases 2, 3, 4 and 5 can run in parallel after Phase 1 (they touch different files, except T2.x and T3.1 which share the new category route).

---

## 5. Tasks

**P** = priority (P0 must, P1 should, P2 nice). Each task lists the work and acceptance criteria (**AC**).

### Phase 0 — Stabilise

**T0.1 (P0) — Replace static `robots.txt` with a generated one** *(B2, B4)*
- Delete `public/robots.txt`; add `/public/robots.txt`, `/public/sitemap*.xml`, `/public/llms*.txt` to `.gitignore` so a local dump can't shadow the routes.
- New `RobotsController` + `BuildRobotsTxt` reading `config/seo.php`. Disallow: `/admin`, `/hub`, `/account`, `/portal`, `/api`, `/auth`, `/login`, `/register`, `/forgot-password`, `/reset-password`, `/two-factor*`, `/staff/invitations`, `/settings`, `/media`, `/users`, `/notifications`, `/rfqs`, `/orders`, `/messages`, `/payments`, `/onboarding`, `/profile`. (Confirm the exact prefixes against `routes/web.php` and `routes/settings.php` when implementing.)
- Add `Sitemap:` line. Emit `Disallow: /` on any non-production environment (`APP_ENV !== production` or `SEO_FORCE_NOINDEX=true`).
- **AC:** `GET /robots.txt` is dynamic; `git ls-files public | grep -E "robots|sitemap|llms"` is empty; a test proves staging returns `Disallow: /`.
- **Note:** `Disallow` stops crawling, not indexing. Private pages also get T0.2.

**T0.2 (P0) — `noindex` for everything non-public** *(B4)*
- Middleware `EnforceSeoStandards`: add `X-Robots-Tag: noindex, nofollow` for the private prefixes in config (and for any response where `Seo` marks the page non-indexable). Add `<meta name="robots">` from `SeoData`.
- Staff-invitation token pages and auth pages: `noindex`. Search/filter result states: `noindex, follow` (see T3.2).
- **AC:** one config list drives robots.txt, meta robots and `X-Robots-Tag`; a parity test iterates the list.

**T0.3 (P0) — Create `config/seo.php`**
- Keys: `force_noindex`, `site_name` (from `app.name`), `default_og_image`, `logo_path`, `private_path_patterns`, `noindex_follow_query_params` (`search`, `category`, `country`, `certification`, `sort`, … match the real Livewire `#[Url]` names), `ai_crawlers`, `social_profiles`, `contact`.
- **AC:** no SEO list is duplicated in controller, middleware and view.

**T0.4 (P0) — Fix favicon and brand image assets** *(B5, B6)*
- Serve `favicon.svg` from `public/` root (move/copy) and **optimise the 1.8 MB SVG** or drop it in favour of PNG/ICO sizes.
- Create assets: `og-default.jpg` (1200×630) and a square `logo-512.png` (≥112×112, recommend 512×512) for `Organization.logo`. Add `32×32` PNG icon.
- **AC:** HEAD on every image URL in the home head returns 200; `Organization.logo` is square; default OG image is 1200×630.

**T0.5 (P1) — Title and description sweep**
- Every indexable page gets a unique `<title>` (≤ ~60 chars) and `<meta name="description">` (140–160 chars). Replace generic titles (`Business Directory`, `Product Directory`) with descriptive ones including country scope from `marketplace.instance_country`.
- **AC:** a test visits every public route and asserts unique non-empty title and description.

### Phase 1 — Shared head layer

**T1.1 (P0) — `SeoData`, `SeoResolver`, `<x-seo.head>`**
- Value object + request-scoped resolver; Blade component renders: title, description, canonical, robots, `og:*`, `twitter:*`, `og:locale`, hreflang slot, JSON-LD (via `SchemaGraph` once Phase 2 lands).
- Layout `storefront.blade.php` (and `auth`/`portal` layouts for `noindex`) render the component in place of `@stack('meta')`. Keep `@stack('meta')` temporarily for unmigrated pages.
- **AC:** pages that set nothing still get a safe fallback (site title, `noindex` where private).

**T1.2 (P0) — Migrate pages**
- Home, `/companies`, `/companies/{slug}`, `/products`, `/products/{slug}`, `/about`, `/faq`, `/terms`, `/privacy`, `/certification/apply`, `/request-a-product`. Remove each view's `@push('meta')` as it moves; delete the stack when empty.
- Canonical on **every** indexable page (query-stripped, absolute, `APP_URL` origin).
- **AC:** `grep -rn "push('meta')" resources/views` is empty; every public page has one canonical.

**T1.3 (P1) — Canonical and tracking-param hygiene**
- Canonical builder strips UTM, `fbclid`, `gclid`, `ref` and the share-button parameters. Confirm `hs-share-buttons` / `ProductShareStat` share the clean canonical URL.
- **AC:** test with `?utm_source=x&fbclid=y` asserts canonical equals the clean URL.

### Phase 2 — Schema graph

**T2.1 (P0) — `SchemaGraph` + `SchemaId`**
- `add()`, `ref()`, de-dupe by `@id`, recursive null/empty stripping, `toArray()` returning `@context` + `@graph`; hex-escaped encode.
- Unit tests: de-dupe, null stripping, id stability, hostile strings (`</script><script>alert(1)</script>`).

**T2.2 (P0) — Sitewide nodes**
- `Organization` (the marketplace operator): `@id`, `name`, `url`, square `logo` as `ImageObject`, `description`, `sameAs` (config; omit when empty), `contactPoint` only if a public support email is configured, `areaServed` from the **instance country** (not hard-coded), `knowsAbout` (halal supply chain, halal certification, B2B sourcing).
- `WebSite`: `@id`, `publisher` → org, `inLanguage` from locale. Add a `SearchAction` only if the site search has a stable GET URL; **(verify)** whether `/products?search=` (actual param name) is a real, crawlable URL — otherwise omit it.
- Full on home; other pages reference by `@id`.

**T2.3 (P0) — `WebPage` + `BreadcrumbList` on every page**
- `WebPage`/`CollectionPage`/`AboutPage`/`FAQPage` wrapper with `isPartOf`, `breadcrumb`, `primaryImageOfPage`, `inLanguage`.
- One shared `BuildBreadcrumbNode`, **mirrors the visible breadcrumb**. Note the product view currently shows only one category ancestor chain via `parent`; schema and UI must match (extend both to full ancestry when category pages exist).

**T2.4 (P0) — Supplier (company) node**
- Type `Organization` (kept distinct from the marketplace org) on `/companies/{slug}`: `@id`, `url`, `name` (`storefront_name`), sanitised `description`, `image`/`logo` only if the company has a real uploaded logo (**verify** there is one; otherwise use none rather than the marketplace logo), `address` as `PostalAddress` with **only** `addressLocality`, `addressRegion`, `addressCountry`, `areaServed`, `knowsAbout` from `capabilities`/`value_chain_positions`, `memberOf` → marketplace org.
- Halal certification: for each **active and `is_verified`** `ShopCertificate`, add `hasCertification` → `Certification` with `name` (the body), `issuedBy` (`Organization` named for the body; `certification_body_other` when "other"). Publish `registration_number` and expiry **only if the company page already shows them** to visitors (**decision D3**).
- `aggregateRating` only when `reviews_enabled` **and** `total_reviews ≥ 1` **and** the page shows it.
- **AC:** test proves no field from the B14 deny-list appears anywhere in head or JSON-LD; test proves expired/unverified certificates are omitted.

**T2.5 (P0) — Product node (B2B semantics)**
- `Product`: `@id`, `url`, `name`, sanitised full `description`, absolute `image[]`, `brand`/`manufacturer`/`seller` → the supplier node `@id` (no separate brand field exists; do not invent one), `category` (breadcrumb path text), `sku` per variant.
- Variants: `ProductGroup` + `hasVariant` when >1 variant (per-variant `sku`, `additionalProperty` for each `attributes` key/value, `variesBy` for real attribute names), otherwise plain `Product`.
- Offers: emit `Offer` **only if a price is displayed publicly on the page**. Include `price`, `priceCurrency` (from the variant's `currency`, never hard-coded `PHP`), `eligibleQuantity` (`QuantitativeValue` with `minValue` = MOQ and `unitText` = unit). Tiered `quantity_breaks` → additional `UnitPriceSpecification` entries only if the tiers are shown. If prices are gated or "request quote", emit **no `offers`** and rely on `Product` description and the supplier link. Do not emit `shippingDetails`, `hasMerchantReturnPolicy`, `priceValidUntil` or `availability` unless real data backs them (`available_quantity` may justify `InStock`/`OutOfStock` — **decide D2**).
- Halal signals: `additionalProperty` for halal status derived from the supplier's active certificate; no per-product certificate exists, so do not claim product-level certification.
- Rating: `aggregateRating` from `products.average_rating/total_reviews` only when `reviews_enabled`, count ≥ 1, and visible on the page. No `Review` nodes until reviews are enabled and visible.
- **AC:** every emitted value equals what `product-detail` renders (test compares schema to the rendered page/model). Expected eligibility is **Product snippets**, not Merchant listings — do not chase merchant-listing errors for a non-checkout site.

**T2.6 (P1) — Listings**
- `/products`, `/companies`, category pages: `CollectionPage` + `ItemList` with `ListItem.item` stubs sharing the detail `@id`s (`url`, `name`, `image`). Page 1 only lists what the page shows; paginated pages self-canonical.

**T2.7 (P1) — Content pages**
- `/about` → `AboutPage` linked to the org; `/faq` → `FAQPage` only after T5.4 makes the Q&A a shared data source; `/terms`, `/privacy` → plain `WebPage`. Add `dateModified` only from a real source (config/date constant per page), never "now".

**T2.8 (P2, deferred) — Video**
- Products can have a video (flagged `product_video_enabled`) but playback uses presigned S3 URLs valid 30 minutes. `VideoObject` needs a stable `contentUrl` and `thumbnailUrl`; revisit when a public stable URL and poster exist.

### Phase 3 — Discovery

**T3.1 (P0) — Public category pages** *(B7)*
- New route `GET /categories/{category:slug}` (+ index `/categories` optional). Add `categories` to `marketplace.slugs.reserved` if slugs are ever served at root. Reuse the `canonical.slug` pattern (redirect to the canonical slug).
- Content: `h1`, unique intro (from `Category.description`, otherwise built from real counts of visible products/companies and real subcategory names), product list using `Product::marketplaceVisible()`, breadcrumb to parent.
- Make `/products?category={id}` canonical to the category page (301 for legacy `?category=` links is optional; decide after checking inbound links).
- **AC:** every category with ≥1 visible product has a 200 page with unique title, description and canonical; empty categories are `noindex` (or 404).

**T3.2 (P0) — Facet and search URL policy** *(B8)*
- Any request carrying search/filter/sort params → `noindex, follow`, canonical to the clean listing URL. `?page=N` self-canonical and indexable up to a reasonable depth.
- **AC:** parity test over `config('seo.noindex_follow_query_params')`.

**T3.3 (P0) — Sitemaps** *(B3)*
- Index `/sitemap.xml` + children: `sitemap-pages.xml`, `sitemap-categories.xml`, `sitemap-companies.xml`, `sitemap-products-{n}.xml` (≤ 50 000 URLs / 50 MB each).
- Sources: static public routes; categories with visible products; **active shops** (apply the same rules as `EnsureCanonicalSlug::isVisible`, including subscription gating); `Product::marketplaceVisible()`. Slug URLs only.
- `lastmod` = real `max(updated_at)`; static pages omit it.
- Cache (Redis) and invalidate on Product/Shop/Category/ShopCertificate changes and on subscription status change (visibility depends on it).
- Generated dynamically; **no** scheduled write into `public/`.
- **AC:** no `localhost`; two requests without data changes give identical `lastmod`; a suspended shop or unpublished product is absent.

**T3.4 (P1) — hreflang / locale strategy** *(B9, decision D4)*
- Today locale is session-based, so crawlers always see the instance default locale. Recommended minimum: set `html lang`, `og:locale`, JSON-LD `inLanguage` from the **effective** locale; **do not** emit hreflang between locales of one URL.
- Cross-country hreflang: when `marketplace.portals[*].url` values are configured, emit `<link rel="alternate" hreflang="{locale}-{CC}">` + `x-default` pointing at the equivalent home URLs only (page-level equivalents don't exist across independent deployments).
- Crawlers must not receive a locale chosen by a cookie/session set via a language switcher on a shared URL; confirm the switcher doesn't change the indexed page (**verify**).
- **AC:** no hreflang is emitted unless target URLs are configured; emitted hreflang is reciprocal.

### Phase 4 — Social and AI previews

**T4.1 (P0) — Complete OG/Twitter set**
- All pages: `og:title/description/url/type/site_name/locale/image` + `image:width/height/alt/secure_url`, `twitter:card`, `twitter:title/description/image`. Correct `og:type`: `website` for home/lists, `article` is only for dated editorial content (none today), `product` for products. **Do not use `business.business` for companies without confirming it fits** — it requires place data (`business:contact_data:*`) that must not include private contact fields; prefer `website`/`profile` with the company name, or `og:type=website`. (**decide D5**)
- Replace the wordmark default with the 1200×630 default image.

**T4.2 (P1) — Company and product images**
- Product: keep `featured-image` when the product is publicly visible; otherwise default image. Fix the visibility check in the image route to use the same `marketplaceVisible()` rules (it currently checks status/shop/subscription but **not** `moderation_status`).
- Make the image URL **versioned** (`?v={updated_at}`) and only send `immutable` for versioned requests (today it is `immutable` for one year on a stable URL, which leaves stale previews after an image change).
- Companies: use a real company logo/cover if one exists; otherwise the default OG image. Do not use private media.
- **(verify)** actual dimensions/size of seller-uploaded images; OG images should be ≥1200×630 where possible and < ~5 MB; decide whether a generated card (as in B2C `GenerateSocialImage`) is worth it for B2B or if a default is enough.

**T4.3 (P2) — Favicon/app signals**
- 180×180 apple-touch-icon exists; add 32×32 and 192/512 PNG, `application-name`, optional `manifest.webmanifest`.

### Phase 5 — GEO layer

**T5.1 (P0) — AI-crawler policy** *(B10, decision D1)*
- Business decision: allow search/answer/user-triggered bots (e.g. `OAI-SearchBot`, `ChatGPT-User`, `PerplexityBot`, `Claude-SearchBot`, `Claude-User`, `Googlebot`, `Bingbot`); training bots (`GPTBot`, `ClaudeBot`, `Google-Extended`, `CCBot`, `Applebot-Extended`, `Bytespider`) behind a toggle (`SEO_BLOCK_AI_TRAINING`).
- A named `User-agent` group replaces `*` for that bot, so **repeat the private `Disallow` lines inside every named group**.
- Verify each vendor's current bot names before launch; they change.
- **AC:** test asserts every named agent repeats all private disallows; toggle changes only training groups.

**T5.2 (P1) — `/llms.txt`**
- `LlmsTxtController` + `BuildLlmsTxt`, cached, production-only (404 on non-production/force-noindex, like robots).
- Content: what 1Halal Store is, countries/instance served, how supplier verification and certificate tracking work (only as the product really does), link sections: top categories, company directory, product directory, FAQ, about, terms/privacy, sitemap. Public URLs only.
- Treat as a low-cost, unproven signal; don't claim it affects ranking.
- **AC:** valid markdown, public 200 URLs only, no B14 fields, cache cleared with sitemap invalidation.

**T5.3 (P0) — Machine-readable trust facts (single source)**
- Create `TrustFacts` fed from `config/marketplace.php` and real behaviour: certificate expiry tracking (60/30/7-day alerts), certificate statuses (`active`, `expiring_soon`, `expired`), antivirus-scanned documents, admin onboarding approval, the commission copy shown today. Only state what is **enabled**: honour feature flags (`orders_enabled`, `escrow_payments_enabled`, `reviews_enabled`, `quotations_enabled`, `chat_enabled`…) so copy, schema and `llms.txt` never advertise escrow/orders/reviews that are off.
- Feeds FAQ, `llms.txt`, Organization description and `about` copy.
- **AC:** with a flag off, its claim appears nowhere (test per flag).

**T5.4 (P1) — FAQ as shared data + FAQPage**
- Extract the FAQ list from `faq-page.blade.php` into `FaqEntries` (translatable via `lang/*`); render visibly **and** emit `FAQPage` from the same list. Make sure answers are in the DOM (not only toggled by Alpine `x-show` without being rendered). Answers short, factual, self-contained.
- Google restricts FAQ rich results, so the value is answer-engine extraction, not a SERP feature.
- **AC:** parity test between visible Q&A and JSON-LD.

**T5.5 (P1) — Crawlable body content audit** *(replaces B2C SSR task)*
- Livewire renders initial HTML on the server. Audit for content that only appears after interaction: `#[Lazy]`/deferred components, `wire:init`, Alpine-only blocks, tabs/accordions without DOM content, sponsored banners.
- Add a crawler-UA smoke test per page type asserting the product/company name, description, certificate status and breadcrumbs are in the **first HTML response**.
- Semantic structure: one `h1`, ordered `h2/h3`, `<dl>` for specifications, `alt` text on product/company images.
- **AC:** `Http::get` with `Googlebot` UA returns body text for home, a category, a product and a company.

**T5.6 (P2) — Entity consistency**
- `sameAs` from configured social profiles; keep name/description identical to those profiles; document all new env vars in `.env.example`.

### Phase 6 — Validation, monitoring, rollout

**T6.1 (P0) — JSON-LD validation in CI**
- `JsonLdValidator` (PHP): single `@context` + `@graph`, `@type` on every node, unique/resolvable `@id`s, no empty values, absolute URLs, Google's required properties for the types we emit. Pest policy test renders home, product (single/variant), company, category, directories, FAQ and about in-process and validates each.
- Add the private-field leak test (B14) to the same gate.
- Confirm CI is green first (B15).

**T6.2 (P0) — Manual pre-release checklist** (needs a deployed staging site)
- Google Rich Results Test (product snippets, breadcrumb, organization, FAQ where relevant), Schema Markup Validator, Facebook Sharing Debugger, LinkedIn Post Inspector, X card validator, WhatsApp/Telegram paste tests. Document results in a short runbook.

**T6.3 (P0) — Search Console / Bing Webmaster**
- Verify each country's domain, submit `sitemap.xml`, watch Enhancements, Page indexing and Core Web Vitals.

**T6.4 (P1) — Monitoring**
- `php artisan seo:audit` scheduled weekly in `routes/console.php` (`Schedule::command(...)->weeklyOn(1, '05:00')->timezone('UTC')`, production only): samples home, static pages, N categories, shops and products; validates live JSON-LD; checks image URLs, sitemap index/children, `robots.txt`, `llms.txt`; logs errors and exits non-zero on regression.
- GA4 AI-referral channel group and crawler-hit reporting are operational tasks, not code.

**T6.5 (P1) — Staged rollout**
1. Ship Phase 0 alone first (robots, noindex, assets): it fixes the most exposed problem.
2. Phases 1–5 to staging (stays `noindex`); run T6.1/T6.2.
3. Release per country instance; request indexing for home, a few categories, companies and products.
4. Compare Search Console at 2 and 6 weeks.

---

## 6. Test matrix (Pest, new `tests/Feature/Seo*` files)

| Test | Covers |
| --- | --- |
| Every public page's JSON-LD has one `@context`, `@graph`, unique `@id`s, all references resolve | T2.1–T2.3 |
| Hostile product/company names can't break out of `<script>` | T2.1 |
| No B14 private field appears in head, JSON-LD, sitemap or `llms.txt` | T2.4, T5.2, T6.1 |
| Expired or unverified certificates are never emitted | T2.4 |
| Product schema price, currency, MOQ, unit equal rendered values; no `offers` when price is hidden | T2.5 |
| `aggregateRating` absent when `reviews_enabled` is false or count is 0 | T2.4, T2.5 |
| robots.txt, meta robots and `X-Robots-Tag` agree for every private pattern | T0.1–T0.3 |
| Non-production returns `Disallow: /` and `noindex` | T0.1 |
| Each named AI user-agent repeats the private disallows | T5.1 |
| Filter/search/sort URLs are `noindex, follow` and canonical to the clean URL | T3.2 |
| Tracking params stripped from canonical | T1.3 |
| Sitemap excludes suspended shops, unpublished/moderated products; no `localhost`; stable `lastmod` | T3.3 |
| `/llms.txt` lists only public 200 URLs; 404 outside production | T5.2 |
| Feature-flag-off claims appear nowhere in copy/schema/llms | T5.3 |
| FAQ visible text equals FAQPage JSON-LD | T5.4 |
| Crawler UA gets body content on each page type | T5.5 |
| Every image URL emitted on home returns 200; OG default is 1200×630 | T0.4, T4.1 |
| Unique non-empty title/description on every public route | T0.5 |

---

## 7. Definition of done

- All P0 tasks merged. No static SEO files in `public/`. `/admin`, `/hub`, `/account`, `/portal`, `/api` and auth/invite URLs are blocked by robots **and** `noindex`.
- Rich Results Test shows 0 errors for the types emitted on ≥10 product pages, a company, a category and the home page.
- Sitemaps, `robots.txt` and `llms.txt` are generated from config and match the AI-crawler decision.
- Sharing home, a product, a company and a category shows correct title, description and image.
- `php artisan test` (incl. the new SEO suites) passes in CI.

---

## 8. Decisions and open questions

| ID | Question | Notes / recommendation |
| --- | --- | --- |
| **D1** | AI-crawler policy | Allow-for-citation vs block-for-training is a business call (T5.1). Recommended default: allow search/answer bots, training bots behind a toggle. |
| **D2** | Does the product page show public prices/availability? | If prices are quote-only or gated, emit no `offers`. Decide per the real page (T2.5). |
| **D3** | Publish certificate registration numbers and expiry dates in schema? | Only if already visible on the company page; suppliers may not expect machine-readable exposure. |
| **D4** | Locale URL strategy | Keep session-based locale and no cross-locale hreflang (minimum), or introduce locale-prefixed URLs (larger project, out of scope here). |
| **D5** | Company `og:type` | `business.business` expects place/contact data; prefer `website` unless a safe, public subset is defined. |
| **D6** | Shop public address | Registration collects full addresses; schema publishes city/region/country only. Confirm suppliers consent before publishing anything more precise. |
| **D7** | Company logos | Confirm whether companies upload a logo/cover that can serve as `og:image`/`logo`; otherwise the default image is used. |
| **D8** | Reviews | `reviews_enabled` is off today. All rating/Review markup is gated on the flag **and** visible UI; revisit when reviews launch. |
| **D9** | Subscription gating | Sitemap, schema and llms.txt must follow `subscriptions_enabled` visibility, and caches must be invalidated when a subscription changes state. |
| **D10** | Vendor drift | Google structured-data policy, crawler names and Merchant rules change; re-verify against current documentation when implementing each phase. |

---

## 9. Suggested sequencing

1. **Day 1:** T0.1, T0.2, T0.3, T0.4 (hotfix PR; ship alone).
2. **Days 2–4:** T0.5, T1.1–T1.3, T2.1.
3. **Days 5–9:** T2.2–T2.7 in parallel with T3.1–T3.3 and T4.1–T4.2; T5.1–T5.3.
4. **Days 10–12:** T3.4, T5.4–T5.6, T6.1.
5. **Days 13–14:** T6.2–T6.5 and per-country rollout.
