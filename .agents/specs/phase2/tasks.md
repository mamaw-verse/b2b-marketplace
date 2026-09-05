# tasks.md
## Execution Plan — Phase 2: Negotiation (RFQ, Quotation, Chat)

| | |
|---|---|
| **Consumes** | `requirements.md`, `design.md` |
| **Audience** | AI Coding Agent (autonomous/semi-autonomous execution) |
| **Execution mode** | Sequential by Epic; tasks within an Epic may parallelize only where marked `[parallel-safe]` |
| **Stack** | PHP/Laravel · Livewire · Tailwind · Redis · PostgreSQL · Reverb · Typesense · Minio/S3 · Laravel Cloud |
| **Precondition** | Phase 1 Epics E0–E9 **all complete** (`[x]`). All Phase 1 tables, models, policies, routes, and jobs exist. |

---

## How to use this document

- Each task has a **stable ID** (`E<epic>.T<task>`), a **status checkbox**, explicit **file paths**, and **acceptance criteria**.
- The agent MUST complete tasks in ID order within an Epic unless marked `[parallel-safe]`, and MUST NOT start an Epic until all of its listed **Depends on** tasks are `[x]`.
- After each task, run the command(s) in **Verify** before marking it `[x]`. If verification fails, fix and re-verify — do not proceed.
- Do not invent schema, routes, or fields not present in `design.md`. If a gap is found, stop and flag it rather than guessing (see §"Escalation" at the end).
- All new Laravel-ecosystem code should be generated with **Laravel Boost's MCP tools/guidelines active** — use Boost's schema/docs/tinker tools to verify against the real app state rather than assuming.
- Phase 2 Epics start at **E11** to avoid collision with Phase 1's E0–E10.

---

## Epic 11 — Buyer Profile & Schema Foundation

**Goal:** `buyer_profiles` table + model + factory + buyer profile CRUD (web + API).
**Depends on:** Phase 1 complete (E9)

- [x] **E11.T1** Create new PostgreSQL enum types migration `[parallel-safe]`
  - File: `database/migrations/xxxx_create_phase2_enum_types.php`
  - Create types: `rfq_status`, `rfq_recipient_status`, `quotation_status` exactly as defined in design.md §2.1.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    vendor/bin/sail artisan tinker --execute 'DB::select("SELECT typname FROM pg_type WHERE typname IN ('"'"'rfq_status'"'"', '"'"'rfq_recipient_status'"'"', '"'"'quotation_status'"'"')");'
    ```
    All 3 enum types listed.

- [x] **E11.T2** Create `buyer_profiles` migration + model + factory `[parallel-safe]`
  - Files:
    - `database/migrations/xxxx_create_buyer_profiles_table.php`
    - `app/Models/BuyerProfile.php`
    - `database/factories/BuyerProfileFactory.php`
  - Schema matches design.md §2.2 `buyer_profiles` DDL exactly: `user_id UNIQUE`, billing/shipping address fields, `contact_phone`, `contact_email`, timestamps.
  - Model defines `belongsTo(User::class)` relationship; User model adds `hasOne(BuyerProfile::class)`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    vendor/bin/sail artisan tinker --execute 'App\Models\BuyerProfile::factory()->make();'
    ```
    Migration runs; factory instantiates without error; `idx_buyer_profiles_user` unique index exists.

- [x] **E11.T3** Create BuyerProfile Livewire component (account/buyer-profile-form) with create/edit form
  - Files:
    - `app/Livewire/Account/BuyerProfileForm.php`
    - `resources/views/livewire/account/buyer-profile-form.blade.php`
  - Form fields: `business_name` (required), billing address (address_1, address_2, city, province, zip_code, country_code), shipping address (same fields), `contact_phone`, `contact_email`.
  - On save: creates or updates `buyer_profiles` row for `auth()->user()`.
  - Uses design tokens (CSS variables) per project conventions.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=BuyerProfileForm
    ```

- [x] **E11.T4** Create API endpoints GET/PUT `/api/v1/buyer-profile`
  - Files:
    - `app/Http/Controllers/Api/V1/BuyerProfileController.php`
    - `app/Http/Requests/BuyerProfileRequest.php`
  - GET returns buyer profile or 404; PUT creates or updates (upsert).
  - Response shape matches design.md §3.1 exactly.
  - Register routes in `routes/api.php` under `v1` prefix with `auth:sanctum` middleware.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/buyer-profile
    ```
    Shows GET and PUT routes.

- [x] **E11.T5** Add buyer account routes to `web.php` (`/account` prefix, auth+verified middleware)
  - File: `routes/web.php`
  - Routes: `/account/profile` → `BuyerProfileForm` Livewire component (full-page).
  - Apply `auth`, `verified` middleware group.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=account
    ```
    Shows `/account/profile` route with correct middleware.

- [x] **E11.T6** Feature tests for buyer profile CRUD
  - File: `tests/Feature/BuyerProfileTest.php`
  - Test cases:
    - Authenticated user can create buyer profile via API (PUT → 200).
    - Authenticated user can retrieve their profile (GET → 200).
    - Unauthenticated request → 401.
    - User without profile → GET returns 404.
    - Validation: missing `business_name` → 422.
    - Livewire component renders and saves successfully.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=BuyerProfileTest
    ```

---

## Epic 12 — RFQ Data Layer & Creation

**Goal:** RFQ tables, creation flow from product page, snapshot capture.
**Depends on:** E11

- [x] **E12.T1** Create `rfqs` migration
  - File: `database/migrations/xxxx_create_rfqs_table.php`
  - Schema matches design.md §2.2: `user_id` FK (RESTRICT), `title VARCHAR(500)`, `description TEXT`, `status rfq_status DEFAULT 'open'`, `buyer_snapshot JSONB NOT NULL`, `last_activity_at`, `closed_at`, timestamps.
  - Indexes: `idx_rfqs_user`, `idx_rfqs_status`, `idx_rfqs_last_activity` (partial: WHERE status = 'open').
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```
    Table exists with correct columns and indexes.

- [x] **E12.T2** Create `rfq_recipients` migration
  - File: `database/migrations/xxxx_create_rfq_recipients_table.php`
  - Schema matches design.md §2.2: `rfq_id` FK (CASCADE), `shop_id` FK (RESTRICT), `status rfq_recipient_status DEFAULT 'received'`, `decline_reason TEXT`, `last_activity_at`, `reviewed_at`, timestamps.
  - Indexes: `idx_rfq_recipients_rfq`, `idx_rfq_recipients_shop`, `idx_rfq_recipients_status` (partial: WHERE status <> 'no_bid'), unique `idx_rfq_recipients_rfq_shop`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **E12.T3** Create `rfq_attachments` migration `[parallel-safe with E12.T2]`
  - File: `database/migrations/xxxx_create_rfq_attachments_table.php`
  - Schema: composite PK `(rfq_id, media_id)`, FKs per design.md §2.2.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **E12.T4** Create Rfq, RfqRecipient models with relationships + factories
  - Files:
    - `app/Models/Rfq.php` — relationships: `belongsTo(User)`, `hasMany(RfqRecipient)`, `belongsToMany(Media, 'rfq_attachments')`, `hasMany(Quotation)`. Cast `buyer_snapshot` as `array`, `status` as native enum.
    - `app/Models/RfqRecipient.php` — relationships: `belongsTo(Rfq)`, `belongsTo(Shop)`, `hasMany(Quotation)`. Cast `status` as native enum.
    - `database/factories/RfqFactory.php`
    - `database/factories/RfqRecipientFactory.php`
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'App\Models\Rfq::factory()->make(); App\Models\RfqRecipient::factory()->make();'
    ```

- [x] **E12.T5** Add 'Request Quote' button to storefront product-detail page (login-gated)
  - File: `resources/views/livewire/storefront/product-detail.blade.php` (modify existing)
  - Add a "Request Quote" button that:
    - If user is not logged in → redirects to `/login` with `intended` URL.
    - If user is logged in → navigates to RFQ creation form with `shop_id` and product title pre-filled as query params.
  - Verify: manual route check — button renders on product detail page; clicking while logged out redirects to login.

