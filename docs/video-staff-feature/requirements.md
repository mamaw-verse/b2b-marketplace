# Requirements — Slugs, Product Video, Seller Staff

| | |
|---|---|
| **Repo** | `mamaw-verse/b2b-marketplace` @ `e389369` |
| **Stack (per `composer.json`)** | Laravel 13.17, Livewire 4.1, Flux 2.13, Pest 5, spatie/laravel-permission 8.3, PostgreSQL, S3/MinIO |
| **Companion docs** | `design.md` (implementation), `tasks.md` (checklist) |
| **Suggested location** | `.kiro/specs/phase7/` (follows the Phase 1–6 convention) |
| **Status** | Draft for review — decisions in §6 need an owner |

---

## 1. Overview

Three independent, additive features for the seller/storefront experience:

| # | Feature | Summary |
|---|---|---|
| F1 | **URL slugs** | Human-readable URLs for products and stores (`/products/halal-chicken-nuggets`, `/companies/al-barakah-foods`) replacing numeric IDs on the public storefront. |
| F2 | **Product video** | A seller may attach **one** video to each product; it plays on the product detail page. |
| F3 | **Seller staff** | A seller (shop owner) may add up to **2 staff members** who can help manage the shop's catalog without sharing the owner's login. |

### 1.1 Goals

- **G1** Storefront URLs are readable, stable and SEO-friendly; **no existing link breaks**.
- **G2** Sellers can show a product in motion without leaving the platform.
- **G3** Owners can delegate day-to-day work with least privilege, and revoke instantly.
- **G4** All three follow existing conventions: soft deletes, feature flags, policy-based authz, queued side effects, `__()` localization.

### 1.2 Non-goals

- Video transcoding, thumbnails, adaptive streaming/HLS, captions, or video for stores/RFQs/reviews.
- Slugs for categories, or localized (per-language) slugs.
- Custom roles/permission editor for staff; more than one staff tier; a user belonging to several shops.
- Changing admin, hub (`/hub/...`) or API v1 URLs — these stay ID-based.
- Billing per seat. The cap is a config value only (see D5).

---

## 2. Findings in the current code that shape these requirements

These are facts from the repository, not assumptions; the design depends on them.

1. **Storefront routes bind by numeric ID**: `/products/{product}` and `/companies/{shop}` (`routes/web.php`). All 17 in-app links use `route('storefront.product', $model)` / `route('storefront.company', $model)`, so a slug change is centralised. Hub routes (`hub/products/{product}/edit`, etc.) and API v1 also bind by ID and must **not** change.
2. **No slug exists** on `shops` or `products`; nothing generates one. `og:url` is `url()->current()`, and there is no canonical tag or sitemap.
3. **Ownership is hard-wired to one user per shop**: `shops.user_id` has a partial unique index; `User::shop()` is a `HasOne`; `$user->shop` is read in ~47 files (Livewire hub, API v1 hub controllers, 5 policies, nav partials, `AppServiceProvider::accessHub`); policies compare `$user->id === $shop->user_id` (56 ownership comparisons across `Policies`, `Conversation`, `routes/channels.php`, listeners). **Staff cannot be added without touching this layer.**
4. **Notifications go to `$shop->user`** in 18 places (jobs, listeners, actions). Staff would receive nothing unless these are routed deliberately.
5. **Uploads are image/doc-only and small**: `MediaUploadService::ALLOWED_MIMES` is a single shared allow-list (pdf/images/office docs), max 20 MB in the service, but **`bin/server-setup.sh` sets PHP `upload_max_filesize=10M`, `post_max_size=12M`**, and Livewire's temp-upload default rule is `max:12288`. Gallery images are capped at 5 MB. **A video cannot be uploaded today.**
6. **Media is streamed through the app** (`media.view`, `Storage::disk('s3')->response(...)`) because of MinIO presigned-URL host mismatches. No `temporaryUrl()` is used anywhere. Laravel's `Storage::response()` does not appear to implement HTTP `Range`; iOS Safari will not play `<video>` without it (to be verified in a spike).
7. **`media.view` public-visibility rules and `ReconcileMediaOrphansJob` enumerate media-referencing tables by hand.** A new video table that is not added to both will be (a) 403 for anonymous visitors and (b) **deleted after 7 days** by the weekly orphan job.
8. **ClamAV is off by default** (`CLAMAV_ENABLED=false`), but when on, `ScanUploadedFileJob` loads the whole file into memory and measures chunks with `mb_*` functions, which is unsafe for large binary content.
9. **The `seller` Spatie role is only assigned in seeders.** Real access control is "does `$user->shop` exist and is it active" (`Gate::define('accessHub')`). Staff should follow the same pattern (membership row), not a new Spatie role.
10. **No ffmpeg / video tooling** is in `composer.json` or `config/`.

