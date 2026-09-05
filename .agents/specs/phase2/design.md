# design.md
## Technical Design Document — Phase 2: Negotiation (RFQ, Quotation, Chat)

| | |
|---|---|
| **Companion docs** | `Phase 1 design.md` (system architecture, existing schema), `Phase 2 requirements.md` (PRD, state machines, user flows) |
| **Author role** | Lead Systems Architect / Principal UI Engineer |
| **Stack** | Same as Phase 1 — PHP/Laravel · Livewire · Tailwind · Redis · PostgreSQL · Reverb · Typesense · Minio/S3 |
| **Status** | Draft for engineering review |

---

## 1. Phase 2 Context & Integration Points

### 1.1 How Phase 2 extends the existing system

Phase 1 delivered **discovery** (verified directory of halal companies and products). Phase 2 adds the **negotiation layer**: RFQs, structured quotations, and persistent buyer–seller messaging. The system architecture (Phase 1 design.md §1) remains unchanged — Phase 2 adds new tables, routes, Livewire components, jobs, and broadcast channels within the existing monolith topology.

### 1.2 Existing tables/services touched

| Existing entity | Phase 2 interaction |
|---|---|
| `users` | Referenced as buyer (via `buyer_profiles`, `rfqs`, `conversations`, `messages`) and seller (via `shops` → `rfq_recipients`) |
| `shops` | Target of `rfq_recipients`; owner participates in conversations |
| `media` | Reused for `rfq_attachments`, `quotation_attachments`, `message_attachments` — same upload pipeline + `ScanUploadedFileJob` |
| `products` | "Request Quote" button added to product detail page (no FK on `rfqs` — product only pre-fills title text) |
| Redis queue | New jobs dispatched to the existing queue infrastructure |
| Laravel Scheduler | Three new daily scheduled jobs added |
| Reverb | New private broadcast channel for real-time chat |

### 1.3 New broadcast channels

| Channel | Type | Purpose |
|---|---|---|
| `conversation.{id}` | Private | Real-time chat messages within a buyer–seller conversation |
| `user.{id}` | Private (existing) | Reused for RFQ/quotation/state-change notifications |

---

## 2. Database Schema — Phase 2 Additions

### 2.1 New Enum Types

```sql
-- ========================= PHASE 2 ENUM TYPES =========================

CREATE TYPE rfq_status AS ENUM ('open', 'closed');

CREATE TYPE rfq_recipient_status AS ENUM ('received', 'reviewed', 'in_progress', 'no_bid');

CREATE TYPE quotation_status AS ENUM (
    'draft',
    'submitted',
    'awarded',
    'rejected',
    'withdrawn',
    'not_awarded',
    'expired'
);
```

### 2.2 New Tables (full DDL)

```sql
-- ========================= BUYER PROFILES =========================
CREATE TABLE buyer_profiles (
    id                      BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id                 BIGINT NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    business_name           VARCHAR(255) NOT NULL,
    billing_address_1       VARCHAR(255),
    billing_address_2       VARCHAR(255),
    billing_city            VARCHAR(120),
    billing_province        VARCHAR(120),
    billing_zip_code        VARCHAR(20),
    billing_country_code    CHAR(2) NOT NULL DEFAULT 'PH',
    shipping_address_1      VARCHAR(255),
    shipping_address_2      VARCHAR(255),
    shipping_city           VARCHAR(120),
    shipping_province       VARCHAR(120),
    shipping_zip_code       VARCHAR(20),
    shipping_country_code   CHAR(2) NOT NULL DEFAULT 'PH',
    contact_phone           VARCHAR(30),
    contact_email           VARCHAR(255),
    created_at              TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at              TIMESTAMP(0) NOT NULL DEFAULT now()
);
CREATE UNIQUE INDEX idx_buyer_profiles_user ON buyer_profiles(user_id);

-- ========================= RFQS =========================
CREATE TABLE rfqs (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id             BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    title               VARCHAR(500) NOT NULL,
    description         TEXT,
    status              rfq_status NOT NULL DEFAULT 'open',
    buyer_snapshot      JSONB NOT NULL,  -- {business_name, billing_address, shipping_address}
    last_activity_at    TIMESTAMP(0),
    closed_at           TIMESTAMP(0),
    created_at          TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP(0) NOT NULL DEFAULT now()
);
CREATE INDEX idx_rfqs_user ON rfqs(user_id);
CREATE INDEX idx_rfqs_status ON rfqs(status);
CREATE INDEX idx_rfqs_last_activity ON rfqs(last_activity_at) WHERE status = 'open';

-- ========================= RFQ RECIPIENTS =========================
CREATE TABLE rfq_recipients (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    rfq_id              BIGINT NOT NULL REFERENCES rfqs(id) ON DELETE CASCADE,
    shop_id             BIGINT NOT NULL REFERENCES shops(id) ON DELETE RESTRICT,
    status              rfq_recipient_status NOT NULL DEFAULT 'received',
    decline_reason      TEXT,
    last_activity_at    TIMESTAMP(0),
    reviewed_at         TIMESTAMP(0),
    created_at          TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP(0) NOT NULL DEFAULT now()
);
CREATE INDEX idx_rfq_recipients_rfq ON rfq_recipients(rfq_id);
CREATE INDEX idx_rfq_recipients_shop ON rfq_recipients(shop_id);
CREATE INDEX idx_rfq_recipients_status ON rfq_recipients(status) WHERE status <> 'no_bid';
CREATE UNIQUE INDEX idx_rfq_recipients_rfq_shop ON rfq_recipients(rfq_id, shop_id);

-- ========================= RFQ ATTACHMENTS =========================
CREATE TABLE rfq_attachments (
    rfq_id      BIGINT NOT NULL REFERENCES rfqs(id) ON DELETE CASCADE,
    media_id    BIGINT NOT NULL REFERENCES media(id) ON DELETE RESTRICT,
    PRIMARY KEY (rfq_id, media_id)
);

-- ========================= QUOTATIONS =========================
CREATE TABLE quotations (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    rfq_id              BIGINT NOT NULL REFERENCES rfqs(id) ON DELETE CASCADE,
    rfq_recipient_id    BIGINT NOT NULL REFERENCES rfq_recipients(id) ON DELETE CASCADE,
    status              quotation_status NOT NULL DEFAULT 'draft',
    notes               TEXT,
    currency            CHAR(3) NOT NULL DEFAULT 'PHP',
    total_amount        NUMERIC(12,2),
    valid_until         TIMESTAMP(0),   -- set to submitted_at + 14 days on submit
    submitted_at        TIMESTAMP(0),
    buyer_snapshot      JSONB,          -- captured at submission time; nullable for drafts
    created_at          TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP(0) NOT NULL DEFAULT now()
);
CREATE INDEX idx_quotations_rfq ON quotations(rfq_id);
CREATE INDEX idx_quotations_rfq_recipient ON quotations(rfq_recipient_id);
CREATE INDEX idx_quotations_status ON quotations(status);
CREATE INDEX idx_quotations_valid_until ON quotations(valid_until) WHERE status = 'submitted';

-- ========================= QUOTATION ITEMS =========================
CREATE TABLE quotation_items (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    quotation_id    BIGINT NOT NULL REFERENCES quotations(id) ON DELETE CASCADE,
    description     VARCHAR(500) NOT NULL,
    quantity        NUMERIC(12,2) NOT NULL,
    unit            VARCHAR(30) NOT NULL,
    unit_price      NUMERIC(12,2) NOT NULL,
    subtotal        NUMERIC(12,2) NOT NULL,
    sort_order      SMALLINT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at      TIMESTAMP(0) NOT NULL DEFAULT now()
);
CREATE INDEX idx_quotation_items_quotation ON quotation_items(quotation_id, sort_order);

-- ========================= QUOTATION ATTACHMENTS =========================
CREATE TABLE quotation_attachments (
    quotation_id    BIGINT NOT NULL REFERENCES quotations(id) ON DELETE CASCADE,
    media_id        BIGINT NOT NULL REFERENCES media(id) ON DELETE RESTRICT,
    PRIMARY KEY (quotation_id, media_id)
);

-- ========================= CONVERSATIONS =========================
CREATE TABLE conversations (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    buyer_user_id   BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    shop_id         BIGINT NOT NULL REFERENCES shops(id) ON DELETE RESTRICT,
    created_at      TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at      TIMESTAMP(0) NOT NULL DEFAULT now()
);
CREATE UNIQUE INDEX idx_conversations_pair ON conversations(buyer_user_id, shop_id);

-- ========================= CONVERSATION THREADS =========================
CREATE TABLE conversation_threads (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    conversation_id BIGINT NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
    title           VARCHAR(255),
    rfq_id          BIGINT REFERENCES rfqs(id) ON DELETE SET NULL,
    is_default      BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at      TIMESTAMP(0) NOT NULL DEFAULT now()
);
CREATE INDEX idx_conversation_threads_conversation ON conversation_threads(conversation_id);
CREATE INDEX idx_conversation_threads_rfq ON conversation_threads(rfq_id) WHERE rfq_id IS NOT NULL;

-- ========================= MESSAGES =========================
CREATE TABLE messages (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    thread_id   BIGINT NOT NULL REFERENCES conversation_threads(id) ON DELETE CASCADE,
    sender_id   BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    body        TEXT NOT NULL,
    created_at  TIMESTAMP(0) NOT NULL DEFAULT now(),
    updated_at  TIMESTAMP(0) NOT NULL DEFAULT now()
);
CREATE INDEX idx_messages_thread ON messages(thread_id, created_at);
CREATE INDEX idx_messages_sender ON messages(sender_id);

-- ========================= MESSAGE ATTACHMENTS =========================
CREATE TABLE message_attachments (
    message_id  BIGINT NOT NULL REFERENCES messages(id) ON DELETE CASCADE,
    media_id    BIGINT NOT NULL REFERENCES media(id) ON DELETE RESTRICT,
    PRIMARY KEY (message_id, media_id)
);
```

