# Product Requirements Document
## Phase 4 — Payouts, Reviews & Analytics

| | |
|---|---|
| **Companion docs** | `Phase 1 requirements.md / design.md`, `Phase 2 requirements.md / design.md`, `Phase 3 requirements.md / design.md / deviations.md` |
| **Phase** | 4 — Post-Transaction |
| **Doc status** | Draft for review |
| **Owner** | Product / Engineering |

---

## 1. Overview & Goals

### 1.1 Product context

Phase 1 delivered **discovery**. Phase 2 delivered **negotiation**. Phase 3 delivered **orders, payments, fulfilment, and disputes**. Phase 4 completes the transactional lifecycle with the post-transaction layer:

> *Can two businesses discover each other, negotiate, place an order, pay securely, deliver goods, and leave feedback?* — Phase 4 answers "leave feedback" and adds the financial infrastructure to move real money to sellers.

### 1.2 Problem statement

Phase 3 collects real payments from buyers (via DLT Collection API) and tracks funds in an internal ledger — but seller payouts are not yet disbursed to real bank accounts. Sellers cannot see an aggregated view of their earnings/payouts. Buyers have no way to rate their experience, and platform admins lack visibility into platform-wide financial health. Phase 4 solves these gaps.

### 1.3 Goals

Each goal is tagged (`G1`–`G7`) so the feature specs in §3 can trace back to the goal(s) they satisfy.

- **G1 — Real disbursements**: move escrowed funds to sellers' registered bank accounts via DLT's Disbursement API, triggered and managed by platform admins.
- **G2 — Payout lifecycle**: introduce batch payouts that aggregate multiple completed orders into a single settlement, with full traceability back to individual orders.
- **G3 — Payout release rules**: admin-configurable conditions (hold periods, minimum thresholds, dispute holds, verification status) that must be met before funds are eligible for release.
- **G4 — Payment history**: dedicated transaction-history views for buyers (payments + refunds), sellers (earnings + payouts), and admins (platform-wide financial ledger).
- **G5 — Reviews & ratings**: let buyers rate products and sellers after confirmed receipt; surface aggregate ratings on the storefront and in search ranking.
- **G6 — Sales analytics expansion**: add payout totals, review/rating averages, and platform-wide GMV to the existing analytics surfaces (seller reports, admin dashboard).
- **G7 — Platform fees**: formalize the two-fee model (transaction fee + commission fee), deducted at payout time.

### 1.4 Non-goals (out of scope for Phase 4)

- Automatic refund reversals / seller-balance deductions for late disputes (if funds are already disbursed, buyer and seller settle off-platform).
- Seller-initiated on-demand payouts (all payout releases are admin-triggered).
- Subscription plans / tiered commission rates (provision the data model for future, but only a flat rate is configurable in Phase 4).
- Review photos/media uploads (text + star rating only in Phase 4).
- Buyer-facing purchase analytics (seller + admin only).
- CSV/PDF export of reports (dashboard-only in Phase 4).
- Automated dispute-to-payout netting (no rolling balance / clawback mechanism).
- Real courier integration for return tracking.

### 1.5 Success metrics

- % of eligible payouts processed within the configured schedule window.
- Payout failure rate (failed disbursement attempts / total attempts).
- Average time from order completion to payout release.
- Review submission rate (reviews / completed orders).
- Average platform rating vs. category-level averages.
- Platform GMV growth period-over-period.
- Profit calculator usage rate among active sellers (adoption signal for pricing tooling).

---

## 2. Dependencies & Assumptions from Phases 1–3

| Dependency | Source | Phase 4 relationship |
|---|---|---|
| `shops` + seller verification | Phase 1 | Seller must have an `active` shop before registering payout account |
| `orders`, `order_items`, `fulfilments` | Phase 3 | Payout eligibility is derived from order/fulfilment completion state |
| `ledger_entries` (escrow_hold, payout, refund) | Phase 3 | Phase 4 extends with real disbursement tracking; existing `payout` type now maps to actual fund movement |
| `disputes` | Phase 3 | Active disputes hold funds from those specific orders |
| `DltPaymentService` | Phase 3 | Extended with disbursement methods (new DLT Disbursement API) |
| `conversations` / `conversation_threads` | Phase 2 | No changes — reviews are not threaded into conversations |
| `payment_attempts` | Phase 3 | Referenced in buyer payment history |
| Typesense search indexes | Phase 1 | Updated to include rating data for sort/filter |
| Platform commission config | Phase 3 (placeholder) | Formalized in Phase 4 with transaction + commission fees |

