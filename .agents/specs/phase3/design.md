# design.md
## Technical Design Document — Phase 3: Orders & Payments

| | |
|---|---|
| **Companion docs** | `Phase 1 design.md`, `Phase 2 design.md`, `Phase 3 requirements.md` |
| **Author role** | Lead Systems Architect / Principal UI Engineer |
| **Stack** | PHP/Laravel · Livewire · Flux UI · Tailwind CSS · Redis · PostgreSQL · Reverb · Typesense · Minio/S3 |
| **Payment Gateway** | DLT (Digital Treasures Corp) Collection API v3.0 — Quick Link hosted checkout |
| **Status** | Draft for engineering review |

---

## 1. Phase 3 Context & Integration Points

### 1.1 How Phase 3 extends the existing system

Phase 1 delivered discovery; Phase 2 delivered negotiation (RFQ, quotation, chat). Phase 3 closes the loop: **orders, payments, fulfilment, disputes, and the Seller Hub**. The monolith topology is unchanged — Phase 3 adds new tables, routes, Livewire components, jobs, broadcast channels, and a DLT payment gateway integration within the existing infrastructure.

### 1.2 Payment gateway decision — DLT Quick Link

The requirements originally described a fully simulated gateway. However, the DLT Payment Collection API v3.0 is available as a real Philippine payment gateway (base URL: `https://checkout.dxp.dtic.com.ph/`), supporting card and e-wallet/APN payments (GCash, etc.) via OAuth2 client-credentials auth. **Phase 3 integrates DLT via the Quick Link hosted checkout path**, which:

- Requires no PCI-sensitive card input on our servers (DLT hosts the payment form).
- Supports all payment brands (card + GCash + other e-wallets) without per-brand branching logic on our side.
- Needs only: `POST /api/v1/quick-link/session` to create a checkout session → redirect buyer to the hosted URL → DLT calls back to our `success_url`/`failure_url`.

This replaces the "simulated gateway" described in the PRD with a real integration at the same API-contract boundary; no other PRD requirements change.

### 1.3 Existing tables/services touched

| Existing entity | Phase 3 interaction |
|---|---|
| `users` | Referenced as buyer (`orders.buyer_user_id`) and seller (via `shops`) |
| `shops` | Referenced as seller on `orders`; new "Customers" surface for standalone order creation |
| `quotations` | `orders.quotation_id` nullable FK; awarded quotation seeds order draft; one-order-per-awarded-quotation enforced at app layer |
| `rfq_recipients` | No schema change; buyer_snapshot on awarded quotation is the default for order |
| `conversation_threads` | Gains optional `order_id` nullable FK alongside existing `rfq_id` |
| `messages` | No change; order thread uses existing message pipeline |
| `media` | Reused for any order/dispute attachments — same upload + `ScanUploadedFileJob` pipeline |
| Redis queue | New jobs dispatched on existing queue infrastructure |
| Laravel Scheduler | New daily scheduled jobs added |
| Reverb | New private channel `order.{id}` for order-thread real-time updates |

---

## 2. Database Schema — Phase 3 Additions

### 2.1 New Enum Types

```sql
-- ========================= PHASE 3 ENUM TYPES =========================

CREATE TYPE order_status AS ENUM (
    'draft',
    'pending_payment',
    'paid',
    'completed',
    'cancelled',
    'refunded'
);

CREATE TYPE fulfilment_status AS ENUM (
    'pending',
    'shipped',
    'delivered',
    'returned_to_seller'
);

CREATE TYPE payment_attempt_status AS ENUM (
    'pending',
    'succeeded',
    'failed'
);

CREATE TYPE payment_term_type AS ENUM ('full');
-- Future: extend with 'deposit', 'partial'

CREATE TYPE ledger_entry_type AS ENUM (
    'escrow_hold',
    'payout',
    'refund'
);

CREATE TYPE dispute_type AS ENUM (
    'return_refund',
    'refund_only'
);

CREATE TYPE dispute_status AS ENUM (
    'open',
    'awaiting_return',
    'return_received',
    'escalated',
    'resolved_refunded',
    'resolved_denied',
    'withdrawn'
);
```

### 2.2 New Tables (full DDL)

