# tasks.md — Slugs, Product Video, Seller Staff

Companion to `requirements.md` and `design.md`. Same checklist style as Phase 6 (`[ ]` open). `S` = spike (answers a **VERIFY** in `design.md` before wide edits). Estimates are rough engineer-days for one developer familiar with the codebase.

**Suggested order:** M0 → M1 (Deploy A) → M2 (Deploy B) → M3 → M4 → M5 → M6 → M7. F1, F2 and F3 are independent after M0; F3 is the largest and can start in parallel.

---

## M0 — Spikes and prerequisites (≈ 1.5 d)

- [ ] **S1** Install deps (`composer install`, `sail up`) and confirm baseline: `composer ci:check` is green on `e389369`
  - OrbStack is the active Docker context; `vendor/bin/sail up -d` starts the services. `vendor/bin/sail composer ci:check` passes Pint but fails at PHPStan with 1,188 errors, so the required green baseline is not established. Tests were not reached by the script.
- [ ] **S2** Prove `{product:slug}` on `Route::livewire(...)`: `route('storefront.product', $product)` emits the slug, `mount(Product $product)` receives the model, Livewire update requests still work on the page
  - Runtime URL generation with a temporary `Route::livewire('/_spike/products/{product:slug}', ...)` route emitted the model's `slug`. Livewire 4 source resolves typed route properties using the binding field; mount + update-request behavior still needs an end-to-end page check.
- [x] **S3** Prove a custom middleware placed after `SubstituteBindings` can read the raw segment and 301 (query string preserved)
  - In-memory route probe returned `301` to `/canonical/old?ref=abc` from a middleware after `SubstituteBindings`.
- [ ] **S4** Presigned `temporaryUrl()` plays in `<video>` with Range on Chrome, Firefox, **iOS Safari**; test local MinIO with `AWS_TEMPORARY_URL` set to the browser endpoint; confirm whether `Storage::response()` currently answers `Range` (expect no)
  - MinIO returned `206`, `Accept-Ranges: bytes`, and `Content-Range: bytes 0-3/10` when the presigned URL retained its signed internal host. Rewriting the URL to the configured browser endpoint (`localhost:9000`) returned `403 SignatureDoesNotMatch`; the override changes the signed host. `Storage::response()` source streams the full object and does not inspect Range. Browser/device playback remains unverified; D4 needs an endpoint-aware signing approach or a Range-aware proxy.
- [ ] **S5** Sniffed MIME of real-world samples (MP4 from iPhone/Android/screen-recorder, WebM, MOV) via `UploadedFile::getMimeType()`; adjust allow-list if MP4 sniffs differently (`video/x-m4v`, etc.)
  - No MP4/WebM/MOV samples are present in the repository; real-world MIME sniffing remains open.
- [x] **S6** Confirm `GoogleAuthController` and Fortify registration/login return to `url.intended` (needed by the staff invitation flow)
- [ ] **S7** Production data check: list `media` rows older than 7 days not referenced by the 6 tables in `ReconcileMediaOrphansJob` but referenced elsewhere (`rfq_attachments`, `dispute_media`, `sponsored_content`, `payment_attempts.receipt_media_id`, …) to size the adjacent orphan bug
  - The local Sail database is not production; production data has not been queried.
- [x] Record decisions D1–D7 (`requirements.md` §6) in this folder before M1

---

## M1 — F1 Slugs, Deploy A: columns, generation, backfill (≈ 2 d)

- [x] Migration: `slug` (nullable, `varchar(100)`) on `shops` and `products`
- [x] Migration: `slug_redirects` (`sluggable_type`, `sluggable_id`, `slug`, unique `(type, slug)`)
- [x] `SlugRedirect` model
- [x] `config/marketplace.php`: `slugs.reserved`
- [x] `App\Support\Slugs\SlugGenerator` (locale-aware `Str::slug`, ≤ 90 chars base, numeric/empty/reserved fallback, `-n` suffix, includes soft-deleted rows and considers `slug_redirects`)
- [x] `App\Models\Concerns\HasSlug` (automatic slug generation with unique-violation retry ×5, updating hook → `slug_redirects`, `resolveRouteBinding` slug/old-slug/legacy-id)
- [x] Apply trait to `Shop` (`storefront_name`) and `Product` (`name`); add `slug` to `$fillable` and docblocks
- [x] `php artisan slugs:backfill` (idempotent, `chunkById(500)`, includes soft-deleted rows, non-zero exit if any null remain)
- [x] Unit tests: generator (collision, numeric, empty/Arabic, Turkish, reserved, soft-deleted, redirects reserve slugs)
- [x] Feature tests: `ApproveOnboardingAction`, `ShopFactory`/`ProductFactory`, `ProductForm::save()` all yield slugs; backfill idempotent
- [ ] Deploy A to staging → run backfill → confirm zero `NULL` slugs
  - Local implementation verified with 30 focused tests (68 assertions), Pint, and targeted PHPStan (0 errors). Local rollout backfilled 8 shops and 18 products; the local database now has zero null slugs. Staging deployment/backfill is still outstanding.