### 2.1 DLT Disbursement API — open item

The DLT Collection API v3.0 (currently integrated for inbound payments) does **not** document disbursement/payout endpoints. DLT is assumed to provide a separate Disbursement API for bank transfers to sellers, but the exact contract (endpoints, fields, authentication, webhooks) is **TBD — pending DLT documentation**.

**Phase 4 design will**:
- Define the application-layer disbursement service interface (`DltDisbursementService`) with the methods needed.
- Model the `payout_disbursements` table to track attempts/status regardless of the underlying gateway.
- Flag the DLT Disbursement API integration as a **blocking dependency** for the payout-release feature — implementation cannot ship without the API contract.

### 2.2 Clarification: Phase 3's notional "payout" entry vs. Phase 4's real disbursement

**Resolved.** Earlier drafts of this document proposed changing `ConfirmReceiptAction`/the auto-accept job to stop writing a `payout` ledger entry at order completion. **That change is not needed and is dropped.** Phase 3's behavior stays exactly as shipped:

- Phase 3 §4.5: on confirm-receipt (explicit or 7-day auto-accept), a `payout`-type `ledger_entries` row is written immediately and the order becomes `completed`. This row is **notional** — it reflects that the buyer confirmed and the platform now recognizes the amount as the seller's for accounting/reporting purposes. It is **not** a real fund movement; no bank transfer has happened. The only real money at this point is what DLT's Collection API already collected from the buyer, tracked by the order's `escrow_hold` entry.
- Phase 3 §4.8: a dispute can still be opened at any time, with no cutoff — this is unchanged.

Given that, Phase 4 does not need to reinterpret or gate this ledger row at all. Instead:

1. **Eligibility source of truth is `payout_items`, not `ledger_entries`.** An order is eligible for a Phase 4 payout batch when it's `completed` **and has no existing `payout_items` row** (i.e., hasn't already been included in a real disbursement). Phase 3's `payout` ledger entry existing for every completed order is irrelevant to this check — it was never meant to indicate real disbursement, and Phase 4 introduces the real signal alongside it rather than replacing it. `payout_items.ledger_entry_id` continues to reference the order's `escrow_hold` entry for the amount, exactly as originally designed.
2. **The 7-day dispute-hold window (§3.2 rule 3) is a new, additional gate Phase 4 introduces before *real* disbursement.** It doesn't conflict with or narrow Phase 3's "no cutoff" dispute rule — a buyer can still open a dispute at any time; the window only controls whether an admin can pull that order into a payout batch yet.
3. **Late disputes after real disbursement: off-platform settlement, no clawback.** This is exactly the existing non-goal in §1.4 ("if funds are already disbursed, buyer and seller settle off-platform") and the out-of-scope row "Rolling seller balance / clawback for late disputes" — confirmed as-is, no rolling-balance/netting mechanism to build.
4. **All payout batch creation and release remains admin-triggered, manual, per-seller** (§3.2, §4) — confirmed as-is, no scheduled/automatic release in Phase 4.
5. **Reporting note**: §3.4's Seller Earnings & Payouts view lists `ledger_entries` (`escrow_hold`, `payout`, `refund`) in its transaction list for context, but its summary figures (`total_earned`, `total_released`, `pending_balance`) already compute from `payout_items`/`payouts`, per §3.6 — not from the ledger's `payout` type. Worth calling out explicitly in §3.4 so nobody is tempted to sum `ledger_entries.type = 'payout'` as "amount released," since that would double-count against real disbursement totals.

No migration, backfill, or Phase 3 code change is required. This subsection is now informational context for engineers, not a blocking dependency.

---

## 3. Feature Specifications

---

### 3.1 Seller Payout Account Registration

*Goals: G1 (real disbursements need a real bank account on file), G3 (an unverified account is itself a release-blocking condition).*

#### Description & user stories

- **As a seller**, I want to register my bank account details so that the platform can disburse my earnings to me.
- **As a seller**, I want to update my payout account if my banking details change.
- **As an admin**, I want to see which sellers have registered payout accounts and verify them before releasing funds.

#### Business rules

- A seller must have an `active` shop to register a payout account.
- Only one payout account per shop (replaceable, not additive).
- Required fields: bank name, account holder name, account number, branch (optional). Bank selection from a curated list of Philippine banks.
- Payout account has a verification status: `pending` (newly registered/updated) → `verified` (admin-confirmed) → `rejected` (admin flags an issue).
- Funds cannot be released to an unverified payout account.
- When a seller updates their account details, the status resets to `pending` (requires re-verification).

