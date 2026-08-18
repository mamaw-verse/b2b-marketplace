# tasks.md
## Execution Plan — Phase 1: Halal Business Directory

| | |
|---|---|
| **Consumes** | `requirements.md`, `design.md` |
| **Audience** | AI Coding Agent (autonomous/semi-autonomous execution) |
| **Execution mode** | Sequential by Epic; tasks within an Epic may parallelize only where marked `[parallel-safe]` |
| **Stack** | PHP/Laravel · Livewire · Tailwind · Redis · PostgreSQL (+PostGIS) · Reverb · Typesense · Minio/S3 · Laravel Cloud |

---

## How to use this document

- Each task has a **stable ID** (`E<epic>.T<task>`), a **status checkbox**, explicit **file paths**, and **acceptance criteria**.
- The agent MUST complete tasks in ID order within an Epic unless marked `[parallel-safe]`, and MUST NOT start an Epic until all of its listed **Depends on** tasks are `[x]`.
- After each task, run the command(s) in **Verify** before marking it `[x]`. If verification fails, fix and re-verify — do not proceed.
- Do not invent schema, routes, or fields not present in `design.md`. If a gap is found, stop and flag it rather than guessing (see §"Escalation" at the end).
- All new Laravel-ecosystem code should be generated with **Laravel Boost's MCP tools/guidelines active** (per design.md §5.5) — use Boost's schema/docs/tinker tools to verify against the real app state rather than assuming.

---

## Epic 0 — Environment & Project Bootstrap

**Goal:** A running Laravel app with all infra dependencies wired, ready for feature work.
**Depends on:** none

- [x] **E0.T1** Scaffold Laravel project
  - Run: `composer create-project laravel/laravel halal-b2b "^12.0"`
  - Verify: `php artisan --version` succeeds inside project root.

- [x] **E0.T2** Install core packages
  - Run:
    ```
    composer require livewire/livewire
    composer require laravel/sanctum
    composer require laravel/reverb
    composer require laravel/scout
    composer require typesense/typesense-php
    composer require spatie/laravel-permission
    composer require league/flysystem-aws-s3-v3 "^3.0"
    composer require laravel/boost --dev
    ```
  - Verify: `composer.json` lists all packages; `composer install` exits 0.

- [x] **E0.T3** Install Tailwind CSS toolchain
  - Run: `npm install -D tailwindcss postcss autoprefixer` then `npx tailwindcss init -p`
  - Edit `tailwind.config.js` to scan `./resources/**/*.blade.php`.
  - Verify: `npm run build` completes without error.

- [x] **E0.T4** Wire up design tokens
  - Create `resources/css/tokens.css` containing the full `:root { --hs-* }` block from the PRD §8 design-system reference.
  - Import `tokens.css` in `resources/css/app.css`.
  - Extend `tailwind.config.js` `theme.extend` to map key tokens (colors, spacing, radius, fontFamily) to the `--hs-*` CSS variables.
  - Verify: a test Blade view using `bg-[var(--hs-green-600)]` renders the correct color in browser dev tools.

- [x] **E0.T5** Install & init Laravel Boost
  - Run: `php artisan boost:install`
  - Create `.ai/guidelines/project-conventions.blade.php` with the project-specific conventions listed in design.md §5.5 (idempotent expiry alerts, `NUMERIC(12,2)` money fields, no hard-deletes on `shops`/`products`).
  - Verify: Boost MCP server tool list is reachable from the configured agent client per `php artisan boost:install` output.

- [x] **E0.T6** Configure environment variables
  - Add to `.env.example`: `DB_CONNECTION=pgsql`, PostGIS-enabled DB host vars, `REDIS_*`, `TYPESENSE_HOST`/`TYPESENSE_API_KEY`, `FILESYSTEM_DISK=s3` + `AWS_*`/Minio equivalents, `REVERB_*`, `SCOUT_DRIVER=typesense`, `DLT_PAYMENTS_*` (placeholders, unused, per design.md §5.3).
  - Verify: `php artisan config:show database.connections.pgsql` reflects `.env` values (in local dev copy).