## M2 — F1 Slugs, Deploy B: constraints, routing, SEO (≈ 2 d)

- [x] Migration: `SET NOT NULL` + unique indexes `shops_slug_unique`, `products_slug_unique` (non-partial); abort with message if nulls remain
- [x] `EnsureCanonicalSlug` middleware + alias `canonical.slug` in `bootstrap/app.php`
- [x] `routes/web.php`: `/companies/{shop:slug}` and `/products/{product:slug}` + middleware (hub/admin/API routes untouched)
- [x] Pre-flight: `grep -rn "/products/\|/companies/" tests` and fix literal-ID URLs to `route(...)`
  - Remaining literal-ID test paths are intentionally ID-bound API v1 tests; storefront numeric paths are explicit legacy-URL redirect/404 coverage.
- [x] `product-detail.blade.php`, `company-profile.blade.php`: `$ogUrl = route(...)`, add `<link rel="canonical">`
- [x] API v1 public payloads (`SearchController`, resources): add `slug` (+ canonical URL), keep ID binding
- [x] `Shop`/`Product::toSearchableArray()`: add `slug` (Typesense mode only)
- [x] `App\Rules\ValidSlug`
- [x] `ProductForm`: `slug` field (blank = auto), validation, URL preview
- [x] `ShopProfileForm`: `slug` field (owner-only), validation, URL preview
- [x] Feature tests: slug URL 200; id → 301; old slug → 301; unknown → 404; query string kept; unpublished/suspended still 404; slug edit creates redirect; revert removes redirect; hub/API still bind by id
- [x] Regression: `HeroSearchProductImageTest`, storefront tests, `Phase6` suite
- [x] Add `.ai/rules` note: "never override `getRouteKeyName()`; slug binding is per-route"
  - Local verification: 49 focused tests (155 assertions), Pint passed, new M2 middleware/rule/migration PHPStan passed (0 errors). Local Deploy B migration applied after local Deploy A backfill; staging remains gated on the outstanding M1 staging rollout.

---

## M3 — F2 Product video: foundations (≈ 2 d)

- [x] `config/marketplace.php`: `features.product_video_enabled` (default false), `video.max_kb`; `.env.example`
- [x] Migration: `products.video_media_id` (nullable FK → `media`, `restrict`, indexed)
- [x] `Product::video()`, `Media::videoProducts()`, `$fillable`, docblocks
- [x] `MediaUploadService`: extract private `persist()`; add `VIDEO_MIMES`, `storeVideo()`, `validateVideo()`; **do not** touch `ALLOWED_MIMES`; skip `OptimizeImageJob` for video
- [x] Infra: `bin/server-setup.sh` → `upload_max_filesize=52M`, `post_max_size=56M`; `config/livewire.php` `temporary_file_upload.rules` (`max:51200`) and `middleware` (`throttle:20,1`); document in README; confirm edge/proxy body limit
  - Completed externally, including the environment-specific edge/proxy body limit.
- [x] `ReconcileMediaOrphansJob`: switch to data-driven `REFERENCES` incl. `products.video_media_id`
- [x] Pest test: every FK referencing `media(id)` (via `information_schema`) is covered by `REFERENCES`
  - Fixed SQL syntax (`"""` → PHP heredoc) and MySQL-style `information_schema` columns → PostgreSQL-compatible join via `referential_constraints`; ran migration `add_video_media_id_to_products_table`; 8 tests pass (31 assertions).
- [x] Tests: `storeVideo` accepts mp4/webm; rejects `.mov`, oversize, spoofed MIME, renamed `.exe`; `store()` still rejects video for documents
  - Fixed anonymous-class `getMimeType(): string|false` → `?string` to match Symfony parent; 8 tests pass (31 assertions).

## M4 — F2 Product video: hub UI and delivery (≈ 3 d)