### 2.3 Relationship Summary (new relationships only)

| Relationship | Cardinality |
|---|---|
| `users` → `buyer_profiles` | 1:1 (`user_id UNIQUE`) |
| `users` → `rfqs` | 1:N (buyer creates many RFQs) |
| `rfqs` → `rfq_recipients` | 1:N (1 in Phase 2, future multi-seller) |
| `shops` → `rfq_recipients` | 1:N (seller receives many RFQs) |
| `rfqs` ↔ `media` | N:M via `rfq_attachments` |
| `rfq_recipients` → `quotations` | 1:N (seller can submit multiple quotations) |
| `rfqs` → `quotations` | 1:N (denormalized FK for easier buyer queries) |
| `quotations` → `quotation_items` | 1:N (ordered line items) |
| `quotations` ↔ `media` | N:M via `quotation_attachments` |
| `users` + `shops` → `conversations` | Unique pair per buyer–seller relationship |
| `conversations` → `conversation_threads` | 1:N (general + RFQ-linked threads) |
| `conversation_threads` → `rfqs` | N:1 optional (nullable FK links thread to an RFQ) |
| `conversation_threads` → `messages` | 1:N |
| `messages` ↔ `media` | N:M via `message_attachments` |
| `users` → `messages` | 1:N (sender) |

### 2.4 Schema Design Decisions

- **`buyer_snapshot` as JSONB** rather than normalized address-snapshot tables — simpler, self-contained audit record. Each RFQ and quotation is its own historical document once terminal.
- **`rfq_recipients` as separate table** even though Phase 2 always creates 1 row — future-proofs multi-seller RFQ matching without schema changes.
- **No `product_id` FK on `rfqs`** — explicit design choice from PRD §11. The originating product only pre-fills the title text; the RFQ is a freeform inquiry, not a structured product reference.
- **Quotation immutability enforced at application layer** (no DB trigger) — once status leaves `draft`, the application layer (Policy + model events) blocks UPDATE on content columns (`notes`, `total_amount`, `currency`, line items). This keeps the schema simple and the enforcement auditable in code.
- **`message_attachments`** instead of polymorphic media attachment — keeps it consistent with `rfq_attachments` / `quotation_attachments` pattern (composite PK join tables referencing `media`).
- **`conversations` unique on `(buyer_user_id, shop_id)`** — ensures exactly one conversation per buyer–seller pair, reused across all their RFQs.
- **`rfq_recipients.rfq_id + shop_id` unique index** — prevents duplicate recipient entries for the same shop on one RFQ.
- **`ON DELETE RESTRICT`** on `rfqs.user_id`, `conversations.buyer_user_id`, `conversations.shop_id`, `messages.sender_id` — these entities form a permanent audit trail; deleting a user/shop should be blocked, not cascaded.
- **`ON DELETE CASCADE`** on child tables (`rfq_recipients`, `quotations`, `quotation_items`, `conversation_threads`, `messages`) — if a parent is deleted (admin hard-delete, which should be extremely rare), children follow.

---

## 3. API Contracts — Phase 2 Endpoints

All endpoints follow Phase 1 conventions (Phase 1 design.md §3.1): base path `/api/v1`, Laravel Sanctum auth, cursor-based pagination, standard error envelope.