```sql
-- ========================= ORDERS =========================
CREATE TABLE orders (
    id                          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    buyer_user_id               BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    shop_id                     BIGINT NOT NULL REFERENCES shops(id) ON DELETE RESTRICT,
    quotation_id                BIGINT REFERENCES quotations(id) ON DELETE RESTRICT,
    status                      order_status NOT NULL DEFAULT 'draft',

    -- Customer / billing / shipping snapshot (editable until paid)
    customer_business_name      VARCHAR(255),
    customer_contact_name       VARCHAR(255),
    customer_contact_phone      VARCHAR(30),
    customer_contact_email      VARCHAR(255),
    billing_address_1           VARCHAR(255),
    billing_address_2           VARCHAR(255),
    billing_city                VARCHAR(120),
    billing_province            VARCHAR(120),
    billing_zip_code            VARCHAR(20),
    billing_country_code        CHAR(2) NOT NULL DEFAULT 'PH',
    shipping_address_1          VARCHAR(255),
    shipping_address_2          VARCHAR(255),
    shipping_city               VARCHAR(120),
    shipping_province           VARCHAR(120),
    shipping_zip_code           VARCHAR(20),
    shipping_country_code       CHAR(2) NOT NULL DEFAULT 'PH',

    -- Financial totals (computed on submit; locked at paid)
    currency                    CHAR(3) NOT NULL DEFAULT 'PHP',
    items_total                 NUMERIC(12,2),
    fees_total                  NUMERIC(12,2),
    order_total                 NUMERIC(12,2),

    -- Timestamps
    submitted_at                TIMESTAMP(0),
    paid_at                     TIMESTAMP(0),
    completed_at                TIMESTAMP(0),
    cancelled_at                TIMESTAMP(0),
    created_at                  TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at                  TIMESTAMP(0) NOT NULL DEFAULT now()
);

CREATE INDEX idx_orders_buyer ON orders(buyer_user_id);
CREATE INDEX idx_orders_shop ON orders(shop_id);
CREATE INDEX idx_orders_quotation ON orders(quotation_id) WHERE quotation_id IS NOT NULL;
CREATE INDEX idx_orders_status ON orders(status);
-- Enforce one order per awarded quotation at DB level
CREATE UNIQUE INDEX idx_orders_quotation_unique ON orders(quotation_id)
    WHERE quotation_id IS NOT NULL;

-- ========================= ORDER ITEMS =========================
CREATE TABLE order_items (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id        BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    description     VARCHAR(500) NOT NULL,
    quantity        NUMERIC(12,2) NOT NULL,
    unit            VARCHAR(30) NOT NULL,
    unit_price      NUMERIC(12,2) NOT NULL,
    subtotal        NUMERIC(12,2) NOT NULL,
    sort_order      SMALLINT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at      TIMESTAMP(0) NOT NULL DEFAULT now()
);

CREATE INDEX idx_order_items_order ON order_items(order_id, sort_order);

-- ========================= ORDER FEES =========================
CREATE TABLE order_fees (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id    BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    label       VARCHAR(255) NOT NULL,
    amount      NUMERIC(12,2) NOT NULL,
    sort_order  SMALLINT NOT NULL DEFAULT 0,
    created_at  TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at  TIMESTAMP(0) NOT NULL DEFAULT now()
);

CREATE INDEX idx_order_fees_order ON order_fees(order_id, sort_order);

-- ========================= ORDER PAYMENT TERMS =========================
-- Forward-compatible: v1 always has exactly one row (type='full', 100% of order_total).
-- Future: multiple rows for deposit/partial schedules.
CREATE TABLE order_payment_terms (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id        BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    type            payment_term_type NOT NULL DEFAULT 'full',
    amount          NUMERIC(12,2) NOT NULL,
    due_at          TIMESTAMP(0),
    created_at      TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at      TIMESTAMP(0) NOT NULL DEFAULT now()
);

CREATE INDEX idx_order_payment_terms_order ON order_payment_terms(order_id);

-- ========================= PAYMENT ATTEMPTS =========================
CREATE TABLE payment_attempts (
    id                          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_payment_term_id       BIGINT NOT NULL REFERENCES order_payment_terms(id) ON DELETE CASCADE,
    status                      payment_attempt_status NOT NULL DEFAULT 'pending',

    -- DLT gateway fields
    dlt_merchant_transaction_id VARCHAR(50) NOT NULL UNIQUE,  -- our generated unique ID sent to DLT
    dlt_quick_link_token        VARCHAR(255),                  -- token from Quick Link session creation
    dlt_payment_url             VARCHAR(500),                  -- hosted checkout URL from DLT
    dlt_payment_status          VARCHAR(30),                   -- raw status from DLT (PAID/PENDING/REJECTED)
    dlt_amount                  VARCHAR(30),                   -- amount string returned by DLT

    -- Audit
    gateway_response            JSONB,   -- full DLT response payload for debugging
    succeeded_at                TIMESTAMP(0),
    failed_at                   TIMESTAMP(0),
    created_at                  TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at                  TIMESTAMP(0) NOT NULL DEFAULT now()
);

CREATE INDEX idx_payment_attempts_term ON payment_attempts(order_payment_term_id);
CREATE UNIQUE INDEX idx_payment_attempts_dlt_txn ON payment_attempts(dlt_merchant_transaction_id);

-- ========================= FULFILMENTS =========================
-- v1: exactly one per order (enforced at app layer + unique index).
-- Future: remove unique index to support split shipments.
CREATE TABLE fulfilments (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id        BIGINT NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
    status          fulfilment_status NOT NULL DEFAULT 'pending',

    -- Tracking info (all optional — sellers may not have formal tracking)
    carrier         VARCHAR(120),
    tracking_code   VARCHAR(255),
    tracking_url    VARCHAR(500),

    shipped_at      TIMESTAMP(0),
    delivered_at    TIMESTAMP(0),
    returned_at     TIMESTAMP(0),
    created_at      TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at      TIMESTAMP(0) NOT NULL DEFAULT now()
);

CREATE INDEX idx_fulfilments_order ON fulfilments(order_id);
CREATE UNIQUE INDEX idx_fulfilments_order_unique ON fulfilments(order_id);
-- Remove the unique index in a future migration to support multiple fulfilments per order.

-- ========================= DISPUTES =========================
CREATE TABLE disputes (
    id                      BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id                BIGINT NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
    opened_by_user_id       BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    type                    dispute_type NOT NULL,
    status                  dispute_status NOT NULL DEFAULT 'open',
    reason                  TEXT NOT NULL,

    -- Resolution fields
    resolution_type         VARCHAR(30),            -- 'full_refund', 'partial_refund', 'denied'
    resolution_amount       NUMERIC(12,2),           -- null if denied
    resolution_notes        TEXT,
    resolved_by_user_id     BIGINT REFERENCES users(id) ON DELETE RESTRICT,
    resolved_at             TIMESTAMP(0),
    escalated_at            TIMESTAMP(0),

    -- Return tracking (optional, for return_refund type)
    return_carrier          VARCHAR(120),
    return_tracking_code    VARCHAR(255),

    created_at              TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at              TIMESTAMP(0) NOT NULL DEFAULT now()
);

CREATE INDEX idx_disputes_order ON disputes(order_id);
CREATE INDEX idx_disputes_status ON disputes(status) WHERE status NOT IN ('resolved_refunded', 'resolved_denied', 'withdrawn');
CREATE INDEX idx_disputes_opened_by ON disputes(opened_by_user_id);

-- ========================= LEDGER ENTRIES =========================
-- Simple transaction log for all fund movement. Atomic with order/dispute status transitions.
CREATE TABLE ledger_entries (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id        BIGINT NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
    type            ledger_entry_type NOT NULL,
    amount          NUMERIC(12,2) NOT NULL,
    note            VARCHAR(255),       -- e.g. "Escrow hold on payment", "Payout on completion"
    created_at      TIMESTAMP(0) NOT NULL DEFAULT now()
    -- No updated_at — ledger entries are append-only and never modified.
);

CREATE INDEX idx_ledger_entries_order ON ledger_entries(order_id);
CREATE INDEX idx_ledger_entries_type ON ledger_entries(type);
```

### 2.3 Phase 2 Table Modifications

```sql
-- Add order_id FK to conversation_threads (alongside existing rfq_id)
ALTER TABLE conversation_threads
    ADD COLUMN order_id BIGINT REFERENCES orders(id) ON DELETE SET NULL;

CREATE INDEX idx_conversation_threads_order
    ON conversation_threads(order_id)
    WHERE order_id IS NOT NULL;
```

### 2.4 Relationship Summary (Phase 3 new relationships)

| Relationship | Cardinality |
|---|---|
| `users (buyer)` → `orders` | 1:N (buyer places many orders) |
| `shops (seller)` → `orders` | 1:N (seller receives many orders) |
| `quotations` → `orders` | 0:1 (nullable — standalone orders have none; awarded quotation maps to at most one order) |
| `orders` → `order_items` | 1:N |
| `orders` → `order_fees` | 1:N |
| `orders` → `order_payment_terms` | 1:N (v1: always 1 row of type `full`) |
| `order_payment_terms` → `payment_attempts` | 1:N (multiple retries possible; only one may succeed) |
| `orders` → `fulfilments` | 1:N schema; 1:1 enforced by unique index in v1 |
| `orders` → `disputes` | 1:N (a withdrawn dispute can be followed by a new one) |
| `orders` → `ledger_entries` | 1:N (append-only audit trail) |
| `conversation_threads` → `orders` | N:1 optional (nullable FK, one thread per order) |

