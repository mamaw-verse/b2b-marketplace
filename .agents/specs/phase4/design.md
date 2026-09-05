# design.md
## Technical Design Document — Phase 4: Payouts, Reviews & Analytics

| | |
|---|---|
| **Companion docs** | `Phase 1 design.md`, `Phase 2 design.md`, `Phase 3 design.md`, `Phase 4 requirements.md` |
| **Author role** | Lead Systems Architect / Principal UI Engineer |
| **Stack** | PHP/Laravel · Livewire · Flux UI · Tailwind CSS · Redis · PostgreSQL · Typesense · Minio/S3 |
| **Payment Gateway** | DLT (Digital Treasures Corp) — Collection API v3.0 (Phase 3) + Disbursement API (TBD, Phase 4) |
| **Status** | Draft for engineering review |

---

## 1. Phase 4 Context & Integration Points

### 1.1 How Phase 4 extends the existing system

Phase 1 delivered **discovery**. Phase 2 delivered **negotiation**. Phase 3 delivered **orders, payments, fulfilment, and disputes**. Phase 4 completes the transactional lifecycle with:

1. **Real disbursements** — move escrowed funds from platform escrow to sellers' bank accounts via DLT Disbursement API.
2. **Payout lifecycle** — batch-level payout management with fee deduction, admin-triggered release, and full traceability.
3. **Reviews & ratings** — buyer-submitted star ratings + text reviews on products and shops, surfaced on storefront and search.
4. **Payment history** — dedicated transaction-history views for buyers, sellers, and admins.
5. **Sales analytics expansion** — platform-wide GMV, payout totals, rating health metrics.
6. **Seller profit calculator** — real-time fee projection tool so sellers can price listings correctly.

The monolith topology is unchanged. Phase 4 adds new tables, enum types, routes, Livewire components, jobs, and a DLT Disbursement service interface within the existing infrastructure.

### 1.2 Existing tables/services touched

| Existing entity | Phase 4 interaction |
|---|---|
| `shops` | Add `average_rating` and `total_reviews` columns; FK target for `seller_payout_accounts`, `payouts` |
| `products` | Add `average_rating` and `total_reviews` columns |
| `orders` | FK source for `payout_items`, `reviews` (eligibility check via `completed` status + `completed_at`) |
| `ledger_entries` | FK target for `payout_items.ledger_entry_id` (the `escrow_hold` entry); displayed in payment history views |
| `payment_attempts` | Displayed in buyer payment history (succeeded attempts) |
| `disputes` | Checked for active status during payout eligibility evaluation |
| `DltPaymentService` | Extended with a sibling `DltDisbursementService` for outbound fund transfers |
| Typesense indexes | `companies` + `products` collections gain `average_rating` and `total_reviews` fields |
| Redis queue | New jobs: `RecalculateRatingsJob`, `ProcessPayoutDisbursementJob`, `IndexShopJob` (updated) |
| `users` | FK source for admin actions (verified_by, released_by, reviewer, responder, reporter) |

### 1.3 Key architectural decisions

- **Payout eligibility is sourced from `payout_items`, not `ledger_entries`.** Phase 3 writes a notional `payout`-type `ledger_entries` row at order completion — this is accounting recognition only. Real disbursement is tracked by `payout_items` + `payouts` tables introduced in Phase 4.
- **All payouts are admin-triggered.** No automatic/scheduled release in Phase 4.
- **Fees deducted at payout time, not at escrow-hold time.** This preserves full amounts in escrow for potential refund scenarios.
- **Rating denormalization** — `shops.average_rating` and `products.average_rating` are recomputed asynchronously via `RecalculateRatingsJob` to avoid blocking request lifecycle.
- **DLT Disbursement API is a blocking dependency** for the payout-release feature. The application layer (service interface, tables, admin UI, eligibility logic) is built now; the DLT wire-up happens once the API contract is available.

---

## 2. Database Schema — Phase 4 Additions

### 2.1 New Enum Types

```sql
-- ========================= PHASE 4 ENUM TYPES =========================

CREATE TYPE payout_account_verification_status AS ENUM (
    'pending',
    'verified',
    'rejected'
);

CREATE TYPE payout_status AS ENUM (
    'pending',
    'processing',
    'completed',
    'failed'
);

CREATE TYPE payout_disbursement_status AS ENUM (
    'pending',
    'succeeded',
    'failed'
);

CREATE TYPE review_report_status AS ENUM (
    'pending',
    'dismissed',
    'actioned'
);
```

### 2.2 New Tables (full DDL)