- [x] **E12.T6** Create RFQ creation Livewire component (account/rfq-create-form)
  - Files:
    - `app/Livewire/Account/RfqCreateForm.php`
    - `resources/views/livewire/account/rfq-create-form.blade.php`
  - Component:
    - Accepts optional `shop_id` and `title` via query params (pre-fill from product page).
    - Fields: `title` (max 500), `description`, `shop_id` (select or pre-filled), file attachments.
    - Validates `buyer_profile` exists for the user before submission (returns validation error if not).
    - On submit: creates `rfqs` row with `buyer_snapshot` captured from `buyer_profiles`, creates `rfq_recipients` row, attaches media, sets `last_activity_at = now()`.
    - Dispatches `RfqCreated` event.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RfqCreateForm
    ```

- [x] **E12.T7** Create RfqPolicy
  - File: `app/Policies/RfqPolicy.php`
  - Rules per design.md §6.1:
    - `viewAny`: authenticated buyer.
    - `view`: buyer owns the RFQ (`rfq->user_id === user->id`).
    - `create`: buyer has a complete `buyer_profile`.
    - `close`: buyer owns the RFQ AND status is `open`.
  - Register in `AuthServiceProvider` (or auto-discovery).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RfqPolicyTest
    ```

- [x] **E12.T8** Create API endpoints: POST/GET `/api/v1/rfqs`, GET `/api/v1/rfqs/{rfq}`, POST `/api/v1/rfqs/{rfq}/close`
  - Files:
    - `app/Http/Controllers/Api/V1/RfqController.php`
    - `app/Http/Requests/CreateRfqRequest.php`
    - `app/Http/Resources/RfqResource.php`
    - `app/Http/Resources/RfqListResource.php`
  - Response shapes match design.md §3.2 exactly.
  - Close endpoint: validates ownership + status=open (409 if already closed), within DB transaction sets `status=closed, closed_at=now()`.
  - Rate limiting: 10 RFQ creations per hour per user.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/rfqs
    ```
    Shows POST, GET (index), GET (show), POST (close) routes.

- [x] **E12.T9** Dispatch `RfqCreated` event on creation — creates/reuses conversation + thread
  - Files:
    - `app/Events/RfqCreated.php`
    - `app/Listeners/CreateRfqConversationThread.php`
  - Listener logic per design.md §5.2:
    1. Find or create `conversations` row for `(buyer_user_id, shop_id)` pair.
    2. Create `conversation_threads` row linked to the RFQ (`rfq_id` FK).
    3. Post system message in thread (RFQ opened).
    4. Dispatch `RfqReceivedNotification` to seller.
  - Note: `conversations` and `conversation_threads` tables must exist — this task depends on E16.T1-T2 being completed first OR uses a deferred approach (queue the listener, tables created by E16). **Resolution:** The listener should be queued; if tables don't exist yet, defer implementation of the conversation/thread creation to E16.T13 and only dispatch the notification here.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RfqCreatedEvent
    ```

- [x] **E12.T10** Feature tests for RFQ creation, listing, closing
  - File: `tests/Feature/RfqTest.php`
  - Test cases:
    - Buyer with profile can create RFQ (POST → 201, correct `buyer_snapshot`).
    - Buyer without profile cannot create RFQ (POST → 422).
    - Buyer can list their RFQs (GET → 200, paginated).
    - Buyer can view their RFQ detail (GET → 200, includes recipients).
    - Buyer cannot view another buyer's RFQ (GET → 403).
    - Buyer can close their open RFQ (POST → 200, status=closed).
    - Closing already-closed RFQ → 409.
    - `RfqCreated` event dispatched on creation.
    - `shop_id` must reference an active shop (422 otherwise).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RfqTest
    ```

---

## Epic 13 — Seller RFQ Queue & Review

**Goal:** Company Hub RFQ queue, recipient status transitions, decline.
**Depends on:** E12

- [x] **E13.T1** Create Livewire component hub/rfq-queue (list received RFQs, filter by status)
  - Files:
    - `app/Livewire/CompanyHub/RfqQueue.php`
    - `resources/views/livewire/company-hub/rfq-queue.blade.php`
  - Lists `rfq_recipients` for the seller's shop with status filter tabs: received, reviewed, in_progress, no_bid.
  - Cursor-paginated table showing: RFQ title, buyer business name, status badge, quotations count, last_activity_at.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RfqQueue
    ```

- [x] **E13.T2** Create Livewire component hub/rfq-detail (view RFQ, show buyer requirements + attachments)
  - Files:
    - `app/Livewire/CompanyHub/RfqDetail.php`
    - `resources/views/livewire/company-hub/rfq-detail.blade.php`
  - Shows: buyer snapshot (business name, shipping address), RFQ title/description, attachments (via pre-signed URLs), recipient status, action buttons.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RfqDetail
    ```

- [x] **E13.T3** Implement auto-transition received → reviewed on first view
  - File: `app/Livewire/CompanyHub/RfqDetail.php` (in `mount()` method)
  - Logic: if `rfqRecipient->status === 'received'`, transition to `reviewed`, set `reviewed_at = now()`, update `last_activity_at`.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RfqDetailAutoReview
    ```

- [x] **E13.T4** Implement `in_progress` and `decline` (no_bid) actions with `last_activity_at` reset
  - File: `app/Livewire/CompanyHub/RfqDetail.php` (Livewire actions)
  - `markInProgress()`: sets status to `in_progress`, updates `last_activity_at` on both `rfq_recipients` and parent `rfqs`.
  - `decline($reason)`: sets status to `no_bid`, stores `decline_reason`, updates `last_activity_at`.
  - Both guard against terminal `no_bid` state (cannot transition from `no_bid`).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RfqStatusTransition
    ```

- [x] **E13.T5** Add hub RFQ routes to `web.php` (under `/hub` prefix)
  - File: `routes/web.php`
  - Routes: `/hub/rfqs` → `RfqQueue`, `/hub/rfqs/{rfqRecipient}` → `RfqDetail`.
  - Middleware: `auth`, `verified`, ownership enforced via `RfqRecipientPolicy`.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=hub/rfqs
    ```

- [x] **E13.T6** Create API endpoints: GET `/api/v1/hub/rfqs`, GET `.../hub/rfqs/{rfqRecipient}`, POST `.../review`, POST `.../in-progress`, POST `.../decline`
  - Files:
    - `app/Http/Controllers/Api/V1/Hub/RfqRecipientController.php`
    - `app/Policies/RfqRecipientPolicy.php`
    - `app/Http/Resources/RfqRecipientResource.php`
  - Policy rules per design.md §6.1: seller's shop matches `rfq_recipients.shop_id`; cannot transition from terminal `no_bid`.
  - Decline endpoint accepts optional `decline_reason` in request body.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/hub/rfqs
    ```
    Shows 5 routes (index, show, review, in-progress, decline).

- [x] **E13.T7** Feature tests for seller RFQ queue, status transitions
  - File: `tests/Feature/SellerRfqQueueTest.php`
  - Test cases:
    - Seller can list received RFQs for their shop.
    - Seller cannot see RFQs for another shop (403).
    - Viewing an RFQ auto-transitions from `received` → `reviewed`.
    - Seller can mark `in_progress` from `reviewed`.
    - Seller can decline with reason.
    - Cannot transition from `no_bid` to any other state.
    - `last_activity_at` updates on each transition.
    - API endpoints return correct response shapes per design.md §3.3.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=SellerRfqQueueTest
    ```

---

## Epic 14 — Quotation Builder

**Goal:** Draft/submit quotation flow, line items, immutability, snapshot capture.
**Depends on:** E13