> **Adjacent bug (out of scope, recommend fixing alongside F2):** `ReconcileMediaOrphansJob` checks only 6 tables (`shop_onboarding_documents`, `shop_documents`, `product_documents`, `product_gallery`, `shop_onboarding_certificates`, `shop_certificates`). Migrations show `media_id` is also referenced by `rfq_attachments`, `quotation_attachments`, `message_attachments`, `dispute_media`, `sponsored_content` (incl. `wide_media_id`/`tall_media_id`) and `payment_attempts.receipt_media_id`. Those files appear to be eligible for deletion after 7 days. Worth confirming against production data before the next Sunday 04:00 UTC run.

---

## 3. F1 — URL slugs for products and stores

### 3.1 Functional requirements

| ID | Requirement | Priority |
|---|---|---|
| F1.1 | Every `shops` and `products` row has a non-null `slug`. | Must |
| F1.2 | Slugs are generated automatically on create from `storefront_name` / `name`: lowercase ASCII, hyphen-separated, ≤ 100 chars. | Must |
| F1.3 | Slugs are **globally unique per table** (including soft-deleted rows, so a restored record never collides and a deleted slug is not recycled). Collisions get a numeric suffix (`-2`, `-3`…). | Must |
| F1.4 | A slug must **never be purely numeric** (it would be ambiguous with legacy ID URLs) and must not be in a reserved list. | Must |
| F1.5 | Names that transliterate to an empty string (e.g. some Arabic input) fall back to a generated slug (`product-<random>` / `store-<random>`). Generation passes the app locale to `Str::slug` (Turkish, etc.). | Must |
| F1.6 | Storefront routes resolve by slug: `/products/{slug}`, `/companies/{slug}`. All existing `route(...)` calls emit slugs with no per-call changes. | Must |
| F1.7 | **Legacy URLs keep working**: `/products/123` and `/companies/45` respond **301** to the canonical slug URL. | Must |
| F1.8 | Existing rows are backfilled by a safe, re-runnable migration/command. | Must |
| F1.9 | Renaming a product/store does **not** change its slug automatically. | Must |
| F1.10 | The owner/staff may edit the slug on the product form and the business-profile form, with validation (format, uniqueness, not numeric, not reserved). | Should |
| F1.11 | When a slug is edited, the **old slug 301-redirects** to the new one (`slug_redirects` table). | Should |
| F1.12 | Product/company pages emit `<link rel="canonical">` and `og:url` using the canonical slug URL. | Should |
| F1.13 | Hub, admin and API v1 URLs are unchanged (still ID-based). | Must |

### 3.2 Acceptance criteria

- Creating "Halal Chicken Nuggets" twice yields `halal-chicken-nuggets` and `halal-chicken-nuggets-2`.
- `GET /products/{id}` → `301` → `/products/{slug}`; `GET /products/{slug}` → `200`; unknown value → `404`.
- A soft-deleted product's slug cannot be reused by a new product.
- Shop `ApproveOnboardingAction`, factories, seeders and `ProductForm` produce slugs with **no code changes at the call sites** (model-level generation).
- Product that is unpublished / shop suspended still returns `404` for the slug URL exactly as today (visibility rules unchanged).

### 3.3 Non-functional

- Backfill must not lock the tables for long: chunked, idempotent, runs before the `NOT NULL` + unique index step.
- Lookup by slug is indexed (unique index doubles as lookup index).

---

## 4. F2 — One video per product

### 4.1 Functional requirements