```sql
-- ========================= SELLER PAYOUT ACCOUNTS =========================
CREATE TABLE seller_payout_accounts (
    id                      BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    shop_id                 BIGINT NOT NULL REFERENCES shops(id) ON DELETE RESTRICT,
    bank_name               VARCHAR(150) NOT NULL,
    bank_code               VARCHAR(30) NOT NULL,
    account_holder_name     VARCHAR(255) NOT NULL,
    account_number          VARCHAR(500) NOT NULL,   -- Encrypted at rest (Laravel encrypted cast)
    branch                  VARCHAR(150),
    verification_status     payout_account_verification_status NOT NULL DEFAULT 'pending',
    verified_at             TIMESTAMP(0),
    verified_by_id          BIGINT REFERENCES users(id) ON DELETE RESTRICT,
    rejection_reason        TEXT,
    created_at              TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at              TIMESTAMP(0) NOT NULL DEFAULT now(),

    CONSTRAINT uq_seller_payout_accounts_shop UNIQUE (shop_id)
);

CREATE INDEX idx_seller_payout_accounts_status ON seller_payout_accounts(verification_status);
CREATE INDEX idx_seller_payout_accounts_verified_by ON seller_payout_accounts(verified_by_id)
    WHERE verified_by_id IS NOT NULL;

-- ========================= PAYOUT SETTINGS =========================
CREATE TABLE payout_settings (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    key             VARCHAR(50) NOT NULL,
    value           VARCHAR(100) NOT NULL,
    updated_by_id   BIGINT REFERENCES users(id) ON DELETE RESTRICT,
    updated_at      TIMESTAMP(0) NOT NULL DEFAULT now(),

    CONSTRAINT uq_payout_settings_key UNIQUE (key)
);

-- Seed defaults
INSERT INTO payout_settings (key, value) VALUES
    ('dispute_hold_days', '7'),
    ('minimum_payout_threshold', '500.00'),
    ('transaction_fee_amount', '15.00'),
    ('commission_fee_rate', '0.03');

-- ========================= PAYOUTS (batch-level) =========================
CREATE TABLE payouts (
    id                      BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    shop_id                 BIGINT NOT NULL REFERENCES shops(id) ON DELETE RESTRICT,
    status                  payout_status NOT NULL DEFAULT 'pending',
    currency                CHAR(3) NOT NULL DEFAULT 'PHP',
    gross_amount            NUMERIC(12,2) NOT NULL,
    total_transaction_fees  NUMERIC(12,2) NOT NULL,
    total_commission_fees   NUMERIC(12,2) NOT NULL,
    net_amount              NUMERIC(12,2) NOT NULL,
    orders_count            INTEGER NOT NULL,
    released_by_id          BIGINT REFERENCES users(id) ON DELETE RESTRICT,
    released_at             TIMESTAMP(0),
    completed_at            TIMESTAMP(0),
    failed_at               TIMESTAMP(0),
    failure_reason          TEXT,
    created_at              TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at              TIMESTAMP(0) NOT NULL DEFAULT now()
);

CREATE INDEX idx_payouts_shop ON payouts(shop_id);
CREATE INDEX idx_payouts_status ON payouts(status);
CREATE INDEX idx_payouts_released_by ON payouts(released_by_id)
    WHERE released_by_id IS NOT NULL;
CREATE INDEX idx_payouts_completed_at ON payouts(completed_at)
    WHERE completed_at IS NOT NULL;

-- ========================= PAYOUT ITEMS (order-level breakdown) =========================
CREATE TABLE payout_items (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    payout_id           BIGINT NOT NULL REFERENCES payouts(id) ON DELETE RESTRICT,
    order_id            BIGINT NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
    ledger_entry_id     BIGINT NOT NULL REFERENCES ledger_entries(id) ON DELETE RESTRICT,
    escrowed_amount     NUMERIC(12,2) NOT NULL,
    transaction_fee     NUMERIC(12,2) NOT NULL,
    commission_fee      NUMERIC(12,2) NOT NULL,
    net_amount          NUMERIC(12,2) NOT NULL,
    created_at          TIMESTAMP(0) NOT NULL DEFAULT now()
    -- No updated_at — payout items are immutable once created
);

CREATE INDEX idx_payout_items_payout ON payout_items(payout_id);
CREATE INDEX idx_payout_items_order ON payout_items(order_id);
CREATE INDEX idx_payout_items_ledger_entry ON payout_items(ledger_entry_id);
-- Enforce: an order can only appear in one payout batch
CREATE UNIQUE INDEX idx_payout_items_order_unique ON payout_items(order_id);

-- ========================= PAYOUT DISBURSEMENTS (DLT attempt log) =========================
CREATE TABLE payout_disbursements (
    id                      BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    payout_id               BIGINT NOT NULL REFERENCES payouts(id) ON DELETE RESTRICT,
    status                  payout_disbursement_status NOT NULL DEFAULT 'pending',
    dlt_reference_id        VARCHAR(100),
    dlt_transaction_id      VARCHAR(50) NOT NULL,
    amount                  NUMERIC(12,2) NOT NULL,
    gateway_response        JSONB,
    attempted_at            TIMESTAMP(0),
    succeeded_at            TIMESTAMP(0),
    failed_at               TIMESTAMP(0),
    created_at              TIMESTAMP(0) NOT NULL DEFAULT now()
    -- No updated_at — append new rows for retries; status is set once at resolution
);

CREATE INDEX idx_payout_disbursements_payout ON payout_disbursements(payout_id);
CREATE INDEX idx_payout_disbursements_status ON payout_disbursements(status);
CREATE UNIQUE INDEX idx_payout_disbursements_dlt_txn ON payout_disbursements(dlt_transaction_id);

-- ========================= REVIEWS =========================
CREATE TABLE reviews (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id            BIGINT NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
    reviewer_user_id    BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    reviewable_type     VARCHAR(50) NOT NULL,   -- 'product' or 'shop'
    reviewable_id       BIGINT NOT NULL,         -- FK to products.id or shops.id
    rating              SMALLINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title               VARCHAR(255),
    body                TEXT,
    is_hidden           BOOLEAN NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP(0) NOT NULL DEFAULT now(),

    -- One review per buyer per entity per order
    CONSTRAINT uq_reviews_per_order UNIQUE (order_id, reviewer_user_id, reviewable_type, reviewable_id)
);

CREATE INDEX idx_reviews_reviewable ON reviews(reviewable_type, reviewable_id);
CREATE INDEX idx_reviews_reviewer ON reviews(reviewer_user_id);
CREATE INDEX idx_reviews_order ON reviews(order_id);
CREATE INDEX idx_reviews_visible ON reviews(reviewable_type, reviewable_id, is_hidden)
    WHERE is_hidden = FALSE;

-- ========================= REVIEW RESPONSES =========================
CREATE TABLE review_responses (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    review_id           BIGINT NOT NULL REFERENCES reviews(id) ON DELETE CASCADE,
    responder_user_id   BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    body                TEXT NOT NULL,
    created_at          TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP(0) NOT NULL DEFAULT now(),

    -- One response per review
    CONSTRAINT uq_review_responses_review UNIQUE (review_id)
);

CREATE INDEX idx_review_responses_responder ON review_responses(responder_user_id);

-- ========================= REVIEW REPORTS =========================
CREATE TABLE review_reports (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    review_id           BIGINT NOT NULL REFERENCES reviews(id) ON DELETE CASCADE,
    reporter_user_id    BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    reason              TEXT NOT NULL,
    status              review_report_status NOT NULL DEFAULT 'pending',
    resolved_by_id      BIGINT REFERENCES users(id) ON DELETE RESTRICT,
    resolved_at         TIMESTAMP(0),
    created_at          TIMESTAMP(0) NOT NULL DEFAULT now()
    -- No updated_at — reports transition once to resolved state
);

CREATE INDEX idx_review_reports_review ON review_reports(review_id);
CREATE INDEX idx_review_reports_status ON review_reports(status)
    WHERE status = 'pending';
CREATE INDEX idx_review_reports_reporter ON review_reports(reporter_user_id);
```

### 2.3 Schema Changes to Existing Tables

```sql
-- Add rating denormalization columns to shops
ALTER TABLE shops
    ADD COLUMN average_rating NUMERIC(3,2),
    ADD COLUMN total_reviews INTEGER NOT NULL DEFAULT 0;

-- Add rating denormalization columns to products
ALTER TABLE products
    ADD COLUMN average_rating NUMERIC(3,2),
    ADD COLUMN total_reviews INTEGER NOT NULL DEFAULT 0;
```

### 2.4 Relationship Summary (Phase 4 new relationships)

| Relationship | Cardinality |
|---|---|
| `shops` → `seller_payout_accounts` | 1:0..1 (one account per shop, replaceable) |
| `shops` → `payouts` | 1:N (seller receives many payout batches) |
| `payouts` → `payout_items` | 1:N (batch contains multiple order payouts) |
| `payouts` → `payout_disbursements` | 1:N (multiple attempts per batch on retry) |
| `orders` → `payout_items` | 1:0..1 (an order appears in at most one payout batch) |
| `ledger_entries` → `payout_items` | 1:0..1 (escrow_hold entry linked to its payout) |
| `orders` → `reviews` | 1:N (buyer may review product + shop per order) |
| `users` → `reviews` | 1:N (buyer submits many reviews across orders) |
| `reviews` → `review_responses` | 1:0..1 (seller posts one response) |
| `reviews` → `review_reports` | 1:N (multiple users can report) |

### 2.5 Schema Design Decisions

- **`seller_payout_accounts.account_number` stored as VARCHAR(500)**: the encrypted ciphertext is larger than the plaintext. Laravel's `encrypted` cast is used at the model layer — the column stores the encrypted value, never plaintext.
- **`payout_items` has no `updated_at`**: like `ledger_entries`, payout items are immutable once created. A failed batch creates new disbursement attempts, not modifications to items.
- **`payout_disbursements` has no `updated_at`**: each attempt is a new row. Status is set once upon resolution (succeeded/failed).
- **`ON DELETE RESTRICT` on all financial tables**: `seller_payout_accounts`, `payouts`, `payout_items`, `payout_disbursements`, `ledger_entries` references, `reviews` (for audit trail). Financial/audit records must never silently vanish.
- **`ON DELETE CASCADE` on `review_responses` (child of `reviews`) and `review_reports` (child of `reviews`)**: if a review is hard-deleted by admin, responses and reports follow. However, in practice, reviews are soft-hidden (`is_hidden = TRUE`), not deleted.
- **`reviewable_type` + `reviewable_id` polymorphic**: avoids two separate review tables. Laravel's morphTo/morphMany handles this cleanly with a constrained morph map.
- **Unique index `idx_payout_items_order_unique`**: DB-level enforcement that an order cannot be included in two payout batches, eliminating race conditions in concurrent admin actions.
- **`payout_settings` key-value table**: simple and extensible. Application casts values to appropriate types (integer, float, decimal) based on key. Changes are audited via `updated_by_id`.