- [x] **E14.T1** Create `quotations` migration
  - File: `database/migrations/xxxx_create_quotations_table.php`
  - Schema matches design.md §2.2: `rfq_id` FK (CASCADE), `rfq_recipient_id` FK (CASCADE), `status quotation_status DEFAULT 'draft'`, `notes TEXT`, `currency CHAR(3) DEFAULT 'PHP'`, `total_amount NUMERIC(12,2)`, `valid_until`, `submitted_at`, `buyer_snapshot JSONB` (nullable for drafts), timestamps.
  - Indexes: `idx_quotations_rfq`, `idx_quotations_rfq_recipient`, `idx_quotations_status`, `idx_quotations_valid_until` (partial: WHERE status = 'submitted').
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **E14.T2** Create `quotation_items` migration
  - File: `database/migrations/xxxx_create_quotation_items_table.php`
  - Schema matches design.md §2.2: `quotation_id` FK (CASCADE), `description VARCHAR(500)`, `quantity NUMERIC(12,2)`, `unit VARCHAR(30)`, `unit_price NUMERIC(12,2)`, `subtotal NUMERIC(12,2)`, `sort_order SMALLINT DEFAULT 0`, timestamps.
  - Index: `idx_quotation_items_quotation` on `(quotation_id, sort_order)`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **E14.T3** Create `quotation_attachments` migration `[parallel-safe with E14.T2]`
  - File: `database/migrations/xxxx_create_quotation_attachments_table.php`
  - Schema: composite PK `(quotation_id, media_id)`, FKs per design.md §2.2.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **E14.T4** Create Quotation, QuotationItem models with relationships + factories
  - Files:
    - `app/Models/Quotation.php` — relationships: `belongsTo(Rfq)`, `belongsTo(RfqRecipient)`, `hasMany(QuotationItem)`, `belongsToMany(Media, 'quotation_attachments')`. Cast `buyer_snapshot` as `array`, `status` as native enum, `total_amount` as `decimal:2`.
    - `app/Models/QuotationItem.php` — relationships: `belongsTo(Quotation)`. Cast `quantity`, `unit_price`, `subtotal` as `decimal:2`.
    - `database/factories/QuotationFactory.php`
    - `database/factories/QuotationItemFactory.php`
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'App\Models\Quotation::factory()->make(); App\Models\QuotationItem::factory()->make();'
    ```

- [x] **E14.T5** Create QuotationPolicy
  - File: `app/Policies/QuotationPolicy.php`
  - Rules per design.md §6.1:
    - `create`: seller's shop matches the rfq_recipient's shop.
    - `update`/`delete`: seller owns AND status is `draft`.
    - `submit`: seller owns AND status is `draft` AND has ≥1 line item.
    - `withdraw`: seller owns AND status is `submitted`.
    - `award`/`reject`: buyer owns the parent RFQ AND quotation status is `submitted`.
  - Register policy.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=QuotationPolicyTest
    ```

- [x] **E14.T6** Create Livewire component hub/quotation-builder
  - Files:
    - `app/Livewire/CompanyHub/QuotationBuilder.php`
    - `resources/views/livewire/company-hub/quotation-builder.blade.php`
  - Features:
    - Repeatable line-item form (Alpine.js add/remove rows): description, quantity, unit, unit_price → auto-calculated subtotal.
    - Notes textarea, file attachments.
    - Save draft action (persists to DB, allows re-editing).
    - Submit action (confirmation modal with immutability warning).
    - Only accessible for quotations in `draft` status.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=QuotationBuilder
    ```

- [x] **E14.T7** Implement submit action: set `valid_until`, capture `buyer_snapshot`, enforce immutability
  - File: `app/Livewire/CompanyHub/QuotationBuilder.php` (or dedicated Action class)
  - Logic:
    1. Validate ≥1 line item exists.
    2. Calculate `total_amount` from sum of item subtotals.
    3. Set `valid_until = now() + 14 days`.
    4. Capture `buyer_snapshot` from the RFQ's buyer profile at submission time.
    5. Set `status = submitted`, `submitted_at = now()`.
    6. After status leaves `draft`, block all UPDATE on content columns (enforced in model's `updating` event or policy).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=QuotationSubmit
    ```

- [x] **E14.T8** Implement withdraw action for submitted quotations
  - File: `app/Livewire/CompanyHub/QuotationBuilder.php` or API controller
  - Logic: verify status is `submitted`; set `status = withdrawn`.
  - Only the owning seller can withdraw.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=QuotationWithdraw
    ```

- [x] **E14.T9** Create API endpoints: POST/PUT/DELETE draft quotations, POST submit, POST withdraw
  - Files:
    - `app/Http/Controllers/Api/V1/Hub/QuotationController.php`
    - `app/Http/Requests/CreateQuotationRequest.php`
    - `app/Http/Requests/UpdateQuotationRequest.php`
    - `app/Http/Resources/QuotationResource.php`
  - Routes per design.md §3.4:
    - POST `/api/v1/hub/rfqs/{rfqRecipient}/quotations` — create draft.
    - PUT `/api/v1/hub/quotations/{quotation}` — update draft.
    - DELETE `/api/v1/hub/quotations/{quotation}` — delete draft.
    - POST `/api/v1/hub/quotations/{quotation}/submit` — submit.
    - POST `/api/v1/hub/quotations/{quotation}/withdraw` — withdraw.
  - Rate limiting: 20 quotation creations per hour per user.
  - Response shapes match design.md §3.4.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/hub/quotations
    vendor/bin/sail artisan route:list --path=api/v1/hub/rfqs --method=POST
    ```

- [x] **E14.T10** Dispatch `QuotationSubmitted` event → notify buyer, post card to thread
  - Files:
    - `app/Events/QuotationSubmitted.php`
    - `app/Listeners/NotifyBuyerQuotationSubmitted.php`
  - Listener logic per design.md §5.2:
    1. Dispatch `QuotationSubmittedNotification` to buyer.
    2. Post quotation card as system message in the RFQ's thread (if conversation/thread exists).
    3. Update `rfq_recipients.last_activity_at` and `rfqs.last_activity_at`.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=QuotationSubmittedEvent
    ```

- [x] **E14.T11** Feature tests for quotation lifecycle
  - File: `tests/Feature/QuotationTest.php`
  - Test cases:
    - Seller can create draft quotation for their rfq_recipient (POST → 201).
    - Seller cannot create quotation for another shop's rfq_recipient (403).
    - Seller can update draft (PUT → 200).
    - Seller cannot update submitted quotation (409 or 403).
    - Seller cannot delete submitted quotation (409 or 403).
    - Submit with ≥1 item → 200, sets `valid_until`, `submitted_at`, `buyer_snapshot`.
    - Submit with 0 items → 422.
    - Submitted quotation is immutable (PUT → 403/409).
    - Withdraw submitted → 200, status=withdrawn.
    - Withdraw non-submitted → 409.
    - `QuotationSubmitted` event dispatched on submit.
    - `total_amount` correctly computed from item subtotals.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=QuotationTest
    ```

---

## Epic 15 — Award, Close & Reject

**Goal:** Buyer-side quotation actions, RFQ close cascade logic.
**Depends on:** E14

- [x] **E15.T1** Implement award action (with race condition guard)
  - Files:
    - `app/Actions/AwardQuotationAction.php`
    - Logic in API controller / Livewire component
  - Logic per design.md §6.2:
    1. Begin DB transaction.
    2. `SELECT ... FOR UPDATE` on quotation row.
    3. Re-verify `status === 'submitted'` (return 409 if expired/withdrawn/already-awarded).
    4. Set `quotation.status = 'awarded'`.
    5. Commit transaction.
    6. After commit: dispatch `QuotationAwarded` event.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=AwardQuotation
    ```

- [x] **E15.T2** Implement reject action
  - File: `app/Actions/RejectQuotationAction.php` or controller method
  - Logic: verify `quotation.status === 'submitted'`; set `status = rejected`.
  - Dispatch `QuotationRejected` event after state change.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RejectQuotation
    ```

- [x] **E15.T3** Implement RFQ close cascade — DB transaction with `not_awarded` transition
  - File: `app/Actions/CloseRfqAction.php`
  - Logic per design.md §5.2 (RfqClosed event):
    1. Begin DB transaction.
    2. Set `rfqs.status = 'closed'`, `rfqs.closed_at = now()`.
    3. Update all child `quotations` WHERE `status = 'submitted'` → `status = 'not_awarded'`.
    4. Commit transaction.
    5. After commit: dispatch `RfqClosed` event.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=CloseRfqCascade
    ```

- [x] **E15.T4** Create buyer RFQ list + detail Livewire components
  - Files:
    - `app/Livewire/Account/RfqList.php`
    - `resources/views/livewire/account/rfq-list.blade.php`
    - `app/Livewire/Account/RfqDetail.php`
    - `resources/views/livewire/account/rfq-detail.blade.php`
  - `RfqList`: table of buyer's sent RFQs with status filter tabs, cursor pagination.
  - `RfqDetail`: shows RFQ info, recipients with their statuses, submitted quotations with award/reject buttons, close RFQ button (confirmation modal).
  - Add routes: `/account/rfqs` → `RfqList`, `/account/rfqs/{rfq}` → `RfqDetail`.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=account/rfqs
    vendor/bin/sail artisan test --compact --filter=RfqList
    ```