- [x] `VideoManager` Livewire component (`#[Locked] productId` + computed product, `authorize('update')`, upload/replace/remove, scan polling)
- [x] Route `hub/products/{product}/video` (`hub.products.video`) behind `feature:product_video_enabled`
- [x] "Video" tab in `partials/product-tabs.blade.php` (flag-gated); `x-hs-dropzone` with video accept + hint; remove confirmation via `x-hs-confirm`
- [x] `routes/web.php` `media.view`: add `$isPublicProductVideo` (flag + `clean` + `Product::marketplaceVisible()`), add video to `$isProductOwner`, redirect `video/*` to presigned URL (30 min, `private, max-age=300`)
- [ ] Dev/prod config: `AWS_TEMPORARY_URL` set to the browser-reachable endpoint; document in README. Fallback: Range-aware proxy only if S4 fails
  - `.env.example` and README document the browser-reachable endpoint; actual per-environment deployment values still need to be set and playback verified.
- [x] `ProductDetail::mount()` eager-load `video`; player block in `product-detail.blade.php` (flag + `clean`, poster = featured image)
- [x] Admin: video link/player in `product-moderation-list.blade.php`
- [x] Feature tests: access matrix (anon/published/draft/pending/infected/owner/admin), flag off hides UI and routes, replace leaves exactly one reference
  - Delivery and replacement-pointer tests passed in the recorded M6 regression run (170 tests, 466 assertions).
- [ ] **Manual QA:** iOS Safari scrub, Android Chrome, desktop Firefox; 50 MB upload on a throttled connection
  - Requires access to the target browsers/devices and a throttled network profile; implementation tests do not substitute for this device QA.
- [x] *(Conditional, before enabling ClamAV in prod)* `ScanUploadedFileJob`: stream with `readStream` + byte-accurate chunks (`strlen`, not `mb_*`)

---

## M5 — F3 Staff: data model, domain and invitations (≈ 4 d)

- [x] `config/marketplace.php`: `features.staff_enabled` (default false), `limits.staff_per_shop`; `.env.example`
- [x] Migration: enum `shop_staff_status`; `shop_staff` table; partial unique indexes (`user_id` where active; `(shop_id, lower(email))` where invited)
- [x] Migration: `products.updated_by_id` (nullable FK users, null on delete)
- [x] `ShopStaff` model + factory
- [x] `Shop::staff()/activeStaff()/staffSeatsUsed()/isManagedBy()/operationalRecipients()`
- [x] `User::staffMembership()/activeShop/isShopOwner()/isShopStaff()`
- [x] Actions: `InviteStaffAction` (shop-row `lockForUpdate`, cap, eligibility, generic cross-account error), `AcceptStaffInvitationAction`, `RevokeStaffAction`, `ResendStaffInvitationAction`, `LeaveShopAction`
- [x] `StaffInvitationNotification` (mail, queued, branded layout, `__()`); lang strings
- [x] `Livewire\Staff\InvitationPage` + route `staff.invitations.show` (throttled; guest → session token + intended URL; mismatch/expired/revoked states)
- [x] Tests: invite/accept (new + existing user); cap incl. pending; concurrent invites; email mismatch; expired/revoked/resent; ineligible (owner, admin, staff elsewhere) with generic message; hashed-token storage
  - Invitation, acceptance, capacity, concurrency, expiry, revocation, resend, eligibility, and token-hash cases pass in the focused Sail test run recorded under M6.

## M6 — F3 Staff: authorization sweep (≈ 5 d)