---

## 3. DLT Disbursement Service

### 3.1 Architecture overview

```
Admin browser ──► [Livewire PayoutReleasePage]
                       │
                       │ releasePayout($payoutId)
                       ▼
               [ReleasePayoutAction]
                       │
                       │ DB transaction: validate eligibility, lock payout,
                       │ set status=processing, create disbursement row
                       ▼
               [ProcessPayoutDisbursementJob] ── dispatched after commit
                       │
                       │ DltDisbursementService::submitDisbursement()
                       ▼
               DLT Disbursement API (TBD endpoint)
                       │ returns reference_id + status
                       ▼
               Update payout_disbursements.status
               Update payouts.status (completed/failed)
                       │
                       ▼
              Seller notified of payout completion / failure
```

### 3.2 `DltDisbursementService` — Interface Design

Located at `app/Services/DltDisbursementService.php`. This is the **application-layer interface** designed ahead of the DLT Disbursement API contract. Methods define what the application needs; the implementation will be wired once DLT provides endpoint documentation.

```php
<?php

namespace App\Services;

use App\Models\Payout;
use App\Models\PayoutDisbursement;
use App\Models\SellerPayoutAccount;

/**
 * Service interface for DLT Disbursement API integration.
 *
 * BLOCKING DEPENDENCY: The DLT Disbursement API contract (endpoints, fields,
 * authentication, webhooks) is TBD. This service defines the application-layer
 * methods needed. Implementation will be completed once the API docs are available.
 *
 * Authentication is assumed to use the same OAuth2 client-credentials flow
 * as the DLT Collection API (shared base_url, client_id, client_secret).
 */
final class DltDisbursementService
{
    private const TOKEN_CACHE_KEY = 'dlt:disbursement_bearer_token';

    public function __construct(
        private readonly \Illuminate\Http\Client\Factory $http,
        private readonly \Illuminate\Contracts\Cache\Repository $cache,
    ) {}

    /**
     * Get Bearer token for disbursement API.
     * May share the same token as Collection API or require separate credentials.
     *
     * @return string Bearer access token (cached)
     */
    public function getBearerToken(): string
    {
        // TODO: Implement once DLT Disbursement API auth is confirmed.
        // Assumed same OAuth2 flow: POST /oauth/token with client_credentials grant.
        throw new \RuntimeException('DLT Disbursement API not yet available.');
    }

    /**
     * Submit a disbursement request to transfer funds to a seller's bank account.
     *
     * @param Payout $payout The payout batch to disburse
     * @param SellerPayoutAccount $account The verified bank account to send to
     * @param string $transactionId Our generated unique reference (alphanumeric only)
     *
     * @return array{
     *     success: bool,
     *     reference_id: string|null,
     *     status: string,
     *     message: string|null,
     *     raw_response: array
     * }
     */
    public function submitDisbursement(
        Payout $payout,
        SellerPayoutAccount $account,
        string $transactionId,
    ): array {
        // TODO: Implement once DLT Disbursement API contract is available.
        // Expected fields to send:
        //   - merchant_account_id
        //   - merchant_transaction_id (our $transactionId)
        //   - amount (net_amount as string decimal)
        //   - currency (PHP)
        //   - bank_code (from $account)
        //   - account_number (decrypted from $account)
        //   - account_holder_name (from $account)
        //   - branch (optional, from $account)
        throw new \RuntimeException('DLT Disbursement API not yet available.');
    }

    /**
     * Check the status of a previously submitted disbursement.
     * Used for polling/retry logic if webhooks are not available.
     *
     * @param string $transactionId Our merchant_transaction_id
     *
     * @return array{
     *     status: string,
     *     reference_id: string|null,
     *     completed_at: string|null,
     *     raw_response: array
     * }
     */
    public function getDisbursementStatus(string $transactionId): array
    {
        // TODO: Implement once DLT Disbursement API contract is available.
        throw new \RuntimeException('DLT Disbursement API not yet available.');
    }
}
```

### 3.3 Configuration

Added to `config/services.php` alongside the existing `dlt` collection config:

```php
'dlt_disbursement' => [
    'base_url'            => env('DLT_DISBURSEMENT_BASE_URL', env('DLT_BASE_URL', 'https://checkout.dxp.dtic.com.ph')),
    'client_id'           => env('DLT_DISBURSEMENT_CLIENT_ID', env('DLT_CLIENT_ID')),
    'client_secret'       => env('DLT_DISBURSEMENT_CLIENT_SECRET', env('DLT_CLIENT_SECRET')),
    'merchant_account_id' => env('DLT_DISBURSEMENT_MERCHANT_ACCOUNT_ID', env('DLT_MERCHANT_ACCOUNT_ID')),
    'service_code'        => env('DLT_DISBURSEMENT_SERVICE_CODE', 'APN-DISBURSEMENT'),
],
```

Falls back to the existing DLT Collection credentials if separate disbursement credentials are not issued. Separate env vars allow independent configuration once DLT confirms whether disbursement uses the same merchant account.

### 3.4 Transaction ID generation

Per the existing DLT integration rule: `merchant_transaction_id` must be **alphanumeric only** (no hyphens/dashes).

```php
// Pattern: "payout" + payout_id + "d" + disbursement attempt number
// e.g., "payout42d1", "payout42d2" (retry)
$transactionId = 'payout' . $payout->id . 'd' . $attemptNumber;
```

### 3.5 Failure handling

- On DLT error/timeout: `payout_disbursements.status = 'failed'`, store full response in `gateway_response`, set `failed_at`.
- `payouts.status` transitions to `failed`, `failed_at` set, `failure_reason` populated with DLT error message.
- Admin can retry: creates a new `payout_disbursements` row (new attempt), transitions `payouts.status` back to `processing`.
- No automatic retries — all retry decisions are admin-initiated.

---

## 4. API Contracts — Phase 4 Endpoints

All endpoints follow existing conventions: base path `/api/v1`, Laravel Sanctum auth (session cookies for browser, PAT for external), cursor-based pagination (`?cursor=<opaque>&limit=20`), standard error envelope `{ "error": { "code": "...", "message": "...", "fields": {...} } }`.

### 4.1 Seller Payout Account

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| GET | `/api/v1/hub/payout-account` | seller | Get seller's payout account |
| PUT | `/api/v1/hub/payout-account` | seller | Create or update payout account |
| GET | `/api/v1/admin/payout-accounts` | admin | List all seller payout accounts |
| POST | `/api/v1/admin/payout-accounts/{id}/verify` | admin | Verify account |
| POST | `/api/v1/admin/payout-accounts/{id}/reject` | admin | Reject account |