### 2.5 Schema Design Decisions

- **`order_total` denormalized on `orders`**: computed (items_total + fees_total) and stored at submission time. Recalculated on every draft save to keep the UI accurate; locked (no further recalculation) once `paid`. This avoids a costly JOIN for every payment/report query.
- **Customer snapshot as flat columns, not JSONB**: unlike Phase 2's `buyer_snapshot` (JSONB on `rfqs`/`quotations`), the order carries its snapshot as named columns on the `orders` table itself. This allows SQL filtering/reporting against shipping city/province (e.g., for leaderboard/report geography breakdown) without a JSONB extraction. Editable until `paid`, locked at `paid`.
- **`dlt_merchant_transaction_id` unique on `payment_attempts`**: generated by us (UUID or scoped unique ID), sent to DLT. The unique constraint prevents duplicate gateway submissions from double-click retries.
- **`ledger_entries` has no `updated_at`**: ledger entries are immutable once created. A refund is a new `refund` row, not an update to the original `escrow_hold`.
- **`ON DELETE RESTRICT` on `orders.buyer_user_id`, `orders.shop_id`, `fulfilments.order_id`, `disputes.order_id`, `ledger_entries.order_id`**: financial/audit records must never silently vanish. Admin hard-delete of a user with orders should be blocked at DB level.
- **`ON DELETE CASCADE` on `order_items`, `order_fees`, `order_payment_terms`**: if an order is deleted (draft-only, admin action), children follow. Paid orders can never be deleted (application layer enforces).
- **`fulfilments` unique index removable**: the comment on `idx_fulfilments_order_unique` documents the migration path for future multi-shipment support.
- **`disputes` 1:N per order**: allows a buyer to withdraw a dispute and open a new one later (e.g., second defect discovered after first dispute resolved). Cap at one *active* dispute (non-terminal status) enforced at application layer.

---

## 3. DLT Payment Gateway Integration

### 3.1 Architecture overview

```
Buyer browser ──► [Livewire PaymentPage]
                       │
                       │ createQuickLinkSession()
                       ▼
               [DltPaymentService]
                       │
                       │ POST /oauth/token       (cached Bearer token)
                       │ POST /api/v1/quick-link/session
                       ▼
               DLT API (checkout.dxp.dtic.com.ph)
                       │ returns hosted checkout URL
                       ▼
              Redirect buyer → DLT hosted checkout
                       │
                       │ buyer pays (card / GCash / other)
                       ▼
              DLT redirects → success_url / failure_url
              (our /orders/{order}/payment/success  or /failure)
                       │
                       ▼
               [OrderPaymentCallbackController]
                       │ verify via GET /api/v1/quick-link/session?token=...
                       │ update payment_attempt status
                       │ if succeeded → order paid, escrow_hold ledger entry
                       ▼
              Buyer sees order confirmation / retry page
```

### 3.2 `DltPaymentService`

Located at `app/Services/DltPaymentService.php`. Responsibilities:

- **Token management**: fetch Bearer token from `/oauth/token`, cache in Redis with a TTL of `expires_in - 60` seconds. Never fetch a new token per request.
- **Create Quick Link session**: `POST /api/v1/quick-link/session` with the order's total, currency, product name, and a `merchant_transaction_id` (generated as `order-{order_id}-attempt-{attempt_id}` or a UUID). Store the returned `token` and hosted URL on the `payment_attempts` row.
- **Verify session**: `GET /api/v1/quick-link/session?token={token}` — called on callback to confirm payment status before trusting the redirect.

```php
// app/Services/DltPaymentService.php
final class DltPaymentService
{
    private const TOKEN_CACHE_KEY = 'dlt:bearer_token';

    public function __construct(
        private readonly \Illuminate\Http\Client\Factory $http,
        private readonly \Illuminate\Contracts\Cache\Repository $cache,
    ) {}

    /**
     * @return string Bearer access token (cached)
     */
    public function getBearerToken(): string { ... }

    /**
     * Create a Quick Link hosted checkout session.
     *
     * @return array{token: string, checkout_url: string}
     */
    public function createQuickLinkSession(
        string $merchantTransactionId,
        string $amount,
        string $productName,
        string $successUrl,
        string $failureUrl,
    ): array { ... }

    /**
     * Fetch a Quick Link session to verify payment status.
     *
     * @return array{payment_status: string|null, ...}
     */
    public function getQuickLinkSession(string $token): array { ... }
}
```

Config values read from `config/services.php`:

```php
'dlt' => [
    'base_url'            => env('DLT_BASE_URL', 'https://checkout.dxp.dtic.com.ph'),
    'client_id'           => env('DLT_CLIENT_ID'),
    'client_secret'       => env('DLT_CLIENT_SECRET'),
    'merchant_account_id' => env('DLT_MERCHANT_ACCOUNT_ID'),
    'service_code'        => env('DLT_SERVICE_CODE', 'APN-COLLECTION'),
],
```

### 3.3 Payment attempt lifecycle with DLT

1. Buyer clicks "Pay Now" on the order's payment page.
2. A new `payment_attempts` row is created (`status = pending`).
3. `DltPaymentService::createQuickLinkSession()` is called; the response `token` and `checkout_url` are stored on the `payment_attempts` row.
4. Buyer is redirected to the DLT hosted checkout URL.
5. After the buyer pays (or cancels), DLT redirects to `success_url` or `failure_url`.
6. **The redirect to `success_url` is the payment confirmation signal from DLT.** Our callback controller calls `DltPaymentService::getQuickLinkSession(token)` as a **tamper guard** — it verifies the token is real, belongs to this order/attempt, and hasn't expired. The Get Quick Link Session response does _not_ include a `payment_status` field; it returns session metadata only. Do not rely on it to confirm payment success — the `success_url` redirect itself is the authoritative signal.
7. If the callback is to `success_url` and the token validates → update `payment_attempts.status = succeeded`, set `succeeded_at`; within the same DB transaction: move order to `paid`, create `escrow_hold` ledger entry, create order thread, broadcast `OrderPaid` event.
8. If the callback is to `failure_url` → update `payment_attempts.status = failed`, set `failed_at`. Order stays `pending_payment`; buyer sees a retry button.
9. **Idempotency**: the callback controller must handle duplicate redirects — if the `payment_attempts` row is already `succeeded`, skip re-processing and redirect to the order confirmation page.

> **Note:** If DLT adds `payment_status` to the Get Quick Link Session response in a future API version, the verification in step 6 can be hardened to also assert `payment_status === 'PAID'` before accepting the callback.

### 3.4 Callback URLs

```
success_url:  /orders/{order}/payment/success?attempt={paymentAttempt}
failure_url:  /orders/{order}/payment/failure?attempt={paymentAttempt}
```