- [x] **E0.T7** Enable PostgreSQL extensions
  - Create migration `database/migrations/0001_01_01_000000_enable_extensions.php` running `CREATE EXTENSION IF NOT EXISTS postgis;` and `CREATE EXTENSION IF NOT EXISTS pgcrypto;`.
  - Verify: `php artisan migrate` succeeds; `SELECT postgis_version();` returns a version string via `php artisan tinker` (or Boost's DB-query tool).

- [x] **E0.T8** Base auth scaffolding
  - Install Laravel's starter auth (Breeze/Livewire stack) or hand-roll session-based auth per design.md §5.4.
  - Verify: `/register`, `/login`, `/email/verify/{id}/{hash}` routes exist and respond.

---

## Epic 1 — Data Layer: Migrations, Models, Factories

**Goal:** Every table in design.md §2.2 exists via migration, with a matching Eloquent model, relationships, and factory.
**Depends on:** E0

> Build order matters for FK integrity: enums/types → independent tables → dependent tables → join tables.

- [x] **E1.T1** Create Postgres enum types migration
  - File: `database/migrations/xxxx_create_enum_types.php`
  - Create types: `onboarding_status`, `business_structure`, `export_readiness`, `shop_status`, `halal_cert_body`, `shop_cert_status`, `listing_status` exactly as defined in design.md §2.2.
  - Verify: `\dT` in `psql` (or Boost DB-schema tool) lists all 7 enum types.

- [x] **E1.T2** `users`, `roles`, `permissions`, `role_has_permissions`, `model_has_roles` migrations `[parallel-safe]`
  - Files under `database/migrations/`.
  - Note: `users` table extends Laravel's default with `phone`, `photo_path` columns per design.md §2.2.
  - Verify: `php artisan migrate:status` shows all 5 migrations `Ran`.

- [x] **E1.T3** `categories` migration `[parallel-safe]`
  - Include unique `slug` index.
  - Verify: migration runs; `categories_slug_unique` constraint exists.

- [x] **E1.T4** `media` migration `[parallel-safe]`
  - FK `uploaded_by_id` → `users.id` (`ON DELETE RESTRICT`).
  - Verify: FK constraint present via `\d media`.

- [x] **E1.T5** `shop_onboardings` migration
  - Depends on: E1.T1, E1.T2
  - Include `geo_location GEOGRAPHY(POINT,4326)` column and GIST index, exactly per design.md §2.2 DDL. Note the circular FK to `shops(id)` — defer that FK constraint to E1.T7 (add via `ALTER TABLE` after `shops` exists), or create `shops` table first without the reverse dependency; use whichever ordering Laravel's migration FK resolution supports cleanly.
  - Verify: table + indexes exist; geo column type confirmed via `\d shop_onboardings`.

- [x] **E1.T6** `shop_onboarding_certificates` + `shop_onboarding_documents` migrations
  - Depends on: E1.T5, E1.T4
  - Verify: FKs to `shop_onboardings`, `media` present; `idx_onboarding_certs_expiry` index exists.

- [x] **E1.T7** `shops` migration
  - Depends on: E1.T2
  - Include `geo_location` + GIST index, `user_id UNIQUE` FK, `deleted_at` for soft deletes.
  - After creation, add the deferred FK from `shop_onboardings.shop_id` → `shops.id` if not already applied in E1.T5.
  - Verify: `shops.user_id` has a unique constraint; soft-delete column present.

- [x] **E1.T8** `shop_certificates`, `shop_categories`, `shop_documents` migrations `[parallel-safe]`
  - Depends on: E1.T7, E1.T3, E1.T4
  - Verify: composite PKs on `shop_categories`/`shop_documents` match design.md §2.2.

- [x] **E1.T9** `products`, `product_categories` migrations
  - Depends on: E1.T7, E1.T3
  - Include `deleted_at` on `products`.
  - Verify: FK `products.shop_id` → `shops.id ON DELETE CASCADE`.

- [x] **E1.T10** `product_variants` migration
  - Depends on: E1.T9
  - Include `UNIQUE(product_id, sku)`, JSONB `attributes` and `quantity_breaks` columns, `currency CHAR(3) DEFAULT 'PHP'`, `price NUMERIC(12,2)`.
  - Verify: unique constraint + JSONB column types confirmed.

- [x] **E1.T11** `product_documents`, `product_gallery` migrations `[parallel-safe]`
  - Depends on: E1.T9, E1.T4
  - Verify: `product_gallery` has `sort_order SMALLINT DEFAULT 0` and supporting index.

- [x] **E1.T12** Run full migration suite end-to-end on a clean DB
  - Run: `php artisan migrate:fresh`
  - Verify: exits 0, all 20 tables from design.md §2.2 present (`\dt`).

- [x] **E1.T13** Eloquent models `[parallel-safe]`
  - Create in `app/Models/`: `User`, `Role`, `Permission`, `Category`, `Media`, `ShopOnboarding`, `ShopOnboardingCertificate`, `ShopOnboardingDocument` (or pivot), `Shop`, `ShopCertificate`, `Product`, `ProductVariant`, `ProductDocument`, `ProductGallery`.
  - Define all relationships per design.md §2.3 relationship table (hasOne/hasMany/belongsToMany with correct pivot tables, `SoftDeletes` trait on `Shop`/`Product`).
  - Cast `value_chain_positions`, `capabilities`, `attributes`, `quantity_breaks` as `array`/`AsCollection`; cast `geo_location` appropriately (custom cast or raw expression, since Eloquent has no native PostGIS type — document the cast approach in the model docblock).
  - Verify: `php artisan tinker` → `Shop::factory()->make()` instantiates without error; relationship methods resolve (`Shop::make()->products()` returns a `HasMany` instance).

- [x] **E1.T14** Model factories & seeders `[parallel-safe]`
  - Create factories for `User`, `Category`, `Shop`, `ShopCertificate`, `Product`, `ProductVariant`.
  - Create `RolePermissionSeeder` seeding `admin`/`seller`/`buyer` roles with the permissions listed in design.md §5.4.
  - Verify: `php artisan db:seed` completes; `Role::count() === 3`.

---

## Epic 2 — Authorization Layer (RBAC + Policies)

**Goal:** Role/permission enforcement and row-level ownership per design.md §5.4.
**Depends on:** E1

- [x] **E2.T1** Configure `spatie/laravel-permission` on `User` model (`HasRoles` trait) and publish/adapt its migrations to match the `roles`/`permissions`/pivot schema already created in E1.T2 (reconcile column names if the package's default migration conflicts with design.md's schema — prefer design.md's schema, adjust package config accordingly).
  - Verify: `$user->assignRole('seller')` then `$user->hasRole('seller')` returns `true` in tinker.

- [x] **E2.T2** Create Policies: `ShopOnboardingPolicy`, `ShopPolicy`, `ProductPolicy`, `ProductVariantPolicy`
  - Path: `app/Policies/`
  - Each policy's `view`/`update`/`delete` methods check `record->user_id === $user->id` (or `record->shop->user_id === $user->id` for nested resources), independent of role, per design.md §5.4.
  - Register in `AuthServiceProvider`.
  - Verify: Feature test — Seller A cannot `update` Seller B's `Product` (assert `403`).

- [x] **E2.T3** Create Gate definitions for permission-based actions (`onboarding.review`, `category.manage`, `document.view_any`)
  - Verify: a user with `admin` role passes `Gate::allows('onboarding.review')`; a `seller`-only user does not.

- [x] **E2.T4** Middleware wiring
  - Apply `auth`, `verified` middleware to onboarding/company-hub routes; apply a `role:admin` (or permission-based) middleware group to `/admin/*` routes.
  - Verify: unauthenticated request to `/hub/products` redirects to login; authenticated non-admin request to `/admin/onboardings` returns `403`.

---

## Epic 3 — Object Storage & Media Pipeline

**Goal:** Working upload → store → scan → attach flow, reusable across onboarding docs, certs, product docs, and gallery.
**Depends on:** E1

- [x] **E3.T1** Configure `filesystems.php` `s3` disk for Minio/S3 per `.env` vars from E0.T6.
  - Verify: `Storage::disk('s3')->put('test.txt','ok')` then `Storage::disk('s3')->exists('test.txt')` returns `true`.

- [x] **E3.T2** Build `MediaUploadService` (`app/Services/MediaUploadService.php`)
  - Method `store(UploadedFile $file, int $uploaderId, string $description): Media` — validates MIME/extension allow-list and max size (per design.md §5.4), stores to S3, creates `media` row.
  - Verify: unit test — rejects `.exe` upload with a `422`-mapped exception; accepts `.pdf`/`.jpg`/`.png` under size limit.

- [x] **E3.T3** `ScanUploadedFileJob` (queued)
  - Path: `app/Jobs/ScanUploadedFileJob.php`
  - Integrate ClamAV (or equivalent) scan; on fail, mark the `media` row (add a `scan_status` enum column via a small follow-up migration if not already present: `pending`/`clean`/`infected`) and prevent it counting toward onboarding-checklist completion until `clean`.
  - Verify: job processes a benign test file → `scan_status = clean`; a known EICAR test file → `scan_status = infected` and is excluded from checklist completeness checks.

- [x] **E3.T4** Pre-signed URL generation for private document viewing
  - Method on `MediaUploadService` (or dedicated `MediaAccessService`): `signedUrl(Media $media, int $minutes = 15): string`.
  - Verify: generated URL is time-limited and returns the file when fetched within the window, `403`/expired after.

---

## Epic 4 — Company Onboarding (Company Hub)

**Goal:** Full 5-step wizard, backed by API endpoints from design.md §3.3, writing to `shop_onboardings` + child tables.
**Depends on:** E1, E2, E3

- [x] **E4.T1** `OnboardingController` / API routes (`routes/api.php`) implementing design.md §3.3 endpoints: `GET /onboarding`, `PUT /onboarding/step/{n}`, `POST /onboarding/certificates`, `DELETE /onboarding/certificates/{id}`, `POST /onboarding/documents`, `POST /onboarding/submit`.
  - Verify: `php artisan route:list` shows all 6 routes under `/api/v1`.

- [x] **E4.T2** `FormRequest` validation classes per step (`Step1Request` … `Step5Request`), matching field lists in design.md §2.2 and PRD §4.2 exactly (including enum value validation for `business_structure`, `export_readiness_status`, `halal_cert_body`).
  - Verify: submitting an invalid `business_structure` value returns `422` with `VALIDATION_ERROR` envelope per design.md §3.7.

- [x] **E4.T3** Geocoding integration on Step 1 save
  - Service: `app/Services/GeocodingService.php` — calls the configured geocoding provider, writes `geo_location`.
  - Must not block/fail the save if geocoding errors (log + continue); queue a retry job (`RetryGeocodeJob`) on failure.
  - Verify: valid PH address → `geo_location` populated; simulated API failure → onboarding step still saves, retry job is dispatched.

- [x] **E4.T4** `POST /onboarding/submit` finalization logic
  - Validates all 5 steps complete + ≥1 certificate + all checklist documents uploaded and `scan_status = clean`; sets `status = pending`, `submitted_at = now()`.
  - Dispatches `OnboardingSubmittedNotification` job (design.md §5.2).
  - Verify: incomplete onboarding → `422` listing missing steps (matches design.md §3.3 example response); complete onboarding → `200`, status `pending`, notification job pushed to queue (assert via `Queue::fake()`).

- [x] **E4.T5** Livewire component: `onboarding-wizard.blade.php` + 5 step sub-components (design.md §4.2)
  - Stepper UI, `wire:model.blur` per field, step-scoped `rules()`, progress persisted after every step via E4.T1 endpoints or direct Eloquent calls from the component.
  - Step 3 renders a repeatable certificate sub-form (add/remove rows) per design.md §4.3.
  - Step 5 renders checklist state (required vs. uploaded vs. scan-pending vs. clean).
  - Verify: Dusk/Livewire test — complete all 5 steps in sequence, submit, assert `shop_onboardings.status = pending` in DB.

- [x] **E4.T6** Onboarding-status realtime notification (Reverb)
  - Private channel `user.{id}`; Livewire `#[On]` listener updates UI on `OnboardingDecisionNotification` broadcast (used later by Epic 5).
  - Verify: broadcasting a test event to the channel updates the connected Livewire component's status banner without page reload.

---

## Epic 5 — Admin Review Console

**Goal:** Queue, detail view, approve/revise/reject flow, category management.
**Depends on:** E4

- [x] **E5.T1** `AdminOnboardingController` implementing design.md §3.4: `GET /admin/onboardings`, `GET /admin/onboardings/{id}`, `POST .../approve`, `POST .../request-revision`, `POST .../reject`.
  - Gate all actions behind `onboarding.review` permission (Epic 2).
  - Verify: non-admin request → `403`; admin request to list `?status=pending` → `200` with correctly filtered results.

- [x] **E5.T2** Approval transaction logic (`ApproveOnboardingAction` in `app/Actions/`)
  - Within a DB transaction: create `shops` row from `shop_onboardings` fields, copy `shop_onboarding_certificates` → `shop_certificates` (`is_verified = true`), copy `shop_onboarding_documents` → `shop_documents` (`is_verified = true`), update `shop_onboardings.status = approved`, `shop_id`, `reviewed_at`, `reviewed_by_id`.
  - After commit (not inside the transaction, per design.md §3.7), dispatch `IndexShopJob` and `OnboardingDecisionNotification`.
  - Verify: integration test — approve a `pending` onboarding → `shops` row exists with matching data, `shop_certificates`/`shop_documents` rows copied with `is_verified = true`, `IndexShopJob` dispatched (`Queue::fake()->assertPushed`).

- [x] **E5.T3** `request-revision` / `reject` actions
  - Require non-empty `notes` field (`422` if missing); update `status` + `admin_notes`, dispatch `OnboardingDecisionNotification`.
  - Verify: missing `notes` → `422`; valid request → status updates correctly, seller receives notification (queued).

- [x] **E5.T4** Livewire components: `onboarding-queue.blade.php`, `onboarding-review.blade.php`, category `Approve/Revise/Reject` modal (design.md §4.2–4.3)
  - Queue table with status-filter tabs; detail view renders all onboarding fields + certs + documents (via pre-signed URLs from E3.T4) + `document-viewer.blade.php` modal for preview.
  - Verify: manual/Dusk walkthrough — admin opens a pending item, views uploaded cert PDF inline, approves, item disappears from `pending` tab and appears under `approved`.

- [x] **E5.T5** Category CRUD: `AdminCategoryController` (`GET/POST /admin/categories`, `PUT/DELETE /admin/categories/{id}`) + `category-manager.blade.php` (Table + Modal Form)
  - Auto-generate `slug` from `name` on create; validate uniqueness.
  - Verify: creating "Bakery Ingredients" produces slug `bakery-ingredients`; duplicate name → `422` (unique constraint violation mapped to validation error).

---

## Epic 6 — Product Catalog (Company Hub)

**Goal:** Product/variant CRUD, documents, gallery — gated to `active` shops only.
**Depends on:** E2, E3, E5 (shop must be able to reach `active` status)

- [x] **E6.T1** `HubProductController` implementing design.md §3.5 endpoints (list, create, update, publish, variants CRUD, gallery upload, documents upload).
  - Enforce via `ShopPolicy`/`ProductPolicy`: only the owning seller of an `active` shop may create/manage products.
  - Verify: seller with a non-`active` shop → `403` on `POST /hub/products`.

- [x] **E6.T2** Product publish validation
  - `POST /hub/products/{id}/publish` requires ≥1 `product_variants` row with `status = published`; else `422`.
  - Verify: product with zero published variants → `422`; add a published variant → publish succeeds, dispatches `IndexProductJob`.

- [x] **E6.T3** Variant management logic
  - `POST/PUT` variant endpoints validate `UNIQUE(product_id, sku)`, `quantity_breaks` JSON shape (`[{min_qty, price}]`), `price`/`minimum_order_quantity` positivity.
  - Verify: duplicate SKU within the same product → `422` (unique constraint); malformed `quantity_breaks` → `422`.

- [x] **E6.T4** Livewire components: `product-list.blade.php`, `product-form.blade.php`, `variant-manager.blade.php` (inline-editable table), `gallery-manager.blade.php` (drag-sort), `document-manager.blade.php` (design.md §4.2–4.3)
  - Gallery drag-sort persists `sort_order` on drop via a Livewire action.
  - Verify: manual/Dusk walkthrough — create product, add 2 variants, upload 3 gallery images, reorder, publish; assert final DB state matches actions taken.

---

## Epic 7 — Search & Discovery (Typesense + Storefront)

**Goal:** NLS + category search across companies/products, public profile pages.
**Depends on:** E5 (shops can become `active`), E6 (products can become `published`)

- [x] **E7.T1** Configure Laravel Scout with the `typesense` driver; define `companies` and `products` Typesense collection schemas (searchable fields: name, storefront_name, categories, certifying body, value-chain tags, capability tags, SKU attributes — per design.md §7.3 of the design doc).
  - Verify: `php artisan scout:sync-index-settings` succeeds; Typesense admin/API confirms both collections exist with expected fields.

- [x] **E7.T2** Implement `Searchable` trait + `toSearchableArray()` on `Shop` and `Product` models; ensure only `active` shops / `published` products are ever indexed (exclude others in `toSearchableArray()`/`shouldBeSearchable()`).
  - Verify: creating an `inactive` shop does not appear in a Typesense query; flipping to `active` (via approval flow) makes it appear (after `IndexShopJob` runs).

- [x] **E7.T3** `IndexShopJob`, `IndexProductJob` (design.md §5.2) — queued upsert/remove into respective Typesense collections.
  - Verify: dispatch each job manually for a fixture record → confirm document appears/disappears in Typesense via direct API query.

- [x] **E7.T4** `SyncSearchIndexJob` scheduled safety-net (design.md §5.1) — reconciles stale index entries every 5 minutes.
  - Register in `app/Console/Kernel.php` (or `routes/console.php` scheduling in Laravel 12).
  - Verify: manually update a `shops` row's `updated_at` without triggering `IndexShopJob`, confirm the scheduled job (run manually via `php artisan schedule:run` in test) catches and re-syncs it.

- [x] **E7.T5** `SearchController` implementing design.md §3.6: `GET /search/companies`, `GET /search/products`, `GET /companies/{slug}`, `GET /products/{id}`, `GET /categories`.
  - Support query params: `q`, `category`, `province`, `lat`/`lng`/`radius_km` (geo filter via Typesense geo query, backed by the `geo_location` PostGIS data captured in Epic 4/5).
  - Verify: response shape matches design.md §3.6 example exactly (`data[]`, `meta.found`, `meta.search_time_ms`, `meta.next_cursor`).

- [x] **E7.T6** Storefront Livewire components: `hero-search.blade.php`, `company-directory.blade.php`, `company-profile.blade.php`, `product-directory.blade.php`, `product-detail.blade.php`, `category-nav.blade.php` (design.md §4.2–4.3)
  - Search input debounced `wire:model.live.debounce.300ms`; results rendered via Card components using the design tokens from Epic 0.
  - Verify: typing a product name in the hero search widget returns matching results within the expected debounce window; category sidebar filters correctly.

---

## Epic 8 — Background Jobs & Notifications (remaining)

**Goal:** All scheduled/event jobs from design.md §5.1–5.2 not already covered.
**Depends on:** E1, E4, E5

- [x] **E8.T1** `ScanCertificateExpiryJob` (scheduled daily)
  - Implements the threshold-crossing logic and idempotency (`last_alert_sent_at` per threshold) exactly as described in design.md §5.1. Add any needed columns via a small migration (e.g., `shop_certificates.last_alert_threshold_days`).
  - Verify: unit test with fixture certificates at 65/45/25/5/-1 days from expiry → correct `status` transitions and exactly one notification per threshold crossed (no duplicates on repeated job runs same day).

- [x] **E8.T2** `PruneStaleOnboardingDraftsJob` (weekly) and `ReconcileMediaOrphansJob` (weekly)
  - Verify: fixture data — a draft onboarding untouched for >N days is flagged/notified; an orphaned `media` row past the grace period is deleted from both DB and S3.

- [x] **E8.T3** Register all scheduled jobs with correct cadence in the scheduler.
  - Verify: `php artisan schedule:list` shows all jobs from design.md §5.1 with correct frequencies.

---

## Epic 9 — Testing, QA & Hardening

**Goal:** Automated coverage of the critical paths + non-functional requirements from PRD §9 / design.md §5.4.
**Depends on:** E1–E8

- [x] **E9.T1** Feature test suite covering full onboarding → approval → publish → search-discoverable pipeline end-to-end (single integration test walking all epics).
  - Verify: `php artisan test --filter=OnboardingToDiscoveryPipelineTest` passes.

- [x] **E9.T2** Authorization test matrix — for every route in Epics 4–7, assert correct `200`/`403`/`401` for each role (`admin`, `seller`-owner, `seller`-non-owner, `buyer`/guest).
  - Verify: full matrix passes; no route allows cross-tenant mutation.

- [x] **E9.T3** Rate limiting verification on `login`, `register`, `search` endpoints (design.md §5.4).
  - Verify: exceeding configured threshold returns `429 RATE_LIMITED` per the error envelope in design.md §3.1.

- [x] **E9.T4** File upload security tests — oversized file, disallowed MIME, EICAR test file — for onboarding documents, certs, and product documents/gallery.
  - Verify: all three cases correctly rejected/quarantined per Epic 3 behavior.

- [x] **E9.T5** Soft-delete verification — deleting a `Shop`/`Product` does not hard-delete DB rows or break FK integrity for historical `product_variants`/`shop_certificates`.
  - Verify: `Shop::find($id)->delete()` sets `deleted_at`, row still queryable via `withTrashed()`, no orphaned children.

---

## Epic 10 — Deployment (Laravel Cloud)

**Goal:** Phase 1 live on the target infrastructure per design.md §1.3.
**Depends on:** E9

- [ ] **E10.T1** Provision Laravel Cloud environment: app + worker processes, managed PostgreSQL (PostGIS enabled), managed Redis.
  - Verify: `php artisan about` on the deployed environment shows correct DB/cache/queue drivers connected.

- [ ] **E10.T2** Provision/connect Typesense (Cloud or self-hosted) and Minio/S3, wire production `.env`.
  - Verify: production health check endpoint confirms connectivity to Typesense and S3 (add a lightweight `/up`-style check if not already present via Laravel's default health route).

- [ ] **E10.T3** Deploy Reverb as its own long-lived worker process; confirm WebSocket upgrade works through the load balancer.
  - Verify: a browser client receives a real-time broadcast (e.g., onboarding-decision notification) in production.

- [ ] **E10.T4** Run production migrations + seeders (roles/permissions, initial category taxonomy).
  - Verify: `php artisan migrate:status` all `Ran` in production; `Role::count() === 3` in production DB.

- [ ] **E10.T5** Configure scheduler (cron) on Laravel Cloud to trigger `schedule:run` every minute.
  - Verify: `ScanCertificateExpiryJob` and other scheduled jobs from Epic 8 execute automatically over a 24h observation window in staging.

---

## Escalation

If, while executing any task, the agent encounters:
- A field, endpoint, or table **not defined** in `design.md` or the PRD,
- A **conflict** between `design.md` and the PRD (e.g., a field present in one but not the other),
- An **ambiguous ordering constraint** (e.g., the `shops` ↔ `shop_onboardings` circular FK in E1.T5/E1.T7),

the agent should **stop, log the specific conflict with file/line references, and flag it for human review** rather than inferring a resolution — per the PRD §11 "Open Questions/Risks," several such conflicts are already known and tracked; new ones should be handled the same way, not silently patched.