**PUT `/api/v1/hub/payout-account`**
```json
// Request
{
  "bank_name": "BDO Unibank",
  "bank_code": "BDO",
  "account_holder_name": "Maslaha Foods Trading Corp.",
  "account_number": "001234567890",
  "branch": "Makati Main"
}

// Response 200 (create or update)
{
  "data": {
    "id": 1,
    "shop_id": 233,
    "bank_name": "BDO Unibank",
    "bank_code": "BDO",
    "account_holder_name": "Maslaha Foods Trading Corp.",
    "account_number_masked": "****7890",
    "branch": "Makati Main",
    "verification_status": "pending",
    "verified_at": null,
    "created_at": "2026-08-24T10:00:00Z",
    "updated_at": "2026-08-24T10:00:00Z"
  }
}
```

**GET `/api/v1/admin/payout-accounts?status=pending&limit=20`**
```json
// Response 200
{
  "data": [
    {
      "id": 1,
      "shop_id": 233,
      "shop_name": "Maslaha Foods Co.",
      "bank_name": "BDO Unibank",
      "bank_code": "BDO",
      "account_holder_name": "Maslaha Foods Trading Corp.",
      "account_number_masked": "****7890",
      "branch": "Makati Main",
      "verification_status": "pending",
      "created_at": "2026-08-24T10:00:00Z"
    }
  ],
  "meta": { "next_cursor": null, "prev_cursor": null, "per_page": 20 }
}
```

**POST `/api/v1/admin/payout-accounts/{id}/verify`**
```json
// Response 200
{
  "data": {
    "id": 1,
    "verification_status": "verified",
    "verified_at": "2026-08-24T12:00:00Z",
    "verified_by_id": 99
  }
}
```

**POST `/api/v1/admin/payout-accounts/{id}/reject`**
```json
// Request
{
  "rejection_reason": "Account holder name does not match registered business name on file."
}

// Response 200
{
  "data": {
    "id": 1,
    "verification_status": "rejected",
    "rejection_reason": "Account holder name does not match registered business name on file."
  }
}
```

### 4.2 Payouts (Admin)

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| GET | `/api/v1/admin/payouts` | admin | List all payout batches |
| GET | `/api/v1/admin/payouts/eligible` | admin | List sellers with eligible orders |
| POST | `/api/v1/admin/payouts` | admin | Create payout batch for a seller |
| GET | `/api/v1/admin/payouts/{payout}` | admin | Payout detail with items + disbursements |
| POST | `/api/v1/admin/payouts/{payout}/release` | admin | Trigger disbursement to DLT |
| POST | `/api/v1/admin/payouts/{payout}/retry` | admin | Retry a failed disbursement |

**GET `/api/v1/admin/payouts/eligible?limit=20`**
```json
// Response 200
{
  "data": [
    {
      "shop_id": 233,
      "shop_name": "Maslaha Foods Co.",
      "eligible_orders_count": 5,
      "releasable_balance": 125000.00,
      "payout_account_status": "verified",
      "oldest_eligible_order_completed_at": "2026-08-10T10:00:00Z"
    }
  ],
  "meta": { "next_cursor": null, "prev_cursor": null, "per_page": 20 }
}
```

**POST `/api/v1/admin/payouts`**
```json
// Request — all eligible orders for a seller
{
  "shop_id": 233,
  "order_ids": "all"
}

// Request — specific orders
{
  "shop_id": 233,
  "order_ids": [101, 102, 105]
}

// Response 201
{
  "data": {
    "id": 7,
    "shop_id": 233,
    "status": "pending",
    "currency": "PHP",
    "gross_amount": 125000.00,
    "total_transaction_fees": 75.00,
    "total_commission_fees": 3750.00,
    "net_amount": 121175.00,
    "orders_count": 5,
    "items": [
      {
        "order_id": 101,
        "escrowed_amount": 25000.00,
        "transaction_fee": 15.00,
        "commission_fee": 750.00,
        "net_amount": 24235.00
      }
    ],
    "created_at": "2026-08-24T12:30:00Z"
  }
}

// Response 422 — eligibility failure
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Some orders are not eligible for payout.",
    "fields": {
      "order_ids": ["Order #103 has an active dispute.", "Order #104 is within the dispute hold window."]
    }
  }
}
```

**POST `/api/v1/admin/payouts/{payout}/release`**
```json
// Response 200
{
  "data": {
    "id": 7,
    "status": "processing",
    "released_by_id": 99,
    "released_at": "2026-08-24T13:00:00Z"
  }
}

// Response 409 — payout account not verified
{
  "error": {
    "code": "CONFLICT",
    "message": "Cannot release payout: seller's payout account is not verified."
  }
}

// Response 503 — DLT unavailable
{
  "error": {
    "code": "SERVICE_UNAVAILABLE",
    "message": "DLT Disbursement API is not yet available. Payout release is blocked."
  }
}
```

### 4.3 Payouts (Seller)

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| GET | `/api/v1/hub/payouts` | seller | Seller's payout history |
| GET | `/api/v1/hub/payouts/{payout}` | seller | Payout detail |
| GET | `/api/v1/hub/payouts/balance` | seller | Releasable balance + eligible orders |

**GET `/api/v1/hub/payouts/balance`**
```json
// Response 200
{
  "data": {
    "releasable_balance": 125000.00,
    "pending_payout_amount": 0.00,
    "total_released": 450000.00,
    "eligible_orders": [
      {
        "order_id": 101,
        "order_total": 25000.00,
        "completed_at": "2026-08-10T10:00:00Z"
      }
    ],
    "payout_account_status": "verified"
  }
}
```

**GET `/api/v1/hub/payouts?limit=20`**
```json
// Response 200
{
  "data": [
    {
      "id": 5,
      "status": "completed",
      "gross_amount": 95000.00,
      "total_transaction_fees": 60.00,
      "total_commission_fees": 2850.00,
      "net_amount": 92090.00,
      "orders_count": 4,
      "completed_at": "2026-08-18T09:00:00Z",
      "created_at": "2026-08-17T14:00:00Z"
    }
  ],
  "meta": { "next_cursor": null, "prev_cursor": null, "per_page": 20 }
}
```

### 4.4 Payout Settings (Admin)

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| GET | `/api/v1/admin/payout-settings` | admin | Get all payout settings |
| PUT | `/api/v1/admin/payout-settings` | admin | Update one or more settings |

**GET `/api/v1/admin/payout-settings`**
```json
// Response 200
{
  "data": {
    "dispute_hold_days": 7,
    "minimum_payout_threshold": 500.00,
    "transaction_fee_amount": 15.00,
    "commission_fee_rate": 0.03
  }
}
```

**PUT `/api/v1/admin/payout-settings`**
```json
// Request (partial update supported)
{
  "transaction_fee_amount": 20.00,
  "commission_fee_rate": 0.025
}

// Response 200
{
  "data": {
    "dispute_hold_days": 7,
    "minimum_payout_threshold": 500.00,
    "transaction_fee_amount": 20.00,
    "commission_fee_rate": 0.025
  }
}
```

### 4.5 Payment History

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| GET | `/api/v1/transactions` | buyer | Buyer's payment history |
| GET | `/api/v1/hub/earnings` | seller | Seller earnings summary + transaction list |
| GET | `/api/v1/admin/ledger` | admin | Platform-wide financial ledger |

**GET `/api/v1/transactions?limit=20&from=2026-08-01&to=2026-08-24`**
```json
// Response 200
{
  "data": {
    "summary": {
      "total_paid": 450000.00,
      "total_refunded": 14000.00,
      "net_spent": 436000.00
    },
    "transactions": [
      {
        "id": 45,
        "type": "payment",
        "order_id": 101,
        "shop_name": "Maslaha Foods Co.",
        "amount": 25000.00,
        "currency": "PHP",
        "status": "succeeded",
        "date": "2026-08-10T09:30:00Z"
      },
      {
        "id": 12,
        "type": "refund",
        "order_id": 98,
        "shop_name": "Manila Spice Trading",
        "amount": 7000.00,
        "currency": "PHP",
        "status": "completed",
        "date": "2026-08-05T14:00:00Z"
      }
    ]
  },
  "meta": { "next_cursor": "eyJpZCI6MTJ9", "prev_cursor": null, "per_page": 20 }
}
```