Both are authenticated routes (the buyer must be logged in). The `attempt` query parameter allows the controller to look up the specific `payment_attempts` row and verify it via DLT's session API before trusting the redirect.

---

## 4. API Contracts — Phase 3 Endpoints

All endpoints follow existing conventions: base path `/api/v1`, Laravel Sanctum auth, cursor-based pagination, standard error envelope.

### 4.1 Orders (Seller — Hub)

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/v1/hub/orders` | Create draft order (from quotation or standalone) |
| GET | `/api/v1/hub/orders` | List seller's orders (filterable by status) |
| GET | `/api/v1/hub/orders/{order}` | Order detail |
| PUT | `/api/v1/hub/orders/{order}` | Update draft order (items, fees, snapshot) |
| POST | `/api/v1/hub/orders/{order}/submit` | Submit draft → pending_payment |
| POST | `/api/v1/hub/orders/{order}/recall` | Recall pending_payment → draft |
| POST | `/api/v1/hub/orders/{order}/cancel` | Seller cancel (paid, pre-fulfilment only) |
| DELETE | `/api/v1/hub/orders/{order}` | Delete draft order |

**POST `/api/v1/hub/orders`**
```json
// Request — from awarded quotation
{
  "quotation_id": 45
}
// Request — standalone
{
  "buyer_user_id": 67
}

// Response 201
{
  "data": {
    "id": 1,
    "status": "draft",
    "quotation_id": 45,
    "buyer": { "id": 67, "name": "Amina Reyes", "business_name": "Manila Bakery Supplies Inc." },
    "customer_business_name": "Manila Bakery Supplies Inc.",
    "shipping_city": "Biñan",
    "shipping_province": "Laguna",
    "items": [...],
    "fees": [],
    "items_total": 70000.00,
    "fees_total": 0.00,
    "order_total": 70000.00,
    "currency": "PHP",
    "created_at": "2026-08-21T15:00:00Z"
  }
}
// Response 409 — quotation already has an order
{
  "error": {
    "code": "CONFLICT",
    "message": "An order has already been created from this quotation."
  }
}
```

**PUT `/api/v1/hub/orders/{order}`**
```json
// Request (all fields optional — partial update supported)
{
  "customer_business_name": "Manila Bakery Supplies Inc.",
  "customer_contact_phone": "+63 917 123 4567",
  "billing_city": "Makati",
  "shipping_address_1": "Warehouse 3, Block 7",
  "shipping_city": "Biñan",
  "shipping_province": "Laguna",
  "items": [
    { "id": null, "description": "Halal Coconut Flour 25kg", "quantity": 20, "unit": "sack", "unit_price": 3500.00 }
  ],
  "fees": [
    { "id": null, "label": "Shipping", "amount": 1500.00 }
  ]
}
// Response 200 — updated order with recalculated totals
```

**POST `/api/v1/hub/orders/{order}/submit`**
```json
// Response 200
{
  "data": {
    "id": 1,
    "status": "pending_payment",
    "order_total": 71500.00,
    "submitted_at": "2026-08-21T15:30:00Z"
  }
}
// Response 422 — no items
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "An order must have at least one item before submission.",
    "fields": { "items": ["Add at least one order item."] }
  }
}
```

### 4.2 Orders (Buyer)

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/orders` | List buyer's orders (filterable by tab: to_pay, to_ship, to_receive, completed, disputed) |
| GET | `/api/v1/orders/{order}` | Order detail |
| POST | `/api/v1/orders/{order}/cancel` | Buyer cancel (pending_payment only) |
| POST | `/api/v1/orders/{order}/confirm-receipt` | Buyer confirms receipt of delivered order |

**GET `/api/v1/orders?tab=to_pay&limit=20`**
```json
// Response 200
{
  "data": [
    {
      "id": 1,
      "shop": { "id": 233, "name": "Maslaha Foods Co." },
      "status": "pending_payment",
      "order_total": 71500.00,
      "currency": "PHP",
      "submitted_at": "2026-08-21T15:30:00Z",
      "created_at": "2026-08-21T15:00:00Z"
    }
  ],
  "meta": { "next_cursor": null, "prev_cursor": null, "per_page": 20 }
}
```

**POST `/api/v1/orders/{order}/confirm-receipt`**
```json
// Response 200
{
  "data": {
    "id": 1,
    "status": "completed",
    "completed_at": "2026-08-28T10:00:00Z"
  }
}
// Response 409 — order not yet delivered
{
  "error": {
    "code": "CONFLICT",
    "message": "The order cannot be confirmed yet as it has not been marked delivered."
  }
}
```

### 4.3 Payment

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/v1/orders/{order}/payment` | Initiate payment — creates payment_attempt, returns DLT checkout URL |
| GET | `/orders/{order}/payment/success` | DLT success callback (web route, authenticated) |
| GET | `/orders/{order}/payment/failure` | DLT failure callback (web route, authenticated) |

**POST `/api/v1/orders/{order}/payment`**
```json
// Response 201
{
  "data": {
    "payment_attempt_id": 5,
    "checkout_url": "https://checkout-ql.dltc.com.ph/?merchant=ACCT-12345678&transaction_id=order-1-attempt-5&amount=71500&quantity=1",
    "expires_at": 1756000000
  }
}
// Response 409 — order not pending_payment
{
  "error": {
    "code": "CONFLICT",
    "message": "This order is not awaiting payment."
  }
}
```

### 4.4 Fulfilment (Seller — Hub)

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/v1/hub/orders/{order}/fulfilment` | Create fulfilment record (order must be paid) |
| PUT | `/api/v1/hub/orders/{order}/fulfilment` | Update tracking info (while pending/shipped) |
| POST | `/api/v1/hub/orders/{order}/fulfilment/ship` | Mark shipped |
| POST | `/api/v1/hub/orders/{order}/fulfilment/deliver` | Mark delivered |
| POST | `/api/v1/hub/orders/{order}/fulfilment/return` | Mark returned to seller |

**POST `/api/v1/hub/orders/{order}/fulfilment`**
```json
// Request (all tracking fields optional)
{
  "carrier": "JRS Express",
  "tracking_code": "JRS1234567890",
  "tracking_url": "https://www.jrs-express.com/tracking?code=JRS1234567890"
}
// Response 201 — fulfilment created with status 'pending'
{
  "data": {
    "id": 1,
    "order_id": 1,
    "status": "pending",
    "carrier": "JRS Express",
    "tracking_code": "JRS1234567890",
    "tracking_url": "https://www.jrs-express.com/tracking?code=JRS1234567890",
    "shipped_at": null,
    "delivered_at": null
  }
}
```

**POST `/api/v1/hub/orders/{order}/fulfilment/ship`**
```json
// Request (may update tracking at same time)
{
  "tracking_code": "JRS1234567890",
  "tracking_url": "https://www.jrs-express.com/tracking?code=JRS1234567890"
}
// Response 200 — fulfilment status → 'shipped', shipped_at set
```