- [x] **E15.T5** Create API endpoints: POST `/api/v1/quotations/{quotation}/award`, POST `/api/v1/quotations/{quotation}/reject`
  - File: `app/Http/Controllers/Api/V1/QuotationActionController.php`
  - Uses `QuotationPolicy` `award`/`reject` gates.
  - Response shapes match design.md §3.5.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/quotations
    ```
    Shows POST award and POST reject routes.

- [x] **E15.T6** Dispatch `QuotationAwarded` / `QuotationRejected` events → notify seller
  - Files:
    - `app/Events/QuotationAwarded.php`
    - `app/Events/QuotationRejected.php`
    - `app/Listeners/NotifySellerQuotationAwarded.php`
    - `app/Listeners/NotifySellerQuotationRejected.php`
  - Each dispatches the appropriate notification to the seller (shop owner) and updates `rfqs.last_activity_at`.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=QuotationAwardedEvent
    vendor/bin/sail artisan test --compact --filter=QuotationRejectedEvent
    ```

- [x] **E15.T7** Dispatch `RfqClosed` event → notify seller
  - Files:
    - `app/Events/RfqClosed.php`
    - `app/Listeners/NotifySellerRfqClosed.php`
  - Dispatches `RfqClosedNotification` to all recipient sellers with non-terminal RFQ status.
  - Posts system message in conversation thread ("RFQ closed").
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RfqClosedEvent
    ```

- [x] **E15.T8** Feature tests for award, reject, close cascade, expired quotation race condition
  - File: `tests/Feature/QuotationActionsTest.php`
  - Test cases:
    - Buyer can award a submitted quotation (status → awarded).
    - Buyer cannot award an expired quotation (409).
    - Buyer cannot award a withdrawn quotation (409).
    - Buyer can reject a submitted quotation (status → rejected).
    - Buyer can close their open RFQ → all submitted quotations become `not_awarded`.
    - Already-terminal quotations (awarded, rejected, withdrawn, expired) are NOT affected by close cascade.
    - Non-owner buyer cannot award/reject (403).
    - Concurrent award attempt on same quotation (simulate with manual status change) → 409.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=QuotationActionsTest
    ```

---

## Epic 16 — Chat System

**Goal:** Conversations, threads, messages, real-time via Reverb, RFQ/quotation cards.
**Depends on:** E12 (conversations created by RFQ flow)

- [x] **E16.T1** Create `conversations` migration
  - File: `database/migrations/xxxx_create_conversations_table.php`
  - Schema matches design.md §2.2: `buyer_user_id` FK (RESTRICT), `shop_id` FK (RESTRICT), timestamps.
  - Unique index: `idx_conversations_pair` on `(buyer_user_id, shop_id)`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **E16.T2** Create `conversation_threads` migration
  - File: `database/migrations/xxxx_create_conversation_threads_table.php`
  - Schema matches design.md §2.2: `conversation_id` FK (CASCADE), `title VARCHAR(255)`, `rfq_id` FK (SET NULL, nullable), `is_default BOOLEAN DEFAULT FALSE`, timestamps.
  - Indexes: `idx_conversation_threads_conversation`, `idx_conversation_threads_rfq` (partial: WHERE rfq_id IS NOT NULL).
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **E16.T3** Create `messages` migration
  - File: `database/migrations/xxxx_create_messages_table.php`
  - Schema matches design.md §2.2: `thread_id` FK (CASCADE), `sender_id` FK (RESTRICT), `body TEXT NOT NULL`, timestamps.
  - Indexes: `idx_messages_thread` on `(thread_id, created_at)`, `idx_messages_sender`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **E16.T4** Create `message_attachments` migration `[parallel-safe with E16.T3]`
  - File: `database/migrations/xxxx_create_message_attachments_table.php`
  - Schema: composite PK `(message_id, media_id)`, FKs per design.md §2.2.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **E16.T5** Create Conversation, ConversationThread, Message models with relationships + factories
  - Files:
    - `app/Models/Conversation.php` — relationships: `belongsTo(User, 'buyer_user_id')`, `belongsTo(Shop)`, `hasMany(ConversationThread)`. Scope: `forUser($user)` (where user is buyer OR shop owner).
    - `app/Models/ConversationThread.php` — relationships: `belongsTo(Conversation)`, `belongsTo(Rfq)` (nullable), `hasMany(Message)`.
    - `app/Models/Message.php` — relationships: `belongsTo(ConversationThread)`, `belongsTo(User, 'sender_id')`, `belongsToMany(Media, 'message_attachments')`.
    - `database/factories/ConversationFactory.php`
    - `database/factories/ConversationThreadFactory.php`
    - `database/factories/MessageFactory.php`
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'App\Models\Conversation::factory()->make(); App\Models\Message::factory()->make();'
    ```

- [x] **E16.T6** Create ConversationPolicy, MessagePolicy
  - Files:
    - `app/Policies/ConversationPolicy.php`
    - `app/Policies/MessagePolicy.php`
  - Rules per design.md §6.1:
    - `ConversationPolicy`: `view`/`createThread` — user is `buyer_user_id` OR owner of `shop_id`.
    - `MessagePolicy`: `create` — user is a participant in the parent conversation.
  - Register policies.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=ConversationPolicyTest
    vendor/bin/sail artisan test --compact --filter=MessagePolicyTest
    ```

- [x] **E16.T7** Register broadcast channel `conversation.{id}` in `channels.php`
  - File: `routes/channels.php`
  - Authorization logic per design.md §5.3:
    ```php
    Broadcast::channel('conversation.{id}', function (User $user, int $id) {
        $conversation = Conversation::findOrFail($id);
        return $user->id === $conversation->buyer_user_id
            || $user->id === $conversation->shop->user_id;
    });
    ```
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'Illuminate\Support\Facades\Broadcast::getRoutes();'
    ```

- [x] **E16.T8** Create shared Livewire components: conversation-panel, thread-list, message-stream
  - Files:
    - `app/Livewire/Shared/ConversationPanel.php`
    - `resources/views/livewire/shared/conversation-panel.blade.php`
    - `app/Livewire/Shared/ThreadList.php`
    - `resources/views/livewire/shared/thread-list.blade.php`
    - `app/Livewire/Shared/MessageStream.php`
    - `resources/views/livewire/shared/message-stream.blade.php`
  - `ConversationPanel`: layout with sidebar (thread list) + main area (message stream).
  - `ThreadList`: lists threads within selected conversation, highlights RFQ-linked threads.
  - `MessageStream`: displays messages with real-time updates via Reverb `#[On('echo:conversation.{conversationId},MessageSent')]`; includes message input with send button and optional attachments.
  - Rate limiting on message send: 60 per minute per user.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=MessageStream
    ```

- [x] **E16.T9** Create RFQ card and quotation card Blade components
  - Files:
    - `resources/views/components/shared/rfq-card.blade.php`
    - `resources/views/components/shared/quotation-card.blade.php`
  - `rfq-card`: renders RFQ title, status badge, buyer business name, created_at.
  - `quotation-card`: renders quotation total, currency, status badge, valid_until, item count.
  - Both are rendered inline within the message stream for system messages.
  - Verify: components render without errors in a Blade test or Livewire test.

- [x] **E16.T10** Create chat inbox Livewire components for both buyer and seller
  - Files:
    - `app/Livewire/Account/ChatInbox.php`
    - `resources/views/livewire/account/chat-inbox.blade.php`
    - `app/Livewire/CompanyHub/ChatInbox.php`
    - `resources/views/livewire/company-hub/chat-inbox.blade.php`
  - Both wrap the shared `ConversationPanel` component, providing the user context (buyer vs seller).
  - Add routes: `/account/messages` → Account ChatInbox, `/hub/messages` → CompanyHub ChatInbox.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=account/messages
    vendor/bin/sail artisan route:list --path=hub/messages
    ```