**GET `/api/v1/hub/earnings?from=2026-08-01&to=2026-08-24`**
```json
// Response 200
{
  "data": {
    "summary": {
      "total_earned": 575000.00,
      "total_fees_paid": 20250.00,
      "total_released": 450000.00,
      "pending_balance": 125000.00
    },
    "transactions": [
      {
        "type": "escrow_hold",
        "order_id": 101,
        "amount": 25000.00,
        "note": "Escrow hold on payment",
        "date": "2026-08-10T09:30:00Z"
      },
      {
        "type": "payout_released",
        "payout_id": 5,
        "net_amount": 92090.00,
        "orders_count": 4,
        "date": "2026-08-18T09:00:00Z"
      }
    ]
  },
  "meta": { "next_cursor": "eyJpZCI6NDV9", "prev_cursor": null, "per_page": 20 }
}
```

**GET `/api/v1/admin/ledger?type=all&limit=20`**
```json
// Response 200
{
  "data": [
    {
      "id": 200,
      "type": "escrow_hold",
      "order_id": 101,
      "shop_name": "Maslaha Foods Co.",
      "buyer_name": "Amina Reyes",
      "amount": 25000.00,
      "note": "Escrow hold on payment",
      "date": "2026-08-10T09:30:00Z"
    },
    {
      "id": 201,
      "type": "payout",
      "order_id": 101,
      "shop_name": "Maslaha Foods Co.",
      "amount": 25000.00,
      "note": "Payout on completion",
      "date": "2026-08-17T10:00:00Z"
    }
  ],
  "meta": { "next_cursor": "eyJpZCI6MjAwfQ", "prev_cursor": null, "per_page": 20 }
}
```

### 4.6 Reviews & Ratings

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| POST | `/api/v1/orders/{order}/reviews` | buyer | Create review (product or shop) |
| PUT | `/api/v1/reviews/{review}` | buyer | Edit review |
| GET | `/api/v1/products/{product}/reviews` | public | List product reviews |
| GET | `/api/v1/companies/{shop}/reviews` | public | List shop reviews |
| POST | `/api/v1/reviews/{review}/respond` | seller | Post response |
| PUT | `/api/v1/review-responses/{response}` | seller | Edit response |
| POST | `/api/v1/reviews/{review}/report` | auth | Report a review |
| GET | `/api/v1/admin/review-reports` | admin | List pending reports |
| POST | `/api/v1/admin/review-reports/{report}/dismiss` | admin | Dismiss report |
| POST | `/api/v1/admin/review-reports/{report}/action` | admin | Hide the review |

**POST `/api/v1/orders/{order}/reviews`**
```json
// Request — product review
{
  "reviewable_type": "product",
  "reviewable_id": 42,
  "rating": 5,
  "title": "Excellent quality flour",
  "body": "Consistent grain size, perfect for our bakery operations. Will reorder."
}

// Response 201
{
  "data": {
    "id": 15,
    "order_id": 101,
    "reviewable_type": "product",
    "reviewable_id": 42,
    "rating": 5,
    "title": "Excellent quality flour",
    "body": "Consistent grain size, perfect for our bakery operations. Will reorder.",
    "reviewer": {
      "id": 67,
      "name": "Amina Reyes"
    },
    "created_at": "2026-08-24T14:00:00Z"
  }
}

// Response 409 — already reviewed
{
  "error": {
    "code": "CONFLICT",
    "message": "You have already reviewed this product for this order."
  }
}

// Response 403 — order not completed
{
  "error": {
    "code": "FORBIDDEN",
    "message": "Reviews can only be submitted for completed orders."
  }
}
```

**GET `/api/v1/products/{product}/reviews?limit=10&sort=recent`**
```json
// Response 200
{
  "data": [
    {
      "id": 15,
      "rating": 5,
      "title": "Excellent quality flour",
      "body": "Consistent grain size, perfect for our bakery operations. Will reorder.",
      "reviewer": { "id": 67, "name": "Amina Reyes" },
      "response": {
        "id": 8,
        "body": "Thank you for the kind words! We're glad the flour meets your standards.",
        "responder": { "id": 45, "name": "Maslaha Foods Co." },
        "created_at": "2026-08-25T09:00:00Z"
      },
      "created_at": "2026-08-24T14:00:00Z"
    }
  ],
  "meta": {
    "average_rating": 4.6,
    "total_reviews": 23,
    "rating_distribution": { "5": 12, "4": 7, "3": 2, "2": 1, "1": 1 },
    "next_cursor": "eyJpZCI6MTB9",
    "prev_cursor": null,
    "per_page": 10
  }
}
```

**POST `/api/v1/reviews/{review}/respond`**
```json
// Request
{
  "body": "Thank you for the kind words! We're glad the flour meets your standards."
}

// Response 201
{
  "data": {
    "id": 8,
    "review_id": 15,
    "body": "Thank you for the kind words! We're glad the flour meets your standards.",
    "created_at": "2026-08-25T09:00:00Z"
  }
}
```

### 4.7 Seller Profit Calculator

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| GET | `/api/v1/hub/fee-rates` | seller | Current fee rates for calculator |

**GET `/api/v1/hub/fee-rates`**
```json
// Response 200
{
  "data": {
    "transaction_fee_amount": 15.00,
    "commission_fee_rate": 0.03,
    "currency": "PHP"
  }
}
```

The calculation is performed client-side in Alpine.js once rates are fetched:
```
transaction_fee = transaction_fee_amount (flat)
commission_fee = sale_price × commission_fee_rate
net_amount = sale_price − transaction_fee − commission_fee
```

### 4.8 Sales Analytics (Admin)

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| GET | `/api/v1/admin/analytics` | admin | Platform-wide metrics |

**GET `/api/v1/admin/analytics?from=2026-08-01&to=2026-08-24`**
```json
// Response 200
{
  "data": {
    "gmv": 2500000.00,
    "total_payouts_disbursed": 1800000.00,
    "total_transaction_fees_collected": 2700.00,
    "total_commission_fees_collected": 54000.00,
    "platform_average_rating": 4.3,
    "total_active_sellers": 45,
    "total_orders": 180,
    "total_completed_orders": 142,
    "window": { "from": "2026-08-01", "to": "2026-08-24" }
  }
}
```

### 4.9 Seller Reports Extension

The existing `GET /api/v1/hub/reports` response (Phase 3) gains additional fields:

```json
// Additional fields in GET /api/v1/hub/reports response
{
  "data": {
    // ... existing Phase 3 fields ...
    "total_earned": 575000.00,
    "pending_balance": 125000.00,
    "total_released": 450000.00,
    "average_rating": 4.6,
    "total_reviews": 23
  }
}
```

### 4.10 Routes Summary