### 4.5 Disputes

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/v1/orders/{order}/disputes` | Buyer opens a dispute |
| GET | `/api/v1/orders/{order}/disputes/{dispute}` | Dispute detail |
| POST | `/api/v1/orders/{order}/disputes/{dispute}/resolve` | Seller resolves (issues refund or partial) |
| POST | `/api/v1/orders/{order}/disputes/{dispute}/confirm-return` | Seller confirms physical return received |
| POST | `/api/v1/orders/{order}/disputes/{dispute}/escalate` | Either party escalates to admin |
| POST | `/api/v1/orders/{order}/disputes/{dispute}/withdraw` | Buyer withdraws dispute |
| POST | `/api/v1/admin/disputes/{dispute}/arbitrate` | Admin arbitrates escalated dispute |

**POST `/api/v1/orders/{order}/disputes`**
```json
// Request
{
  "type": "return_refund",
  "reason": "Received 18 sacks instead of 20. Two sacks appear to be from a different batch with no halal certification label."
}
// Response 201
{
  "data": {
    "id": 1,
    "order_id": 1,
    "type": "return_refund",
    "status": "open",
    "reason": "Received 18 sacks instead of 20...",
    "created_at": "2026-08-28T11:00:00Z"
  }
}
```

**POST `/api/v1/orders/{order}/disputes/{dispute}/resolve`**
```json
// Request (seller issues partial refund)
{
  "resolution_type": "partial_refund",
  "resolution_amount": 7000.00,
  "resolution_notes": "Refunding 2 sacks (₱3,500 each) as agreed."
}
// Response 200
{
  "data": {
    "id": 1,
    "status": "resolved_refunded",
    "resolution_type": "partial_refund",
    "resolution_amount": 7000.00,
    "resolved_at": "2026-08-29T09:00:00Z"
  }
}
```

**POST `/api/v1/admin/disputes/{dispute}/arbitrate`**
```json
// Request
{
  "resolution_type": "full_refund",
  "resolution_notes": "Order total was ₱71,500. Buyer evidence is conclusive. Full refund issued."
}
// Response 200 — dispute resolved, order refunded, ledger entries created
```

### 4.6 Seller Hub

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/hub/dashboard` | To-do widget counts |
| GET | `/api/v1/hub/customers` | Customers list (cursor paginated) |
| GET | `/api/v1/hub/reports` | Sales & fulfilment metrics over a time window |
| GET | `/api/v1/hub/leaderboards` | Top products / customers / categories |

**GET `/api/v1/hub/dashboard`**
```json
// Response 200
{
  "data": {
    "new_rfqs":              3,
    "orders_in_progress":    5,
    "unfulfilled_orders":    2,
    "draft_orders":          1,
    "open_disputes":         1,
    "pending_quotations":    4
  }
}
```

**GET `/api/v1/hub/reports?window=30&from=2026-07-01`**
```json
// Response 200
{
  "data": {
    "window_days": 30,
    "new_customers":       3,
    "returning_customers": 7,
    "orders_placed":      18,
    "items_purchased":   360,
    "fulfilled_quantity": 340,
    "gross_sales":    1260000.00,
    "total_refunds":    14000.00,
    "platform_commission": 12600.00,
    "net_sales":       1233400.00
  }
}
```

**GET `/api/v1/hub/leaderboards?rank_by=revenue&window=30&type=products&limit=10`**
```json
// Response 200
{
  "data": [
    {
      "rank": 1,
      "product_name": "Halal Coconut Flour 25kg",
      "revenue": 700000.00,
      "order_count": 10,
      "quantity_sold": 200
    }
  ]
}
```

---

## 5. UI Component Architecture — Phase 3 Additions

### 5.1 Component hierarchy

```
resources/views/livewire/
│
├── account/                                  (BUYER additions)
│   ├── orders/
│   │   ├── order-list.blade.php              [Tab-filtered order list: to_pay / to_ship / to_receive / completed / disputes]
│   │   ├── order-detail.blade.php            [Order detail — items, fees, snapshot, status actions, thread]
│   │   └── payment-page.blade.php            [Pay Now → creates attempt → redirects to DLT; shows retry on failure]
│   └── disputes/
│       ├── dispute-form.blade.php            [Open dispute — type, reason, optional photos]
│       └── dispute-detail.blade.php          [Dispute status, thread, escalate/withdraw actions]
│
├── company-hub/                              (SELLER additions)
│   ├── orders/
│   │   ├── order-queue.blade.php             [Order list filtered by status: draft / pending_payment / to_fulfil / disputed]
│   │   ├── order-builder.blade.php           [Draft editor — line items, fees, snapshot (Alpine repeatable rows)]
│   │   └── order-detail.blade.php            [Order detail — fulfilment actions, dispute inbox, thread]
│   ├── fulfilment/
│   │   └── fulfilment-panel.blade.php        [Embedded in order-detail — ship/deliver/return actions + tracking fields]
│   ├── disputes/
│   │   └── dispute-panel.blade.php           [Embedded in order-detail — resolve/confirm-return/escalate actions]
│   └── hub/
│       ├── dashboard.blade.php               [To-do widget with action-count cards + deep links]
│       ├── customers-list.blade.php          [Paginated customer list — entry point for standalone order creation]
│       ├── reports.blade.php                 [Selectable time window — metrics grid]
│       └── leaderboards.blade.php            [Top products / customers / categories — rank-by selector]
│
├── admin/                                    (ADMIN additions)
│   └── disputes/
│       ├── dispute-queue.blade.php           [Escalated disputes list]
│       └── dispute-arbitration.blade.php     [Full order/dispute/thread view + arbitrate action]
│
└── shared/                                   (SHARED additions)
    ├── order-card.blade.php                  [Order status card rendered in conversation thread]
    └── dispute-card.blade.php                [Dispute status card rendered in conversation thread]
```

### 5.2 Requirement → Component Mapping