- [x] **E16.T11** Create API endpoints: GET conversations, GET threads, POST threads, GET messages, POST messages
  - Files:
    - `app/Http/Controllers/Api/V1/ConversationController.php`
    - `app/Http/Controllers/Api/V1/ThreadController.php`
    - `app/Http/Controllers/Api/V1/MessageController.php`
    - `app/Http/Requests/CreateThreadRequest.php`
    - `app/Http/Requests/SendMessageRequest.php`
    - `app/Http/Resources/ConversationResource.php`
    - `app/Http/Resources/ThreadResource.php`
    - `app/Http/Resources/MessageResource.php`
  - Routes per design.md §3.6:
    - GET `/api/v1/conversations`
    - GET `/api/v1/conversations/{conversation}/threads`
    - POST `/api/v1/conversations/{conversation}/threads`
    - GET `/api/v1/threads/{thread}/messages`
    - POST `/api/v1/threads/{thread}/messages`
  - Response shapes match design.md §3.6 exactly.
  - Message validation: `body` required max 10000 chars, `attachment_media_ids` max 5 per message.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/conversations
    vendor/bin/sail artisan route:list --path=api/v1/threads
    ```

- [x] **E16.T12** Implement `MessageSent` event → broadcast to `conversation.{id}` channel
  - Files:
    - `app/Events/MessageSent.php`
    - Event implements `ShouldBroadcast`, broadcasts on `conversation.{conversationId}` channel.
  - Dispatched after message is persisted to DB.
  - Also dispatches `NewMessageNotification` to counterpart (email if offline) via listener.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=MessageSentBroadcast
    ```

- [x] **E16.T13** Update RFQ creation flow to create/reuse conversation + auto-create RFQ thread
  - File: `app/Listeners/CreateRfqConversationThread.php` (complete implementation from E12.T9)
  - Logic per design.md §5.2:
    1. `Conversation::firstOrCreate(['buyer_user_id' => ..., 'shop_id' => ...])`.
    2. Create `ConversationThread` with `rfq_id` FK and title from RFQ title.
    3. Create system `Message` in the thread with RFQ card content.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RfqConversationThread
    ```

- [x] **E16.T14** Update `last_activity_at` on rfqs and rfq_recipients when messages are sent in RFQ-linked threads
  - File: `app/Listeners/UpdateRfqActivityOnMessage.php`
  - Logic: when a `MessageSent` event fires, check if the thread has an `rfq_id`; if so, touch `rfqs.last_activity_at` and corresponding `rfq_recipients.last_activity_at`.
  - This resets the auto-close timer per design.md §5.2.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RfqActivityOnMessage
    ```

- [x] **E16.T15** Feature tests for chat (send message, real-time broadcast, thread creation, conversation reuse)
  - File: `tests/Feature/ChatTest.php`
  - Test cases:
    - Buyer can list their conversations.
    - Seller can list their conversations.
    - User cannot access conversations they're not party to (403).
    - User can create a thread in their conversation.
    - User can send a message in their thread (POST → 201).
    - User cannot send message in another user's thread (403).
    - `MessageSent` event is broadcast on correct channel.
    - Conversation is reused for same buyer–shop pair (not duplicated).
    - RFQ thread is auto-created on RFQ creation.
    - Sending message in RFQ-linked thread updates `rfqs.last_activity_at`.
    - Message validation: body required, max 10000, max 5 attachments.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=ChatTest
    ```

---

## Epic 17 — Timer Jobs & Notifications

**Goal:** Scheduled timeout jobs, real-time + email notifications for all state changes.
**Depends on:** E15, E16

- [x] **E17.T1** Create `SellerNoBidTimeoutJob`
  - File: `app/Jobs/SellerNoBidTimeoutJob.php`
  - Logic per design.md §5.1: query `rfq_recipients` WHERE `status IN ('received','reviewed','in_progress') AND last_activity_at < NOW() - INTERVAL '7 days'` → set `status = 'no_bid'`, dispatch `RfqNoBidNotification` to buyer.
  - Idempotent: only transitions rows not already in `no_bid`; safe to run repeatedly.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=SellerNoBidTimeoutJob
    ```

- [x] **E17.T2** Create `RfqAutoCloseJob`
  - File: `app/Jobs/RfqAutoCloseJob.php`
  - Logic per design.md §5.1: query `rfqs` WHERE `status = 'open' AND last_activity_at < NOW() - INTERVAL '7 days'` → within DB transaction: set `status = 'closed', closed_at = NOW()`; cascade child `quotations` in `submitted` to `not_awarded`; dispatch `RfqClosedNotification`.
  - Idempotent: only transitions `open` RFQs; already-terminal quotations untouched.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=RfqAutoCloseJob
    ```

- [x] **E17.T3** Create `QuotationExpiryJob`
  - File: `app/Jobs/QuotationExpiryJob.php`
  - Logic per design.md §5.1: query `quotations` WHERE `status = 'submitted' AND valid_until < NOW()` → set `status = 'expired'`, dispatch `QuotationExpiredNotification` to both buyer and seller.
  - Idempotent: only transitions `submitted` quotations past `valid_until`; already-expired rows are no-ops.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=QuotationExpiryJob
    ```

- [x] **E17.T4** Register all three jobs in scheduler (daily)
  - File: `routes/console.php` (or `app/Console/Kernel.php`)
  - Register with `->daily()->withoutOverlapping()`:
    - `SellerNoBidTimeoutJob` at 03:00 UTC
    - `RfqAutoCloseJob` at 03:15 UTC
    - `QuotationExpiryJob` at 03:30 UTC
  - Verify:
    ```bash
    vendor/bin/sail artisan schedule:list
    ```
    Shows all 3 jobs with correct schedule.

- [x] **E17.T5** Create notification classes
  - Files under `app/Notifications/`:
    - `RfqReceivedNotification.php`
    - `RfqNoBidNotification.php`
    - `QuotationSubmittedNotification.php`
    - `QuotationAwardedNotification.php`
    - `QuotationRejectedNotification.php`
    - `QuotationWithdrawnNotification.php`
    - `QuotationExpiredNotification.php`
    - `RfqClosedNotification.php`
    - `NewMessageNotification.php`
  - All implement `ShouldQueue` and deliver via `mail` + `broadcast` channels.
  - Broadcast on `user.{id}` private channel (existing).
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'new App\Notifications\RfqReceivedNotification(App\Models\Rfq::factory()->make());'
    ```
    Instantiates without error.

- [x] **E17.T6** Wire notifications to events
  - File: `app/Providers/EventServiceProvider.php` (or event auto-discovery)
  - Event → Listener → Notification mapping:
    - `RfqCreated` → `RfqReceivedNotification` to seller
    - `QuotationSubmitted` → `QuotationSubmittedNotification` to buyer
    - `QuotationAwarded` → `QuotationAwardedNotification` to seller
    - `QuotationRejected` → `QuotationRejectedNotification` to seller
    - `QuotationWithdrawn` → `QuotationWithdrawnNotification` to buyer
    - `RfqClosed` → `RfqClosedNotification` to all recipient sellers
    - `MessageSent` → `NewMessageNotification` to counterpart
    - Timer jobs dispatch their own notifications directly (no event needed).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=NotificationWiring
    ```

- [x] **E17.T7** Feature tests for timer jobs (idempotency, correct state transitions, notifications dispatched)
  - File: `tests/Feature/TimerJobsTest.php`
  - Test cases:
    - `SellerNoBidTimeoutJob`: rfq_recipient with last_activity > 7 days → transitions to `no_bid`; already `no_bid` → no change.
    - `RfqAutoCloseJob`: open RFQ with no activity > 7 days → closed + submitted quotations → `not_awarded`; already-closed RFQ → no change.
    - `QuotationExpiryJob`: submitted quotation past `valid_until` → expired; already-expired → no change.
    - All jobs: run 3x consecutively → no duplicate state transitions.
    - All jobs: correct notifications dispatched (`Notification::fake()->assertSentTo`).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=TimerJobsTest
    ```

- [x] **E17.T8** Feature tests for notification delivery (correct recipients, no duplicates)
  - File: `tests/Feature/NotificationDeliveryTest.php`
  - Test cases:
    - RFQ creation → seller (shop owner) receives `RfqReceivedNotification`.
    - Quotation submission → buyer receives `QuotationSubmittedNotification`.
    - Award → seller receives `QuotationAwardedNotification`.
    - Reject → seller receives `QuotationRejectedNotification`.
    - Withdraw → buyer receives `QuotationWithdrawnNotification`.
    - RFQ close → all recipient sellers receive `RfqClosedNotification`.
    - Message → counterpart receives `NewMessageNotification`.
    - Verify no duplicate notifications sent for same event.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=NotificationDeliveryTest
    ```