```php
// ========================= PHASE 4 API ROUTES =========================
// All under /api/v1, Sanctum-authenticated unless noted

// Seller — Payout Account
Route::get    ('hub/payout-account',             [SellerPayoutAccountController::class, 'show']);
Route::put    ('hub/payout-account',             [SellerPayoutAccountController::class, 'upsert']);

// Seller — Payouts
Route::get    ('hub/payouts',                    [SellerPayoutController::class, 'index']);
Route::get    ('hub/payouts/balance',            [SellerPayoutController::class, 'balance']);
Route::get    ('hub/payouts/{payout}',           [SellerPayoutController::class, 'show']);

// Seller — Fee Rates (profit calculator)
Route::get    ('hub/fee-rates',                  [FeeRateController::class, 'index']);

// Seller — Earnings
Route::get    ('hub/earnings',                   [SellerEarningsController::class, 'index']);

// Buyer — Payment History
Route::get    ('transactions',                   [BuyerTransactionController::class, 'index']);

// Buyer — Reviews
Route::post   ('orders/{order}/reviews',         [ReviewController::class, 'store']);
Route::put    ('reviews/{review}',               [ReviewController::class, 'update']);
Route::post   ('reviews/{review}/report',        [ReviewReportController::class, 'store']);

// Seller — Review Responses
Route::post   ('reviews/{review}/respond',       [ReviewResponseController::class, 'store']);
Route::put    ('review-responses/{response}',    [ReviewResponseController::class, 'update']);

// Public — Reviews (no auth required)
Route::get    ('products/{product}/reviews',     [ProductReviewController::class, 'index']);
Route::get    ('companies/{shop}/reviews',       [ShopReviewController::class, 'index']);

// Admin — Payout Accounts
Route::get    ('admin/payout-accounts',                    [AdminPayoutAccountController::class, 'index'])
    ->middleware('role:admin');
Route::post   ('admin/payout-accounts/{account}/verify',   [AdminPayoutAccountController::class, 'verify'])
    ->middleware('role:admin');
Route::post   ('admin/payout-accounts/{account}/reject',   [AdminPayoutAccountController::class, 'reject'])
    ->middleware('role:admin');

// Admin — Payouts
Route::get    ('admin/payouts',                  [AdminPayoutController::class, 'index'])
    ->middleware('role:admin');
Route::get    ('admin/payouts/eligible',         [AdminPayoutController::class, 'eligible'])
    ->middleware('role:admin');
Route::post   ('admin/payouts',                  [AdminPayoutController::class, 'store'])
    ->middleware('role:admin');
Route::get    ('admin/payouts/{payout}',         [AdminPayoutController::class, 'show'])
    ->middleware('role:admin');
Route::post   ('admin/payouts/{payout}/release', [AdminPayoutController::class, 'release'])
    ->middleware('role:admin');
Route::post   ('admin/payouts/{payout}/retry',   [AdminPayoutController::class, 'retry'])
    ->middleware('role:admin');

// Admin — Payout Settings
Route::get    ('admin/payout-settings',          [AdminPayoutSettingsController::class, 'index'])
    ->middleware('role:admin');
Route::put    ('admin/payout-settings',          [AdminPayoutSettingsController::class, 'update'])
    ->middleware('role:admin');

// Admin — Review Reports
Route::get    ('admin/review-reports',                       [AdminReviewReportController::class, 'index'])
    ->middleware('role:admin');
Route::post   ('admin/review-reports/{report}/dismiss',      [AdminReviewReportController::class, 'dismiss'])
    ->middleware('role:admin');
Route::post   ('admin/review-reports/{report}/action',       [AdminReviewReportController::class, 'action'])
    ->middleware('role:admin');

// Admin — Analytics
Route::get    ('admin/analytics',                [AdminAnalyticsController::class, 'index'])
    ->middleware('role:admin');

// Admin — Ledger
Route::get    ('admin/ledger',                   [AdminLedgerController::class, 'index'])
    ->middleware('role:admin');
```

---

## 5. UI Component Architecture — Phase 4 Additions

### 5.1 Component hierarchy

```
resources/views/livewire/
│
├── account/                                  (BUYER additions)
│   ├── transactions/
│   │   └── payment-history.blade.php         [Payment history: payments + refunds, filterable by date/status]
│   └── reviews/
│       └── review-form.blade.php             [Create/edit review — star picker, title, body]
│
├── company-hub/                              (SELLER additions)
│   ├── payouts/
│   │   ├── payout-account.blade.php          [Bank account registration/update form]
│   │   ├── payout-list.blade.php             [Payout history — status, amounts, dates]
│   │   ├── payout-detail.blade.php           [Single payout breakdown — items, fees, disbursement status]
│   │   └── balance-summary.blade.php         [Releasable balance card + eligible orders list]
│   ├── earnings/
│   │   └── earnings-dashboard.blade.php      [Summary figures + transaction timeline]
│   ├── reviews/
│   │   ├── review-list.blade.php             [All reviews for seller's shop/products]
│   │   └── review-response-form.blade.php    [Respond to a review — inline form]
│   └── calculator/
│       └── profit-calculator.blade.php       [Alpine.js interactive fee calculator widget]
│
├── admin/                                    (ADMIN additions)
│   ├── payouts/
│   │   ├── payout-accounts-list.blade.php    [All seller payout accounts — verify/reject actions]
│   │   ├── payout-queue.blade.php            [Eligible sellers + create batch + release actions]
│   │   ├── payout-detail.blade.php           [Batch detail — items, disbursement log, retry action]
│   │   └── payout-settings.blade.php         [Configure hold days, threshold, fee rates]
│   ├── reviews/
│   │   └── review-reports.blade.php          [Pending review reports — dismiss/action]
│   ├── analytics/
│   │   └── analytics-dashboard.blade.php     [Platform-wide GMV, payouts, ratings, order metrics]
│   └── ledger/
│       └── financial-ledger.blade.php        [Platform-wide ledger view with filters]
│
├── storefront/                               (PUBLIC additions — reviews on existing pages)
│   ├── product-reviews.blade.php             [Reviews section embedded on product detail page]
│   └── shop-reviews.blade.php               [Reviews section embedded on shop profile page]
│
└── shared/                                   (SHARED additions)
    ├── star-rating.blade.php                 [Reusable star rating display (1–5 stars, supports half)]
    ├── star-picker.blade.php                 [Interactive star picker for review forms]
    └── rating-summary.blade.php             [Rating distribution bar chart + average display]
```

### 5.2 Requirement → Component Mapping

| PRD Requirement | UI Component(s) | Notes |
|---|---|---|
| §3.1 — Payout account registration | `company-hub/payouts/payout-account` | Bank dropdown from curated list; account_number masked after save; verification badge |
| §3.2 — Payout history (seller) | `company-hub/payouts/payout-list`, `payout-detail` | Status badges (pending/processing/completed/failed); fee breakdown per item |
| §3.2 — Releasable balance | `company-hub/payouts/balance-summary` | Card showing balance, eligible order count, payout account status |
| §3.2 — Admin payout creation | `admin/payouts/payout-queue` | Table of eligible sellers; "Create Batch" action per row; confirm modal with fee preview |
| §3.2 — Admin payout release | `admin/payouts/payout-detail` | "Release" button (requires `payout.release` permission); retry on failure |
| §3.3 — Payout settings | `admin/payouts/payout-settings` | Form with numeric inputs; save confirmation |
| §3.4 — Buyer payment history | `account/transactions/payment-history` | Filterable table: date range, status (paid/refunded), amount range |
| §3.4 — Seller earnings | `company-hub/earnings/earnings-dashboard` | Summary cards + scrollable transaction timeline |
| §3.4 — Admin ledger | `admin/ledger/financial-ledger` | Full ledger with type/date/seller/status filters |
| §3.5 — Create review | `account/reviews/review-form` | Star picker + optional title/body; shown on order detail after completion |
| §3.5 — Product reviews (public) | `storefront/product-reviews` | Embedded section on existing product detail page; rating distribution + paginated reviews |
| §3.5 — Shop reviews (public) | `storefront/shop-reviews` | Embedded section on existing shop profile; aggregate badge + recent reviews |
| §3.5 — Seller response | `company-hub/reviews/review-response-form` | Inline respond/edit form below each review in seller's review list |
| §3.5 — Review moderation | `admin/reviews/review-reports` | Pending reports queue with dismiss/hide-review actions |
| §3.6 — Admin analytics | `admin/analytics/analytics-dashboard` | Metric cards (GMV, payouts, fees, ratings); date range selector |
| §3.7 — Profit calculator | `company-hub/calculator/profit-calculator` | Alpine.js reactive: input price → live net calculation; embedded on listing create/edit and earnings page |