### 3.1 Buyer Profile

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/buyer-profile` | Get current user's buyer profile |
| PUT | `/api/v1/buyer-profile` | Create or update buyer profile |

**GET `/api/v1/buyer-profile`**
```json
// Response 200
{
  "data": {
    "id": 12,
    "business_name": "Manila Bakery Supplies Inc.",
    "billing_address_1": "Unit 5, Golden Tower",
    "billing_address_2": null,
    "billing_city": "Makati",
    "billing_province": "Metro Manila",
    "billing_zip_code": "1226",
    "billing_country_code": "PH",
    "shipping_address_1": "Warehouse 3, Block 7",
    "shipping_address_2": "Laguna Technopark",
    "shipping_city": "Biñan",
    "shipping_province": "Laguna",
    "shipping_zip_code": "4024",
    "shipping_country_code": "PH",
    "contact_phone": "+63 917 123 4567",
    "contact_email": "purchasing@manilabakery.ph"
  }
}
// Response 404 (no profile yet)
{
  "error": {
    "code": "NOT_FOUND",
    "message": "Buyer profile not found."
  }
}
```

**PUT `/api/v1/buyer-profile`**
```json
// Request
{
  "business_name": "Manila Bakery Supplies Inc.",
  "billing_address_1": "Unit 5, Golden Tower",
  "billing_city": "Makati",
  "billing_province": "Metro Manila",
  "billing_zip_code": "1226",
  "shipping_address_1": "Warehouse 3, Block 7",
  "shipping_address_2": "Laguna Technopark",
  "shipping_city": "Biñan",
  "shipping_province": "Laguna",
  "shipping_zip_code": "4024",
  "contact_phone": "+63 917 123 4567",
  "contact_email": "purchasing@manilabakery.ph"
}
// Response 200 (created or updated)
{
  "data": {
    "id": 12,
    "business_name": "Manila Bakery Supplies Inc.",
    "billing_address_1": "Unit 5, Golden Tower",
    "billing_address_2": null,
    "billing_city": "Makati",
    "billing_province": "Metro Manila",
    "billing_zip_code": "1226",
    "billing_country_code": "PH",
    "shipping_address_1": "Warehouse 3, Block 7",
    "shipping_address_2": "Laguna Technopark",
    "shipping_city": "Biñan",
    "shipping_province": "Laguna",
    "shipping_zip_code": "4024",
    "shipping_country_code": "PH",
    "contact_phone": "+63 917 123 4567",
    "contact_email": "purchasing@manilabakery.ph"
  }
}
```

### 3.2 RFQs (Buyer)

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/v1/rfqs` | Create RFQ (requires buyer profile to exist) |
| GET | `/api/v1/rfqs` | List buyer's sent RFQs (cursor paginated, filterable by status) |
| GET | `/api/v1/rfqs/{rfq}` | RFQ detail (with recipients, quotations) |
| POST | `/api/v1/rfqs/{rfq}/close` | Manually close RFQ |

**POST `/api/v1/rfqs`**
```json
// Request
{
  "title": "500kg Halal-Certified Coconut Flour — bulk pricing inquiry",
  "description": "We need 500kg of food-grade coconut flour for our bakery line. Looking for monthly supply contract pricing. Must have valid halal certification from IDCP or equivalent NCMF-recognized body. Please quote FOB Manila.",
  "shop_id": 233,
  "attachment_media_ids": [9901, 9902]
}
// Response 201
{
  "data": {
    "id": 78,
    "title": "500kg Halal-Certified Coconut Flour — bulk pricing inquiry",
    "description": "We need 500kg of food-grade coconut flour...",
    "status": "open",
    "buyer_snapshot": {
      "business_name": "Manila Bakery Supplies Inc.",
      "billing_address": {
        "address_1": "Unit 5, Golden Tower",
        "city": "Makati",
        "province": "Metro Manila",
        "zip_code": "1226",
        "country_code": "PH"
      },
      "shipping_address": {
        "address_1": "Warehouse 3, Block 7",
        "address_2": "Laguna Technopark",
        "city": "Biñan",
        "province": "Laguna",
        "zip_code": "4024",
        "country_code": "PH"
      }
    },
    "recipients": [
      {
        "id": 101,
        "shop_id": 233,
        "shop_name": "Maslaha Foods Co.",
        "status": "received"
      }
    ],
    "attachments": [
      { "media_id": 9901, "original_filename": "spec-sheet.pdf" },
      { "media_id": 9902, "original_filename": "required-certifications.pdf" }
    ],
    "last_activity_at": "2026-08-18T09:00:00Z",
    "created_at": "2026-08-18T09:00:00Z"
  }
}
// Response 422 (no buyer profile)
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "A buyer profile is required before sending an RFQ.",
    "fields": {
      "buyer_profile": ["Please complete your buyer profile before submitting a request for quotation."]
    }
  }
}
```

**GET `/api/v1/rfqs?status=open&limit=20`**
```json
// Response 200
{
  "data": [
    {
      "id": 78,
      "title": "500kg Halal-Certified Coconut Flour — bulk pricing inquiry",
      "status": "open",
      "recipients_count": 1,
      "quotations_count": 2,
      "last_activity_at": "2026-08-18T14:30:00Z",
      "created_at": "2026-08-18T09:00:00Z"
    }
  ],
  "meta": {
    "next_cursor": "eyJpZCI6NzcsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0",
    "prev_cursor": null,
    "per_page": 20
  }
}
```

**POST `/api/v1/rfqs/{rfq}/close`**
```json
// Response 200
{
  "data": {
    "id": 78,
    "status": "closed",
    "closed_at": "2026-08-18T16:00:00Z",
    "quotations_not_awarded_count": 1
  }
}
// Response 409 (already closed)
{
  "error": {
    "code": "CONFLICT",
    "message": "This RFQ is already closed."
  }
}
```

### 3.3 RFQs (Seller / Hub)

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/hub/rfqs` | List RFQs received by seller's shop (filterable by recipient status) |
| GET | `/api/v1/hub/rfqs/{rfqRecipient}` | RFQ detail from seller perspective |
| POST | `/api/v1/hub/rfqs/{rfqRecipient}/review` | Mark as reviewed (also auto-triggered on first view) |
| POST | `/api/v1/hub/rfqs/{rfqRecipient}/in-progress` | Mark as in_progress |
| POST | `/api/v1/hub/rfqs/{rfqRecipient}/decline` | Decline (optional reason) |

**GET `/api/v1/hub/rfqs?status=received&limit=20`**
```json
// Response 200
{
  "data": [
    {
      "rfq_recipient_id": 101,
      "rfq_id": 78,
      "title": "500kg Halal-Certified Coconut Flour — bulk pricing inquiry",
      "buyer_business_name": "Manila Bakery Supplies Inc.",
      "status": "received",
      "quotations_count": 0,
      "last_activity_at": "2026-08-18T09:00:00Z",
      "created_at": "2026-08-18T09:00:00Z"
    }
  ],
  "meta": {
    "next_cursor": null,
    "prev_cursor": null,
    "per_page": 20
  }
}
```

**POST `/api/v1/hub/rfqs/{rfqRecipient}/decline`**
```json
// Request
{
  "decline_reason": "We cannot fulfil this volume within the requested timeline."
}
// Response 200
{
  "data": {
    "rfq_recipient_id": 101,
    "status": "no_bid",
    "decline_reason": "We cannot fulfil this volume within the requested timeline."
  }
}
```

### 3.4 Quotations (Seller)

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/v1/hub/rfqs/{rfqRecipient}/quotations` | Create draft quotation |
| PUT | `/api/v1/hub/quotations/{quotation}` | Update draft (only while status=draft) |
| DELETE | `/api/v1/hub/quotations/{quotation}` | Delete draft |
| POST | `/api/v1/hub/quotations/{quotation}/submit` | Submit (becomes immutable) |
| POST | `/api/v1/hub/quotations/{quotation}/withdraw` | Withdraw submitted quotation |

