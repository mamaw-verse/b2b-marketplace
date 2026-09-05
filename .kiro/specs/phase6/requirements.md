# Product Requirements Document
## Phase 6 — Subscription Model & Lead-Generation Pivot

| | |
|---|---|
| **Companion docs** | `Phase 1–4 requirements.md / design.md`, `Phase 5 progress-report.md` (refactor baseline) |
| **Phase** | 6 — Business Model Change |
| **Doc status** | Draft for review |
| **Owner** | Product / Engineering |
| **Last updated** | 2026-09-04 |

---

## 1. Overview

### 1.1 Product context

Phases 1–5 built a full transactional B2B marketplace: verified directory (P1) → RFQ/quotation negotiation with buyer–seller chat (P2) → orders, escrow payments, fulfilment, disputes (P3) → payouts, reviews, analytics (P4) → codebase hardening (P5).

Phase 6 is a **business-model pivot**, not an additive feature phase. The platform moves from *"transact on-platform"* to a **subscription-funded lead-generation directory**: merchants pay a **yearly subscription** to be discoverable and to list products; buyers use RFQs purely to **express interest and request quotes**, routed through **admin** rather than directly negotiated with sellers; the platform's revenue and control surface shifts to **subscriptions + sponsored content (ads)**, both newly modeled entities.

Concretely, this phase:
- Introduces **subscriptions** as the gate for product listing (replacing "shop approved" as the sole gate).
- Introduces **sponsored content** (multi-tier ads) as an admin-managed monetization surface.
- Moves **RFQ ownership from seller to admin** — sellers no longer receive, review, or quote RFQs directly.
- Decouples **halal certification** from onboarding into its own optional, post-onboarding application flow, and simplifies its fields.
- Simplifies onboarding document capture and adds free-text description fields.
- Adds **country flags**, **localization**, and **more country portals** to the storefront.
- Adds a **PH-only free 6-month subscription trial**.
- **Disables — without deleting — every Phase 2–4 capability that depended on the old transact-on-platform model**: quotations, orders, escrow payments, fulfilment, disputes, and payouts. Schema, code, and data are preserved behind feature flags so the transactional model can be re-enabled in a future phase without a rebuild.
- **Retains buyer–seller chat** — unlike the other Phase 2–4 capabilities, chat stays enabled in Phase 6, decoupled from the RFQ pipeline (see §4.5).

### 1.2 Problem statement

Running full payment-escrow-fulfilment-dispute-payout machinery for every transaction has proven heavier than the business needs right now: sellers want **visibility and qualified leads**, not necessarily an in-platform checkout, and the platform wants a **predictable subscription revenue line** plus an **ads line**, rather than depending on transaction take-rate. RFQs handled peer-to-peer between buyer and seller also removed the platform from the loop entirely once a thread was open, giving admin no visibility or control over deal quality — Phase 6 puts admin back in the loop as the RFQ router/manager. At the same time, the halal-certification requirement embedded in onboarding was blocking merchants who aren't yet certified from onboarding at all; decoupling it lets any merchant onboard, subscribe, and list, while certification becomes an optional trust signal applied for separately.

### 1.3 Goals

Tagged `G1`–`G12` so §3/§4 can trace back to them.

- **G1 — Subscriptions**: approved merchants can subscribe to a **yearly plan** with payment; product listing becomes subscription-gated.
- **G2 — PH free trial**: Philippine merchants get a **free 6-month trial** in place of/before the paid yearly plan.
- **G3 — Decoupled certification**: certification becomes an **optional**, post-onboarding **"Apply for Certification"** flow with a simplified 2-field submission (expiry date + file).
- **G4 — Simplified onboarding**: onboarding keeps only **business permit** + **other supporting document** uploads, adds free-text **company description** and **"describe the products you sell"** fields, and drops the mandatory certification step.
- **G5 — Admin-owned RFQs**: RFQ intake, triage, and management move entirely to admin; sellers lose RFQ visibility/actions.
- **G6 — Sponsored content**: admin can create and publish **multi-tier sponsored content/ads** across storefront placements.
- **G7 — Admin merchant management**: admin gets a dedicated merchant management surface (beyond the onboarding approval queue) — view, search, suspend/reactivate merchants.
- **G8 — Admin product moderation**: admin can moderate (approve/flag/suspend) individual product listings independent of shop-level status.
- **G9 — Admin subscription tracking**: admin can see and manage the full subscription book (active, trialing, expiring, lapsed, by plan/country).
- **G10 — Localization & flags**: storefronts display a **country flag**; the platform supports **localization** (multi-locale UI + content).
- **G11 — Expanded country portals**: homepage country-portal section adds **Malaysia, Kingdom of Saudi Arabia, Brunei, Indonesia, Turkey** alongside the existing Philippines portal.
- **G12 — Preserve, don't delete**: every Phase 2–4 transactional capability that *is* turned off (all except chat, which is retained — see §5) is **feature-flagged off**, with schema, models, and code left intact for a possible future re-enablement.