### 5.3 State management (Phase 4 additions)

| State type | Where it lives | Mechanism |
|---|---|---|
| Payout account form | PostgreSQL + Livewire props | Standard Livewire form binding; account_number never sent back to client (masked) |
| Profit calculator | Client-only (Alpine.js) | Fetches fee rates once from `GET /hub/fee-rates`; all calculation is local, no persistence |
| Star rating picker | Client-only (Alpine.js) | `x-data="{ rating: 0 }"` with hover/click handlers; bound to Livewire prop via `wire:model` on submit |
| Payout status (real-time) | PostgreSQL | No real-time push needed — admin refreshes manually; seller views update on page load |
| Review submission | PostgreSQL + Livewire | Form submission dispatches `RecalculateRatingsJob` after commit |
| Analytics metrics | PostgreSQL (query-time) | Computed on each request; no caching in Phase 4 (revisit if performance is a concern) |

### 5.4 Profit Calculator — Alpine.js Implementation Notes

The profit calculator is a stateless, client-side widget embedded as a Livewire component that fetches fee rates on mount:

```blade
{{-- company-hub/calculator/profit-calculator.blade.php --}}
<div
    x-data="{
        salePrice: 0,
        transactionFee: {{ $transactionFeeAmount }},
        commissionRate: {{ $commissionFeeRate }},
        get commissionFee() { return Math.round(this.salePrice * this.commissionRate * 100) / 100 },
        get netAmount() { return Math.max(0, this.salePrice - this.transactionFee - this.commissionFee) }
    }"
>
    {{-- Input + live output display --}}
</div>
```

For orders already in `payout_items`, the component displays the **actual recorded** fees (not recomputed) to match what was really disbursed.

---

## 6. Background Jobs & Events — Phase 4

### 6.1 New Jobs

| Job | Queue | Trigger | Logic |
|---|---|---|---|
| `RecalculateRatingsJob` | `default` | Dispatched after review create/update/hide | Recomputes `average_rating` and `total_reviews` on the `reviewable` entity (shop or product). Uses `ROUND(AVG(rating), 2)` excluding hidden reviews. Dispatches `IndexShopJob`/`IndexProductJob` if the entity is a shop/product respectively. |
| `ProcessPayoutDisbursementJob` | `default` | Dispatched after `ReleasePayoutAction` commits | Calls `DltDisbursementService::submitDisbursement()`. On success: updates `payout_disbursements.status = succeeded`, `payouts.status = completed`. On failure: updates statuses to `failed`. |
| `IndexShopJob` (updated) | `default` | Existing trigger + after `RecalculateRatingsJob` | Extended to include `average_rating` and `total_reviews` in the Typesense document payload for the `companies` collection. |
| `IndexProductJob` (updated) | `default` | Existing trigger + after `RecalculateRatingsJob` | Extended to include `average_rating` and `total_reviews` in the Typesense document payload for the `products` collection. |

### 6.2 Events & Listeners

| Event | Trigger | Listeners / Actions |
|---|---|---|
| `PayoutAccountUpdated` | Seller creates/updates payout account | Reset `verification_status` to `pending` if details changed; notify admin of new pending verification |
| `PayoutAccountVerified` | Admin verifies account | Dispatch `PayoutAccountVerifiedNotification` to seller (email) |
| `PayoutAccountRejected` | Admin rejects account | Dispatch `PayoutAccountRejectedNotification` to seller (email, includes reason) |
| `PayoutBatchCreated` | Admin creates payout batch | No immediate notification (batch is still `pending`) |
| `PayoutReleased` | Admin triggers release | Dispatches `ProcessPayoutDisbursementJob`; log released_at |
| `PayoutCompleted` | `ProcessPayoutDisbursementJob` succeeds | Dispatch `PayoutCompletedNotification` to seller (email: net amount + bank masked) |
| `PayoutFailed` | `ProcessPayoutDisbursementJob` fails | Dispatch `PayoutFailedNotification` to admin (email: error details for investigation) |
| `ReviewCreated` | Buyer submits review | Dispatch `RecalculateRatingsJob` for the reviewable entity; dispatch `ReviewReceivedNotification` to seller |
| `ReviewUpdated` | Buyer edits review | Dispatch `RecalculateRatingsJob` for the reviewable entity |
| `ReviewHidden` | Admin hides flagged review | Dispatch `RecalculateRatingsJob` for the reviewable entity; dispatch `ReviewHiddenNotification` to reviewer |

### 6.3 RecalculateRatingsJob — Implementation Detail

```php
<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Recalculates denormalized rating fields on a reviewable entity.
 * Dispatched on review create, update, or hide.
 */
final class RecalculateRatingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $reviewableType,
        public readonly int $reviewableId,
    ) {}

    public function handle(): void
    {
        $model = match ($this->reviewableType) {
            'product' => Product::find($this->reviewableId),
            'shop' => Shop::find($this->reviewableId),
            default => null,
        };

        if (! $model) {
            return;
        }

        $stats = $model->reviews()
            ->where('is_hidden', false)
            ->selectRaw('ROUND(AVG(rating)::numeric, 2) as avg_rating, COUNT(*) as total')
            ->first();

        $model->update([
            'average_rating' => $stats->avg_rating,
            'total_reviews' => $stats->total,
        ]);

        // Re-index for search
        if ($this->reviewableType === 'shop') {
            IndexShopJob::dispatch($model);
        } else {
            IndexProductJob::dispatch($model);
        }
    }
}
```

### 6.4 Typesense Index Changes

**`companies` collection** — add fields:

| Field | Type | Facet | Sort |
|---|---|---|---|
| `average_rating` | `float` | No | Yes — `sort_by=average_rating:desc` |
| `total_reviews` | `int32` | No | Yes |

**`products` collection** — add fields:

| Field | Type | Facet | Sort |
|---|---|---|---|
| `average_rating` | `float` | No | No (display only in Phase 4) |
| `total_reviews` | `int32` | No | No |

The existing `SyncSearchIndexJob` (Phase 1, runs every 5 minutes) will naturally pick up the new fields on its next reconciliation pass.

---

## 7. Security & Authorization — Phase 4 Additions

### 7.1 New Policies