#### Data model

**`seller_payout_accounts`**

| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| shop_id | BIGINT FK → shops, UNIQUE | One account per shop |
| bank_name | VARCHAR(150) | From curated list |
| bank_code | VARCHAR(30) | Standardized code for DLT API |
| account_holder_name | VARCHAR(255) | |
| account_number | VARCHAR(50) | Encrypted at rest |
| branch | VARCHAR(150) | Optional |
| verification_status | ENUM('pending','verified','rejected') | |
| verified_at | TIMESTAMP | |
| verified_by_id | BIGINT FK → users | Admin who verified |
| rejection_reason | TEXT | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

#### API/endpoint changes

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/hub/payout-account` | Get seller's payout account |
| PUT | `/api/v1/hub/payout-account` | Create or update payout account |
| GET | `/api/v1/admin/payout-accounts` | List all seller payout accounts (filterable by status) |
| POST | `/api/v1/admin/payout-accounts/{id}/verify` | Admin verifies account |
| POST | `/api/v1/admin/payout-accounts/{id}/reject` | Admin rejects account (with reason) |

---

### 3.2 Payout Lifecycle & Disbursement

*Goals: G1 (this is the feature that actually moves money), G2 (batching + traceability), G7 (fees are deducted here).*

#### Description & user stories

- **As an admin**, I want to see which orders have funds ready for release, grouped by seller.
- **As an admin**, I want to release payouts to sellers in batches, disbursing real funds to their bank accounts.
- **As an admin**, I want to see the status of each disbursement attempt (succeeded/failed/pending).
- **As a seller**, I want to see my total releasable balance and which orders comprise it.
- **As a seller**, I want to see my payout history — each batch that was released, when, and for how much.

#### Business rules

- Payouts are **admin-initiated only** — no seller self-service, no automatic scheduled release (admin triggers manually per the configured rules).
- A payout batch groups one or more eligible `ledger_entries` (type = `escrow_hold`) from completed orders for a single seller into one disbursement.
- Each payout batch must reference all constituent `ledger_entries` so that every order's fund release is traceable.
- **Two fees deducted at payout time** (not at escrow-hold time):
  - **Transaction fee**: flat per-transaction amount (e.g., ₱15 per order in the batch).
  - **Commission fee**: percentage of order total (e.g., 3% of each order's escrowed amount).
  - Both are configurable at the platform level (see §3.3).
  - Net payout = sum(escrowed amounts) − sum(transaction fees) − sum(commission fees).
- Payout disbursement is attempted via DLT Disbursement API.
- On **success**: a `payout_items` row is created for each constituent order (referencing its `escrow_hold` entry — see §2.2), the `payouts` batch is marked `completed`, and a `payout_disbursements` row records the DLT confirmation. This is the authoritative "already disbursed" record — Phase 3's earlier, notional `payout` ledger entry from order completion is left untouched.
- On **failure**: the payout batch is marked `failed`; admin resolves manually (reviews error, corrects account details or retries).
- Once disbursed, funds are non-reversible. If a dispute arises on a previously-paid-out order, buyer and seller settle off-platform (see §2.2, point 3).

#### Payout eligibility rules (all must be true per order)

1. Order status is `completed`, **and the order has no existing `payout_items` row** (i.e., it hasn't already been included in a payout batch). This is the sole "already disbursed" check — Phase 3's `payout`-type `ledger_entries` row, written for every completed order regardless of real disbursement, is not part of this check (see §2.2).
2. No active dispute on the order (status NOT IN `open`, `awaiting_return`, `return_received`, `escalated`).
3. Dispute window has passed: at least **7 days** since `completed_at` with no dispute opened. This is a new admin-side hold Phase 4 introduces before an order can be pulled into a payout batch — it doesn't change when a buyer can open a dispute (Phase 3's no-cutoff rule is unchanged), only when the order becomes payout-eligible.
4. The seller's payout account is `verified`.
5. The cumulative releasable balance for the seller meets the **minimum payout threshold** (configurable, default ₱500).

#### State machine — Payout batch

```
[*] → pending: admin creates batch from eligible orders
pending → processing: disbursement submitted to DLT
processing → completed: DLT confirms successful transfer
processing → failed: DLT returns error / timeout
failed → processing: admin retries
completed → [*]
```

#### Data model

**`payouts`** (batch-level record)

| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| shop_id | BIGINT FK → shops | |
| status | ENUM('pending','processing','completed','failed') | |
| currency | CHAR(3) | Default 'PHP' |
| gross_amount | NUMERIC(12,2) | Sum of escrowed amounts |
| total_transaction_fees | NUMERIC(12,2) | Sum of per-order transaction fees |
| total_commission_fees | NUMERIC(12,2) | Sum of per-order commission fees |
| net_amount | NUMERIC(12,2) | gross − transaction_fees − commission_fees |
| orders_count | INTEGER | Number of orders in batch |
| released_by_id | BIGINT FK → users | Admin who triggered release |
| released_at | TIMESTAMP | |
| completed_at | TIMESTAMP | DLT confirms success |
| failed_at | TIMESTAMP | |
| failure_reason | TEXT | DLT error message / manual notes |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**`payout_items`** (order-level breakdown within a batch)

| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| payout_id | BIGINT FK → payouts | |
| order_id | BIGINT FK → orders | |
| ledger_entry_id | BIGINT FK → ledger_entries | The `escrow_hold` entry being released |
| escrowed_amount | NUMERIC(12,2) | Original escrowed amount for this order |
| transaction_fee | NUMERIC(12,2) | Flat fee for this order |
| commission_fee | NUMERIC(12,2) | Percentage fee for this order |
| net_amount | NUMERIC(12,2) | escrowed − transaction_fee − commission_fee |
| created_at | TIMESTAMP | |

**`payout_disbursements`** (DLT API attempt log)

| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| payout_id | BIGINT FK → payouts | |
| status | ENUM('pending','succeeded','failed') | |
| dlt_reference_id | VARCHAR(100) | DLT's disbursement reference (if returned) |
| dlt_transaction_id | VARCHAR(50) | Our generated unique ID sent to DLT |
| amount | NUMERIC(12,2) | Net amount sent |
| gateway_response | JSONB | Full DLT response payload |
| attempted_at | TIMESTAMP | |
| succeeded_at | TIMESTAMP | |
| failed_at | TIMESTAMP | |
| created_at | TIMESTAMP | |

#### API/endpoint changes

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/admin/payouts` | List all payout batches (filterable by status, shop) |
| GET | `/api/v1/admin/payouts/eligible` | List sellers with eligible orders + releasable amounts |
| POST | `/api/v1/admin/payouts` | Create payout batch for a seller (specify order IDs or "all eligible") |
| POST | `/api/v1/admin/payouts/{payout}/release` | Trigger disbursement to DLT |
| POST | `/api/v1/admin/payouts/{payout}/retry` | Retry a failed disbursement |
| GET | `/api/v1/admin/payouts/{payout}` | Payout detail (items, disbursement history) |
| GET | `/api/v1/hub/payouts` | Seller's payout history |
| GET | `/api/v1/hub/payouts/{payout}` | Seller's payout detail |
| GET | `/api/v1/hub/payouts/balance` | Seller's releasable balance + eligible orders |

