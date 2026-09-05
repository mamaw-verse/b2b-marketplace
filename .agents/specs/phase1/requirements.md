# Product Requirements Document & Technical Specification
## Phase 1 — Halal Business Directory (Company & Product Listings)

| | |
|---|---|
| **Product** | Halal B2B Marketplace |
| **Phase** | 1 of 3 — Discovery |
| **Doc status** | Draft for review |
| **Owner** | Product / Engineering |
| **Last updated** | 2026-08-13 |

---

## 1. Overview

### 1.1 Product context
The Halal B2B Marketplace is being built in three sequential phases, each solving one part of a single end-to-end question:

> *Can two businesses discover each other, negotiate, place an order, pay securely, deliver goods, and leave feedback?*

| Phase | Theme | Core capabilities |
|---|---|---|
| **1 (this doc)** | **Discovery** | Accounts, company onboarding/verification, product catalog, categories, NLS search |
| 2 | Negotiation | RFQ, chat, quotes |
| 3 | Orders & Payments | Orders, payment gateway + escrow, fulfilment, disputes |

Phase 1 delivers the foundational layer: a verified, searchable **halal business and product directory**. Nothing in Phase 1 involves transacting — it is purely discovery and trust-building (verification, halal certification integrity) so that Phases 2 and 3 have a credible base of suppliers and buyers to build on.

### 1.2 Problem statement
Halal-certified manufacturers, processors, and suppliers in the Philippines (and the buyers who source from them) currently lack a centralized, verifiable directory. Buyers cannot easily confirm a supplier's halal certification status, production capacity, or value-chain position, and suppliers have no dedicated channel to showcase certified products to B2B buyers. Phase 1 solves the **discovery and trust** problem before any commercial transaction layer is introduced.

### 1.3 Goals
- Allow a business to register, complete a structured onboarding wizard, and become a **verified, listed company** in the directory.
- Allow admins to **review, verify, or reject** company registrations, with certification data as a first-class, auditable field.
- Allow verified companies to **list products** (with variants, documents, gallery, categories) via a company hub.
- Allow buyers/users to **discover** companies and products via natural-language search (NLS) and category browsing on a public storefront.
- Establish the **data model and portal structure** that Phases 2–3 (RFQ, chat, orders, payments) will build on top of.

### 1.4 Non-goals (out of scope for Phase 1)
- RFQs, chat, quoting (Phase 2)
- Orders, payments, escrow, fulfilment, disputes (Phase 3)
- In-app certificate renewal workflow (explicitly deferred; only expiry *tracking/alerting* is in scope)
- Multi-company accounts (one company per user account in Phase 1)

### 1.5 Success metrics (Phase 1)
- % of company onboardings that reach `approved` status within X business days of submission
- Number of verified companies and published products live in the directory
- Search-to-result relevance / zero-result-query rate on NLS search
- Admin review turnaround time (submission → decision)
- Rate of `requires_revision`/`rejected` due to certificate issues (data quality signal)

---

## 2. Users & Portals

Phase 1 spans **four portals**, each with a distinct user and job-to-be-done.

| Portal | Primary user | Purpose |
|---|---|---|
| **Admin** | Internal ops/reviewer | Review & verify company onboarding, manage categories, moderate listings |
| **Company Hub / Dashboard** | Business owner/staff (seller) | Complete onboarding, manage company profile, list products |
| **User / Buyer Account** | Buyer | Browse/search, save/discover companies & products (no transactions yet) |
| **Sitefront (Storefront)** | Public / buyer | Public-facing directory, NLS search, category browsing, company & product pages |

### 2.1 Roles & permissions
Company hub, admin, and buyer are backed by a shared `users` table with a **roles & permissions** layer (a stated dependency of the Discovery feature) gating admin-portal access. Company hub access is scoped to the single `shop` owned by `user_id` (1:1 in Phase 1).

---

## 3. Functional Requirements

Sourced from the Discovery feature spec; each maps to a portal above.

1. A user can set up **one company** (1 user : 1 shop).
2. An admin can **review, verify, or reject** company registrations.
3. An admin can **create categories**.
4. A user can **list products** in their shop via the company hub portal.
5. A user (buyer) can **find products and companies** via NLS search or category browsing in the storefront.

### 3.1 Sub-features
- Company setup (onboarding wizard)
- Company verification process (admin review workflow)
- Categories (admin-managed taxonomy)
- Product listing (catalog management)

---

## 4. Detailed Feature Specifications

### 4.1 Company setup flow (end-to-end)

```
Create account → Login → Verify email → Setup company (wizard) → Admin verification → Shop goes live
```