| PRD Requirement | UI Component(s) | Notes |
|---|---|---|
| §4.1 — Order creation from quotation | `company-hub/orders/order-builder` | "Create Order" action on awarded quotation card; auto-populates from quotation |
| §4.1 — Standalone order | `company-hub/hub/customers-list` → `order-builder` | From customer row, "New Order" button; builder starts empty |
| §4.2 — Draft editing | `company-hub/orders/order-builder` | Alpine.js add/remove rows for items and fees; real-time total recalculation; unit fields use `<x-hs-unit-select>` per project convention (see `.ai/rules/views.md`) |
| §4.2 — Submit for payment | `order-builder` submit action | Confirmation modal warning buyer will be notified |
| §4.2 — Recall to draft | `company-hub/orders/order-detail` | Recall button visible while `pending_payment` only |
| §4.5 — Buyer payment | `account/orders/payment-page` | "Pay Now" creates payment attempt, redirects to DLT; failure page shows retry button |
| §4.6 — Fulfilment actions | `company-hub/fulfilment/fulfilment-panel` | Ship / Deliver / Return buttons with confirmation modals; tracking fields visible |
| §4.6 — Buyer order tracking | `account/orders/order-detail` | CTA switches between "Track order" (with tracking link) and "Confirm receipt" per fulfilment status |
| §4.7 — Buyer order tabs | `account/orders/order-list` | Five tabs: To pay / To ship / To receive / Completed / Disputes |
| §4.8 — Dispute creation | `account/disputes/dispute-form` | Type selector (return_refund / refund_only), reason text, optional attachment upload |
| §4.8 — Seller dispute resolution | `company-hub/disputes/dispute-panel` | Resolve with full/partial refund + notes; confirm-return action for return_refund type |
| §4.8 — Admin arbitration | `admin/disputes/dispute-arbitration` | Full order context + dispute thread + arbitrate form (resolution_type + notes) |
| §4.9 — Order thread | `shared/order-card` embedded in `shared/message-stream` | Order card reflects live status; same real-time Reverb pipeline as Phase 2 |
| §4.10 — Seller Hub dashboard | `company-hub/hub/dashboard` | Six action-count cards; each deep-links to filtered queue |
| §4.10 — Customers list | `company-hub/hub/customers-list` | Shows buyer name, business, order count, last order date; "New Order" CTA per row |
| §4.10 — Reports | `company-hub/hub/reports` | Date-range selector + metrics grid (gross/net sales, refunds, commission) |
| §4.10 — Leaderboards | `company-hub/hub/leaderboards` | Tabbed by type (products/customers/categories); rank-by selector; time-window selector |

### 5.3 State management (Phase 3 additions)

| State type | Where it lives | Mechanism |
|---|---|---|
| Order draft (items, fees) | PostgreSQL + Livewire props | Alpine.js manages add/remove row UX; Livewire persists on autosave or explicit save action |
| Payment redirect | Browser (DLT handles checkout state) | After callback return, Livewire page polls order status or listens on `order.{id}` channel |
| Order/dispute status (real-time) | PostgreSQL + Reverb broadcast | Livewire listens to `order.{id}` private channel; status changes push live updates to both buyer and seller |
| Leaderboard/reports data | PostgreSQL (query-time) | No caching in v1; selectable window passed as query param; future: cache via Redis with short TTL |

---

## 6. Routes

### 6.1 Web routes (auth required)

```php
// Payment callback routes (DLT redirects here after checkout)
Route::middleware(['auth'])->group(function () {
    Route::get('/orders/{order}/payment/success', [OrderPaymentCallbackController::class, 'success'])
        ->name('orders.payment.success');
    Route::get('/orders/{order}/payment/failure', [OrderPaymentCallbackController::class, 'failure'])
        ->name('orders.payment.failure');
});
```

### 6.2 API routes summary (new — all under `/api/v1`, Sanctum-authenticated)

```php
// Buyer — orders
Route::get    ('orders',                          [BuyerOrderController::class, 'index']);
Route::get    ('orders/{order}',                  [BuyerOrderController::class, 'show']);
Route::post   ('orders/{order}/cancel',           [BuyerOrderController::class, 'cancel']);
Route::post   ('orders/{order}/confirm-receipt',  [BuyerOrderController::class, 'confirmReceipt']);
Route::post   ('orders/{order}/payment',          [OrderPaymentController::class, 'store']);

// Buyer — disputes
Route::post   ('orders/{order}/disputes',             [DisputeController::class, 'store']);
Route::get    ('orders/{order}/disputes/{dispute}',   [DisputeController::class, 'show']);
Route::post   ('orders/{order}/disputes/{dispute}/escalate', [DisputeController::class, 'escalate']);
Route::post   ('orders/{order}/disputes/{dispute}/withdraw', [DisputeController::class, 'withdraw']);

// Seller — Hub orders
Route::post   ('hub/orders',                          [HubOrderController::class, 'store']);
Route::get    ('hub/orders',                          [HubOrderController::class, 'index']);
Route::get    ('hub/orders/{order}',                  [HubOrderController::class, 'show']);
Route::put    ('hub/orders/{order}',                  [HubOrderController::class, 'update']);
Route::post   ('hub/orders/{order}/submit',           [HubOrderController::class, 'submit']);
Route::post   ('hub/orders/{order}/recall',           [HubOrderController::class, 'recall']);
Route::post   ('hub/orders/{order}/cancel',           [HubOrderController::class, 'cancel']);
Route::delete ('hub/orders/{order}',                  [HubOrderController::class, 'destroy']);

// Seller — fulfilment
Route::post   ('hub/orders/{order}/fulfilment',           [FulfilmentController::class, 'store']);
Route::put    ('hub/orders/{order}/fulfilment',           [FulfilmentController::class, 'update']);
Route::post   ('hub/orders/{order}/fulfilment/ship',      [FulfilmentController::class, 'ship']);
Route::post   ('hub/orders/{order}/fulfilment/deliver',   [FulfilmentController::class, 'deliver']);
Route::post   ('hub/orders/{order}/fulfilment/return',    [FulfilmentController::class, 'returnToSeller']);

// Seller — dispute actions
Route::post   ('orders/{order}/disputes/{dispute}/resolve',        [DisputeController::class, 'resolve']);
Route::post   ('orders/{order}/disputes/{dispute}/confirm-return', [DisputeController::class, 'confirmReturn']);

// Seller Hub
Route::get    ('hub/dashboard',    [SellerHubController::class, 'dashboard']);
Route::get    ('hub/customers',    [SellerHubController::class, 'customers']);
Route::get    ('hub/reports',      [SellerHubController::class, 'reports']);
Route::get    ('hub/leaderboards', [SellerHubController::class, 'leaderboards']);

// Admin — disputes
Route::post   ('admin/disputes/{dispute}/arbitrate', [AdminDisputeController::class, 'arbitrate'])
    ->middleware('role:admin');
Route::get    ('admin/disputes',   [AdminDisputeController::class, 'index'])
    ->middleware('role:admin');
```

---

## 7. Business Logic — Key Actions

### 7.1 `CreateOrderAction`

`app/Actions/CreateOrderAction.php`

```php
// From quotation: copy items, fees (none initially), buyer snapshot
// From standalone: create empty order against existing buyer_user_id
// Enforce: quotation must have status='awarded'; no prior order for this quotation
// Create one order_payment_terms row (type='full', amount=0 initially)
// Returns Order
```

**Copying quotation items → order items (Phase 2 variant link awareness):**

Phase 2 Epic 20 added `variant_id` (nullable FK) and `variant_snapshot` (JSONB) to `quotation_items`. `order_items` does **not** carry these columns — items are fully decoupled once copied. The copy logic must account for this:

- If `quotation_item.variant_snapshot` is non-null (normal case — all submitted quotations have snapshots written at submission time), populate `order_item.description`, `unit`, and `unit_price` from the snapshot fields (`description`, `unit`, `unit_price`).
- If `variant_snapshot` is null but `variant_id` is set (edge case: draft quotation item somehow carried forward), fall back to reading the `ProductVariant` record directly.
- If both are null (freeform item), copy `description`, `unit`, and `unit_price` directly from the `quotation_item` columns.

`order_items` inherits only the human-readable values — the variant link itself (`variant_id`) is not propagated. Editing an order item never touches the source quotation or its variant.

### 7.2 `SubmitOrderAction`

`app/Actions/SubmitOrderAction.php`

```php
// Validate: at least one item, order total > 0
// Compute items_total, fees_total, order_total (recalculate final)
// Update order_payment_terms.amount = order_total
// Set status = 'pending_payment', submitted_at = now()
// DB transaction
// After commit: create/reuse conversation, create order thread
//   (conversation_threads with order_id), post system message with order-card
// Dispatch OrderSubmittedForPayment notification to buyer
```

### 7.3 `ProcessOrderPaymentAction`

`app/Actions/ProcessOrderPaymentAction.php`

```php
// Called on success callback (after DLT confirms PAID)
// DB transaction:
//   1. Lock payment_attempt (SELECT FOR UPDATE) — idempotency guard
//   2. If already succeeded, return early
//   3. Update payment_attempts: status=succeeded, succeeded_at=now(), gateway_response
//   4. Update order: status=paid, paid_at=now()
//   5. Create ledger_entry: type=escrow_hold, amount=order_total
// After commit: dispatch OrderPaid event → broadcast to order.{id} channel
```

### 7.4 `ConfirmReceiptAction`

`app/Actions/ConfirmReceiptAction.php`

```php
// DB transaction:
//   1. Validate: fulfilment.status = 'delivered', no active dispute
//   2. Set order.status = 'completed', order.completed_at = now()
//   3. Create ledger_entry: type=payout, amount=(order_total - platform_commission)
//      + ledger_entry: type=platform_commission if commission > 0
// After commit: dispatch OrderCompleted event → buyer + seller notifications
```

### 7.5 `ResolveDisputeAction`

`app/Actions/ResolveDisputeAction.php`

```php
// DB transaction:
//   1. Validate resolution_type and amount
//   2. For return_refund: require dispute.status = 'return_received'
//   3. Update dispute: status=resolved_refunded or resolved_denied, resolved_at, resolved_by
//   4. If refund:
//      a. Create ledger_entry: type=refund, amount=resolution_amount
//      b. If partial: create ledger_entry: type=payout, amount=(order_total - resolution_amount - commission)
//      c. Set order.status = 'refunded'
//   5. If denied: set order.status = 'completed' (order stands)
// After commit: dispatch DisputeResolved event → both parties notified
```

---

## 8. Background Jobs & Events — Phase 3

### 8.1 New Scheduled Jobs

| Job | Schedule | Logic | Idempotency |
|---|---|---|---|
| `OrderAutoAcceptJob` | Daily, 04:00 UTC | Query orders WHERE status='paid' AND fulfilment.status='delivered' AND fulfilment.delivered_at < NOW() - INTERVAL '7 days' AND no active dispute → call `ConfirmReceiptAction` (auto-confirm) | Only processes rows meeting all criteria; `ConfirmReceiptAction` is wrapped in a DB transaction with a row-level lock on the order |
| `OrderAutoAcceptJob` (dispute pause) | Same job | Skip any order with an active dispute (dispute.status NOT IN terminal states) | `WHERE disputes.status NOT IN ('resolved_refunded', 'resolved_denied', 'withdrawn')` — or simply `WHERE NOT EXISTS (SELECT 1 FROM disputes WHERE disputes.order_id = orders.id AND disputes.status NOT IN (...))` |

### 8.2 Event-Driven Jobs & Events

| Event | Trigger | Actions |
|---|---|---|
| `OrderCreated` | Seller creates draft order | No immediate notification (draft is seller-only) |
| `OrderSubmittedForPayment` | `SubmitOrderAction` completes | 1. Create order thread in existing conversation (or new conversation) <br> 2. Post system message with `order-card` <br> 3. Dispatch `OrderPendingPaymentNotification` to buyer (email + Reverb `user.{buyerId}`) |
| `OrderPaid` | `ProcessOrderPaymentAction` completes | 1. Broadcast order status update on `order.{id}` channel <br> 2. Dispatch `OrderPaidNotification` to seller (email + Reverb `user.{sellerUserId}`) |
| `OrderShipped` | Seller marks shipped | 1. Broadcast on `order.{id}` <br> 2. Dispatch `OrderShippedNotification` to buyer |
| `OrderDelivered` | Seller marks delivered | 1. Broadcast on `order.{id}` (buyer CTA switches to "Confirm receipt") <br> 2. Dispatch `OrderDeliveredNotification` to buyer <br> 3. Start 7-day auto-accept timer (fulfilled by `OrderAutoAcceptJob`) |
| `OrderCompleted` | Buyer confirms receipt OR `OrderAutoAcceptJob` | 1. Dispatch `OrderCompletedNotification` to seller |
| `OrderCancelled` | Buyer or seller cancels | 1. If paid: `ConfirmReceiptAction` → create refund ledger entry <br> 2. Dispatch `OrderCancelledNotification` to counterpart |
| `DisputeOpened` | Buyer opens dispute | 1. Post dispute card as message in order thread <br> 2. Dispatch `DisputeOpenedNotification` to seller |
| `DisputeResolved` | Seller resolves or admin arbitrates | 1. Broadcast on `order.{id}` <br> 2. Dispatch `DisputeResolvedNotification` to both parties |
| `DisputeEscalated` | Either party escalates | 1. Dispatch `DisputeEscalatedNotification` to admin |

### 8.3 Broadcast Channels (Phase 3 additions)

| Channel | Type | Purpose |
|---|---|---|
| `order.{id}` | Private | Real-time order status updates, new messages in order thread, dispute status changes |

Channel authorization:

```php
Broadcast::channel('order.{id}', function (User $user, int $id) {
    $order = Order::findOrFail($id);

    return $user->id === $order->buyer_user_id
        || $user->id === $order->shop->user_id;
});
```

---

## 9. Security & Authorization — Phase 3 Additions

### 9.1 New Policies