---

## Epic 18 — Integration Testing & Hardening

**Goal:** End-to-end pipeline tests, authorization matrix, edge cases.
**Depends on:** E11–E17

- [x] **E18.T1** End-to-end pipeline test
  - File: `tests/Feature/NegotiationPipelineTest.php`
  - Single integration test walking the full pipeline:
    1. Buyer creates buyer profile.
    2. Buyer sends RFQ to a seller's shop.
    3. Conversation + thread auto-created.
    4. Seller views RFQ → auto-reviewed.
    5. Seller marks in_progress.
    6. Seller creates draft quotation, adds line items.
    7. Seller submits quotation → buyer notified.
    8. Buyer sends message in RFQ thread → `last_activity_at` resets.
    9. Buyer awards quotation → seller notified.
    10. Buyer closes RFQ → remaining submitted quotations become `not_awarded`.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=NegotiationPipelineTest
    ```

- [x] **E18.T2** Authorization matrix test
  - File: `tests/Feature/Phase2AuthorizationMatrixTest.php`
  - For every Phase 2 route (buyer profile, RFQ CRUD, seller RFQ queue, quotation CRUD, quotation actions, chat), assert correct HTTP status for:
    - `buyer-owner` (200)
    - `buyer-non-owner` (403)
    - `seller-owner` (200 for seller routes)
    - `seller-non-owner` (403)
    - `admin` (varies)
    - `guest` (401)
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=Phase2AuthorizationMatrixTest
    ```

- [x] **E18.T3** Concurrency/race condition tests
  - File: `tests/Feature/Phase2ConcurrencyTest.php`
  - Test cases:
    - Award expired quotation → 409 (simulate: set `valid_until` to past, then attempt award).
    - Award withdrawn quotation → 409.
    - Close RFQ while quotation being submitted → one wins atomically, no inconsistent state.
    - Double-award same quotation → second attempt gets 409.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=Phase2ConcurrencyTest
    ```

- [x] **E18.T4** Timer job idempotency stress test
  - File: `tests/Feature/TimerJobIdempotencyTest.php`
  - Run each of the 3 timer jobs 3x consecutively on the same fixture data:
    - Verify state transitions happen only once.
    - Verify notifications dispatched only once (no duplicates on repeated runs).
    - Verify rows already in terminal state are untouched.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=TimerJobIdempotencyTest
    ```