**POST `/api/v1/hub/rfqs/{rfqRecipient}/quotations`**
```json
// Request
{
  "notes": "Pricing valid for monthly contract. Minimum 3-month commitment required.",
  "items": [
    {
      "description": "Halal Coconut Flour, Food Grade A, 25kg sack",
      "quantity": 20,
      "unit": "sack",
      "unit_price": 3500.00
    }
  ],
  "attachment_media_ids": [10001]
}
// Response 201
{
  "data": {
    "id": 45,
    "rfq_id": 78,
    "rfq_recipient_id": 101,
    "status": "draft",
    "notes": "Pricing valid for monthly contract. Minimum 3-month commitment required.",
    "currency": "PHP",
    "total_amount": 70000.00,
    "valid_until": null,
    "items": [
      {
        "id": 112,
        "description": "Halal Coconut Flour, Food Grade A, 25kg sack",
        "quantity": 20.00,
        "unit": "sack",
        "unit_price": 3500.00,
        "subtotal": 70000.00,
        "sort_order": 0
      }
    ],
    "attachments": [
      { "media_id": 10001, "original_filename": "formal-quotation.pdf" }
    ],
    "created_at": "2026-08-18T11:00:00Z"
  }
}
```

**POST `/api/v1/hub/quotations/{quotation}/submit`**
```json
// Response 200
{
  "data": {
    "id": 45,
    "status": "submitted",
    "submitted_at": "2026-08-18T11:30:00Z",
    "valid_until": "2026-09-01T11:30:00Z",
    "buyer_snapshot": {
      "business_name": "Manila Bakery Supplies Inc.",
      "billing_address": {
        "address_1": "Unit 5, Golden Tower",
        "city": "Makati",
        "province": "Metro Manila",
        "zip_code": "1226",
        "country_code": "PH"
      },
      "shipping_address": {
        "address_1": "Warehouse 3, Block 7",
        "address_2": "Laguna Technopark",
        "city": "Biñan",
        "province": "Laguna",
        "zip_code": "4024",
        "country_code": "PH"
      }
    },
    "total_amount": 70000.00,
    "currency": "PHP"
  }
}
// Response 409 (not in draft status)
{
  "error": {
    "code": "CONFLICT",
    "message": "Only draft quotations can be submitted."
  }
}
```

**POST `/api/v1/hub/quotations/{quotation}/withdraw`**
```json
// Response 200
{
  "data": {
    "id": 45,
    "status": "withdrawn"
  }
}
// Response 409 (not in submitted status)
{
  "error": {
    "code": "CONFLICT",
    "message": "Only submitted quotations can be withdrawn."
  }
}
```

### 3.5 Quotation Actions (Buyer)

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/v1/quotations/{quotation}/award` | Award a submitted quotation |
| POST | `/api/v1/quotations/{quotation}/reject` | Reject a submitted quotation |

**POST `/api/v1/quotations/{quotation}/award`**
```json
// Response 200
{
  "data": {
    "id": 45,
    "status": "awarded",
    "rfq_id": 78
  }
}
// Response 409 (not in submitted status — race condition guard)
{
  "error": {
    "code": "CONFLICT",
    "message": "This quotation is no longer in a state that can be awarded. It may have expired or been withdrawn."
  }
}
```

**POST `/api/v1/quotations/{quotation}/reject`**
```json
// Response 200
{
  "data": {
    "id": 45,
    "status": "rejected",
    "rfq_id": 78
  }
}
```

### 3.6 Chat / Messaging

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/conversations` | List user's conversations (as buyer or seller) |
| GET | `/api/v1/conversations/{conversation}/threads` | List threads in a conversation |
| POST | `/api/v1/conversations/{conversation}/threads` | Create new thread |
| GET | `/api/v1/threads/{thread}/messages` | List messages (cursor paginated, newest last) |
| POST | `/api/v1/threads/{thread}/messages` | Send message |

**GET `/api/v1/conversations`**
```json
// Response 200
{
  "data": [
    {
      "id": 5,
      "counterpart": {
        "type": "shop",
        "id": 233,
        "name": "Maslaha Foods Co."
      },
      "threads_count": 3,
      "last_message_at": "2026-08-18T14:30:00Z",
      "created_at": "2026-08-18T09:00:00Z"
    }
  ],
  "meta": {
    "next_cursor": null,
    "prev_cursor": null,
    "per_page": 20
  }
}
```

**POST `/api/v1/threads/{thread}/messages`**
```json
// Request
{
  "body": "Thanks for the quotation! Can you confirm the lead time for the first shipment?",
  "attachment_media_ids": []
}
// Response 201
{
  "data": {
    "id": 892,
    "thread_id": 14,
    "sender": {
      "id": 42,
      "name": "Amina Reyes"
    },
    "body": "Thanks for the quotation! Can you confirm the lead time for the first shipment?",
    "attachments": [],
    "created_at": "2026-08-18T14:35:00Z"
  }
}
```

**POST `/api/v1/conversations/{conversation}/threads`**
```json
// Request
{
  "title": "Packaging customization discussion"
}
// Response 201
{
  "data": {
    "id": 15,
    "conversation_id": 5,
    "title": "Packaging customization discussion",
    "rfq_id": null,
    "is_default": false,
    "created_at": "2026-08-18T15:00:00Z"
  }
}
```

---

## 4. UI Component Architecture — Phase 2 Additions

### 4.1 Component hierarchy

```
resources/views/livewire/
├── account/                              (NEW — Buyer portal)
│   ├── buyer-profile-form.blade.php      [Form — business name, billing/shipping address]
│   ├── rfq-list.blade.php                [Table — sent RFQs with status filter tabs]
│   ├── rfq-detail.blade.php              [Detail — RFQ info, recipient status, quotations list]
│   └── chat-inbox.blade.php              [Inbox — conversations list, thread selector, message stream]
│
├── company-hub/                          (EXISTING — additions)
│   ├── rfq-queue.blade.php               (NEW) [Table — received RFQs with status filter tabs]
│   ├── rfq-detail.blade.php              (NEW) [Detail — buyer info, RFQ content, action buttons]
│   ├── quotation-builder.blade.php       (NEW) [Form — line items, notes, attachments, submit]
│   └── chat-inbox.blade.php              (NEW) [Inbox — same shared component, seller context]
│
├── storefront/                           (EXISTING — modifications)
│   └── product-detail.blade.php          (MODIFIED — add "Request Quote" button, login-gated)
│
└── shared/                               (NEW — shared chat/negotiation components)
    ├── conversation-panel.blade.php      [Layout — sidebar threads + message area]
    ├── thread-list.blade.php             [List — threads within a conversation]
    ├── message-stream.blade.php          [Stream — messages with Reverb real-time updates]
    ├── rfq-card.blade.php                [Card — RFQ summary rendered in chat thread]
    └── quotation-card.blade.php          [Card — quotation summary rendered in chat thread]
```