---

### 3.3 Payout Release Configuration

*Goals: G3 (this is where the release conditions live), G7 (fee rates are admin-configured here too).*

#### Description & user stories

- **As an admin**, I want to configure the payout release rules (hold period, minimum threshold, fee rates) so that the platform operates consistently.
- **As an admin**, I want to adjust fee rates without changing code.

#### Business rules

- Configuration is **platform-wide, admin-only** — sellers cannot customize their own payout schedule.
- Configurable settings:

| Setting | Default | Description |
|---|---|---|
| `dispute_hold_days` | 7 | Days after order `completed_at` before funds become eligible (dispute window) |
| `minimum_payout_threshold` | 500.00 | Minimum releasable balance (PHP) before a payout can be created |
| `transaction_fee_amount` | 15.00 | Flat fee per order in a payout batch (PHP) |
| `commission_fee_rate` | 0.03 | Percentage (3%) of each order's escrowed amount |

- Settings stored in a `payout_settings` table (key-value, admin-editable via UI).
- Provision for future: `commission_fee_rate` field supports per-plan override later (higher subscription plan → lower rate), but Phase 4 only uses the global value.
- Changes to settings apply to **future** payouts only — already-created/completed batches retain their original fee calculations.

#### Data model

**`payout_settings`**

| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| key | VARCHAR(50) UNIQUE | e.g. `dispute_hold_days`, `minimum_payout_threshold`, `transaction_fee_amount`, `commission_fee_rate` |
| value | VARCHAR(100) | Stored as string, cast by application |
| updated_by_id | BIGINT FK → users | Last admin who changed it |
| updated_at | TIMESTAMP | |