- [x] **E18.T5** Chat security tests
  - File: `tests/Feature/ChatSecurityTest.php`
  - Test cases:
    - User A cannot list User B's conversations (403).
    - User cannot access conversation they're not party to (403).
    - User cannot send messages to threads in conversations they don't belong to (403).
    - User cannot create threads in conversations they don't belong to (403).
    - Messages are only visible to conversation participants.
    - Broadcast channel auth rejects non-participants.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=ChatSecurityTest
    ```

---

## Phase 2 — Remaining UI/UX Implementation Report (Merged from phase2-remaining-work.md)

| | |
|---|---|
| **Status** | ✅ All Priority 1 & 2 items complete |
| **Date** | 2026-08-18 |
| **Context** | All 70 backend tasks done (E11–E18), 287 tests passing. Frontend wiring complete. |

---

### Priority 1 — Functional Gaps ✅ ALL RESOLVED

#### ~~1.1 Chat UI Not Accessible from Navigation~~ ✅ DONE

Added "Messages" nav links to both `hub` and `account` portal sidebars in `portal-nav.blade.php`. Fixed layout variable override bug.

#### ~~1.2 Real-Time Chat Not Working (Echo Not Wired)~~ ✅ DONE

- Laravel Echo configured in `resources/js/app.js` with Reverb connection (via `pusher-js`)
- Dynamic Echo listeners in both `ChatInbox` components using `getListeners()` method
- Alpine.js auto-scroll on mount + after render
- Computed message cache cleared on `MessageSent` broadcast

#### ~~1.3 Notification Bell/Badge Not Present~~ ✅ DONE

- `NotificationBell` Livewire component created with unread count badge, dropdown, mark-as-read
- Embedded in portal header (`portal-header.blade.php`)
- Listens for `notification-received` Echo event for instant updates
- 6 tests passing

#### ~~1.4 Reverb WebSocket Server Not Auto-Started~~ ✅ DONE

- Added `reverb` service to `compose.yaml` (Laravel Sail runtime 8.5, port 8080)
- Added as dependency under `laravel.test`

---

### Priority 2 — Missing Navigation Links ✅ ALL RESOLVED

#### ~~2.1 Account Portal Sidebar Incomplete~~ ✅ DONE

Added: My RFQs, Messages links.

#### ~~2.2 Hub Portal Sidebar Missing Phase 2 Links~~ ✅ DONE

Added: RFQs Received, Messages links.

---

### Priority 3 — Polish / Enhancement (Deferred)

#### ~~3.1 Chat Message Stream Auto-Scroll~~ ✅ DONE

Alpine.js auto-scroll on mount + after Livewire render cycle.

#### ~~3.2 RFQ/Quotation Cards in Chat Thread~~ ✅ DONE

System messages with RFQ prefix are detected and rendered as inline cards using `<x-shared.rfq-card>` component. Quotation card component also created for future use.

#### 3.3 Typing Indicators — NOT YET (Low Priority)

Add debounced `TypingStarted` event broadcast + "typing..." indicator.

#### ~~3.4 Unread Message Count Per Conversation~~ ✅ DONE

Added `conversation_reads` table tracking `last_read_at` per user per conversation. Unread count badge shown on conversation list. Conversations marked as read on selection.

---

### Remaining Implementation Order

| # | Item | Effort | Status |
|---|------|--------|--------|
| 1 | Nav links (2.1 + 2.2) | Small | ✅ Done |
| 2 | Echo in app.js (1.2) | Small | ✅ Done |
| 3 | Reverb in docker-compose (1.4) | Small | ✅ Done |
| 4 | Echo listeners in ChatInbox (1.2) | Medium | ✅ Done |
| 5 | Notification bell (1.3) | Medium | ✅ Done |
| 6 | Auto-scroll (3.1) | Small | ✅ Done |
| 7 | RFQ/Quotation cards in chat (3.2) | Medium | ✅ Done |
| 8 | Unread count (3.4) | Medium | ✅ Done |
| 9 | Typing indicators (3.3) | Low priority | Deferred |

---

### Current Test Count

```
Total: 287 tests, 872 assertions — all passing
Phase 2 specific: 109 tests (99 original + 10 new frontend wiring tests)
```

---

## Epic 19 — Notification URL Fix (Post-Implementation Documentation)

**Goal:** Document the fix for notification URLs to correctly route users based on their portal access (buyer `/account` vs seller `/hub`) rather than role-based checks.

**Background:** Users can be both buyers AND sellers simultaneously (have an active shop). The original implementation used `$notifiable->hasRole('seller')` to determine URLs, but access is determined by portal structure, not roles.

**Changes Made:**

| Notification | Recipient | Old URL Logic | New URL |
|--------------|-----------|---------------|---------|
| `rfq_received` | Seller | Role-based (`/hub/rfqs/{rfq_id}`) | `/hub/rfqs/{rfqRecipient_id}` ✓ |
| `quotation_submitted` | Buyer | Role-based (`/account` or `/hub`) | `/account/rfqs/{rfq_id}` ✓ |
| `quotation_awarded` | Seller | Role-based | `/hub/rfqs/{rfqRecipient_id}` ✓ |
| `quotation_rejected` | Seller | Role-based | `/hub/rfqs/{rfqRecipient_id}` ✓ |
| `quotation_withdrawn` | Buyer | Role-based | `/account/rfqs/{rfq_id}` ✓ |
| `rfq_closed` | Seller | Role-based | `/hub/rfqs/{rfqRecipient_id}` ✓ |
| `rfq_no_bid` | Buyer | Role-based | `/account/rfqs/{rfq_id}` ✓ |
| `new_message` | Both | Role-based | `/hub/messages` if active shop, else `/account/messages` ✓ |
| `certificate_expiring/expired` | Seller | Already correct | `/hub/onboarding` |
| `stale_onboarding_reminder` | Seller | Already correct | `/hub/onboarding` |

**Technical Changes:**
1. **Notifications now accept `RfqRecipient`** instead of `Rfq` for seller-facing notifications — provides access to `rfqRecipient_id` for `/hub/rfqs/{id}` routes
2. **Listeners/Jobs updated** to pass `RfqRecipient` where needed
3. **URL generation** is context-aware (based on notification type/recipient) not role-based
4. **NewMessageNotification** checks `$notifiable->shop->status === 'active'` instead of roles

**Files Modified:**
- `app/Notifications/RfqReceivedNotification.php` (accepts `RfqRecipient`)
- `app/Notifications/QuotationSubmittedNotification.php`
- `app/Notifications/QuotationAwardedNotification.php`
- `app/Notifications/QuotationRejectedNotification.php`
- `app/Notifications/QuotationWithdrawnNotification.php`
- `app/Notifications/RfqClosedNotification.php` (accepts `RfqRecipient`)
- `app/Notifications/RfqNoBidNotification.php`
- `app/Notifications/NewMessageNotification.php`
- `app/Listeners/NotifySellerRfqReceived.php`
- `app/Listeners/NotifySellerRfqClosed.php`
- `app/Jobs/RfqAutoCloseJob.php`

**Tests Verified:**
- All 289 existing tests pass
- `NotificationBellTest.php` — 6 tests pass
- `NotificationDeliveryTest.php` — 5 tests pass
- `TimerJobsTest.php` — 11 tests pass
- `CertificateExpiryJobTest.php` — 6 tests pass

---

## Escalation

If, while executing any task, the agent encounters:
- A field, endpoint, or table **not defined** in `design.md` or the PRD `requirements.md`,
- A **conflict** between `design.md` and `requirements.md` (e.g., a field present in one but not the other),
- An **ambiguous dependency** (e.g., E12.T9 requires chat tables from E16 — resolved by deferring conversation creation to E16.T13),
- A **race condition** or concurrency scenario not covered by the existing `SELECT ... FOR UPDATE` patterns in design.md §6.2,

the agent should **stop, log the specific conflict with file/line references, and flag it for human review** rather than inferring a resolution. Known cross-epic dependencies (E12 ↔ E16 for conversation creation) are already annotated in the task descriptions — follow the documented resolution approach.

---

---

## Epic 20 — Product Variant Attribute System & Seller Tooling

**Status:** ✅ Complete (two sessions on 2026-08-22)
**Depends on:** E18 complete; Phase 1 `categories`, `products`, `product_variants` tables in place

---

### Session A Tasks (Confirmed by git diff against HEAD)

- [x] **E20.T1** Fix notification URL generation — context-aware routing
  - Files: `app/Notifications/RfqReceivedNotification.php`, `QuotationSubmittedNotification.php`, `QuotationAwardedNotification.php`, `QuotationRejectedNotification.php`, `QuotationWithdrawnNotification.php`, `RfqClosedNotification.php`, `RfqNoBidNotification.php`, `NewMessageNotification.php`
  - Files: `app/Listeners/NotifySellerRfqReceived.php`, `NotifySellerRfqClosed.php`, `app/Jobs/RfqAutoCloseJob.php`
  - Seller-facing notifications now accept `RfqRecipient` (not `Rfq`) for `/hub/rfqs/{id}` URLs
  - `NewMessageNotification` checks `$notifiable->shop->status === 'active'` instead of role
  - See design.md §20.3 and Epic 19 for full detail
  - Verify: all 289 tests pass

- [x] **E20.T2** Add `variant_attributes_schema` to `categories` table
  - File: `database/migrations/2026_08_22_090626_add_variant_attributes_schema_to_categories_table.php`
  - `JSONB NOT NULL DEFAULT '{}'`
  - Model: `app/Models/Category.php` — added to `$fillable`, cast as `'array'`

- [x] **E20.T3** Add `variant_attributes_schema` to `products` table
  - File: `database/migrations/2026_08_22_090710_add_variant_attributes_schema_to_products_table.php`
  - `JSONB NOT NULL DEFAULT '{}'`
  - Model: `app/Models/Product.php` — added to `$fillable`, cast as `'array'`

- [x] **E20.T4** Create `CategoryCreate` Livewire component
  - Files: `app/Livewire/Admin/CategoryCreate.php`, `resources/views/livewire/admin/category-create.blade.php`
  - Full-page form: name (with live slug preview), description, schema editor with add/remove rows
  - Route: `/admin/categories/create` → `admin.categories.create`

- [x] **E20.T5** Create `CategoryEdit` Livewire component
  - Files: `app/Livewire/Admin/CategoryEdit.php`, `resources/views/livewire/admin/category-edit.blade.php`
  - Pre-populates schema from existing category; same schema editor as create
  - Route: `/admin/categories/{category}/edit` → `admin.categories.edit`

- [x] **E20.T6** Update `CategoryManager` blade to link to new routes
  - File: `resources/views/livewire/admin/category-manager.blade.php`
  - "Add Category" button and edit pencil changed from `wire:click` to `href="{{ route(...) }}" wire:navigate`
  - The inline create/edit modal pattern replaced by dedicated pages

- [x] **E20.T7** Add schema editor to `ProductForm` (initial — buggy category toggle)
  - Files: `app/Livewire/CompanyHub/ProductForm.php`, `resources/views/livewire/company-hub/product-form.blade.php`
  - Added `variantAttributesSchema`, `addAttribute()`, `removeAttribute()`, `loadFromCategory()`
  - Category pills used `wire:click="$toggle()"` — broken (see E20.T10 for fix)

---

### Session B Tasks (This Session)

- [x] **E20.T8** Fix `$mergedAttributeSchema` undefined variable in variant-manager blade
  - File: `resources/views/livewire/company-hub/variant-manager.blade.php`
  - `$mergedAttributeSchema` → `$this->mergedAttributeSchema` (Livewire 4 `#[Computed]` blade syntax)
  - Fixed HTML-in-echo for required asterisk (`{{ ... ? '<span>' : '' }}` → `@if` block)

- [x] **E20.T9** Fix broken `mergedAttributeSchema()` implementation in `VariantManager`
  - File: `app/Livewire/CompanyHub/VariantManager.php`
  - `pluck()->collapse()` fails on keyed associative JSONB arrays
  - Simplified to `return $this->product->variant_attributes_schema ?? []`

- [x] **E20.T10** Fix `variant="secondary"` `UnhandledMatchError` on product edit page
  - File: `resources/views/livewire/company-hub/product-form.blade.php`
  - `variant="secondary"` does not exist in Flux 2.x — `UnhandledMatchError` in compiled view
  - Changed to `variant="outline"`

- [x] **E20.T11** Fix broken category toggle — replace `$toggle` with `toggleCategory()` action
  - File: `app/Livewire/CompanyHub/ProductForm.php`
  - Root cause: `$toggle` calls `$wire.set(name, !$wire.get(name))` — boolean flip, not array push/pop
  - Added `toggleCategory(int $id)` — proper push/pop, triggers `populateSchemaFromCategories()` on create when schema is empty

- [x] **E20.T12** Implement auto-populate and reload schema from categories
  - File: `app/Livewire/CompanyHub/ProductForm.php`
  - `populateSchemaFromCategories()` — merges schemas from all selected categories, deduplicates by lowercase label
  - `reloadSchemaFromCategories()` — clears + rebuilds; persists to DB on edit page; shows warning if no categories have schemas
  - On **create**: fires automatically when category selected and schema is empty
  - On **edit**: only via explicit "Reload" button (existing product schema is preserved)

