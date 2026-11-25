# SEO & GEO rollout runbook (T6.2 · T6.3 · T6.5)

Operational companion to `seo-geo-optimization.md`. These steps need a deployed site and
third-party accounts, so they cannot be automated from the repo. Record results in the
tables at the bottom of this file (one row per run, per country instance).

## 0. Before anything else

- [ ] `php artisan test --compact` is green in CI (B15 resolved; includes the SEO suites).
- [ ] Re-verify vendor drift (D10): crawler names in `config/seo.php` against each vendor's
      current docs; Google structured-data and Rich Results requirements.
- [ ] Confirm D1 (AI-crawler policy: `SEO_BLOCK_AI_TRAINING`), and replace the placeholder
      `admin@gmail.com` in the FAQ with the real support address (`SEO_CONTACT_EMAIL`).
- [ ] Set per-instance env: `APP_URL`, `MARKETPLACE_INSTANCE_COUNTRY`, `SEO_SOCIAL_PROFILES`,
      `SEO_ENTITY_DESCRIPTION`, `PORTAL_xx_URL` (+ `SEO_HREFLANG_X_DEFAULT_COUNTRY` if wanted).

## 1. Staging validation (T6.2)

Staging stays `noindex` (non-production). Run the in-app audit first:

```bash
php artisan seo:audit --url=https://staging.example.com --allow-noindex --sample=10
```

Then check these by hand and note the result:

| Tool | Pages | Pass when |
| --- | --- | --- |
| Google Rich Results Test | 10+ products (single and variant), a company, a category, home | 0 errors; Product snippets, Breadcrumb, Organization detected |
| Schema Markup Validator (validator.schema.org) | same set | 0 errors |
| Facebook Sharing Debugger | home, a product, a company, a category | title, description and a 1200×630 image shown |
| LinkedIn Post Inspector | same | same |
| X card validator / paste test | same | `summary_large_image` with image |
| WhatsApp and Telegram paste | home and a product | preview with image |

Known expectations: eligibility is **Product snippets**, not Merchant listings; no
`aggregateRating` while `reviews_enabled` is off; FAQ rich results are restricted by Google.

Still open from T4.2: check the dimensions and size of real seller-uploaded product images
(OG images should be ≥1200×630 and under ~5 MB) and decide whether a generated card is worth it.

## 2. Search Console and Bing Webmaster (T6.3)

Per country domain, after the production release:

1. Verify the domain property (DNS TXT) in Google Search Console and Bing Webmaster Tools.
2. Submit `https://{domain}/sitemap.xml`.
3. Request indexing for home, 3–5 categories, 3–5 companies and 3–5 products.
4. Watch: Pages (indexing) for unexpected "Excluded by noindex" on public URLs, Enhancements
   (Breadcrumbs, Product snippets), Sitemaps (discovered vs indexed), Core Web Vitals.
5. GA4 (operational): add an AI-referral channel group; review crawler hits in server logs.

## 3. Staged rollout (T6.5)

1. Ship Phase 0 alone first (robots, noindex, assets) if it is not yet in production.
2. Phases 1–5 to staging; run section 1 above and fix findings.
3. Release one country instance at a time. After each release run
   `php artisan seo:audit --sample=10` on production; the weekly schedule (Mondays 05:00 UTC,
   production only) then keeps watching and logs `seo:audit found N problem(s)` on regression.
4. Compare Search Console at 2 and 6 weeks (impressions, indexed pages, rich-result counts).
5. Halt plan: if public pages drop out of the index, check `SEO_FORCE_NOINDEX`, `APP_ENV`
   and the `X-Robots-Tag` header first; rolling back means unsetting `SEO_FORCE_NOINDEX`
   or redeploying the previous release.

## Results log

### Staging validation

| Date | Instance | Tool | Pages | Result | Notes |
| --- | --- | --- | --- | --- | --- |
|  |  |  |  |  |  |

### Production rollout

| Date | Instance | Search Console verified | Sitemap submitted | Indexing requested | 2-week check | 6-week check |
| --- | --- | --- | --- | --- | --- | --- |
|  |  |  |  |  |  |  |