#### API/endpoint changes

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/admin/payout-settings` | Get all payout settings |
| PUT | `/api/v1/admin/payout-settings` | Update one or more settings |

---

### 3.4 Payment History

*Goal: G4.*

#### Description & user stories

- **As a buyer**, I want a dedicated transaction history page showing all my payments and refunds across orders.
- **As a seller**, I want a "Payouts & Earnings" page showing my total earnings, pending balance, released payouts, and refunds.
- **As an admin**, I want a platform-wide financial ledger showing all fund movements (escrow holds, payouts, refunds) with filtering and search.

#### Business rules

- **Buyer Payment History**: aggregates `payment_attempts` (succeeded) and `ledger_entries` (type = `refund`) across all the buyer's orders. Filterable by date range, status (paid/refunded), and amount range.
- **Seller Earnings & Payouts**: shows summary (total earned, total fees paid, total disbursed, pending balance) computed from `payout_items`/`payouts` per the definitions in §3.6 — **not** by summing `ledger_entries` where `type = 'payout'`, since that entry is written notionally at order completion (§2.2) and doesn't indicate real disbursement. The page's transaction list, separately, does show `ledger_entries` (`escrow_hold`, `payout`, `refund`) alongside `payout_items` for full context. Filterable by date range and order.
- **Admin Financial Ledger**: platform-wide view of all `ledger_entries` + `payouts` + `payout_disbursements`. Filterable by type, date range, seller, and status.
- All records are permanently retained (no TTL). Ledger entries remain append-only with no updates.
- All amounts displayed with currency code (PHP).

#### Data model

No new tables required — the views are built from existing `ledger_entries`, `payment_attempts`, `payouts`, and `payout_items` tables.

#### API/endpoint changes

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/transactions` | Buyer's payment history (cursor paginated, filterable) |
| GET | `/api/v1/hub/earnings` | Seller earnings summary + transaction list |
| GET | `/api/v1/admin/ledger` | Platform-wide financial ledger (cursor paginated, filterable) |

---

### 3.5 Reviews & Ratings

*Goal: G5.*

#### Description & user stories

- **As a buyer**, I want to rate and review the products I purchased after confirming receipt.
- **As a buyer**, I want to rate the seller (shop) after a completed order.
- **As a buyer**, I want to edit my reviews at any time.
- **As a seller**, I want to respond publicly to reviews on my products and shop.
- **As a buyer/seller**, I want to report inappropriate reviews for admin moderation.
- **As a visitor**, I want to see product ratings and seller ratings on the storefront to inform my decisions.
- **As a visitor**, I want to sort search results by rating.

#### Business rules

- **Reviewable entities**: Products (per order item) and Sellers (shops, per order).
- **Rating scale**: 1–5 stars (integer).
- **Eligibility**: buyer can only review items/shops from orders with status `completed` (receipt confirmed). Disputed/refunded orders are NOT reviewable.
- **One review per buyer per product per order**: a buyer may review the same product multiple times if purchased across different orders.
- **One review per buyer per shop per order**: similarly scoped.
- **Text is optional**: a buyer can submit a star rating without written text.
- **Edit window**: unlimited — buyers can edit their reviews at any time.
- **Seller responses**: a seller can post one public response per review. Editable at any time.
- **Reporting**: either party can report a review for admin moderation (flag + reason). Admin can hide a review (soft-delete) or dismiss the report.
- **No auto-moderation** in Phase 4 (no profanity filter, no minimum character count).
- **Impact on search**: aggregate seller rating (average stars) is indexed in Typesense and available as a sort option on the storefront. Product ratings are displayed on product cards/detail but do not alter search relevance ranking in Phase 4 (purely informational on products, sort-able on shops).
- **Display**: product detail pages show individual reviews with star rating, text, buyer name, date, and seller response (if any). Shop profile pages show the aggregate rating and recent reviews.

#### Data model

**`reviews`**

| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| order_id | BIGINT FK → orders | The order that grants eligibility |
| reviewer_user_id | BIGINT FK → users | The buyer |
| reviewable_type | VARCHAR(50) | 'product' or 'shop' |
| reviewable_id | BIGINT | FK to `products.id` or `shops.id` |
| rating | SMALLINT | 1–5 |
| title | VARCHAR(255) | Optional |
| body | TEXT | Optional (star-only reviews allowed) |
| is_hidden | BOOLEAN DEFAULT FALSE | Admin can hide flagged reviews |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Unique constraint: `(order_id, reviewer_user_id, reviewable_type, reviewable_id)` — one review per buyer per entity per order.

**`review_responses`**

| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| review_id | BIGINT FK → reviews, UNIQUE | One response per review |
| responder_user_id | BIGINT FK → users | The seller |
| body | TEXT | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**`review_reports`**

| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| review_id | BIGINT FK → reviews | |
| reporter_user_id | BIGINT FK → users | |
| reason | TEXT | |
| status | ENUM('pending','dismissed','actioned') | |
| resolved_by_id | BIGINT FK → users | Admin |
| resolved_at | TIMESTAMP | |
| created_at | TIMESTAMP | |

**Schema changes to existing tables:**

- `shops`: add `average_rating NUMERIC(3,2)` and `total_reviews INTEGER DEFAULT 0` (denormalized, updated on review create/edit/delete).
- `products`: add `average_rating NUMERIC(3,2)` and `total_reviews INTEGER DEFAULT 0` (denormalized).

#### API/endpoint changes

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/v1/orders/{order}/reviews` | Create review (product or shop) |
| PUT | `/api/v1/reviews/{review}` | Edit review |
| GET | `/api/v1/products/{product}/reviews` | List product reviews (public, paginated) |
| GET | `/api/v1/companies/{shop}/reviews` | List shop reviews (public, paginated) |
| POST | `/api/v1/reviews/{review}/respond` | Seller posts response |
| PUT | `/api/v1/review-responses/{response}` | Seller edits response |
| POST | `/api/v1/reviews/{review}/report` | Report a review |
| GET | `/api/v1/admin/review-reports` | Admin: list pending reports |
| POST | `/api/v1/admin/review-reports/{report}/dismiss` | Admin dismisses report |
| POST | `/api/v1/admin/review-reports/{report}/action` | Admin hides the review |

#### Search index changes

- Typesense `companies` collection: add `average_rating` (float) and `total_reviews` (int32) fields. Enable sorting by `average_rating:desc`.
- Typesense `products` collection: add `average_rating` (float) and `total_reviews` (int32) fields for display (not ranking weight in Phase 4).

---

### 3.6 Sales Analytics Expansion

*Goal: G6 (and reads on top of the G1/G2/G7 payout data and G5 rating data produced above).*

#### Description & user stories

- **As a seller**, I want to see my payout totals (earned, pending, released) in my hub reports.
- **As a seller**, I want to see my average review rating in my reports.
- **As an admin**, I want a platform-wide dashboard showing GMV, total payouts disbursed, and aggregate rating health.

#### Business rules

- **Seller Hub reports** (extends Phase 3 reports): add payout totals and review averages to the existing selectable-window reports.
- **Admin dashboard** (new): platform-wide metrics including GMV, total payouts, and rating averages.
- All metrics are computed at query time from existing tables (no separate materialized views in Phase 4).
- Dashboard-only (no CSV/PDF export).

#### New seller metrics (added to existing `GET /api/v1/hub/reports`)

| Metric | Computation |
|---|---|
| `total_earned` | Sum of `payout_items.net_amount` for completed payouts in window |
| `pending_balance` | Sum of eligible but not-yet-released escrowed amounts |
| `total_released` | Sum of `payouts.net_amount` WHERE status = 'completed' in window |
| `average_rating` | From denormalized `shops.average_rating` |
| `total_reviews` | From denormalized `shops.total_reviews` |

#### New admin metrics (new endpoint)

| Metric | Computation |
|---|---|
| `gmv` | Sum of `orders.order_total` WHERE status IN ('completed','refunded') in window |
| `total_payouts_disbursed` | Sum of `payouts.net_amount` WHERE status = 'completed' in window |
| `total_transaction_fees_collected` | Sum of `payouts.total_transaction_fees` in window |
| `total_commission_fees_collected` | Sum of `payouts.total_commission_fees` in window |
| `platform_average_rating` | AVG of all `shops.average_rating` WHERE total_reviews > 0 |
| `total_active_sellers` | Count of shops with status = 'active' |
| `total_orders` | Count of orders in window |

#### API/endpoint changes

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/admin/analytics` | Platform-wide metrics dashboard |

(Seller metrics are added to the existing `GET /api/v1/hub/reports` response.)

---

### 3.7 Seller Profit Calculator

*Goal: G7 (uses the same fee configuration sellers are otherwise only shown after the fact, so they can plan ahead of it).*

#### Description & user stories

- **As a seller**, I want to enter a sale price and immediately see the transaction fee, commission fee, and my resulting net payout, so I can price listings correctly instead of guessing at fees.
- **As a seller**, I want the calculator on an order's detail page to show me exactly what I will net (or already netted) for that order, without doing the math myself.
- **As a seller**, I don't want to memorize the current fee rates — the calculator should always reflect whatever the platform has configured right now.