### 1.4 Non-goals (out of scope for Phase 6)

- Deleting or migrating away from any existing orders/payments/disputes/payouts/reviews/quotations schema or code — these are **disabled, not removed** (see §5).
- Re-architecting the RFQ/quotation *data model* — Phase 6 changes **who** acts on an RFQ (admin instead of seller) and **retires quoting**, but reuses the existing `rfqs` table/snapshot pattern from Phase 2 (see §6).
- Rebuilding chat as an admin-facing coordination tool — any admin↔buyer or admin↔seller communication needed to action an RFQ happens inside the **admin RFQ management** screen (internal notes), not through chat. Chat itself remains a **retained, enabled** direct buyer↔seller feature (see §4.5) — it's simply no longer auto-triggered by RFQ activity.
- A self-serve seller-facing subscription downgrade/cancel-and-refund flow beyond what's specified in §4.3 — refund/proration policy is flagged as an open question (§11).
- Multi-currency billing — yearly plan pricing is defined per the merchant's country grouping (see §4.3), not a full multi-currency payment engine.
- Full translation of all historic Phase 1–5 UI copy on day one of localization — Phase 6 ships the **localization infrastructure** and the highest-traffic surfaces (storefront, onboarding, subscription, homepage); backfilling every screen's translation strings is iterative (see §11).

### 1.5 Disabled-not-deleted principle

Phase 5 already established the pattern this phase relies on: `config('marketplace.features.payouts_enabled')`-style flags gating functionality without dropping tables (`phase5/progress-report.md`, Task 1.1). Phase 6 formalizes and extends this into a full **feature-flag registry** (`config/marketplace.php` → `features` array) covering every capability listed in §5. Rules of engagement:

1. **No destructive migrations.** No `DROP TABLE`, `DROP COLUMN`, or hard-deletes of Phase 2–4 data as part of Phase 6.
2. **Routes/policies/UI are gated, not deleted.** Disabled feature routes return `404`/redirect via middleware checking the flag; Livewire components and nav entries are conditionally rendered; the underlying classes/files remain in the codebase.
3. **Flags default OFF** for the newly-disabled capabilities in Phase 6's shipped config, and are individually toggleable per environment for QA/rollback.
4. **Existing data stays queryable** by admin (read-only, unless a flag is re-enabled) for historical/audit purposes — e.g., an admin can still look up a Phase 3 order placed before the pivot.
5. **Re-enablement is a config change, not a rebuild** — flipping a flag back on should restore prior behavior, modulo any Phase 6 schema additions that need backfilling (documented per-feature in `design.md`).

### 1.6 Success metrics

- Subscription conversion rate: approved merchants → paid/trialing subscribers, within X days of approval.
- % of PH merchants on the free trial who convert to paid at trial end.
- Subscription renewal rate (yearly).
- RFQ → admin first-action time (median), now that admin is the sole actor.
- Certification application submission rate among subscribed merchants (adoption of the now-optional flow).
- Sponsored content: impressions/clicks per tier, and tier upsell rate.
- Localization coverage: % of in-scope screens with complete translations per supported locale.
- Country-portal engagement: storefront sessions by portal/country, pre- vs. post- expansion.

---

## 2. Users & Portals

| Portal | User | Phase 6 changes |
|---|---|---|
| **Admin** | Internal ops/reviewer | **New**: certification application queue, subscription tracking, merchant management, product moderation, sponsored content composer, RFQ management (received/managed by admin). **Retained, now dormant behind flags**: orders, disputes, payouts, review moderation. |
| **Company Hub / Dashboard** (merchant/seller — same entity) | Merchant | **New**: "Apply for Certification" page (post-onboarding), Subscription page (plan selection + payment + status). **Changed**: onboarding wizard fields (§4.1); product listing now also gated on subscription status (§4.4). **Retained**: direct buyer-seller chat/inbox (§4.5), now decoupled from RFQs. **Removed from view (flagged off)**: RFQs Received queue, quotation builder, order management, disputes, payouts/earnings. |
| **User / Buyer Account** | Buyer | **Retained**: send RFQ, view/manage own sent RFQs, direct chat/inbox with sellers (§4.5). **Removed from view (flagged off)**: quotations on an RFQ (no seller quoting), order history/payment, disputes, reviews. |
| **Sitefront (Storefront)** | Public / buyer | **New**: country flag on each storefront/company page; localized UI (locale switcher); expanded homepage country-portal section (6 countries); sponsored content placements. **Changed**: "Request Quote" still present, but no longer auto-opens a chat thread with the seller — chat is initiated separately via a "Message this company" entry point on the company page. |