Email verification and account sign-in are explicit dependencies of this feature and are assumed to exist as a prerequisite system (not built in this phase's UI scope, but required infrastructure).

### 4.2 Company setup wizard

A 5-step wizard writes into a **staging table** (`shop_onboardings`) rather than the live `shops` table, so nothing is publicly visible until admin approval. On approval, data is copied into `shops`.

| Step | Name | Key fields |
|---|---|---|
| **1** | Company profile & legal identity | Registered company name, storefront name, business structure (sole proprietorship/DTI, partnership/SEC, corporation/SEC), DTI/SEC registration number, TIN, business address (phone, email, address_1, address_2, city, province, zip, country ISO code) |
| **2** | Operation capacity & regulatory clearances | Primary product categories (multi-select), FDA LTO number, average production lead time (days), export readiness status (never exported / exporting regionally-ASEAN / exporting globally) |
| **3** | Halal integrity & certification data | One-or-more certificates: certifying body (IDCP / HDIP / Prime Certification / Other NCMF-recognized body — free text), certificate registration number, expiry date, certificate file upload |
| **4** | Value chain position & capabilities | Value chain position(s) — multi-select: Farm, Processing, Manufacturing, Distribution, Retail, Food Service. Capabilities — multi-select: Processing, Contract Processing, Packaging, Contract Manufacturing, OEM/ODM Manufacturing, Private Label, Cold Storage, Warehousing, Distribution, Wholesale, Importing, Exporting |
| **5** | Document upload checklist | DTI/SEC registration certificate (scan), current-year mayor's/business permit, BIR Certificate of Registration (Form 2303), complete raw material & ingredient master list |

**Geo note:** Address capture should integrate a maps/geocoding API to persist lat/long coordinates on the shop record, enabling future geo-spatial search and distance-based discovery.

**Certification note:** Halal certificate data is the trust-critical section of the product. It must be:
- Admin-verifiable (linked source document, not just free text)
- Tracked for **expiry** — the system should proactively alert the company as certificates approach or pass their expiry date
- Structured to support **multiple certificates per shop** (a company may hold certs from more than one body, or for different product lines)
- In-app **renewal** is explicitly a future/Phase-2+ capability, not Phase 1

### 4.3 Company verification process (admin)

- Admin reviews a submitted `shop_onboardings` record (status: `pending`).
- Admin can move it to:
  - `approved` → system creates the `shops` record, copies onboarding data + documents (marked `is_verified = true`), links `shop_id` back onto the onboarding row.
  - `requires_revision` → admin leaves `admin_notes`; company can edit and resubmit.
  - `rejected` → terminal state (with reason in `admin_notes`).
- Post-approval, any **new** document a company uploads (e.g., a renewed certificate) starts as unverified (`shop_documents.is_verified = false`) and requires a fresh admin check — the verification trust model is per-document, not just per-onboarding.

### 4.4 Categories (admin-managed)
- Admin CRUD for `categories` (name, description, slug).
- Categories are used in two places:
  1. Onboarding Step 2 — "primary product categories" (snapshotted as CSV on the onboarding record, since category taxonomy may evolve after submission).
  2. Product listing — categories attach to individual products (`shop_categories` for company-level primary categories; product-level categorization inferred via the product/category relationship).
- Storefront category browsing is one of the two discovery entry points (alongside NLS search).

### 4.5 Product catalog (product listing)

**Entity model:**
```
Product
 ├── Variants (SKU, attributes, price, MOQ, quantity breaks, lead time, pack size, status)
 ├── Documents (specs, user manuals, warranty papers)
 ├── Gallery (photos — photo upload only for Phase 1)
 └── Categories
```

- Only companies with an **approved** shop status can publish products.
- Products have a top-level `status` (`published` / `archived`); variants independently carry their own `published`/`archived` status, allowing partial variant retirement without archiving the whole product.
- B2B-specific commerce fields live at the **variant** level (not the product level), since B2B pricing, MOQ, and lead time are typically SKU-specific: SKU, attributes (size/color/material/etc.), price, MOQ, quantity breaks, available quantity, lead time, pack size.

### 4.6 Discovery / NLS search
- Powered by **Typesense** as the dedicated search server (see §7).
- Two discovery surfaces on the storefront:
  1. **NLS (natural-language search)** — free-text query across companies and products.
  2. **Category browsing** — structured navigation via the admin-managed category taxonomy.
- Search index should surface only `published` products and `active` shops (verification-gated visibility).

---

## 5. Data Model

The canonical Phase 1 schema (from the Discovery feature spec). Where the onboarding-wizard field list and this schema differ only in naming, the schema below is authoritative for implementation.

### 5.1 Onboarding & verification

**`shop_onboardings`**
| Field | Notes |
|---|---|
| id | |
| registered_business_name | |
| storefront_name | |
| business_structure | enum: sole_proprietorship / partnership / corporation |
| dti_sec_number | |
| tax_identification_number | |
| phone, email | |
| address_1, address_2, city, province, zip_code, country_code | |
| product_categories | CSV snapshot of category names at time of submission |
| fda_lto_number | |
| average_production_lead_time | days |
| export_readiness_status | enum |
| halal_certification_body | |
| halal_certificate_registration_number | |
| halal_certificate_expiry_date | drives expiry alerting |
| user_id | |
| status | enum: pending / requires_revision / approved / rejected |
| shop_id | populated once approved |
| admin_notes | revision/rejection feedback |

> **Schema note:** the wizard supports **multiple** halal certificates per company (Step 3), while `shop_onboardings` as currently modeled stores a single certification body/number/expiry set. Recommend either (a) a child table `shop_onboarding_certificates` (onboarding_id, body, reg_number, expiry_date, media_id) for a true 1:many relationship, or (b) explicitly scoping Phase 1 to a single primary certificate per shop and deferring multi-certificate support. **Flagged as an open decision — see §11.**

**`shop_onboarding_documents`** — onboarding_id, media_id

### 5.2 Live directory entities

**`shops`** — mirrors `shop_onboardings`' business/regulatory/halal fields, plus:
- `user_id` (unique — enforces one shop per account)
- `status`: active / inactive / suspended

**`shop_categories`** — shop_id, category_id

**`shop_documents`** — shop_id, media_id, `is_verified` (true when copied from an approved onboarding; new uploads require re-verification)

**`categories`** — id, name, description, slug

### 5.3 Product catalog

**`products`** — id, name, description, featured_image_path, status (published/archived), shop_id

**`product_variants`** — id, product_id, sku, description, minimum_order_quantity, unit, price
> Extend with `quantity_breaks`, `available_quantity`, `lead_time`, `pack_size`, and `status` (published/archived) per the Phase-1 feature brief's product database schema — these fields are named in the brief but not yet reflected in the Discovery doc's `product_variants` table; reconcile before implementation.

**`product_documents`** — product_id, media_id

**`product_gallery`** — product_id, media_id, sort_order

### 5.4 Shared infrastructure entities

**`users`** — id, first_name, last_name, email, password, phone, photo_path, email_verified_at

**`media`** — id, original_filename, extension, storage_path, mime_type, description, uploaded_by_id
- Single polymorphic-style media table backs onboarding documents, shop documents, product documents, and product gallery images — all pointing at `media.id`.

### 5.5 Entity relationship summary

```
users 1───1 shop_onboardings 1───1 shops 1───N products 1───N product_variants
                │                    │                │
                │                    │                ├──N product_documents ─┐
                │                    │                └──N product_gallery ───┤
                │                    ├──N shop_categories ──N categories       │
                │                    └──N shop_documents ─────────────────────┤
                └──N shop_onboarding_documents ─────────────────────────────► media
```

---

## 6. Key User Flows

### 6.1 Seller: company onboarding
1. Register account → verify email
2. Enter Company Hub → 5-step wizard (§4.2), saved as draft/`pending` in `shop_onboardings`
3. Submit → status `pending`, awaits admin
4. **If `requires_revision`**: seller sees `admin_notes`, edits, resubmits
5. **If `approved`**: `shops` record created, seller can now access product listing
6. **If `rejected`**: terminal; seller notified with reason

### 6.2 Seller: product listing
1. (Prerequisite: shop status = `active`)
2. Create product → name, description, featured image
3. Add ≥1 variant → SKU, price, MOQ, quantity breaks, lead time, pack size
4. Attach documents (specs/manuals/warranty) and gallery photos
5. Assign categories
6. Set status → `published` (goes live in search index) or keep `archived`

### 6.3 Admin: verification review
1. Queue of `pending` onboardings
2. Review submitted fields + uploaded documents (esp. halal certificate + expiry)
3. Decision: approve / request revision (+notes) / reject (+notes)
4. On approval, system provisions `shops` + `shop_documents` (verified)

### 6.4 Buyer: discovery
1. Land on storefront (public, no login required to browse)
2. Search via NLS bar **or** browse by category
3. View company profile (verification badge, certs, value chain, capabilities) or product detail (variants, MOQ, price, lead time, gallery, documents)
4. (Phase 1 ends here — no RFQ/contact-seller action yet; that's Phase 2)

---

## 7. Technical Architecture

### 7.1 Stack

| Layer | Technology | Role in Phase 1 |
|---|---|---|
| Application framework | **PHP / Laravel** | Core backend: auth, onboarding logic, admin workflows, product CRUD, API |
| UI reactivity | **Livewire** | Server-driven interactivity for the multi-step onboarding wizard, admin review screens, product/variant management, without a separate SPA build |
| Styling | **Tailwind CSS** | Utility-first styling; token system in §8 should map directly to a Tailwind theme config |
| Cache | **Redis** | Session store, cache layer (category lists, shop lookups), queue driver for background jobs (e.g., certificate-expiry alert checks) |
| Database | **PostgreSQL** | System of record — all entities in §5 |
| Realtime | **Reverb** | Laravel's WebSocket server — in Phase 1 scope this is primarily forward-looking infrastructure for Phase 2 chat, but can also power live admin-queue updates and onboarding-status notifications |
| Search | **Typesense** | Powers NLS search + category-filtered discovery across companies and products; indexed on approval/publish events |
| Payments | **DLT Payments** | Not used in Phase 1 (reserved for Phase 3 orders/escrow) — listed here as part of the overall v1 stack |
| Object storage | **Minio / S3** | Stores all `media` records — certificate scans, business permits, product galleries/specs |
| Hosting/platform | **Laravel Cloud** | Deployment target |

### 7.2 System design components
Per the v1 architecture: **web app, cache, relational database, web socket, search server, payment gateway, storage.** Phase 1 activates all of these except the payment gateway, which stays dormant/unintegrated until Phase 3.

```
                      ┌───────────────┐
   Buyer / Seller ───►│   Web App     │◄── Livewire + Tailwind (server-rendered UI)
                      │  (Laravel)    │
                      └──────┬────────┘
                 ┌───────────┼────────────────┬──────────────┐
                 ▼           ▼                ▼              ▼
           ┌──────────┐ ┌─────────┐   ┌──────────────┐ ┌───────────┐
           │PostgreSQL│ │  Redis  │   │  Typesense   │ │ Minio/S3  │
           │ (system  │ │ (cache, │   │ (NLS search  │ │ (media:   │
           │ of record│ │ queue,  │   │  index)      │ │  certs,   │
           │ )        │ │ session)│   │              │ │  photos)  │
           └──────────┘ └─────────┘   └──────────────┘ └───────────┘
                                 ▲
                                 │
                         ┌───────┴───────┐
                         │    Reverb     │  (WebSocket — status/queue
                         │  (WebSockets) │   notifications; Phase 2 chat groundwork)
                         └───────────────┘
```

### 7.3 Search indexing strategy
- Index writes triggered on: shop `approved`→`active` transition, product/variant `published` status change, category updates.
- Index reads (published `shops` + `products`) exclude anything `pending`, `inactive`, `suspended`, or `archived`.
- Suggested indexed fields: company name, storefront name, categories, certifying body, value-chain tags, capability tags, product name/description, SKU attributes.

### 7.4 File/media handling
- All uploads (certs, permits, BIR form, gallery, specs) go to Minio/S3 via the `media` table pattern; Laravel handles signed uploads and validation (file type/size) before persisting `media` rows.
- Certificate documents specifically should be retained even after onboarding approval (for audit/expiry-tracking), not just at time of review.

### 7.5 Background jobs (Redis-queued)
- Certificate expiry scan (scheduled) → notify company as expiry approaches/passes.
- Search index sync on relevant model events.
- Onboarding-status change notifications (email + in-app).

---

## 8. Design System (Frontend Reference)

A design system was extracted from the existing marketing/reference page and should govern the Tailwind theme for both storefront and portal UIs.

- **Brand palette:** Halal Emerald green (`--hs-green-600 #158A5A` primary), deep forest surface (`--hs-green-950 #05291B` for hero/footer/dark surfaces), restrained gold accent (`--hs-gold-500 #C9A24B`) reserved for export/premium cues.
- **Neutrals:** slightly green-tinted neutral scale (`--hs-neutral-0` → `--hs-neutral-900`) for text/surfaces/borders.
- **Semantic colors:** success/warning/danger/info, used for verification badges, lead-time cautions, form errors, and certification notices respectively.
- **Typography:** Display font "Sora" (headings, stat numerals, prices) / body font "Inter" (UI, body copy), with a defined type scale from 12px captions to 56px hero H1.
- **Spacing:** 4px-base scale (`--hs-space-1` through `--hs-space-24`).
- **Radius:** 4px (tags) up to 24px (hero search panel), pill (`999px`) for verification badges and value-chain steps.
- **Elevation/motion:** defined shadow scale and transition timings for card hovers, badge changes, and section reveals.
- **Key components directly relevant to Phase 1 UI:** Company Card (with VERIFIED badge, sector avatar, tag row, lead-time chip), Value Chain Stepper (Farm→Food Service), Product Card (halal badge overlay, price + MOQ row), Badge system (`verified`, `halal`, `tag`, `gold`/export-ready), Hero Search Widget (Find Companies / Find Products toggle).

The full token set (CSS custom properties, component inventory, and rationale) is maintained separately as the canonical design-system reference and should be wired into the Tailwind config (`tailwind.config.js` theme extension) rather than duplicated here.

---

## 9. Non-Functional Requirements

| Category | Requirement |
|---|---|
| **Trust & data integrity** | Halal certificate data must be admin-verified before a shop is publicly discoverable; every certificate must have a linked source document. |
| **Auditability** | Onboarding decisions (approve/revise/reject) must retain `admin_notes` and be attributable/timestamped for audit. |
| **Availability** | Storefront (public discovery) should remain read-available independent of admin/company-hub load. |
| **Performance** | Search queries (Typesense) should return in a UX-acceptable latency (<300ms typical) even as directory scales. |
| **Data privacy** | Sensitive regulatory identifiers (TIN, DTI/SEC numbers) and uploaded government documents require access control — restricted to the owning company and authorized admins only. |
| **Scalability** | One-company-per-user in Phase 1 is a deliberate constraint, not an architectural ceiling — schema (`user_id` unique on `shops`) should be revisited if multi-company support is needed later. |
| **Internationalization/localization** | Country field uses ISO codes; address model should not assume Philippines-only, even though initial certifying bodies (IDCP, HDIP, Prime Certification, NCMF) are PH-specific. |

---

## 10. Milestones (indicative)

| Milestone | Includes |
|---|---|
| M1 — Auth & account foundation | Sign-up, login, email verification, roles/permissions (dependency) |
| M2 — Company onboarding wizard | 5-step wizard, `shop_onboardings` + document upload |
| M3 — Admin verification | Admin queue, approve/revise/reject workflow, `shops` provisioning |
| M4 — Categories | Admin CRUD for categories |
| M5 — Product catalog | Products, variants, documents, gallery, category assignment |
| M6 — Discovery/storefront | NLS search (Typesense integration), category browsing, public company/product pages |
| M7 — Certificate expiry alerting | Scheduled job + notifications |

---

## 11. Open Questions / Risks

1. **Multi-certificate modeling:** The onboarding wizard supports multiple halal certificates per company, but the current `shop_onboardings`/`shops` schema only has single certification-body/number/expiry fields. Needs a child-table decision before build (see §5.1).
2. **Product-level vs. shop-level categories:** The schema defines `shop_categories` explicitly but doesn't show an equivalent `product_categories` join table, despite the product entity diagram listing "Categories" as a child of Product. Clarify whether product categorization is inherited from the shop's categories or independently assignable per product.
3. **`product_variants` field gaps:** The Phase-1 feature brief lists `quantity_breaks`, `available_quantity`, `lead_time`, `pack_size`, and variant-level `status`, none of which currently appear in the Discovery doc's `product_variants` schema. Reconcile before implementation (§5.3).
4. **Certificate renewal UX:** Explicitly deferred, but expiry *alerting* is in scope — confirm notification channel(s) (email/in-app/both) and lead time before expiry.
5. **Geo-spatial search:** Maps API integration for address coordinates is noted as a "consider" in the source brief, not a firm requirement — confirm whether geo-spatial discovery (e.g., "suppliers near me") is truly in Phase 1 scope or deferred.
6. **Company-hub multi-user access:** Is company hub access restricted to the single onboarding user, or should it support inviting teammates? Current schema (`shops.user_id` unique) implies single-owner only.

---

## 12. Appendix — Glossary

| Term | Meaning |
|---|---|
| **NLS** | Natural-Language Search |
| **MOQ** | Minimum Order Quantity |
| **LTO** | (FDA) License To Operate |
| **DTI / SEC** | Philippine business registration bodies (sole proprietorship vs. partnership/corporation) |
| **NCMF** | National Commission on Muslim Filipinos — accredits halal certifying bodies |
| **IDCP / HDIP** | Recognized halal certification bodies referenced in the certification step |
| **RFQ** | Request for Quotation (Phase 2) |