| ID | Requirement | Priority |
|---|---|---|
| F2.1 | A product has **0 or 1** video. Enforced in the database, not just the UI. | Must |
| F2.2 | Seller (owner or staff) uploads, replaces, and removes the video from a product-management screen (new tab beside Gallery/Documents). | Must |
| F2.3 | Accepted formats: **MP4 (H.264/AAC)** and **WebM** only. Rejected by extension **and** sniffed MIME. (MOV is excluded: iPhone footage is often HEVC and does not play in Chrome/Firefox.) | Must |
| F2.4 | Max size: **50 MB** (see D3). Clear validation message with the limit. | Must |
| F2.5 | Replacing a video swaps the reference atomically; the old file becomes an orphan and is deleted by the (fixed) orphan job. | Must |
| F2.6 | The video plays on the public product detail page (`<video controls preload="metadata" playsinline>`), seekable on desktop **and iOS**. | Must |
| F2.7 | Scan status is respected: a video is shown publicly only when `scan_status = 'clean'`; pending/infected show a notice in the hub only. | Must |
| F2.8 | Visibility follows the product: only published, moderated-active products of active shops (and active subscription when enabled) expose the video to the public; otherwise owner/staff/admin only. | Must |
| F2.9 | Admin moderation can see the video in the moderation list. | Should |
| F2.10 | Optional poster frame: use the product's featured image as `poster` (no frame extraction in v1). | Should |
| F2.11 | Feature flag `MARKETPLACE_PRODUCT_VIDEO_ENABLED` (default **false**) gates routes, UI and public rendering. | Must |
| F2.12 | Video is deleted with its product's lifecycle rules (cascade on hard delete; products are soft-deleted so it is retained). | Must |

### 4.2 Acceptance criteria

- Uploading a second video to the same product **replaces** the first (one row remains).
- A 51 MB file, a `.mov`, a renamed `.exe`, and a `.mp4` whose sniffed MIME is not `video/mp4` are all rejected with specific messages.
- Anonymous user can fetch the video of a published product (200/206 with `Accept-Ranges`) and gets 403 for a draft product's video.
- Safari/iOS can scrub the video (verified on a real device or BrowserStack).
- With the flag off, no tab, route, or player renders and existing uploads are untouched.
- The orphan job does **not** delete a media row referenced by a product video.

### 4.3 Out of scope / risks

- No duration limit in v1 (needs ffprobe); the 50 MB cap is the guard. Revisit if ffmpeg is added.
- Bandwidth/storage cost of video — see D3/D4.

---

## 5. F3 — Up to 2 staff members per seller

### 5.1 Roles

| Role | Who | Notes |
|---|---|---|
| **Owner** | `shops.user_id` | Unchanged. Exactly one. |
| **Staff** | A user with an `active` `shop_staff` row | Max **2** per shop (active + unexpired pending invitations). One shop per user, ever (extends the existing "one company per user" rule). |

### 5.2 Functional requirements