| Policy | Model | Key rules |
|---|---|---|
| `SellerPayoutAccountPolicy` | `SellerPayoutAccount` | `view`/`upsert`: user owns the shop associated with the account; `viewAny`/`verify`/`reject`: admin role |
| `PayoutPolicy` | `Payout` | `viewAny (admin)`: admin role; `view (seller)`: payout belongs to user's shop; `create`/`release`/`retry`: admin role + `payout.release` permission |
| `ReviewPolicy` | `Review` | `create`: user is the buyer on a `completed` order with no active dispute AND no existing review for this entity+order; `update`: user is the reviewer (unlimited edit window); `respond`: user owns the shop that the review is about; `report`: any authenticated user; `hide`: admin role |
| `ReviewResponsePolicy` | `ReviewResponse` | `create`: seller owns the shop; one response per review (enforced by unique constraint); `update`: user is the responder |
| `ReviewReportPolicy` | `ReviewReport` | `create`: any authenticated user; `dismiss`/`action`: admin role |
| `PayoutSettingsPolicy` | `PayoutSetting` | `viewAny`/`update`: admin role |

### 7.2 New Permissions (Spatie)

| Permission | Description | Assigned to |
|---|---|---|
| `payout-account.verify` | Verify seller payout accounts | admin |
| `payout-account.reject` | Reject seller payout accounts | admin |
| `payout.create` | Create payout batches | admin |
| `payout.release` | Trigger disbursement to DLT | admin |
| `payout.retry` | Retry failed disbursements | admin |
| `payout-settings.update` | Modify payout configuration | admin |
| `review-report.manage` | Dismiss/action review reports | admin |
| `analytics.view` | View platform-wide analytics | admin |
| `ledger.view` | View platform-wide financial ledger | admin |

### 7.3 Rate Limiting

| Endpoint group | Limit | Rationale |
|---|---|---|
| Review creation (`POST /orders/{order}/reviews`) | 10 per hour per user | Prevent review spam |
| Review reporting (`POST /reviews/{review}/report`) | 5 per hour per user | Prevent report flooding |
| Payout account update (`PUT /hub/payout-account`) | 5 per hour per user | Prevent abuse |
| Fee rates (`GET /hub/fee-rates`) | 60 per minute per user | Calculator widget may poll |
| Admin payout release (`POST /admin/payouts/{payout}/release`) | 10 per hour per user | Guard against accidental rapid-fire releases |
| Analytics / Reports | 60 per minute per user | Standard query endpoints |

### 7.4 Data Privacy & Encryption

- **`seller_payout_accounts.account_number`**: stored encrypted using Laravel's `encrypted` cast. Never returned in API responses — only a masked version (`****` + last 4 digits) is exposed.
- **Payout account details**: visible only to the owning seller and admins. Never exposed via public API, storefront, or search indexes.
- **Review author identity**: reviewer name (first_name + last initial) is public on storefront. Full user details are not exposed.
- **Financial amounts in seller payouts**: visible only to the specific seller and admins. Cross-seller financial data is never exposed.

### 7.5 Financial Integrity

All payout-creation operations (batch creation with fee calculation) must be executed in a single DB transaction with row-level locks on constituent orders to prevent double-inclusion:

```php
DB::transaction(function () use ($shopId, $orderIds) {
    // Lock eligible orders to prevent concurrent batch creation
    $orders = Order::whereIn('id', $orderIds)
        ->where('shop_id', $shopId)
        ->lockForUpdate()
        ->get();

    // Validate: no existing payout_items for these orders
    $alreadyPaid = PayoutItem::whereIn('order_id', $orderIds)->exists();
    if ($alreadyPaid) {
        throw new ConflictException('One or more orders are already in a payout batch.');
    }

    // ... create payout + payout_items with fee calculations ...
});
// dispatch events AFTER transaction commits
```

### 7.6 Fee Calculation Rounding

When `commission_fee_rate` produces fractional centavos, round to 2 decimal places using `ROUND_HALF_UP`:

```php
$commissionFee = round($escrowedAmount * $commissionRate, 2, PHP_ROUND_HALF_UP);
$netAmount = $escrowedAmount - $transactionFee - $commissionFee;
```

This is documented for financial reconciliation. The same rounding is applied consistently in batch creation and the profit calculator.

### 7.7 Input Validation

| Endpoint | Key validations |
|---|---|
| `PUT /hub/payout-account` | `bank_name` required, max 150, must be in curated bank list; `bank_code` required, max 30; `account_holder_name` required, max 255; `account_number` required, max 50 (plaintext); `branch` optional, max 150 |
| `POST /admin/payouts` | `shop_id` required, must exist; `order_ids` required, either `"all"` or array of valid order IDs belonging to that shop |
| `POST /admin/payouts/{payout}/release` | Payout must be `pending`; seller's payout account must be `verified` |
| `POST /admin/payout-accounts/{id}/reject` | `rejection_reason` required, max 1000 |
| `PUT /admin/payout-settings` | Each key must be a recognized setting; `dispute_hold_days` integer 1–90; `minimum_payout_threshold` numeric 0–1000000; `transaction_fee_amount` numeric 0–10000; `commission_fee_rate` numeric 0–1 (decimal percentage) |
| `POST /orders/{order}/reviews` | `reviewable_type` in `['product','shop']`; `reviewable_id` must exist and belong to the order; `rating` integer 1–5; `title` optional max 255; `body` optional max 5000 |
| `POST /reviews/{review}/respond` | `body` required, max 5000 |
| `POST /reviews/{review}/report` | `reason` required, max 2000 |

---

## 8. Cross-References

| Document | What it provides |
|---|---|
| `Phase 1 design.md` §1 | Full system architecture (topology, data flow, deployment) |
| `Phase 1 design.md` §2 | Base schema (`users`, `shops`, `products`, `media`, `categories`) |
| `Phase 1 design.md` §3 | API conventions, auth, error envelope format, pagination |
| `Phase 2 design.md` §2 | Phase 2 schema (`buyer_profiles`, `rfqs`, `quotations`, `conversations`, `threads`, `messages`) |
| `Phase 3 design.md` §2 | Phase 3 schema (`orders`, `order_items`, `fulfilments`, `disputes`, `ledger_entries`, `payment_attempts`) |
| `Phase 3 design.md` §3 | DLT Payment Gateway integration (Collection API, Quick Link flow, `DltPaymentService`) |
| `Phase 3 design.md` §7 | Business logic actions (`ConfirmReceiptAction`, `ResolveDisputeAction`) — Phase 4 payout eligibility depends on these completed actions |
| `Phase 3 design.md` §8 | Background jobs (`OrderAutoAcceptJob`) — 7-day auto-accept sets `completed_at` which starts the Phase 4 dispute-hold window |
| `Phase 3 requirements.md` §4.8 | Dispute lifecycle — no dispute cutoff; Phase 4's hold window is an additional gate before disbursement, not a change to when buyers can dispute |
| `Phase 4 requirements.md` §2.2 | Clarification: Phase 3's notional `payout` ledger entry vs Phase 4's real disbursement — resolved, no code change needed |
| `Phase 4 requirements.md` §3.2 | Payout eligibility rules (all 5 conditions documented) |
| `Phase 4 requirements.md` §5.1 | Implementation order — items 1–5 ship independently; item 6 blocked on DLT Disbursement API |
| DLT Payment API skill | `/.kiro/skills/dlt-payment-api/SKILL.md` — Collection API v3.0 reference; disbursement endpoints TBD |
| `.ai/rules/services.md` | DLT integration quirks (alphanumeric merchant_transaction_id, amount formatting, etc.) |
| `.ai/rules/livewire.md` | External URL redirect pattern (Alpine.js dispatch, not `$this->redirect()`) |
| `.ai/rules/views.md` | Design token system (CSS variables, never raw Tailwind colors) |
| `.ai/rules/views-livewire.md` | Order detail page layout structure, timeline/stepper pattern |