### 4.2 Requirement → Component Mapping

| PRD Requirement | UI Component(s) | Notes |
|---|---|---|
| §4.1 — Buyer company/address profile | `account/buyer-profile-form` | Prompted at first RFQ attempt if incomplete; also editable from account portal |
| §4.2 — RFQ creation ("Request Quote") | `storefront/product-detail` (button) + modal/page with RFQ form | Entry point on product page; pre-fills title from product name; targets one shop |
| §4.2 — RFQ attachments | File upload within RFQ form | Reuses existing `WithFileUploads` pattern from Phase 1 |
| §4.3 — Seller RFQ queue | `company-hub/rfq-queue` | Status-filter tabs: received / reviewed / in_progress / no_bid |
| §4.3 — Seller RFQ review & response | `company-hub/rfq-detail` | Shows buyer snapshot, description, attachments; action buttons for status transitions |
| §4.4 — Quotation builder | `company-hub/quotation-builder` | Repeatable line-item form (Alpine-driven add/remove rows), notes, file attachments |
| §4.4 — Quotation submission | Submit action within `quotation-builder` | Confirmation modal before submit (immutability warning) |
| §4.7 — Award quotation | `account/rfq-detail` | Award/reject buttons on each submitted quotation card |
| §4.7 — Close RFQ | `account/rfq-detail` | Close button with confirmation modal |
| §4.6 — Chat conversation panel | `shared/conversation-panel` + `shared/thread-list` + `shared/message-stream` | Shared between buyer (`account/chat-inbox`) and seller (`company-hub/chat-inbox`) |
| §4.6 — RFQ/quotation cards in thread | `shared/rfq-card` + `shared/quotation-card` | Rendered inline within message stream; reflect current status with appropriate badges |
| §3 Req 14 — Buyer tracks sent RFQs | `account/rfq-list` | Table with cursor pagination, status filters |
| §3 Req 14 — Seller tracks received RFQs | `company-hub/rfq-queue` | Table with cursor pagination, status filters |

### 4.3 State management (Phase 2 additions)

| State type | Where it lives | Mechanism |
|---|---|---|
| RFQ/quotation data | PostgreSQL | Livewire component properties hydrate from Eloquent models |
| Quotation line items (draft editing) | Livewire public property (`array $items`) | Alpine.js for add/remove row UX, Livewire sync on save/submit |
| Chat messages (real-time) | PostgreSQL + Reverb broadcast | Messages persisted to DB on send; broadcast to `conversation.{id}` channel; Livewire `#[On('MessageSent')]` listener appends to the DOM |
| Notification badges (new RFQ, new message) | Reverb `user.{id}` channel | Livewire component listens for badge-count updates; clears on page visit |

---

## 5. Background Jobs & Events — Phase 2

### 5.1 New Scheduled Jobs

| Job | Schedule | Logic | Idempotency |
|---|---|---|---|
| `SellerNoBidTimeoutJob` | Daily, 03:00 UTC | Query `rfq_recipients` WHERE `status IN ('received','reviewed','in_progress') AND last_activity_at < NOW() - INTERVAL '7 days'` → set `status = 'no_bid'`, dispatch `RfqNoBidNotification` to buyer | Only transitions rows not already in `no_bid`; safe to run repeatedly |
| `RfqAutoCloseJob` | Daily, 03:15 UTC | Query `rfqs` WHERE `status = 'open' AND last_activity_at < NOW() - INTERVAL '7 days'` → within a DB transaction: set `status = 'closed', closed_at = NOW()`; cascade any child `quotations` in `submitted` to `not_awarded`; dispatch `RfqClosedNotification` to seller | Only transitions `open` RFQs; child quotation cascade uses `WHERE status = 'submitted'` — already-terminal quotations untouched |
| `QuotationExpiryJob` | Daily, 03:30 UTC | Query `quotations` WHERE `status = 'submitted' AND valid_until < NOW()` → set `status = 'expired'`, dispatch `QuotationExpiredNotification` to both parties | Only transitions `submitted` quotations past their `valid_until`; already-expired rows are no-ops |

All jobs are registered in `app/Console/Kernel.php` (or `routes/console.php`) with `->daily()` scheduling and `->withoutOverlapping()`.

### 5.2 Event-Driven Jobs

| Event | Trigger | Actions |
|---|---|---|
| `RfqCreated` | Buyer submits an RFQ | 1. Dispatch `NewRfqNotification` to seller (email + Reverb broadcast on `user.{shopOwnerId}`) <br> 2. Create or reuse `conversations` row for the buyer–seller pair <br> 3. Create `conversation_threads` row linked to the RFQ <br> 4. Post system message in thread ("RFQ opened") with `shared/rfq-card` |
| `QuotationSubmitted` | Seller submits a quotation | 1. Dispatch `NewQuotationNotification` to buyer <br> 2. Post quotation card as system message in the RFQ's thread <br> 3. Update `rfq_recipients.last_activity_at` and `rfqs.last_activity_at` |
| `QuotationAwarded` | Buyer awards a quotation | 1. Dispatch `QuotationAwardedNotification` to seller <br> 2. Post status-update message in thread <br> 3. Update `rfqs.last_activity_at` |
| `QuotationRejected` | Buyer rejects a quotation | 1. Dispatch `QuotationRejectedNotification` to seller <br> 2. Update `rfqs.last_activity_at` |
| `QuotationWithdrawn` | Seller withdraws a quotation | 1. Dispatch `QuotationWithdrawnNotification` to buyer <br> 2. Update `rfqs.last_activity_at` |
| `RfqClosed` | Buyer manually closes OR `RfqAutoCloseJob` | 1. Cascade `submitted` quotations → `not_awarded` (within DB transaction) <br> 2. Dispatch `RfqClosedNotification` to seller <br> 3. Post system message in thread ("RFQ closed") |
| `MessageSent` | User sends a chat message | 1. Broadcast to `conversation.{id}` private channel via Reverb <br> 2. Dispatch `NewMessageNotification` to counterpart (email if offline) <br> 3. Update `rfqs.last_activity_at` if thread is RFQ-linked (resets auto-close timer) |

### 5.3 Broadcast Channels