### 2.1 Roles & permissions

No new role types. New admin-only permissions are added to the existing roles & permissions layer (Phase 1 §2.1): `subscription.manage`, `certification.review`, `sponsored_content.manage`, `rfq.manage` (superseding the seller-side RFQ permissions, which are retained in the permission table but no longer granted to the `seller` role by default), `merchant.manage`, `product.moderate`.

---

## 3. Functional Requirements

Numbered per the updated brief; each row states whether it's **new**, **changed**, or **retained** relative to Phases 1–5.

| # | Requirement | Status |
|---|---|---|
| FR-1 | Merchant can **onboard** | Changed (§4.1) |
| FR-2 | Merchant can **apply for certification** | New, decoupled from onboarding (§4.2) |
| FR-3 | **Approved** merchant can **subscribe to a yearly plan** (with payment) | New (§4.3) |
| FR-4 | **Subscribed** merchant can **list products** | Changed — gate moves from "approved shop" to "approved + subscribed" (§4.4) |
| FR-5 | Buyer can **send an RFQ** | Retained, entry point unchanged (§4.5) |
| FR-6 | Buyer can **manage sent RFQs** | Retained (§4.5) |
| FR-7 | Admin can **review and approve/reject onboarding** | Retained (Phase 1 §4.3, unchanged) |
| FR-8 | Admin can **view certificate application requests** | New (§4.2) |
| FR-9 | Admin can **track subscriptions** | New (§4.3) |
| FR-10 | Admin can **manage merchants** | New, dedicated surface (§4.6) |
| FR-11 | Admin can **moderate products** | New (§4.7) |
| FR-12 | Admin can **manage RFQs** (**not seller anymore**) | Changed — ownership transfer from seller to admin (§4.5) |
| FR-13 | Admin can **post sponsored content** (ads, multi-tier) | New (§4.8) |

---

## 4. Detailed Feature Specifications

### 4.1 Merchant onboarding (changed)

The 5-step wizard (Phase 1 §4.2) is revised:

| Step | Phase 1–5 | Phase 6 |
|---|---|---|
| 1 — Company profile & legal identity | Name, structure, DTI/SEC, TIN, address | **+ new textarea: "Company description"** |
| 2 — Operations & regulatory | Categories, FDA LTO, lead time, export readiness | **+ new textarea: "Describe the products you sell"** |
| 3 — Halal integrity & certification | Mandatory: certifying body, reg. number, expiry, file | **Removed from onboarding entirely.** Certification is no longer part of the wizard (see §4.2). |
| 4 — Value chain & capabilities | Unchanged | Unchanged |
| 5 — Document upload checklist | DTI/SEC cert scan, mayor's/business permit, BIR 2303, raw material list (4 fields) | **Simplified to 2 fields: "Business permit" and "Other supporting document"** |

- The wizard is now effectively **4 steps** (renumbered in UI; step indices in the data model are preserved for backward compatibility — see `design.md`).
- `shop_onboardings.company_description` and `shop_onboardings.products_description` are new nullable text columns; copied to `shops` on approval, same pattern as all other onboarding fields (Phase 1 §4.3).
- Existing `shop_onboarding_documents` / `shop_documents` records from before Phase 6 (which may hold the 4 old document types, including certs) are **left as-is** — no backfill/relabeling. New submissions only present the 2 simplified fields.
- On approval, the merchant is shown a confirmation screen with a clear call-to-action link: **"Apply for Certification"** (optional, §4.2) and, if not yet subscribed, **"Choose a Subscription Plan"** (§4.3).

### 4.2 Apply for Certification (new, decoupled)

- A **new standalone page/form** in the Company Hub, reachable any time post-onboarding-approval — not gated on subscription status, since a merchant may want to certify before deciding to subscribe.
- **Fields — exactly two, both optional to leave blank until ready to submit:**
  1. **Expiry date**
  2. **Certificate file** (upload)