| ID | Requirement | Priority |
|---|---|---|
| F3.1 | Owner opens **Hub → Team** (`/hub/team`) to see staff, pending invitations, and remaining seats (`n of 2`). | Must |
| F3.2 | Owner invites a staff member by **email**. An invitation email contains a single-use, expiring (7 days) link. Works whether or not the invitee already has an account. | Must |
| F3.3 | **Cap = 2** counting active staff **plus pending, unexpired invitations**. Enforced server-side under a row lock so concurrent invites cannot exceed it. Configurable (`MARKETPLACE_MAX_STAFF_PER_SHOP`, default 2). | Must |
| F3.4 | Invitee accepts after signing in/registering **with the invited email** (email verified). A different signed-in email is rejected with guidance. | Must |
| F3.5 | Rejected invitees: the email already belongs to an owner of any shop, an admin, or staff of any shop (including this one). | Must |
| F3.6 | Owner can **revoke** an invitation or **remove** a staff member; access ends on the next request. Owner can resend an invitation (new token, old one invalidated). | Must |
| F3.7 | Staff can **leave** the shop voluntarily. | Should |
| F3.8 | Staff **can**: view dashboard; create/edit/publish/archive products; manage variants, gallery, documents, product video; (when their feature flags are on) view/respond to RFQs, orders, messages and review replies. | Must |
| F3.9 | Staff **cannot**: edit business profile / legal identifiers, subscription & billing, payout account / payouts / earnings, certificates & certification applications, manage team, delete/suspend the shop, or start seller onboarding. These return 403/404 and are hidden from nav. | Must |
| F3.10 | Staff cannot become owners: the onboarding wizard and "Register Business" CTA are hidden/blocked for staff. | Must |
| F3.11 | Operational notifications (new RFQ, message, order, review) go to owner **and** staff; financial/legal ones (subscription, payout, certificate, product flagged by admin) go to the **owner only**. | Should |
| F3.12 | Audit: records show **who** performed catalog changes via existing `uploaded_by_id` (media) and a new `updated_by_id` on products. Invitation/removal events are logged with actor + timestamp in `shop_staff`. | Should |
| F3.13 | Storefront "own shop" checks (can't message yourself, own-shop redirect) treat staff as the shop. | Must |
| F3.14 | Staff access requires an active shop; if the shop is suspended or soft-deleted, staff lose hub access exactly like the owner (`accessHub`). | Must |
| F3.15 | Staff membership is enforced by policy on every product/media route, including `media.view` (draft product images) and API v1 hub endpoints. | Must |
| F3.16 | Admin can see a shop's staff on the merchant detail page and remove a staff member. | Should |

### 5.3 Permission matrix

| Capability | Owner | Staff |
|---|:--:|:--:|
| Dashboard (shop stats) | ✔ | ✔ (no billing/payout cards) |
| Products, variants, gallery, documents, **video** | ✔ | ✔ |
| Publish / archive product | ✔ | ✔ *(D6)* |
| Messages, reviews replies, RFQ/orders/quotations *(flagged)* | ✔ | ✔ |
| Business profile (legal & contact) | ✔ | ✘ |
| Certificates / apply for certification | ✔ | ✘ |
| Subscription & payment | ✔ | ✘ |
| Payout account, payouts, earnings *(flagged)* | ✔ | ✘ |
| Team management | ✔ | ✘ |
| Edit slug of **store** | ✔ | ✘ |
| Edit slug of **product** | ✔ | ✔ |

### 5.4 Acceptance criteria

- Owner with 1 active staff + 1 pending invite sees `2 of 2 seats used` and the invite form is disabled; a crafted request is rejected with a validation error.
- Two simultaneous invites with 1 seat left → exactly one succeeds.
- Staff can edit a product of the shop, cannot open `/hub/shop/profile`, `/hub/subscription`, `/hub/team`, and cannot edit another shop's product (403).
- Revoked staff gets 403/redirect on their next request and cannot see draft product images via `media.view`.
- Staff user visiting `/account/onboarding` is redirected with an explanation.
- Re-inviting an expired or revoked email works and issues a new token; old token returns "invalid or expired".

---

## 6. Decisions needed (defaults proposed)

| ID | Question | Proposed default | Alternative |
|---|---|---|---|
| D1 | Slug uniqueness scope | **Global** per table (`/products/{slug}`) — keeps all 17 `route()` calls unchanged | Per-shop (`/companies/{shop}/products/{product}`) — nicer names, changes every product link |
| D2 | Seller-editable slugs + old-slug redirects | Editable, with redirect table (F1.10/11) | Auto-only; ship immutable slugs first |
| D3 | Video cap & upload path | 50 MB through Livewire, raise PHP/Livewire limits (simple) | Direct browser→S3 presigned/multipart upload (scales; needs S3 CORS + confirm endpoint) |
| D4 | Video delivery | Presigned **S3 URL** for video only (native Range, no PHP streaming) | Proxy via app with a Range-aware responder |
| D5 | Staff cap source | Env/config constant (2) | Tie to subscription plan (`subscription_plans.max_staff`) |
| D6 | May staff publish products? | Yes | Draft-only for staff; owner publishes |
| D7 | Staff of a suspended/unsubscribed shop | Same as owner (blocked) | — |

---

## 7. Cross-cutting requirements

- **Security**: invitation tokens stored hashed (SHA-256), compared in constant time; rate-limited accept/resend (`throttle`); video MIME sniffed server-side; no direct public bucket paths.
- **Localization**: all new copy through `__()`; new notification/mail follows the branded mail layout (`resources/views/vendor/mail/`).
- **Feature flags**: F2 behind `product_video_enabled`; F3 behind `staff_enabled` (default **false**) in `config/marketplace.php`, following Phase 6 pattern. F1 is ungated (migration + redirect is safe) but ships in two deploys (see `design.md` §1.5).
- **Backwards compatibility**: additive migrations only; no destructive changes (Phase 6 "disabled, not deleted" principle).
- **Testing**: Pest feature tests for every acceptance criterion; no raw HTTP to external services.
- **Docs**: add `.ai/rules/*` entries for the new gotchas (slug binding, staff vs owner `->shop`, video orphan handling) so agents don't regress them.