| Channel | Auth logic | Events broadcast |
|---|---|---|
| `conversation.{id}` (NEW) | User must be either `conversations.buyer_user_id` OR the owner of `conversations.shop_id` | `MessageSent`, typing indicators |
| `user.{id}` (EXISTING) | User owns the ID | `NewRfqNotification`, `NewQuotationNotification`, `QuotationAwardedNotification`, `QuotationRejectedNotification`, `QuotationWithdrawnNotification`, `RfqClosedNotification`, `RfqNoBidNotification`, `QuotationExpiredNotification`, `NewMessageNotification` |

Channel authorization (in `routes/channels.php`):
```php
Broadcast::channel('conversation.{id}', function (User $user, int $id) {
    $conversation = Conversation::findOrFail($id);

    return $user->id === $conversation->buyer_user_id
        || $user->id === $conversation->shop->user_id;
});
```

---

## 6. Security & Authorization — Phase 2 Additions

### 6.1 New Policies

| Policy | Model | Key rules |
|---|---|---|
| `RfqPolicy` | `Rfq` | `viewAny`: authenticated buyer; `view`: buyer owns the RFQ; `create`: buyer has a complete `buyer_profile`; `close`: buyer owns the RFQ AND status is `open` |
| `RfqRecipientPolicy` | `RfqRecipient` | `view`: seller's shop matches `rfq_recipients.shop_id`; `review`/`inProgress`/`decline`: seller owns the shop AND recipient not in terminal `no_bid` state |
| `QuotationPolicy` | `Quotation` | `create`: seller's shop matches the rfq_recipient's shop; `update`/`delete`: seller owns AND status is `draft`; `submit`: seller owns AND status is `draft` AND has ≥1 line item; `withdraw`: seller owns AND status is `submitted`; `award`/`reject`: buyer owns the parent RFQ AND quotation status is `submitted` |
| `ConversationPolicy` | `Conversation` | `view`/`createThread`: user is `buyer_user_id` OR owner of `shop_id` |
| `MessagePolicy` | `Message` | `create`: user is a participant in the parent conversation (buyer or shop owner) |

### 6.2 Race Condition Handling

The **award** action is the primary race-condition risk (buyer loads page with quotation showing `submitted`, but between load and click, it may have been withdrawn or expired). The implementation must:

1. Re-check `quotation.status === 'submitted'` at execution time inside a DB transaction with a `SELECT ... FOR UPDATE` lock on the quotation row.
2. Return HTTP 409 (`CONFLICT`) if the status has changed, with a user-friendly message explaining what happened.

Similarly, **RFQ close** must atomically cascade child quotations within a transaction to prevent a quotation from being awarded concurrently with the close.

### 6.3 Data Privacy