- No certifying-body or registration-number fields (removed, per the simplification note — a deliberate reduction from the Phase 1 model, which required both; see §11 for the trade-off this creates on admin-side auditability).
- Submission creates a `certification_applications` row (`status: pending`), independent of `shop_onboardings`/`shops`. A merchant can have **zero, one, or more** certification applications over time (e.g., renewing an expiring cert).
- **Admin review** (FR-8): admin queue lists pending applications; admin can **approve** (sets `shops.is_halal_certified = true`, snapshots the current application's expiry date onto the shop record for storefront display and expiry alerting — reusing the Phase 1 expiry-alert job pattern) or **reject** (with `admin_notes`, merchant may reapply).
- Approved certification is displayed as a badge on the storefront company page, same visual treatment as the Phase 1 "VERIFIED" badge concept, but now sourced from `certification_applications` rather than the onboarding record.

### 4.3 Subscriptions — yearly plan with payment (new)

- Gated on `shops.status = approved` (only approved merchants can subscribe — FR-3 as written).
- **Plan**: a single **yearly** plan per merchant (multi-tier/monthly plans are not in scope for Phase 6 — see §11), priced per the merchant's country grouping.
- **Philippine merchants**: automatically eligible for a **free 6-month trial** the first time they attempt to subscribe — no payment collected, `subscriptions.status = trialing`, `trial_ends_at = now() + 6 months`. At trial end, the merchant is prompted to pay for the yearly plan to remain listed; if they don't, `status` moves to `lapsed` and product listing is blocked again (§4.4).
- **Non-Philippine merchants** (and PH merchants past their trial): pay for the yearly plan upfront via the existing **DLT Payments** integration already wired for Phase 3 order payments — Phase 6 reuses the same gateway/ledger pattern for a new payable type (`subscription`) rather than integrating a second payment provider.
- On successful payment, `subscriptions.status = active`, `current_period_start`/`current_period_end` set to a 1-year window.
- **Renewal**: a scheduled job flags subscriptions approaching `current_period_end` (reusing the Phase 1 expiry-alert job pattern) and notifies the merchant; renewal is a repeat of the pay-for-yearly-plan flow. No auto-charge/auto-renew in Phase 6 (flagged in §11).
- **Lapsed subscriptions** immediately block new product publishing (§4.4) but do not unpublish already-published products retroactively in Phase 6 v1 (flagged in §11 as a policy decision to confirm).

### 4.4 Product listing — subscription-gated (changed)

- Phase 1's rule ("only companies with an approved shop status can publish products") becomes: **only companies with an approved shop status *and* an `active` or `trialing` subscription** can publish products.
- Enforcement point: the product `status → published` transition (Company Hub) checks both conditions; the check is centralized in a single authorization method (e.g., `ProductPolicy::publish()`) so it's a one-place change, consistent with the Phase 5 centralized-authorization pattern.
- A merchant with `approved` shop status but no active/trialing subscription can still **draft** products (unpublished) — they just can't go live — so they aren't blocked from doing setup work while deciding on a plan.

### 4.5 RFQs — admin-owned, seller removed (changed)

- **Buyer side is unchanged**: buyer sends an RFQ from a product page (Phase 2 §4.2), buyer views/manages their sent RFQs from their account portal (Phase 2 §4.1/§7.1 steps 1–4).
- **Seller side is removed**: the Company Hub "RFQs Received" queue, the quotation builder, and all seller-facing RFQ actions (mark reviewed, decline/no-bid, submit quotation) are **disabled** (§5). Sellers no longer see RFQs at all.
- **Admin side is new**: a Company-Hub-equivalent queue lives in the **Admin** portal instead — admin sees every RFQ across all merchants, can view the buyer's freeform request + attachments + buyer profile snapshot (reusing Phase 2's `rfq_recipients`/snapshot schema, now with admin as the acting party instead of the seller), leave **internal notes**, and set a management status (e.g., `received` → `in_progress` → `resolved` / `closed`) — reusing the existing RFQ status machine's shape rather than inventing a parallel one.
- **Quotations are not part of Phase 6.** No structured seller quote is generated in-app; if a deal needs a formal quote, that now happens **off-platform**, coordinated by admin (e.g., admin relays buyer requirements to the merchant directly and facilitates outside the app). This is a deliberate scope reduction — see §11 for the trade-off and what a future re-enablement of §5's disabled quotation feature would restore.
- The 7-day no-bid timeout and quotation-expiry jobs (Phase 2 §8) are **disabled** along with quotations (§5); the RFQ auto-close-after-7-days-inactivity job is **retained**, now measuring admin + buyer activity instead of seller + buyer.
- **Chat is retained, but decoupled from RFQs.** Phase 2's buyer–seller conversation feature (Phase 2 §4.7) stays enabled — a buyer can still message a seller directly (e.g., via a "Message this company" entry point on the company page), and the seller keeps a chat inbox in the Company Hub. What changes is the **auto-threading behavior**: submitting an RFQ no longer automatically opens/attaches a chat thread with the seller, since the seller is no longer a party to the RFQ workflow. RFQ-specific coordination (buyer requirement clarification, relaying to the merchant, etc.) happens through the admin RFQ management screen (internal notes), not through the chat thread.