| Policy | Model | Key rules |
|---|---|---|
| `OrderPolicy` | `Order` | `create`: authenticated seller with active shop; `view`: order belongs to the user (as buyer or seller's shop owner); `update`/`submit`/`recall`: seller owns AND status is `draft`/`pending_payment` respectively; `cancel (buyer)`: buyer owns AND status is `pending_payment`; `cancel (seller)`: seller owns AND status is `paid` AND no fulfilment exists; `confirmReceipt`: buyer owns AND fulfilment.status is `delivered` AND no active dispute |
| `FulfilmentPolicy` | `Fulfilment` | `create`/`ship`/`deliver`/`return`: seller owns the parent order AND order.status is `paid` |
| `DisputePolicy` | `Dispute` | `create`: buyer owns the order AND order fulfilment is `delivered`/`returned_to_seller`; `resolve`/`confirmReturn`: seller owns the order's shop AND dispute is in appropriate pre-resolution state; `escalate`: user is buyer OR seller AND dispute is not already escalated/terminal; `withdraw`: buyer is the opener AND dispute is not terminal; `arbitrate`: admin role AND dispute.status is `escalated` |
| `SellerHubPolicy` | (gate) | `accessHub`: user owns an active shop |

### 9.2 Financial integrity

All fund-movement operations (escrow hold, payout, refund) must be executed in a single DB transaction with the corresponding order/dispute status transition. No order can reach `paid`, `completed`, or `refunded` without a matching, consistent `ledger_entries` row.

Pattern enforced in `ProcessOrderPaymentAction`, `ConfirmReceiptAction`, and `ResolveDisputeAction`:

```php
DB::transaction(function () use ($order, ...) {
    $order->lockForUpdate()->findOrFail($order->id);  // row-level lock
    // ... validate current state ...
    // ... update order status ...
    // ... create ledger_entry ...
});
// dispatch events AFTER transaction commits
```

### 9.3 Race condition handling

- **Payment callback duplication**: DLT may redirect to `success_url` more than once. `ProcessOrderPaymentAction` locks the `payment_attempts` row (`SELECT FOR UPDATE`) and returns early if already `succeeded`.
- **Concurrent confirm-receipt + dispute open**: `ConfirmReceiptAction` checks for active disputes within the transaction. If a dispute is opened milliseconds before auto-accept fires, the job finds the dispute and skips auto-accept.
- **Duplicate order from quotation**: enforced by the unique index `idx_orders_quotation_unique` — a DB-level constraint eliminates any race window.

### 9.4 Data privacy

Order customer/billing/shipping details are visible only to the buyer, the specific seller, and admin handling an escalated dispute. They must never appear in Typesense indexes, public API responses, or storefront views.

### 9.5 Input validation

| Endpoint | Key validations |
|---|---|
| `POST /hub/orders` | `quotation_id` must exist and status='awarded' (if provided); `buyer_user_id` must be an existing user who appears in this shop's customer list — i.e., has at least one prior awarded quotation or completed/paid order with this shop (standalone order customer constraint, see requirements §4.1) |
| `PUT /hub/orders/{order}` | Order must be `draft`; items: `description` max 500, `quantity` > 0, `unit` max 30, `unit_price` >= 0; fees: `label` max 255, `amount` >= 0 |
| `POST /hub/orders/{order}/submit` | At least one item; `order_total` > 0 |
| `POST /orders/{order}/disputes` | Fulfilment must be `delivered` or `returned_to_seller`; `type` must be valid enum; `reason` required, max 2000 chars |
| `POST /hub/disputes/{dispute}/resolve` | `resolution_type` in `['full_refund','partial_refund','denied']`; `resolution_amount` required and ≤ `order_total` if type is `partial_refund` |
| `POST /admin/disputes/{dispute}/arbitrate` | `resolution_type` required; `resolution_notes` required |

### 9.6 Rate limiting

| Endpoint group | Limit |
|---|---|
| Order creation | 20 per hour per user |
| Payment initiation | 10 per hour per order |
| Dispute opening | 5 per day per user |
| Report/leaderboard queries | 60 per minute per user |

---

## 10. Seller Hub — Reports & Leaderboards (Query Design)

All report queries are scoped to `orders WHERE shop_id = ? AND status IN ('completed', 'refunded') AND paid_at BETWEEN ? AND ?`.

### 10.1 Sales report query skeleton

```sql
SELECT
    COUNT(DISTINCT CASE WHEN o.buyer_user_id NOT IN (
        SELECT buyer_user_id FROM orders
        WHERE shop_id = :shop_id AND paid_at < :window_start
    ) THEN o.buyer_user_id END)                       AS new_customers,
    COUNT(DISTINCT o.buyer_user_id)                   AS total_customers,
    COUNT(o.id)                                       AS orders_placed,
    COALESCE(SUM(oi.quantity), 0)                     AS items_purchased,
    COALESCE(SUM(o.order_total), 0)                   AS gross_sales,
    COALESCE(SUM(l_refund.amount), 0)                 AS total_refunds,
    COALESCE(SUM(o.order_total), 0)
        - COALESCE(SUM(l_refund.amount), 0)
        - (COALESCE(SUM(o.order_total), 0) * :commission_rate) AS net_sales
FROM orders o
LEFT JOIN order_items oi ON oi.order_id = o.id
LEFT JOIN ledger_entries l_refund
    ON l_refund.order_id = o.id AND l_refund.type = 'refund'
WHERE o.shop_id = :shop_id
  AND o.status IN ('completed', 'refunded')
  AND o.paid_at BETWEEN :window_start AND :window_end;
```

### 10.2 Leaderboard query skeleton (top products by revenue)

```sql
SELECT
    oi.description                   AS product_name,
    SUM(oi.subtotal)                 AS revenue,
    COUNT(DISTINCT o.id)             AS order_count,
    SUM(oi.quantity)                 AS quantity_sold
FROM order_items oi
JOIN orders o ON o.id = oi.order_id
WHERE o.shop_id = :shop_id
  AND o.status IN ('completed', 'refunded')
  AND (:window_start IS NULL OR o.paid_at >= :window_start)
GROUP BY oi.description
ORDER BY revenue DESC   -- or order_count / quantity_sold based on rank_by param
LIMIT :limit;
```

---

## 11. Cross-References

| Document | What it provides |
|---|---|
| `Phase 1 design.md` §1 | Full system architecture (topology, data flow, deployment) |
| `Phase 1 design.md` §2 | Existing schema (users, shops, products, media, categories) |
| `Phase 1 design.md` §3 | API conventions, auth, error envelope format |
| `Phase 2 design.md` §2 | Phase 2 schema (buyer_profiles, rfqs, quotations, conversations, threads, messages) |
| `Phase 2 design.md` §5 | Broadcast channel conventions (`user.{id}`) |
| `Phase 3 requirements.md` | PRD, state machines (§5), detailed feature specs, user flows, non-functional requirements, open questions |
| DLT Payment API skill | `/.kiro/skills/dlt-payment-api/SKILL.md` + `references/api-reference.md` — DLT endpoint specs, field tables, OAuth2 token handling, Quick Link flow |