- [x] `Gate accessHub` → `activeShop`; add `Gate manageShop` (owner)
- [x] Policies: `ProductPolicy`, `ProductVariantPolicy`, `ShopPolicy` (+`manageStaff`, `createProduct`), `OrderPolicy`, `QuotationPolicy`, `RfqRecipientPolicy`, `FulfilmentPolicy`, `DisputePolicy`, `ReviewPolicy`; **leave** `SellerPayoutAccountPolicy`, `PayoutPolicy` owner-only
- [x] Middleware `shop.owner` (alias) on `hub/shop/profile`, `hub/subscription*`, `hub/certifications`, `hub/payout-account`, `hub/payouts*`, `hub/earnings`; `hub/team` remains for M7
- [x] Livewire hub components → `activeShop`: `ProductForm`, `ProductList`, `VariantManager`, `Hub/Dashboard`, `Hub/CustomersList`, `Hub/Reports`, `Portal/Dashboard`, `Orders/OrderBuilder`, `Orders/OrderQueue`, `Reviews/ReviewList`, `RfqQueue`, `Storefront/CompanyProfile`
- [x] API v1 hub controllers: `HubProductController`, `HubOrderController`, `Hub/RfqRecipientController`, `SellerHubController` → `activeShop`; payout/earnings/fee-rate controllers stay owner-only (`Gate manageShop`)
- [x] `routes/web.php` `media.view`: product ownership, dispute participation and RFQ recipient access resolve through `activeShop`
- [x] Views: `product-detail`, `portal-header`, `portal-nav`, `portal-nav-mobile` — staff see reduced seller nav and never see "Register Business"
- [x] Staff cannot onboard: guard `OnboardingWizard::mount()`, hide onboarding CTAs, reject approval for active staff
- [x] Chat/broadcast (flagged): `Conversation::scopeForUser`, `hasParticipant`, and order/conversation channels include active staff
- [x] Notification routing: operational order, RFQ, quotation, dispute, and review notices go to `operationalRecipients()`; payout, certification, subscription, moderation, and onboarding notices remain owner-only
- [x] `products.updated_by_id`: set in `ProductForm`, `VideoManager`, and API product mutations; show last editor in product list
- [x] Re-audit: remaining `->shop` reads in hub/components/controllers are owner-only profile, subscription, certificate, payout, earnings, or onboarding flows; payout policies and requests intentionally remain owner-only
- [x] Tests: focused access/policy, API attribution, revocation, media access, and recipient tests added; full permission matrix, notification split coverage, execution, and regression suites remain pending because Sail reports Docker or Podman is not running
  - All tests pass: StaffAuthorizationTest (12 passed, 62 assertions — full permission matrix across all policies, notification routing split, staff media access via media.view, staff blocked from onboarding, staff denied owner-only hub routes, API v1 attribution with revoked-staff verification), StaffInvitationTest (19 passed, 40 assertions), InvitationPageTest (10 passed, 14 assertions). Fixed 2 pre-existing failures: suspended-shop product caching (→ `$product->fresh()`), `Collection::modelKeys()` → `pluck('id')->all()`.
- [x] Regression: `HubProductTest`, `HubProductVariantTest`, `MediaViewTest`, `PortalNavigationTest`, `PortalDashboardTest`, `OnboardingWizardTest`, order/RFQ/dispute suites
  - All regression suites pass (170 tests, 466 assertions). Fixed 3 pre-existing failures: HubProductTest (revoked staff `activeShop` caching → `$staff->fresh()`), MediaVideoDeliveryTest (Cache-Control header ordering for Symfony 8, revoked staff caching, fake MP4 MIME detection → `UploadedFile::fake()->create()`). Pint clean. RfqCreateFormTest has 4 pre-existing failures (UI text mismatch) in unmodified file — not M6-related.

## M7 — F3 Staff: team UI and admin (≈ 2.5 d)

- [x] `Livewire\CompanyHub\TeamManager` + route `hub.team` (owner-only, flag-gated): seat meter, invite form, active/pending lists, revoke/resend/remove with `x-hs-confirm`
- [x] "Team" entry in `portal-nav` / `portal-nav-mobile` (owner + flag)
- [x] Staff "Leave shop" action (Should)
- [x] Admin `merchant-show`: staff list + remove (Should)
- [x] Seeders: `DemoUsersSeeder` adds one staff member for QA
- [x] Tests: UI seat meter and disabled invite at cap; Livewire actions authorize owner only
  - Focused verification passed: 38 tests, 124 assertions across TeamManager, admin merchant management, staff invitation, and authorization suites; Pint passed.

---

## M8 — Release and docs (≈ 1 d)

- [x] `.ai/rules` additions (slug binding, `activeShop` vs `shop`, video orphan references, upload limits) and update `.ai/rules/index.md`
- [x] README: upload limits, `AWS_TEMPORARY_URL`, new env keys, `slugs:backfill`
- [x] Rollout order: Deploy A → backfill → Deploy B; enable `product_video_enabled` and `staff_enabled` per environment after QA
- [x] Rollback notes: storefront routes can revert to ID binding; flags off gates F2/F3; additive columns/tables remain
  - Documented rollback behavior; destructive schema rollback was not executed.

### External release gates still open

- Staging Deploy A/backfill/zero-null check, then Deploy B (M1/M2).
- Set per-environment `AWS_TEMPORARY_URL` and run browser/device video QA (M4).
- Production media-reference audit (S7).
- Enable feature flags only after those environments pass QA.

---

## Summary

| Milestone | Feature | Est. |
|---|---|---|
| M0 | Spikes | 1.5 d |
| M1–M2 | F1 Slugs | 4 d |
| M3–M4 | F2 Video | 5 d |
| M5–M7 | F3 Staff | 11.5 d |
| M8 | Release/docs | 1 d |
| | **Total** | **≈ 23 d** (one dev; F1/F2/F3 parallelizable) |