### 4.6 Admin: manage merchants (new)

- A dedicated **Merchants** admin screen, distinct from the onboarding-approval queue (Phase 1 §4.3): searchable/filterable list of all `shops` (by status, country, subscription state, certification state), with a merchant detail view showing onboarding history, subscription history, certification history, and product count.
- Admin actions: **suspend** / **reactivate** a merchant (`shops.status = suspended` ↔ `active`) — a suspended merchant's products are removed from the storefront/search index but not deleted, and the merchant cannot publish new products while suspended, independent of their subscription state.

### 4.7 Admin: moderate products (new)

- Admin can view any product regardless of shop, and take a moderation action: **flag** (visible to merchant with a reason, product stays live), **unpublish** (removes from storefront/search, merchant notified), or **restore**.
- This is independent of the merchant-level suspend action (§4.6) and of the merchant's own publish/archive toggle (Phase 1 §4.5) — three separate levers over a product's visibility, all landing on the same `products.status` field with an added `moderation_status` column to distinguish an admin-driven unpublish from a merchant's own archive action (see `design.md`).

### 4.8 Admin: sponsored content / ads (new, multi-tier)

- Admin composes **sponsored content** entries: title, creative (image/media), destination link, target placement, and **tier** (e.g., `gold` / `silver` / `bronze`, or however tiers are named at build time — modeled as a lookup, not a hardcoded enum, so tiers can be added later without a migration).
- Placements: storefront homepage, category browsing pages, search results — the exact placement inventory is a `design.md` decision; the requirement here is that sponsored content is **placement-aware** and **tier-aware** (higher tiers get priority placement/rotation weight).
- Sponsored content has a scheduling window (`starts_at`/`ends_at`) and an active/inactive toggle; only admin creates/manages entries — merchants do not self-serve buy ads in Phase 6 (flagged in §11 as a natural fast-follow).
- Basic performance tracking (impressions, clicks) feeds the analytics surface (§4.9).

### 4.9 Analytics (changed scope)

Given orders/payouts/reviews are disabled (§5), the Phase 4 analytics surfaces (GMV, payout totals, ratings) go dormant along with their source features. Phase 6's live analytics surface covers what's still active:

- **Admin dashboard**: subscription counts by status/plan/country, trial-to-paid conversion, certification application volume, RFQ volume and admin resolution time, sponsored content impressions/clicks by tier.
- **Merchant (Company Hub)**: their own subscription status/renewal date, certification status, basic product/listing counts, and (if applicable) their sponsored content performance if they have paid placements in a future phase.
- The Phase 4 `ShopAnalyticsService`-style pattern (Phase 5 §3.1) is extended with new aggregation methods rather than replaced, so the dormant order/payout aggregations can be reactivated later without re-architecting the service.

### 4.10 Storefront: country flag

- Each storefront/company page displays a **country flag** derived from `shops.country_code` (already an ISO code per Phase 1 §5.2), rendered next to the company name/header.

### 4.11 Localization

