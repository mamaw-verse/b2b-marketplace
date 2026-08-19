{{--
    Halal B2B Marketplace — Project Conventions
    These conventions MUST be followed by all agents working in this repository.
--}}

# Project Conventions

## Database & Schema

- All money/price fields use `NUMERIC(12,2)` — never `FLOAT` or `DOUBLE`.
- Primary keys are `BIGINT GENERATED ALWAYS AS IDENTITY` (Laravel `$table->id()`).
- All tables include `created_at` / `updated_at` managed by Eloquent.
- Never hard-delete `shops` or `products` — use soft deletes (`deleted_at` / `SoftDeletes` trait).
- PostgreSQL enums are used for status fields (`onboarding_status`, `business_structure`, `export_readiness`, `shop_status`, `halal_cert_body`, `shop_cert_status`, `listing_status`).
- JSONB columns (`value_chain_positions`, `capabilities`, `attributes`, `quantity_breaks`) are cast to `array` or `AsCollection` in Eloquent models.
- PostGIS `GEOGRAPHY(POINT, 4326)` type is used for geospatial columns (`geo_location`).

## Certificate Expiry Alerts

- Certificate expiry alerts MUST be idempotent per threshold — track `last_alert_threshold_days` on `shop_certificates` to avoid duplicate notifications on repeated job runs.
- Thresholds: 60, 30, 7 days before expiry, and on expiry day.
- Status transitions: `active` → `expiring_soon` → `expired`, driven by the scheduled `ScanCertificateExpiryJob`.

## File Upload Security

- Strict MIME/extension allow-list enforced both client-side and server-side.
- All uploaded files must pass antivirus scan (`ScanUploadedFileJob`) before being marked `clean`.
- Documents blocked from onboarding-checklist completion until scan passes.
- Private documents served via time-limited pre-signed S3/Minio URLs — never public paths.

## Authorization Model

- RBAC via `spatie/laravel-permission` — roles: `admin`, `seller`, `buyer`.
- Row-level ownership enforced via Laravel Policies independent of role (`record->user_id === auth()->id()`).
- Admin document access gated by `document.view_any` permission.

## Search & Indexing

- Only `active` shops and `published` products appear in the Typesense search index.
- Index writes are event-driven (queued jobs: `IndexShopJob`, `IndexProductJob`) — never synchronous with the HTTP request.
- A scheduled safety-net job (`SyncSearchIndexJob`) reconciles stale entries every 5 minutes.

## API Conventions

- All API endpoints versioned under `/api/v1`.
- Auth: Laravel Sanctum (session cookies for browser clients, personal access tokens for external API consumers).
- Pagination: cursor-based (`?cursor=<opaque>&limit=20`).
- Error envelope: `{ "error": { "code": "...", "message": "...", "fields": {...} } }`.
- Admin mutations (approve/reject) wrapped in DB transactions; index sync dispatched after commit, not inside the transaction.

## Phase 1 Constraints

- One company per user account (`shops.user_id UNIQUE`).
- No RFQ, chat, quoting, orders, or payments — Phase 1 is discovery only.
- Certificate renewal workflow is deferred; only expiry tracking/alerting is in scope.