#### Business rules

- Pure **projection tool** — it does not create, persist, or reference any `payout`/`payout_items` records for hypothetical inputs. It's a live calculation, not a ledger event.
- Formula mirrors the real payout calculation exactly (§3.2, §3.3), using the current `payout_settings` values:
  - `transaction_fee = transaction_fee_amount` (flat, per order)
  - `commission_fee = sale_price × commission_fee_rate`
  - `net_amount = sale_price − transaction_fee − commission_fee`
- Two entry points:
  - **Listing create/edit page**: seller types a candidate price and sees estimated net proceeds live, before publishing.
  - **Order detail / Payouts & Earnings page (§3.4)**: pre-filled with the order's actual `escrowed_amount`, showing the same math — for a `completed` order not yet in a `payout_items` row this is still a projection (rates could change before real disbursement, per the "changes apply to future payouts only" rule in §3.3); for an order already in `payout_items`, the page shows the **actual** recorded `transaction_fee`/`commission_fee`/`net_amount` from that row rather than recomputing, so the numbers never drift from what was really disbursed.
- Does **not** factor in the 7-day dispute hold, minimum payout threshold, or account verification status (§3.2 rules 3–5) — those affect *when* money moves, not *how much*. The calculator only answers "how much would I net," so nothing here implies payout eligibility.
- No history or persistence of past calculator inputs — stateless per request.

#### API/endpoint changes

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/hub/fee-rates` | Seller-readable current `transaction_fee_amount` and `commission_fee_rate` (read-only subset of `payout_settings` — no seller access to `dispute_hold_days`/`minimum_payout_threshold` needed here, though those aren't sensitive either) |

No new tables — reads the existing `payout_settings` table via a seller-scoped endpoint (the existing `/api/v1/admin/payout-settings` stays admin-only). Calculation itself can run client-side once rates are fetched, or server-side if given a `sale_price` query param — either is acceptable; client-side is simpler given there's no persistence.

---

| Item | Reason |
|---|---|
| Automatic scheduled payouts (cron-driven release) | Admin-only release in Phase 4; scheduled automation deferred |
| Seller-initiated payout requests | Not needed — admin controls release |
| Subscription plans / tiered commission | Data model provisioned but only flat rate in Phase 4 |
| Rolling seller balance / clawback for late disputes | Once disbursed, funds are final; off-platform settlement |
| Review media (photos/videos) | Text + stars only in Phase 4 |
| Buyer purchase analytics | Seller + admin only |
| Report exports (CSV/PDF) | Dashboard-only |
| DLT webhook callbacks for disbursement status | Depends on DLT Disbursement API contract (TBD) |
| Multi-currency payouts | PHP only |
| Payout scheduling UI (calendar/recurrence) | Admin releases manually |
| Review gamification / badges | Purely informational ratings |
| AI-powered review summarization | Future enhancement |

---

## 5. Rollout & Testing Considerations

### 5.1 Feature dependencies (implementation order)

```
1. Seller Payout Account Registration (no gateway dependency)
2. Payout Settings (admin config)
3. Reviews & Ratings (no gateway dependency)
4. Payment History views (reads from existing data — build the `payout_items`-based
   sourcing per §2.2/§3.4 so summary figures don't accidentally sum the ledger's
   notional `payout` type)
5. Sales Analytics expansion (reads from existing data)
6. Payout Lifecycle & Disbursement (BLOCKED on DLT Disbursement API contract)
7. Seller Profit Calculator (depends only on item 2's `payout_settings` /
   new `GET /api/v1/hub/fee-rates` endpoint — can ship as early as right after item 2)