- The platform adds **locale support**: a locale switcher on the storefront, translated UI strings for in-scope surfaces (storefront, onboarding, subscription, homepage — per §1.4), and locale-aware formatting (dates, currency display) tied to the merchant's/buyer's country context.
- Implementation approach (Laravel's built-in localization, `lang` files + a translation-management path for admin-editable strings where needed) is detailed in `design.md`; this is infrastructure work that subsequent phases will keep extending coverage on.

### 4.12 Homepage country portals (expanded)

- The homepage's country-portal section currently reflects the Philippines-only starting scope; Phase 6 adds portal entries for **Malaysia, Kingdom of Saudi Arabia, Brunei, Indonesia, and Turkey**, alongside the existing Philippines portal (6 total).
- Each portal entry links into a country-filtered storefront view (companies/products where `country_code` matches), consistent with the existing country-code field already on `shops`.

---

## 5. Features Disabled in Phase 6 (not deleted)

Per §1.5, every row below is turned off via a feature flag; code, routes, and schema remain in the codebase for a future re-enable.

| Feature | Introduced | Flag (proposed) | What "off" means | Data retained |
|---|---|---|---|---|
| Quotations (seller quote builder + award) | Phase 2 | `features.quotations_enabled` | No quotation UI anywhere; RFQs never produce a quotation in Phase 6 | `quotations`, `quotation_items` tables untouched |
| Company Hub RFQ queue & seller RFQ actions | Phase 2 | `features.seller_rfq_queue_enabled` | Seller nav loses "RFQs Received"; seller-side policies deny access | `rfq_recipients` schema untouched; admin now the acting party (§4.5) |
| Orders (draft/submit/finalize) | Phase 3 | `features.orders_enabled` | No order creation from awarded quotations (moot, since quotations are also off) or standalone | `orders`, `order_items`, `order_fees` untouched |
| Escrow payments & ledger (buyer pay-in) | Phase 3 | `features.escrow_payments_enabled` | Simulated payment gateway not invoked for order payment (still invoked for subscription payment, §4.3, as a distinct payable type) | `payments`, `ledger_entries` untouched |
| Fulfilment | Phase 3 | `features.fulfilment_enabled` | No shipment/tracking/delivery-confirmation UI | `fulfilments` untouched |
| Disputes | Phase 3 | `features.disputes_enabled` | No dispute creation/arbitration UI (admin or buyer/seller) | `disputes`, related tables untouched |
| Payouts | Phase 4 | `features.payouts_enabled` (already exists per Phase 5) | No disbursement batching/release UI | `payouts`, `payout_items` untouched — flag already present, default flips to `false` in Phase 6 |
| Reviews & ratings | Phase 4 | `features.reviews_enabled` | No post-order review prompt/submission; no rating aggregation shown on storefront | `reviews`, `review_reports` untouched |

All eight flags live under `config('marketplace.features')`, following the exact pattern Phase 5 introduced for `payouts_enabled` (see `phase5/progress-report.md` Task 1.1). See `design.md` §2 for the middleware/policy gating mechanics and the full config file diff.

> **Chat is explicitly *not* in this table.** Unlike the other Phase 2 negotiation features, buyer–seller chat (`conversations`, `conversation_threads`, `messages`, `message_cards`) stays **enabled** in Phase 6 — see §4.5 for how its behavior changes (decoupled from RFQ auto-threading) without being turned off.

---

## 6. Data Model (additions only — no drops)

New entities introduced in Phase 6. Full DDL-level detail (columns, indexes, constraints) is in `design.md`; this section states the shape and relationships.

```
shops 1───N subscriptions
subscription_plans 1───N subscriptions
shops 1───N certification_applications
shops 1───N sponsored_content_impressions/clicks (via sponsored_content)
sponsored_content_tiers 1───N sponsored_content
sponsored_content 1───N sponsored_content_placements
country_portals (standalone lookup, seeded)
```

**`subscription_plans`** — id, name, country_scope (nullable — null means "all countries"), price, billing_period (`yearly`), trial_days (nullable, used for the PH trial), is_active

**`subscriptions`** — id, shop_id, subscription_plan_id, status (`trialing` / `active` / `lapsed` / `cancelled`), trial_ends_at (nullable), current_period_start, current_period_end, payment_id (nullable — links to the `payments` ledger reused from Phase 3 for the `subscription` payable type)

**`certification_applications`** — id, shop_id, expiry_date, media_id (certificate file), status (`pending` / `approved` / `rejected`), admin_notes, reviewed_by, reviewed_at

**`sponsored_content_tiers`** — id, name, priority_weight, is_active (lookup, not a hardcoded enum — see §4.8)

**`sponsored_content`** — id, sponsored_content_tier_id, title, media_id, destination_url, starts_at, ends_at, is_active, created_by (admin user)

**`sponsored_content_placements`** — id, sponsored_content_id, placement_key (e.g., `homepage`, `category_page`, `search_results`)

**`sponsored_content_events`** — id, sponsored_content_id, event_type (`impression`/`click`), occurred_at (lightweight tracking table for §4.9's basic performance metrics)

**`country_portals`** — id, country_code, display_name, is_active, sort_order (seeded with the 6 countries from §4.12)

**Modified (additive) columns:**
- `shop_onboardings`: `+ company_description` (text, nullable), `+ products_description` (text, nullable)
- `shops`: `+ company_description`, `+ products_description` (copied on approval, same as other onboarding fields), `+ is_halal_certified` (bool, default false), `+ certified_expiry_date` (nullable, snapshotted from the approved `certification_applications` row)
- `products`: `+ moderation_status` (enum: `active` / `flagged` / `admin_unpublished`, default `active`) — see §4.7

No existing column is dropped or renamed; the old `shop_onboardings.halal_certification_body` / `halal_certificate_registration_number` / `halal_certificate_expiry_date` fields (Phase 1 §5.1) are left in place but unused by the new onboarding wizard (§4.1) going forward.

---

## 7. Key User Flows

### 7.1 Merchant: onboard → subscribe → list

1. Register → verify email → 4-step onboarding wizard (§4.1) → submit (`pending`)
2. Admin approves → `shops` record created (unchanged mechanics, Phase 1 §4.3)
3. Merchant lands on a post-approval screen with two optional next steps: **Apply for Certification** (§4.2) and **Choose a Subscription Plan** (§4.3)
4. Merchant selects the yearly plan → if PH, trial starts free (`trialing`, 6 months); if not PH (or PH past trial), pays via DLT Payments → `active`
5. With shop `approved` + subscription `active`/`trialing`, merchant can now publish products (§4.4)

### 7.2 Merchant: certification (optional, any time post-approval)

1. Merchant opens **Apply for Certification** from Company Hub
2. Submits expiry date + certificate file → `certification_applications` row, `pending`
3. Admin reviews (queue, FR-8) → approve (badge appears on storefront, expiry tracked) or reject (`admin_notes`, merchant may reapply)

### 7.3 Buyer: RFQ (admin-managed)

1. Buyer completes/has a buyer profile (Phase 2 §4.1, unchanged) → clicks "Request Quote" on a product page → submits RFQ (Phase 2 §4.2, unchanged)
2. RFQ appears in **admin's** RFQ queue (not the seller's) → admin reviews, works it (`in_progress`), leaves internal notes, coordinates with the merchant off-platform as needed
3. Admin closes/resolves the RFQ; buyer sees status update in "My RFQs" (no quotation object is ever attached in Phase 6)
4. If no admin/buyer activity for 7 days, RFQ auto-closes (same timeout mechanic as Phase 2 §7.4, re-scoped to admin instead of seller)

### 7.4 Admin: sponsored content

1. Admin creates a sponsored content entry, selects a tier and one or more placements, sets a schedule window
2. Content rotates into the selected placement(s) per tier priority while `is_active` and within the schedule window
3. Admin views impressions/clicks per entry/tier in the analytics surface (§4.9)

---

## 8. Technical Architecture

### 8.1 Feature-flag layer

Extends `config/marketplace.php` (Phase 5's `features` array) with the eight flags in §5, each independently toggleable per environment. A single `EnsureFeatureEnabled` middleware (parameterized with the flag key) gates the relevant route groups (RFQ-received routes, order/payment/fulfilment/dispute/payout/review routes); Livewire components and Blade nav partials check the same config value directly for conditional rendering. Chat routes are **not** gated by this middleware — chat stays enabled (§4.5) — but the RFQ-submission handler is updated to stop invoking the chat-thread auto-creation call. Policies (`OrderPolicy`, `DisputePolicy`, `PayoutPolicy`, `QuotationPolicy`, etc. — all pre-existing per Phase 3–5) gain an early `return false` when their governing flag is off, so even a direct route/API hit is denied, not just hidden from nav.

### 8.2 Subscription payments

Reuses the Phase 3 **DLT Payments** simulated gateway and internal ledger (`payments` table) rather than a new integration — a `payable_type` discriminator (already a reasonable pattern given the ledger's role as system-of-record) distinguishes a `subscription` payment from an `order` payment (dormant per §5). This keeps a single payment code path instead of maintaining two.

### 8.3 RFQ ownership transfer

`rfq_recipients` (Phase 2 §5, conceptually — the join between an RFQ and its recipient) is repointed: in Phase 6, the "recipient" acting party for status transitions becomes admin rather than the seller's shop. The existing status enum (`received`/`reviewed`/`in_progress`/`no_bid`) is reused with `no_bid` retired in favor of `closed` (admin doesn't "decline to bid" — they resolve or close). Exact schema handling (new admin-facing status column vs. reinterpreting the existing one) is a `design.md` decision.

### 8.4 Localization

Laravel's native localization (`lang/{locale}/*.php` + `App::setLocale()`) for static UI strings; locale-aware model attributes (company description, sponsored content copy, etc.) considered for a translatable-columns approach (e.g., JSON column or a package like `spatie/laravel-translatable`) if admin/merchant-authored content needs multi-locale variants — confirmed in `design.md`.

### 8.5 Search index impact

Typesense indexing rules (Phase 1 §7.3) gain an additional exclusion: a product must belong to a shop with an `active`/`trialing` subscription to be indexed, in addition to the existing `published` + `active` shop checks. A suspended merchant (§4.6) or an admin-unpublished product (§4.7) is likewise excluded.

---

## 9. Non-Functional Requirements

| Category | Requirement |
|---|---|
| **Backward compatibility** | No Phase 2–4 migration may drop or rename a column/table; Phase 6 migrations are additive only (§6). |
| **Reversibility** | Every flag in §5 must be independently toggleable in a non-production environment for QA without requiring a code deploy. |
| **Auditability** | Certification application decisions and merchant suspend/reactivate actions retain `admin_notes`/actor/timestamp, consistent with the Phase 1 onboarding-decision audit pattern. |
| **Data privacy** | Certificate files and business-permit/supporting documents keep the existing Phase 1 access-control model (owning merchant + authorized admins only). |
| **Payment integrity** | Subscription payments use the same ledger integrity guarantees (idempotency, atomicity) already required of order payments in Phase 3 §9. |
| **Localization** | Locale switching must not require a page reload of unrelated application state; missing translation keys fall back to the default locale rather than rendering blank. |
| **Search consistency** | A subscription lapsing must remove the merchant's products from the search index within the same job-cycle latency Phase 1 §7.3 already targets for other visibility changes. |

---

## 10. Milestones (indicative)

| Milestone | Includes |
|---|---|
| M1 — Feature flag registry | `config/marketplace.php` extended with all 9 flags (§5); middleware + policy gating wired; nav/UI conditionally hidden |
| M2 — Onboarding changes | Wizard reduced to 4 steps, new textareas, simplified document uploads, cert step removed (§4.1) |
| M3 — Certification decoupling | `certification_applications` table, merchant-facing form, admin review queue (§4.2) |
| M4 — Subscriptions | `subscription_plans` + `subscriptions`, PH free-trial logic, DLT payment reuse for `subscription` payable type, subscription-gated product publish (§4.3–4.4) |
| M5 — RFQ ownership transfer | Admin RFQ management screen, seller RFQ queue hidden, quotations flag off, RFQ→chat auto-threading removed while chat itself stays enabled (§4.5, §5) |
| M6 — Admin merchant & product management | Merchant list/suspend/reactivate (§4.6), product moderation (§4.7) |
| M7 — Sponsored content | Tiers, placements, scheduling, impression/click tracking, admin composer (§4.8) |
| M8 — Analytics rework | Dormant order/payout aggregations flagged off; new subscription/RFQ/sponsored-content aggregations added (§4.9) |
| M9 — Storefront/homepage | Country flags, expanded country portals (6 countries), localization infrastructure + first-pass translated surfaces (§4.10–§4.12) |

---

## 11. Open Questions / Risks

1. **Certification auditability trade-off**: removing the certifying-body and registration-number fields (§4.2) simplifies submission but weakens admin's ability to independently verify a certificate against the issuing body's records — confirm this trade-off is accepted, or whether at least a free-text "issuing body" field should be kept even if registration number is dropped.
2. **Subscription auto-renewal**: Phase 6 as specified has no auto-charge-on-renewal; confirm whether that's intentional (merchant must manually re-pay) or whether auto-renewal should be scoped in before build.
3. **Lapsed-subscription retroactive unpublish**: §4.3 currently leaves already-published products live when a subscription lapses (only blocks *new* publishes). Confirm whether lapsed merchants should have existing listings pulled from the storefront immediately instead.
4. **RFQ without quotations — deal completion**: with quotations and orders both dormant, there's no in-app record of whether an RFQ ever became a real transaction. If the business wants to track conversion (RFQ → actual sale) even while transacting off-platform, consider a lightweight "outcome" field on the admin RFQ record (won/lost/no outcome) — currently not specified.
5. **Sponsored content self-serve**: Phase 6 is admin-only for ad creation (§4.8); confirm whether merchant self-serve ad purchase is intended as a near-term fast-follow, since it would reuse the subscription payment path (§8.2) fairly directly.
6. **Multi-country subscription pricing**: `subscription_plans.country_scope` (§6) supports per-country pricing, but the actual price list for the 5 newly-added countries (§4.12/§1.3 G11) isn't specified in the source brief — needs business input before M4.
7. **Existing in-flight Phase 2–4 data at cutover**: merchants/buyers with open RFQs, active quotations, or in-progress orders at the moment flags flip off — confirm whether these get a grace period to complete (flags stay on per-entity until terminal state) or are hard-frozen read-only immediately.

---

## 12. Appendix — Glossary

| Term | Meaning |
|---|---|
| **Merchant** | Same entity as "seller" in Phases 1–5; Phase 6 standardizes on "merchant" per updated terminology. |
| **Trialing** | Subscription status for a merchant on the PH free 6-month trial, not yet paid. |
| **Lapsed** | Subscription status after `current_period_end` (or trial end) passes with no renewal payment. |
| **Sponsored content** | Admin-managed, tiered advertising placements on the storefront. |
| **Moderation status** | Admin-driven visibility control on a product, independent of the merchant's own publish/archive toggle. |
| **Feature flag** | A `config('marketplace.features.*')` boolean gating a Phase 2–4 capability off in Phase 6 without deleting its code/schema. |