- **Buyer snapshot data** (business name, billing/shipping addresses) is NEVER exposed in search/discovery surfaces (Typesense indexes, public API endpoints, storefront). It is only visible:
  - To the buyer themselves (their own profile and RFQs)
  - To the specific seller(s) who received that RFQ
  - Within the quotation context (embedded in the quotation's own snapshot)
- **Chat messages** are private to the conversation participants — no admin access in Phase 2 scope.
- **Draft quotations** are private to the seller — not visible to the buyer until submitted.

### 6.4 Input Validation

| Endpoint | Key validations |
|---|---|
| `PUT /buyer-profile` | `business_name` required, max 255; address fields max 255; `country_code` must be valid ISO 3166-1 alpha-2 |
| `POST /rfqs` | `title` required, max 500; `description` optional; `shop_id` must exist + active; `attachment_media_ids` must belong to the user |
| `POST /quotations` | `items` required, min 1; each item: `description` required max 500, `quantity` > 0, `unit` required max 30, `unit_price` > 0; `attachment_media_ids` must belong to the user |
| `POST /messages` | `body` required, max 10000 chars; `attachment_media_ids` must belong to the user, max 5 per message |

### 6.5 Rate Limiting

| Endpoint group | Limit |
|---|---|
| RFQ creation | 10 per hour per user |
| Message sending | 60 per minute per user |
| Quotation creation | 20 per hour per user |

---

## 7. Cross-References

| Document | What it provides |
|---|---|
| `Phase 1 design.md` §1 | Full system architecture (topology, data flow, deployment) — not repeated here |
| `Phase 1 design.md` §2 | Existing schema (users, shops, products, media, categories) that Phase 2 extends |
| `Phase 1 design.md` §3 | API conventions, auth, error envelope format reused by Phase 2 endpoints |
| `Phase 1 design.md` §4 | Existing Livewire component hierarchy that Phase 2 adds to |
| `Phase 1 design.md` §5 | Existing job/queue/integration infrastructure reused by Phase 2 jobs |
| `Phase 2 requirements.md` | PRD, state machines (§5), detailed feature specs, user flows, milestones, non-functional requirements |

---

---

## Epic 20 — Product Variant Attribute System & Seller Tooling

**Sessions:** Two independent sessions on 2026-08-22.
**Session A** (prior session, confirmed by git diff) covered notifications, schema migrations, category management pages, and the initial product form schema editor.
**Session B** (this session) fixed bugs introduced in Session A, completed the auto-populate flow, overhauled the variant manager, and added the demo seeder.

---

### 20.1 Architectural Overview

A three-tier attribute schema propagates from admin → product → variant:

```
Admin: Category.variant_attributes_schema (JSONB)
    ↓ auto-populated when seller selects a category
Seller: Product.variant_attributes_schema (JSONB, editable)
    ↓ rendered as inputs in the variant add/edit modal
Per-SKU: ProductVariant.attributes (JSONB, values per variant)
```

The category schema is the template; the product schema is the seller's editable fork; the variant attributes are the concrete per-SKU values. Sellers never have to manually configure schemas — selecting a category populates them automatically.

**Schema key format:** `attr_<uniqid()>` — opaque, stable keys. Labels are human-readable; keys are the stable reference used in `ProductVariant.attributes`.

**Schema shape (both `categories.variant_attributes_schema` and `products.variant_attributes_schema`):**
```json
{
  "attr_6a2f1b3": {
    "label": "Cut",
    "type": "select",
    "options": "Whole,Half,Quarter,Breast,Thigh",
    "required": true
  },
  "attr_9c4d2e1": {
    "label": "Weight",
    "type": "number",
    "options": "",
    "required": true
  }
}
```

`type` is one of `select | text | number`. For `select`, `options` is a comma-separated string. `options` is always a string (not an array) — consistent with how the admin form saves and the variant form reads it.

**`ProductVariant.attributes` shape:**
```json
{
  "attr_6a2f1b3": "Whole",
  "attr_9c4d2e1": "1.2"
}
```

---

### 20.2 Database Changes

| Table | Column | Type | Notes |
|---|---|---|---|
| `categories` | `variant_attributes_schema` | `JSONB NOT NULL DEFAULT '{}'` | Added in Session A |
| `products` | `variant_attributes_schema` | `JSONB NOT NULL DEFAULT '{}'` | Added in Session A |

No additional migrations in Session B.

---

### 20.3 Session A Changes (Confirmed by git diff)

#### Notifications — context-aware URL fix
8 notification classes updated (see Epic 19 in tasks.md). Core change: seller-facing notifications now accept `RfqRecipient` instead of `Rfq` to generate `/hub/rfqs/{rfqRecipient_id}` URLs. `NewMessageNotification` checks `$notifiable->shop->status === 'active'` instead of role. 2 listeners + 1 job updated accordingly.

#### Schema migrations
- `2026_08_22_090626_add_variant_attributes_schema_to_categories_table.php`
- `2026_08_22_090710_add_variant_attributes_schema_to_products_table.php`
- `Category.php` + `Product.php` — added `variant_attributes_schema` to `$fillable` and cast as `'array'`

#### Category management pages
- `CategoryCreate` + `CategoryEdit` Livewire components created as full pages (not modals)
- Routes: `/admin/categories/create`, `/admin/categories/{category}/edit`
- `CategoryManager` blade updated: "Add Category" button and edit pencil changed from `wire:click` inline actions to `href="{{ route(...) }}" wire:navigate` — the category create/edit modal was replaced by dedicated pages

#### Product Form — initial schema editor
`ProductForm.php` and `product-form.blade.php` updated with `variantAttributesSchema` property, `addAttribute()`, `removeAttribute()`. An `updatedCategoryIds()` hook and `loadFromCategory()` button were added. **Note:** the `$toggle` approach for category pills introduced here was broken and fixed in Session B.

---

### 20.4 Session B Changes (This Session)

#### Bugs fixed

| File | Bug | Fix |
|---|---|---|
| `variant-manager.blade.php` | `$mergedAttributeSchema` → `ErrorException: Undefined variable` | `#[Computed]` in Livewire 4 accessed as `$this->mergedAttributeSchema` in blade |
| `variant-manager.blade.php` | Required asterisk `{{ ... ? '<span>*</span>' : '' }}` rendered as escaped text | Replaced with `@if` block |
| `VariantManager.php` | `mergedAttributeSchema()` used `pluck()->collapse()` — fails on keyed JSONB | Simplified to `return $product->variant_attributes_schema ?? []` |
| `product-form.blade.php` | `variant="secondary"` on `<flux:button>` — `UnhandledMatchError` (no such Flux 2.x variant) | Changed to `variant="outline"` |
| `product-form.blade.php` | `wire:click="$toggle('category_ids', id)"` — `$toggle` is `!value` (boolean flip), not array push/pop | Replaced with `wire:click="toggleCategory(id)"` Livewire action |
| `hs-modal.blade.php` | `ParseError: unexpected ":"` — `@if` blocks inside Blade component opening tags invalid | Replaced with PHP ternary string expressions |

#### ProductForm — auto-populate product options

`toggleCategory(int $id)` replaces the broken `$toggle` + `updatedCategoryIds()` pattern:

```php
public function toggleCategory(int $id): void
// Pushes/pops $id from $category_ids.
// On CREATE: if schema is empty, calls populateSchemaFromCategories().
// On EDIT: never auto-populates (saved schema is authoritative).
```

```php
public function reloadSchemaFromCategories(): void
// Clears and rebuilds schema from all selected categories.
// Works on both create and edit. On edit: persists to DB immediately.
// Shows warning toast if none of the selected categories have schemas.

private function populateSchemaFromCategories(): void
// Merges schemas from all selected categories.
// Deduplicates by lowercase label — the same attribute name from two
// categories is only added once.
```

**Why not `wire:model` on a checkbox array?** `wire:model` on checkbox arrays requires the `updated*` hook which is unreliable when the property is mutated via JavaScript. A dedicated action owns the mutation and the side effect explicitly.

#### Product Options UI — all three forms

Applied to `product-form.blade.php`, `category-create.blade.php`, `category-edit.blade.php`:

- Category pills: `<button wire:click="toggleCategory()">` with server-side `in_array()` active state and a checkmark icon when selected
- Attribute rows: `flex flex-wrap lg:flex-nowrap` with fixed widths (`w-64` label, `w-32` type, `flex-1` choices)
- Column headers above the first row only (`@if ($loop->first)`)
- **Choices field always in DOM** — `x-data="{ type: '...' }"` per row, `x-model="type"` on the select alongside `wire:model`. `x-bind:disabled="type !== 'select'"` + `opacity-40 cursor-not-allowed` disables it instantly without a round-trip
- Dashed-border empty state with context-aware message

#### Reusable `x-hs-modal` Blade component

`resources/views/components/hs-modal.blade.php`:

```blade
<x-hs-modal model="showVariantModal" title="Add Variant" subtitle="..." size="lg">
    ...slot...
</x-hs-modal>
```

Props: `model` (wire:model), `name` (named modal), `title`, `subtitle`, `size` (`sm|md|lg|xl|2xl`). Wraps `flux:modal` with a consistent bordered title/subtitle header.

#### VariantManager overhaul

**Before:** inline table with form inputs in every cell — unusable on mobile, required a "Save All" button.

**After:** read-only variant cards + modal for add/edit with immediate persistence.

`VariantManager.php` new state:
```php
public bool $showVariantModal = false;
public ?int $editingVariantId = null;  // null = create, int = edit by DB id
public string $draftSku, $draftPrice, $draftMoq, $draftUnit;
public string $draftLeadTime, $draftPackSize, $draftStatus;
public array $draftAttributes = [];    // pre-keyed from schema via blankAttributes()
```

Key method changes:

| Old | New | Change |
|---|---|---|
| `addVariant()` | `openAddVariant()` | Opens modal with blank draft |
| _(none)_ | `openEditVariant(int $id)` | Opens modal pre-filled from DB record |
| `saveAll()` | `saveVariant()` | Validates + persists single variant immediately |
| `toggleVariantStatus(int $index)` | `toggleStatus(int $id)` | Operates by DB id, persists immediately |
| `removeVariant(int $index)` | `removeVariant(int $id)` | Operates by DB id |
| `saveQuantityBreaks()` (array-based) | `saveQuantityBreaks()` (DB write) | Now persists directly to the DB record |

`blankAttributes()` returns `array_fill_keys(array_keys($this->mergedAttributeSchema()), '')` — ensures all schema keys are present in `draftAttributes` even when creating a new variant.

**Blade cards** — each variant shows:
- Top bar: SKU (monospace) + clickable status pill + Qty Breaks button (with count badge) + edit + delete
- Body: Price | Min Order+Unit | Lead Time | Pack Size
- Attribute tag row: `Label: Value` pills for non-empty attributes

**Add/Edit modal** (via `x-hs-modal size="lg"`):
- Fields: SKU, Status, Price, MOQ, Unit, Lead Time, Pack Size
- "Product Options" section (only when schema is non-empty): schema-driven inputs rendered from `mergedAttributeSchema`, bound to `draftAttributes.{key}`

**Quantity Breaks modal** (via `x-hs-modal size="md"`): 3-column grid (Min Qty | Price | Delete), saves directly to DB.

#### Demo seeder

`DemoUsersSeeder.php` seeds:
- `buyer@gmail.com` / `password` — role `buyer`, `BuyerProfile` (Santos Food Enterprises, Makati City)
- `seller@gmail.com` / `password` — role `seller`, active `Shop` (Macapaar Halal Foods Corporation, Cotabato City), 5 published products with Unsplash featured images

Registered in `DatabaseSeeder` after `DemoDataSeeder`. All use `firstOrCreate` — safe to re-run.

---

### 20.5 Architectural Decisions

| Decision | Rationale |
|---|---|
| `options` stored as comma-separated string | Consistent across admin form (saves as string) and seller/variant forms (splits on `,` + `trim()` at render). Not normalized to array. |
| Product schema is source of truth for variants | No need to re-query category schemas on every variant page load. The product schema is the seller's already-customised fork. `mergedAttributeSchema()` returns `$product->variant_attributes_schema` directly. |
| `toggleCategory()` action instead of `wire:model` | `$toggle` is a boolean flipper. `wire:model` on array checkboxes doesn't reliably trigger `updated*` hooks. Explicit action owns both mutation and side effect. |
| Immediate save on variant modal submit | Eliminates risk of losing unsaved changes when navigating between tabs. No "Save All" needed. Same principle applied to quantity breaks. |
| `x-hs-modal` wraps `flux:modal` | Ensures consistent title/subtitle header, width scale, and spacing across all modals in the app. Pass `model=` for `wire:model` binding or `name=` for named modals. |

---

### 20.6 Session C Changes (This Session — Quotation Variant Link + Unit Picker)

#### Quotation line item soft variant link

**Problem:** The quotation builder was a blank freeform form — sellers had to re-type product descriptions, prices, and units that already existed on their published variants. No traceability between quoted items and the seller's catalogue.

**Decision:** Nullable `variant_id` FK (`ON DELETE SET NULL`) + `variant_snapshot` JSONB on `quotation_items`. The FK is optional — freeform items work as before. At submission the snapshot is written, making each item self-contained regardless of future catalogue changes.

**Why not a mandatory FK:** Freeform items are a valid use case (custom packaging, one-off bundles). A mandatory FK would force sellers to add every quoted item to their catalogue first. `ON DELETE RESTRICT` would also block routine catalogue cleanup.

**New `quotation_items` columns:**

| Column | Type | Notes |
|---|---|---|
| `variant_id` | `BIGINT NULL FK → product_variants ON DELETE SET NULL` | Soft link; null for freeform items |
| `variant_snapshot` | `JSONB NULL` | Written at submit: `{sku, product_name, description, unit_price, unit, moq, pack_size}` |

**`ProductVariant.toQuotationSnapshot()` returns:**
```json
{
  "sku": "ALA-CHK-1200",
  "product_name": "Halal Frozen Whole Chicken",
  "description": "1.2 kg average weight per bird",
  "unit_price": "220.00",
  "unit": "piece",
  "moq": 100,
  "pack_size": "10 pieces per carton"
}
```

**`QuotationBuilder` new behaviour:**
- `shopVariants()` `#[Computed]` — loads all published variants from the seller's shop, returned as a flat array for the picker dropdown
- `selectVariant(int $index, ?int $variantId)` — security: validates variant belongs to this shop before filling; auto-fills `description`, `unit`, `unit_price`, `quantity` (from MOQ); `null` clears back to freeform
- `captureVariantSnapshots()` — called at `submitQuotation()`, bulk-writes snapshots via a single variant query + loop

**Wire:change bug and fix:**
`wire:change="..., Number($event.target.value)"` threw 500 because `Number()` is a JavaScript global that Livewire's server-side expression parser doesn't recognise. Fixed by switching to `x-on:change="$wire.selectVariant(..., parseInt(...))"` with an `x-data` Alpine wrapper — the expression stays client-side, then calls Livewire as RPC.

---

#### Unit-of-measure picker

**Problem:** Unit fields were free-text `flux:input` everywhere, leading to inconsistent entries (`kg`, `Kg`, `KG`, `kilogram`). No curation.

**Solution:** `config/units.php` + `x-hs-unit-select` Blade component.

`config/units.php` — 23 curated units in 5 groups (Weight, Volume, Packaging, Count, Other). Single source of truth; extend by editing one file.

`x-hs-unit-select` — wraps a native `<select>` with `<optgroup>` sections. "Other…" option reveals a debounced text input for custom values. Syncs to Livewire via `$wire.set()` in Alpine watchers (not `wire:model` — anonymous Blade components don't have `@this` scope). Handles existing DB values not in the list by auto-selecting "Other…" and pre-filling the text.

Applied to: `draftUnit` in variant manager modal, `items.N.unit` in quotation builder line items.

---

#### UI pattern rules documented

All patterns established across this session's blade work were recorded in `.ai/rules/views.md`:
- Standard page card shell
- Multi-card page layout (`space-y-6`)
- Form layout (section dividers, action row, validation errors)
- Info and warning notice boxes
- Modal pattern (`x-hs-modal` always, never `flux:modal` directly)
- Status-dependent card backgrounds
- Empty states (full and dashed-border inline)
- Attachment lists, file upload input, inline data tables
- Reusable component reference (`x-hs-modal`, `x-hs-unit-select`, `x-hs-status-pill`, `x-hs-empty-state`)

4 binding rules recorded via `record-rule`:

| Rule | Scope |
|---|---|
| Standard card shell | All blade templates |
| Always use `x-hs-modal`, never `flux:modal` directly | All blade templates |
| Always use `x-hs-unit-select` for unit fields | All blade templates |
| Form layout: `space-y-6`, border-t dividers, action row | All blade templates |

---

### 20.7 Updated Architectural Decisions

| Decision | Rationale |
|---|---|
| Nullable `variant_id` on `quotation_items` | Freeform quotation items remain valid. `ON DELETE SET NULL` means catalogue cleanup is never blocked by historical quotations. |
| Snapshot at submission, not at draft time | Draft is mutable; snapshot serves as immutable audit record. Written once at `submitQuotation()` alongside `buyer_snapshot`. |
| `config/units.php` not a DB table | Units don't need admin UI or versioning at this scale. A PHP config is instantly available, cacheable, and requires no migration. Add units by editing one file. |
| `x-hs-unit-select` uses `$wire.set()` not `wire:model` | Anonymous Blade components don't have access to `@this`. `$wire` is the Alpine-accessible Livewire proxy, available in any template where Livewire is active. |