```

Items 1–5 can ship independently of the DLT Disbursement API. Item 6 is the only feature blocked on external coordination.

### 5.2 Testing strategy

| Feature | Test approach |
|---|---|
| Payout account registration | Feature tests: CRUD, verification flow, reset-on-update |
| Payout eligibility rules | Unit tests: each condition (hold days, threshold, dispute hold, verification) independently and in combination |
| Payout batch creation | Feature tests: correct order grouping, fee calculation, net amount |
| Disbursement | Mocked DLT service (success/failure); integration test against DLT sandbox when available |
| Payment history | Feature tests: correct aggregation, filtering, pagination |
| Reviews | Feature tests: eligibility enforcement, one-per-entity-per-order, edit, seller response, report flow, rating denormalization |
| Search index updates | Feature tests: rating fields appear in Typesense documents after review |
| Analytics | Feature tests: correct metric computation for a known dataset |
| Fees calculation | Unit tests: edge cases (zero orders, single order at threshold, max precision) |

### 5.3 Migration safety

- New tables (`seller_payout_accounts`, `payouts`, `payout_items`, `payout_disbursements`, `payout_settings`, `reviews`, `review_responses`, `review_reports`) are purely additive — no existing table alterations beyond adding `average_rating` and `total_reviews` columns to `shops` and `products`.
- The `average_rating` and `total_reviews` columns should default to `NULL` / `0` respectively and be backfilled only if historical review data needs to be seeded.
- `payout_settings` should be seeded with defaults on migration.

### 5.4 Security considerations

- `seller_payout_accounts.account_number` must be encrypted at rest (Laravel's `encrypted` cast or database-level encryption).
- Payout account details visible only to the owning seller and admins — never exposed via public API or storefront.
- Payout release actions (`/admin/payouts/*/release`) require explicit admin permission (`payout.release`).
- Review eligibility is server-enforced (not just UI-gated) — the API must reject reviews for non-completed orders regardless of what the client sends.
- Rate limiting on review creation: 10 per hour per user (prevent spam).

---

## 6. Open Questions & Risks

1. **DLT Disbursement API contract**: The exact endpoints, authentication, request/response schemas, and error codes for DLT's disbursement service are not yet documented in any available reference. This is a **blocking dependency** for the payout release feature. Mitigation: build the application layer (service interface, tables, admin UI, eligibility logic) now; wire up DLT integration once the API docs are available.

2. **Bank account verification process**: Is admin verification purely visual (admin reviews the details and clicks "verify"), or does DLT provide a micro-deposit / account validation API? Assumed visual-only for Phase 4.

3. **Fee rounding**: When commission_fee_rate (3%) produces fractional centavos, round to 2 decimal places using PHP's `ROUND_HALF_UP`. Document this for financial reconciliation.

4. **Review spam / fake reviews**: Phase 4 has no auto-moderation. If review spam becomes a problem, add rate limiting tighter than 10/hour, or introduce a minimum-order-value threshold for review eligibility. Flagged for monitoring post-launch.

5. **Rating denormalization consistency**: `shops.average_rating` and `products.average_rating` are denormalized. If a review is hidden (admin action) or edited, these must be recomputed. Use a queued job (`RecalculateRatingsJob`) dispatched on review create/update/hide events to avoid blocking the request.

6. **Payout account changes mid-batch**: If a seller updates their bank details while a payout is `pending` (not yet sent to DLT), the system should use the **latest verified** account at disbursement time, not the account captured at batch creation. If the new account is `pending` verification, the disbursement should be blocked until re-verified.

7. **Platform fee structure evolution**: The two-fee model (transaction + commission) is designed to support future subscription tiers where `commission_fee_rate` varies by plan. The `payout_settings` table stores the global default; a future `shop_payout_overrides` table (keyed by `shop_id`) would allow per-seller rate customization. Not built in Phase 4 but the architecture accommodates it.

8. **Phase 3's notional `payout` ledger entry vs. Phase 4's real disbursement (see §2.2) — resolved, documented here to prevent regression.** Phase 3 writes a `payout`-type `ledger_entries` row immediately at order completion; this is notional (accounting recognition only) and not a real fund movement. Phase 4 does not modify this behavior. The risk to guard against during implementation is purely a reporting one: any engineer computing "amount released to seller" must source it from `payout_items`/`payouts`, never from summing `ledger_entries.type = 'payout'`, or seller earnings will read as far higher than what's actually been disbursed. Covered in §3.4's business rules and the §3.6 metrics table.

---

## 7. Appendix — Glossary

| Term | Meaning |
|---|---|
| **Payout** | A batch disbursement of funds from the platform's escrow to a seller's bank account |
| **Payout batch** | One or more orders' escrowed funds grouped into a single bank transfer |
| **Disbursement** | The actual transfer of funds to the seller's bank account via DLT |
| **Transaction fee** | Flat per-order charge deducted from the seller's earnings at payout time |
| **Commission fee** | Percentage-based charge on each order's escrowed amount, deducted at payout time |
| **Releasable balance** | Sum of escrowed funds from eligible orders (completed, past dispute window, no active dispute) that haven't yet been paid out |
| **Dispute window** | Configurable period (default 7 days) after order completion during which a dispute can be opened before funds become releasable |
| **GMV** | Gross Merchandise Value — total value of orders transacted on the platform |
| **Denormalized rating** | Pre-computed average rating stored on `shops`/`products` for fast query/display, recomputed on review changes |