- [x] **E20.T13** Add Alpine-driven instant disable of Choices field when type ≠ select
  - Files: `product-form.blade.php`, `category-create.blade.php`, `category-edit.blade.php`
  - `x-data="{ type: '{{ $attr[\"type\"] ?? \"select\" }}' }"` per row
  - Type select: `x-model="type"` alongside `wire:model` for local reactive state
  - Choices input: `x-bind:disabled="type !== 'select'"` + `x-bind:class` for `opacity-40 cursor-not-allowed`
  - No server round-trip needed for the disable/enable toggle

- [x] **E20.T14** Improve Product Options UI layout across all three forms
  - Files: `product-form.blade.php`, `category-create.blade.php`, `category-edit.blade.php`
  - Attribute rows: `flex flex-wrap lg:flex-nowrap` with `w-64` label, `w-32` type, `flex-1` choices, fixed actions width
  - Column headers rendered above first row only (`@if ($loop->first)`)
  - Category pills: `<button wire:click="toggleCategory()">` with checkmark icon on selected state
  - Dashed-border empty state with icon and context-aware message

- [x] **E20.T15** Create reusable `x-hs-modal` Blade component
  - File: `resources/views/components/hs-modal.blade.php`
  - Props: `model` (wire:model binding), `name` (named modal), `title`, `subtitle`, `size` (`sm|md|lg|xl|2xl`)
  - Renders `flux:modal` with a consistent bordered title/subtitle header block
  - Used by variant manager and available for all future modals

- [x] **E20.T16** Overhaul `VariantManager` — modal-driven flow, immediate persistence
  - File: `app/Livewire/CompanyHub/VariantManager.php`
  - Added: `showVariantModal`, `editingVariantId`, `draftSku/Price/Moq/Unit/LeadTime/PackSize/Status/Attributes`
  - Added: `openAddVariant()`, `openEditVariant(int $id)`, `saveVariant()`, `blankAttributes()`
  - Added: `toggleStatus(int $id)`, `removeVariant(int $id)` — by DB id, persist immediately
  - Updated: `saveQuantityBreaks()` — now writes directly to DB record (not intermediate array)
  - Removed: `saveAll()`, `addVariant()`, `toggleVariantStatus(int $index)`, `removeVariant(int $index)`, `openQuantityBreaks(int $index)`
  - Verify: `/hub/products/{id}/variants` returns 200; "Add Variant" opens modal; save persists to DB

- [x] **E20.T17** Rewrite variant-manager blade with read-only cards and modals
  - File: `resources/views/livewire/company-hub/variant-manager.blade.php`
  - Variant cards: SKU + status pill (clickable toggle) + Qty Breaks button (count badge) + edit + delete
  - Card body: Price, MOQ/Unit, Lead Time, Pack Size, attribute tag pills for non-empty values
  - Add/Edit modal via `<x-hs-modal size="lg">`: all variant fields + "Product Options" section from schema
  - Quantity Breaks modal via `<x-hs-modal size="md">`: 3-column grid layout
  - No-schema info notice links to Basic Info tab

- [x] **E20.T18** Create `DemoUsersSeeder` with buyer and seller accounts
  - File: `database/seeders/DemoUsersSeeder.php`
  - `buyer@gmail.com` / `password` — role `buyer`, `BuyerProfile` (Santos Food Enterprises, Makati)
  - `seller@gmail.com` / `password` — role `seller`, active `Shop` (Macapaar Halal Foods, Cotabato City), 5 published products with Unsplash images
  - Registered in `DatabaseSeeder` after `DemoDataSeeder`; all use `firstOrCreate` (re-run safe)
  - Verify: `vendor/bin/sail artisan db:seed --class=DemoUsersSeeder` completes without error

---

### Verification

```bash
vendor/bin/sail artisan migrate           # schema columns present
vendor/bin/sail artisan route:list --path=admin/categories  # create + edit routes present
vendor/bin/sail artisan view:clear

# All pages return 200 (after login as seller@gmail.com / password):
# /hub/products/create
# /hub/products/{id}/edit
# /hub/products/{id}/variants
# /admin/categories/create          (as admin)
# /admin/categories/{id}/edit       (as admin)
```

- [x] **E20.T19** Add soft variant link to `quotation_items`
  - File: `database/migrations/2026_08_22_124906_add_variant_link_to_quotation_items.php`
  - Added `variant_id BIGINT NULLABLE FK → product_variants ON DELETE SET NULL`
  - Added `variant_snapshot JSONB NULLABLE` — written at submission time; self-contained audit record
  - Ran: `vendor/bin/sail artisan migrate`

- [x] **E20.T20** Update `QuotationItem` and `ProductVariant` models for variant link
  - `app/Models/QuotationItem.php` — added `variant_id`, `variant_snapshot` to `$fillable`; cast `variant_snapshot` as `'array'`; added `variant(): BelongsTo(ProductVariant)`
  - `app/Models/ProductVariant.php` — added `quotationItems(): HasMany(QuotationItem)`; added `toQuotationSnapshot(): array` returning `{sku, product_name, description, unit_price, unit, moq, pack_size}`

- [x] **E20.T21** Add variant picker and auto-fill to `QuotationBuilder`
  - File: `app/Livewire/CompanyHub/QuotationBuilder.php`
  - Added `shopVariants()` `#[Computed]` — returns published variants from seller's shop with `{id, label, sku, unit, price, moq}`
  - Added `selectVariant(int $index, ?int $variantId)` — validates variant belongs to this shop; auto-fills `description`, `unit`, `unit_price`, `quantity` (from MOQ); passing `null` clears to freeform
  - Updated `saveDraft()` — persists `variant_id` per item
  - Added `captureVariantSnapshots()` — called on `submitQuotation()`; bulk-writes `variant_snapshot` to all linked items
  - Updated `mount()` — loads existing `variant_id` per item from DB

- [x] **E20.T22** Update quotation-builder blade with variant picker per line item
  - File: `resources/views/livewire/company-hub/quotation-builder.blade.php`
  - Per item: `flux:select` variant picker using `x-on:change="$wire.selectVariant(index, parseInt(val) || null)"` (Alpine `x-data` wrapper required; `wire:change` with `Number()` caused 500 — fixed)
  - "Linked to catalogue SKU" hint shown when `variant_id` is set
  - Running total shown live below items
  - Submit confirmation migrated to `<x-hs-modal>`
  - RFQ title displayed as `<span class="font-medium">` in header subtitle

- [x] **E20.T23** Fix `wire:change` 500 on variant picker select
  - File: `resources/views/livewire/company-hub/quotation-builder.blade.php`
  - Root cause: `wire:change="..., Number($event.target.value)"` — `Number()` is a JS global; Livewire evaluates `wire:` expressions server-side and throws `BadFunctionCallException`
  - Fix: changed to `x-on:change="$wire.selectVariant(..., parseInt(...))"` with `x-data` wrapper — Alpine keeps the expression client-side, then calls Livewire as RPC

- [x] **E20.T24** Create `config/units.php` — curated unit-of-measure list
  - File: `config/units.php`
  - 23 units in 5 groups: Weight (kg, g, mt, lb), Volume (L, mL), Packaging (bag, bottle, box, bundle, can, carton, crate, jar, pack, pail, pouch, sack, tray, tub), Count (pcs, dozen, gross), Other (escape hatch)

- [x] **E20.T25** Create `x-hs-unit-select` reusable Blade component
  - File: `resources/views/components/hs-unit-select.blade.php`
  - Props: `wire-model` (Livewire property path), `value` (current value), `label`, `size` (sm|null), `required`
  - Renders `<select>` with `<optgroup>` from `config/units.php`; "Other…" option reveals a debounced text input
  - Syncs to Livewire via `$wire.set()` in Alpine watchers — works correctly inside loops and Blade anonymous components
  - Handles values not in the list (auto-selects "Other…" and pre-fills custom text)
  - Applied to: `variant-manager.blade.php` (draftUnit), `quotation-builder.blade.php` (items.N.unit)

- [x] **E20.T26** Record UI patterns in `.ai/rules/views.md`
  - File: `.ai/rules/views.md`
  - Added: standard card shell, multi-card layout, form layout, info/warning notices, modal pattern, status-dependent card backgrounds, empty states, attachment lists, file upload, inline tables, reusable component reference
  - 4 rules recorded via `record-rule`: card shell, `x-hs-modal` mandatory, `x-hs-unit-select` mandatory, form layout + validation errors
