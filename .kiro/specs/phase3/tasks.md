# tasks.md
## Execution Plan — Phase 3: Orders & Payments (Orders, Escrow, Fulfilment, Disputes)

| | |
|---|---|
| **Consumes** | `requirements.md`, `design.md` |
| **Audience** | AI Coding Agent (autonomous/semi-autonomous execution) |
| **Execution mode** | Sequential by Epic; tasks within an Epic may parallelize only where marked `[parallel-safe]` |
| **Stack** | PHP/Laravel · Livewire · Tailwind · Redis · PostgreSQL · Reverb · Typesense · Minio/S3 |
| **Payment gateway** | DLT (Digital Treasures Corp) Collection API v3.0 — Quick Link hosted checkout |
| **Precondition** | Phase 2 Epics E11–E20 **all complete** (`[x]`). All Phase 2 tables, models, policies, routes, jobs, and chat system exist. |

---

## How to use this document

- Each task has a **stable ID** (`E<epic>.T<task>`), a **status checkbox**, explicit **file paths**, and **acceptance criteria**.
- The agent MUST complete tasks in ID order within an Epic unless marked `[parallel-safe]`, and MUST NOT start an Epic until all of its listed **Depends on** tasks are `[x]`.
- After each task, run the command(s) in **Verify** before marking it `[x]`. If verification fails, fix and re-verify — do not proceed.
- Do not invent schema, routes, or fields not present in `design.md`. If a gap is found, stop and flag it rather than guessing (see §"Escalation" at the end).
- All new Laravel-ecosystem code should be generated with **Laravel Boost's MCP tools/guidelines active** — use Boost's schema/docs/tinker tools to verify against the real app state rather than assuming.
- Phase 3 Epics start at **E21** to avoid collision with Phase 1's E0–E10 and Phase 2's E11–E20.

---

## Epic 21 — Order Schema & Data Layer

**Goal:** Phase 3 enum types, `orders`, `order_items`, `order_fees`, `order_payment_terms` tables + models + factories. Extend `conversation_threads` with `order_id`.
**Depends on:** Phase 2 complete (E20)

- [ ] **E21.T1** Create Phase 3 PostgreSQL enum types migration `[parallel-safe]`
  - File: `database/migrations/xxxx_create_phase3_enum_types.php`
  - Create types exactly as defined in design.md §2.1: `order_status`, `fulfilment_status`, `payment_attempt_status`, `payment_term_type`, `ledger_entry_type`, `dispute_type`, `dispute_status`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    vendor/bin/sail artisan tinker --execute 'DB::select("SELECT typname FROM pg_type WHERE typname IN ('"'"'order_status'"'"', '"'"'fulfilment_status'"'"', '"'"'payment_attempt_status'"'"', '"'"'payment_term_type'"'"', '"'"'ledger_entry_type'"'"', '"'"'dispute_type'"'"', '"'"'dispute_status'"'"')");'
    ```
    All 7 enum types listed.

- [ ] **E21.T2** Create `orders` migration
  - File: `database/migrations/xxxx_create_orders_table.php`
  - Schema matches design.md §2.2 `orders` DDL exactly: `buyer_user_id` FK (RESTRICT), `shop_id` FK (RESTRICT), `quotation_id` FK (RESTRICT, nullable), `status order_status DEFAULT 'draft'`, all customer/billing/shipping snapshot columns, financial totals (`items_total`, `fees_total`, `order_total` as `NUMERIC(12,2)`), `currency CHAR(3) DEFAULT 'PHP'`, timestamp columns (`submitted_at`, `paid_at`, `completed_at`, `cancelled_at`, `created_at`, `updated_at`).
  - Indexes: `idx_orders_buyer`, `idx_orders_shop`, `idx_orders_quotation` (partial WHERE NOT NULL), `idx_orders_status`, `idx_orders_quotation_unique` (unique partial WHERE NOT NULL).
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```
    Table exists with correct columns, FKs, and indexes.

- [ ] **E21.T3** Create `order_items` migration `[parallel-safe with E21.T4, E21.T5]`
  - File: `database/migrations/xxxx_create_order_items_table.php`
  - Schema matches design.md §2.2: `order_id` FK (CASCADE), `description VARCHAR(500)`, `quantity NUMERIC(12,2)`, `unit VARCHAR(30)`, `unit_price NUMERIC(12,2)`, `subtotal NUMERIC(12,2)`, `sort_order SMALLINT DEFAULT 0`, timestamps.
  - Index: `idx_order_items_order` on `(order_id, sort_order)`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [ ] **E21.T4** Create `order_fees` migration `[parallel-safe with E21.T3, E21.T5]`
  - File: `database/migrations/xxxx_create_order_fees_table.php`
  - Schema matches design.md §2.2: `order_id` FK (CASCADE), `label VARCHAR(255)`, `amount NUMERIC(12,2)`, `sort_order SMALLINT DEFAULT 0`, timestamps.
  - Index: `idx_order_fees_order` on `(order_id, sort_order)`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [ ] **E21.T5** Create `order_payment_terms` migration `[parallel-safe with E21.T3, E21.T4]`
  - File: `database/migrations/xxxx_create_order_payment_terms_table.php`
  - Schema matches design.md §2.2: `order_id` FK (CASCADE), `type payment_term_type DEFAULT 'full'`, `amount NUMERIC(12,2)`, `due_at TIMESTAMP(0) nullable`, timestamps.
  - Index: `idx_order_payment_terms_order`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [ ] **E21.T6** Create `payment_attempts` migration
  - File: `database/migrations/xxxx_create_payment_attempts_table.php`
  - Schema matches design.md §2.2: `order_payment_term_id` FK (CASCADE), `status payment_attempt_status DEFAULT 'pending'`, DLT gateway fields (`dlt_merchant_transaction_id VARCHAR(50) NOT NULL UNIQUE`, `dlt_quick_link_token VARCHAR(255)`, `dlt_payment_url VARCHAR(500)`, `dlt_payment_status VARCHAR(30)`, `dlt_amount VARCHAR(30)`), `gateway_response JSONB`, `succeeded_at`, `failed_at`, timestamps.
  - Indexes: `idx_payment_attempts_term`, `idx_payment_attempts_dlt_txn` (unique on `dlt_merchant_transaction_id`).
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [ ] **E21.T7** Create `fulfilments` migration
  - File: `database/migrations/xxxx_create_fulfilments_table.php`
  - Schema matches design.md §2.2: `order_id` FK (RESTRICT), `status fulfilment_status DEFAULT 'pending'`, tracking fields (`carrier VARCHAR(120)`, `tracking_code VARCHAR(255)`, `tracking_url VARCHAR(500)`), `shipped_at`, `delivered_at`, `returned_at`, timestamps.
  - Indexes: `idx_fulfilments_order`, `idx_fulfilments_order_unique` (unique on `order_id` — v1 enforces one fulfilment per order).
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [ ] **E21.T8** Create `disputes` migration
  - File: `database/migrations/xxxx_create_disputes_table.php`
  - Schema matches design.md §2.2: `order_id` FK (RESTRICT), `opened_by_user_id` FK (RESTRICT), `type dispute_type`, `status dispute_status DEFAULT 'open'`, `reason TEXT`, resolution fields (`resolution_type VARCHAR(30)`, `resolution_amount NUMERIC(12,2)`, `resolution_notes TEXT`, `resolved_by_user_id` FK nullable, `resolved_at`), `escalated_at`, return tracking (`return_carrier VARCHAR(120)`, `return_tracking_code VARCHAR(255)`), timestamps.
  - Indexes: `idx_disputes_order`, `idx_disputes_status` (partial WHERE status NOT IN terminal states), `idx_disputes_opened_by`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [ ] **E21.T9** Create `ledger_entries` migration
  - File: `database/migrations/xxxx_create_ledger_entries_table.php`
  - Schema matches design.md §2.2: `order_id` FK (RESTRICT), `type ledger_entry_type`, `amount NUMERIC(12,2)`, `note VARCHAR(255)`, `created_at TIMESTAMP(0)`. **No** `updated_at` — ledger entries are append-only.
  - Indexes: `idx_ledger_entries_order`, `idx_ledger_entries_type`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [ ] **E21.T10** Add `order_id` nullable FK to `conversation_threads`
  - File: `database/migrations/xxxx_add_order_id_to_conversation_threads.php`
  - Add nullable BIGINT `order_id` column referencing `orders(id) ON DELETE SET NULL`.
  - Index: `idx_conversation_threads_order` (partial WHERE NOT NULL).
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    vendor/bin/sail artisan tinker --execute 'Schema::hasColumn("conversation_threads", "order_id");'
    ```
    Returns `true`.

- [ ] **E21.T11** Create Order model with relationships + factory
  - Files:
    - `app/Models/Order.php`
    - `database/factories/OrderFactory.php`
  - Model defines: `belongsTo(User::class, 'buyer_user_id')`, `belongsTo(Shop::class)`, `belongsTo(Quotation::class)` (nullable), `hasMany(OrderItem::class)`, `hasMany(OrderFee::class)`, `hasMany(OrderPaymentTerm::class)`, `hasMany(Fulfilment::class)`, `hasMany(Dispute::class)`, `hasMany(LedgerEntry::class)`.
  - Casts: `status` as native enum, financial columns as `decimal:2`, timestamps as `datetime`.
  - Scopes: `scopeForBuyer($query, $userId)`, `scopeForShop($query, $shopId)`, `scopeInStatus($query, $status)`.
  - Factory: creates order with all snapshot fields filled, default `status = draft`.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'App\Models\Order::factory()->make();'
    ```

- [ ] **E21.T12** Create OrderItem, OrderFee, OrderPaymentTerm models + factories `[parallel-safe]`
  - Files:
    - `app/Models/OrderItem.php` — `belongsTo(Order)`, casts `quantity`/`unit_price`/`subtotal` as `decimal:2`.
    - `app/Models/OrderFee.php` — `belongsTo(Order)`, casts `amount` as `decimal:2`.
    - `app/Models/OrderPaymentTerm.php` — `belongsTo(Order)`, `hasMany(PaymentAttempt)`, casts `amount` as `decimal:2`, `type` as native enum.
    - `database/factories/OrderItemFactory.php`
    - `database/factories/OrderFeeFactory.php`
    - `database/factories/OrderPaymentTermFactory.php`
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'App\Models\OrderItem::factory()->make(); App\Models\OrderFee::factory()->make(); App\Models\OrderPaymentTerm::factory()->make();'
    ```

- [ ] **E21.T13** Create PaymentAttempt, Fulfilment, Dispute, LedgerEntry models + factories `[parallel-safe]`
  - Files:
    - `app/Models/PaymentAttempt.php` — `belongsTo(OrderPaymentTerm)`, casts `status` as native enum, `gateway_response` as `array`.
    - `app/Models/Fulfilment.php` — `belongsTo(Order)`, casts `status` as native enum.
    - `app/Models/Dispute.php` — `belongsTo(Order)`, `belongsTo(User, 'opened_by_user_id')`, `belongsTo(User, 'resolved_by_user_id')`, casts `type`/`status` as native enums, `resolution_amount` as `decimal:2`.
    - `app/Models/LedgerEntry.php` — `belongsTo(Order)`, casts `type` as native enum, `amount` as `decimal:2`. **No** `updated_at` — set `const UPDATED_AT = null;` on model.
    - `database/factories/PaymentAttemptFactory.php`
    - `database/factories/FulfilmentFactory.php`
    - `database/factories/DisputeFactory.php`
    - `database/factories/LedgerEntryFactory.php`
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'App\Models\PaymentAttempt::factory()->make(); App\Models\Fulfilment::factory()->make(); App\Models\Dispute::factory()->make(); App\Models\LedgerEntry::factory()->make();'
    ```

- [ ] **E21.T14** Update ConversationThread model to add `order_id` relationship
  - File: `app/Models/ConversationThread.php` (modify existing)
  - Add `belongsTo(Order::class)` (nullable).
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute '(new App\Models\ConversationThread)->order();'
    ```
    Returns a BelongsTo relationship without error.

- [ ] **E21.T15** Add inverse relationships on User, Shop, Quotation models
  - Files (modify existing):
    - `app/Models/User.php` — add `hasMany(Order::class, 'buyer_user_id')`.
    - `app/Models/Shop.php` — add `hasMany(Order::class)`.
    - `app/Models/Quotation.php` — add `hasOne(Order::class)`.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute '(new App\Models\User)->orders(); (new App\Models\Shop)->orders(); (new App\Models\Quotation)->order();'
    ```

- [ ] **E21.T16** Feature tests for schema and model relationships
  - File: `tests/Feature/OrderSchemaTest.php`
  - Test cases:
    - Order can be created via factory with all default states.
    - Order → items, fees, payment_terms, fulfilments, disputes, ledger_entries relationships resolve.
    - Unique constraint on `quotation_id` prevents duplicate orders from same quotation.
    - `ON DELETE RESTRICT` prevents deleting a user/shop with orders (expect DB exception).
    - `ON DELETE CASCADE` on items/fees: deleting a draft order removes children.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=OrderSchemaTest
    ```

---

## Epic 22 — Order Creation & Draft Editing

**Goal:** `CreateOrderAction`, `OrderPolicy`, draft CRUD (API + Livewire), customer list for standalone orders.
**Depends on:** E21

- [ ] **E22.T1** Create `CreateOrderAction`
  - File: `app/Actions/CreateOrderAction.php`
  - Logic per design.md §7.1:
    - **From quotation**: accepts `quotation_id`. Validates quotation exists, `status = awarded`, no existing order for it. Copies items from `quotation_items` → `order_items` (description, quantity, unit, unit_price, subtotal, sort_order — see design.md §7.1 on variant snapshot handling). Copies buyer snapshot from quotation's `buyer_snapshot` → order's flat snapshot columns. Creates one `order_payment_terms` row (`type = full`, `amount = 0`).
    - **Standalone**: accepts `buyer_user_id`. Validates buyer exists and is in the seller's customer list (has at least one prior awarded quotation or order with this shop). Pre-fills snapshot from buyer's most recent order or quotation with this seller (if available). Creates empty order (no items). Creates one `order_payment_terms` row (`type = full`, `amount = 0`).
    - Sets `status = draft`. Returns the created Order (eager-loaded with items, fees).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=CreateOrderActionTest
    ```

- [ ] **E22.T2** Create `OrderPolicy`
  - File: `app/Policies/OrderPolicy.php`
  - Rules per design.md §9.1:
    - `create`: authenticated user with an active shop (seller).
    - `view`: user is the buyer (`order->buyer_user_id`) OR the seller's shop owner (`order->shop->user_id`).
    - `update`: seller owns via shop AND `status = draft`.
    - `submit`: seller owns via shop AND `status = draft`.
    - `recall`: seller owns via shop AND `status = pending_payment`.
    - `cancelAsBuyer`: buyer owns AND `status = pending_payment`.
    - `cancelAsSeller`: seller owns via shop AND `status = paid` AND no fulfilment exists (or fulfilment status is `pending` with no shipping started).
    - `confirmReceipt`: buyer owns AND fulfilment `status = delivered` AND no active dispute.
    - `delete`: seller owns via shop AND `status = draft`.
  - Register in service provider or rely on auto-discovery.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=OrderPolicyTest
    ```

- [ ] **E22.T3** Create `HubOrderController` with store/index/show/update/destroy endpoints
  - Files:
    - `app/Http/Controllers/Api/V1/HubOrderController.php`
    - `app/Http/Requests/StoreOrderRequest.php`
    - `app/Http/Requests/UpdateOrderRequest.php`
    - `app/Http/Resources/OrderResource.php`
    - `app/Http/Resources/OrderListResource.php`
  - Endpoints per design.md §4.1:
    - `POST /api/v1/hub/orders` — calls `CreateOrderAction`; response 201 with full order resource. Returns 409 if quotation already has an order.
    - `GET /api/v1/hub/orders` — lists seller's orders; filterable by `status`; cursor-paginated.
    - `GET /api/v1/hub/orders/{order}` — full order detail (items, fees, payment terms, fulfilment, dispute).
    - `PUT /api/v1/hub/orders/{order}` — update draft: partial update of snapshot fields, items (full replace array), fees (full replace array). Recalculate `items_total`, `fees_total`, `order_total` on every save.
    - `DELETE /api/v1/hub/orders/{order}` — delete draft order only.
  - Rate limiting: 20 order creations per hour per user.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/hub/orders
    ```
    Shows POST, GET (index), GET (show), PUT, DELETE routes.

- [ ] **E22.T4** Create `SubmitOrderAction`
  - File: `app/Actions/SubmitOrderAction.php`
  - Logic per design.md §7.2:
    - Validate: at least one item, `order_total > 0`.
    - DB transaction:
      - Recalculate `items_total`, `fees_total`, `order_total` (final computation).
      - Update `order_payment_terms.amount = order_total`.
      - Set `status = pending_payment`, `submitted_at = now()`.
    - After commit: create/reuse conversation, create order thread (`conversation_threads` with `order_id`), post system message with `order:{order_id}` body prefix.
    - After commit: dispatch `OrderSubmittedForPayment` event.
    - Returns the updated Order.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=SubmitOrderAction
    ```

- [ ] **E22.T5** Create submit and recall endpoints on `HubOrderController`
  - File: `app/Http/Controllers/Api/V1/HubOrderController.php` (extend)
  - `POST /api/v1/hub/orders/{order}/submit`:
    - Policy: `submit`.
    - Calls `SubmitOrderAction`.
    - Returns 200 with updated order.
    - Returns 422 if no items.
  - `POST /api/v1/hub/orders/{order}/recall`:
    - Policy: `recall`.
    - Validate `status = pending_payment`.
    - Set `status = draft`, clear `submitted_at`.
    - Returns 200 with updated order.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/hub/orders
    ```
    Shows submit and recall routes.

- [ ] **E22.T6** Create seller cancel endpoint on `HubOrderController`
  - File: `app/Http/Controllers/Api/V1/HubOrderController.php` (extend)
  - `POST /api/v1/hub/orders/{order}/cancel`:
    - Policy: `cancelAsSeller`.
    - DB transaction: set `status = cancelled`, `cancelled_at = now()`. If order was `paid`: create `ledger_entry` type `refund` for full `order_total`.
    - After commit: dispatch `OrderCancelled` event.
    - Returns 200.
    - Returns 409 if fulfilment already exists and is shipped/delivered.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/hub/orders
    ```

- [ ] **E22.T7** Register all Hub order API routes
  - File: `routes/api.php` (modify)
  - Add routes under `api/v1` prefix with `auth:sanctum` middleware:
    ```php
    Route::post('hub/orders', ...);
    Route::get('hub/orders', ...);
    Route::get('hub/orders/{order}', ...);
    Route::put('hub/orders/{order}', ...);
    Route::post('hub/orders/{order}/submit', ...);
    Route::post('hub/orders/{order}/recall', ...);
    Route::post('hub/orders/{order}/cancel', ...);
    Route::delete('hub/orders/{order}', ...);
    ```
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/hub/orders
    ```
    All 8 routes listed.

- [ ] **E22.T8** Create `OrderBuilder` Livewire component (company-hub/orders/order-builder)
  - Files:
    - `app/Livewire/CompanyHub/Orders/OrderBuilder.php`
    - `resources/views/livewire/company-hub/orders/order-builder.blade.php`
  - Component:
    - Accepts `$orderId` route parameter (for editing existing draft) or `$quotationId`/`$buyerUserId` (for creation).
    - On mount: if `$orderId`, load existing draft; if `$quotationId`, call `CreateOrderAction` to create from quotation; if `$buyerUserId`, call `CreateOrderAction` standalone.
    - Displays editable: customer contact, billing address, shipping address, order items (Alpine.js add/remove/reorder rows with description, quantity, unit via `<x-hs-unit-select>`, unit_price — subtotal auto-calculated), fees (Alpine.js add/remove rows with label, amount).
    - Real-time total recalculation client-side (Alpine.js) and server-side (on save).
    - Save action: calls `PUT /api/v1/hub/orders/{order}` (or internal action equivalent).
    - Submit action: calls submit endpoint; shows confirmation modal warning buyer will be notified.
  - Uses project view conventions: card layout, design tokens, `x-hs-unit-select`, `x-hs-modal`.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=OrderBuilder
    ```

- [ ] **E22.T9** Create `OrderQueue` Livewire component (company-hub/orders/order-queue)
  - Files:
    - `app/Livewire/CompanyHub/Orders/OrderQueue.php`
    - `resources/views/livewire/company-hub/orders/order-queue.blade.php`
  - Component:
    - Lists seller's orders with tab filter: Draft / Pending payment / To fulfil / Disputed.
    - Each row shows: order ID, buyer business name, order total, status pill (`<x-hs-status-pill>`), created date, action link.
    - "New Order" button → navigates to OrderBuilder (standalone creation flow — modal to pick customer).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=OrderQueue
    ```

- [ ] **E22.T10** Create seller's order detail Livewire component (company-hub/orders/order-detail)
  - Files:
    - `app/Livewire/CompanyHub/Orders/OrderDetail.php`
    - `resources/views/livewire/company-hub/orders/order-detail.blade.php`
  - Component:
    - Displays full order info: status badge, customer snapshot, items table, fees table, totals.
    - Conditional actions based on status: Recall (if `pending_payment`), Cancel (if `paid` pre-fulfilment), Edit (if `draft` → navigates to OrderBuilder).
    - Embeds fulfilment panel (E24.T6) and dispute panel (E25.T7) as sub-views.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=OrderDetail
    ```

- [ ] **E22.T11** Create `BuyerOrderController` with index/show/cancel/confirm-receipt endpoints
  - Files:
    - `app/Http/Controllers/Api/V1/BuyerOrderController.php`
    - `app/Http/Resources/BuyerOrderResource.php`
  - Endpoints per design.md §4.2:
    - `GET /api/v1/orders` — list buyer's orders; filterable by `tab` (to_pay, to_ship, to_receive, completed, disputed); cursor-paginated.
    - `GET /api/v1/orders/{order}` — order detail for buyer.
    - `POST /api/v1/orders/{order}/cancel` — buyer cancel (`pending_payment` only).
    - `POST /api/v1/orders/{order}/confirm-receipt` — see E24.T4.
  - Register routes in `routes/api.php`.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/orders
    ```
    Shows GET (index), GET (show), POST (cancel), POST (confirm-receipt).

- [ ] **E22.T12** Create buyer `OrderList` Livewire component (account/orders/order-list)
  - Files:
    - `app/Livewire/Account/Orders/OrderList.php`
    - `resources/views/livewire/account/orders/order-list.blade.php`
  - Component per design.md §5.2:
    - Five tabs: To pay / To ship / To receive / Completed / Disputes.
    - Tab logic per requirements §4.7:
      - To pay: `status = pending_payment`.
      - To ship: `status = paid` AND (no fulfilment OR fulfilment.status = `pending`).
      - To receive: fulfilment.status IN (`shipped`, `delivered`).
      - Completed: `status = completed`.
      - Disputes: has active dispute (non-terminal status); removes order from other tabs while disputed.
    - Each row: shop name, order total, status pill, date, link to detail.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=OrderList
    ```

- [ ] **E22.T13** Create buyer `OrderDetail` Livewire component (account/orders/order-detail)
  - Files:
    - `app/Livewire/Account/Orders/OrderDetail.php`
    - `resources/views/livewire/account/orders/order-detail.blade.php`
  - Component:
    - Displays order info: shop name, items table, fees, totals, fulfilment tracking, status.
    - Conditional CTAs: "Pay Now" (if `pending_payment`), "Cancel" (if `pending_payment`), "Track Order" (if shipped, not delivered), "Confirm Receipt" (if delivered), "Open Dispute" (if delivered or completed).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter="Account\\\\Orders\\\\OrderDetail"
    ```

- [ ] **E22.T14** Register buyer order web routes
  - File: `routes/web.php` (modify)
  - Routes under authenticated buyer area:
    - `/account/orders` → `OrderList` Livewire page.
    - `/account/orders/{order}` → `OrderDetail` Livewire page.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=account/orders
    ```

- [ ] **E22.T15** Register seller order web routes
  - File: `routes/web.php` (modify)
  - Routes under Company Hub area:
    - `/hub/orders` → `OrderQueue` Livewire page.
    - `/hub/orders/create` → `OrderBuilder` (new order from customer selection).
    - `/hub/orders/{order}/edit` → `OrderBuilder` (edit existing draft).
    - `/hub/orders/{order}` → `OrderDetail` Livewire page.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=hub/orders
    ```

- [ ] **E22.T16** Feature tests for order creation, draft editing, submission, recall
  - File: `tests/Feature/OrderCreationTest.php`
  - Test cases:
    - Seller can create order from awarded quotation (POST → 201; items copied correctly; snapshot populated).
    - Seller cannot create order from non-awarded quotation (POST → 422/409).
    - Seller cannot create duplicate order from same quotation (POST → 409).
    - Seller can create standalone order from customer list (POST → 201).
    - Seller cannot create standalone order for non-customer buyer (POST → 422).
    - Seller can update draft order (items, fees, snapshot; totals recalculated).
    - Seller cannot update non-draft order (PUT → 403/409).
    - Seller can submit order with items (POST submit → 200; status changes to `pending_payment`).
    - Seller cannot submit order with no items (POST submit → 422).
    - Seller can recall pending_payment order (POST recall → 200; status back to `draft`).
    - Seller can delete draft order (DELETE → 200; children cascade deleted).
    - Seller cannot delete non-draft order (DELETE → 403).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=OrderCreationTest
    ```

- [ ] **E22.T17** Feature tests for buyer order listing, cancellation
  - File: `tests/Feature/BuyerOrderTest.php`
  - Test cases:
    - Buyer can list orders filtered by tab (index → 200; correct orders in each tab).
    - Buyer can view their order detail (show → 200).
    - Buyer cannot view another buyer's order (show → 403).
    - Buyer can cancel `pending_payment` order (cancel → 200; status = cancelled).
    - Buyer cannot cancel `paid` order (cancel → 409).
    - Buyer cannot cancel another buyer's order (cancel → 403).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=BuyerOrderTest
    ```

---

## Epic 23 — Payment & DLT Gateway Integration

**Goal:** `DltPaymentService`, payment initiation endpoint, DLT callback handling, escrow hold ledger entry.
**Depends on:** E22

- [ ] **E23.T1** Add DLT configuration to `config/services.php`
  - File: `config/services.php` (modify)
  - Add `dlt` key per design.md §3.2:
    ```php
    'dlt' => [
        'base_url'            => env('DLT_BASE_URL', 'https://checkout.dxp.dtic.com.ph'),
        'client_id'           => env('DLT_CLIENT_ID'),
        'client_secret'       => env('DLT_CLIENT_SECRET'),
        'merchant_account_id' => env('DLT_MERCHANT_ACCOUNT_ID'),
        'service_code'        => env('DLT_SERVICE_CODE', 'APN-COLLECTION'),
    ],
    ```
  - Add placeholder keys to `.env.example` (never real credentials).
  - Verify:
    ```bash
    vendor/bin/sail artisan config:show services.dlt
    ```
    Shows all 5 keys.

- [ ] **E23.T2** Create `DltPaymentService`
  - File: `app/Services/DltPaymentService.php`
  - Responsibilities per design.md §3.2:
    - `getBearerToken()`: OAuth2 client credentials grant to `/oauth/token`. Cache in Redis with TTL = `expires_in - 60`. Return cached token if valid.
    - `createQuickLinkSession(...)`: `POST /api/v1/quick-link/session` with `merchant_account_id`, `service_code`, `merchant_transaction_id`, `amount`, `product_name`, `success_url`, `failure_url`. Returns `['token' => ..., 'checkout_url' => ...]`.
    - `getQuickLinkSession(string $token)`: `GET /api/v1/quick-link/session?token=...`. Returns session metadata (used as tamper guard).
  - Uses Laravel HTTP client (`Http::withToken(...)->post(...)` pattern).
  - Bind in `AppServiceProvider` as singleton.
  - Refer to DLT Payment API skill (`/.kiro/skills/dlt-payment-api/`) and its `references/api-reference.md` for exact field names and response shapes.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=DltPaymentServiceTest
    ```

- [ ] **E23.T3** Create `OrderPaymentController` — payment initiation endpoint
  - File: `app/Http/Controllers/Api/V1/OrderPaymentController.php`
  - Endpoint per design.md §4.3: `POST /api/v1/orders/{order}/payment`:
    - Validate order is `pending_payment` and buyer owns it.
    - Create `payment_attempts` row (status=pending, generate `dlt_merchant_transaction_id` as `order-{order_id}-{ulid}`).
    - Call `DltPaymentService::createQuickLinkSession(...)` with order total, product name (first item description or "Order #{id}"), success/failure URLs.
    - Store returned `token` and `checkout_url` on the payment_attempts row.
    - Return 201 with `{ payment_attempt_id, checkout_url, expires_at }`.
    - Return 409 if order is not `pending_payment`.
  - Rate limiting: 10 payment initiations per hour per order.
  - Register route in `routes/api.php`.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/orders
    ```
    Shows `POST orders/{order}/payment`.

- [ ] **E23.T4** Create `OrderPaymentCallbackController` — success/failure web routes
  - File: `app/Http/Controllers/OrderPaymentCallbackController.php`
  - Routes per design.md §3.4:
    - `GET /orders/{order}/payment/success?attempt={paymentAttempt}` — calls `ProcessOrderPaymentAction` (success path).
    - `GET /orders/{order}/payment/failure?attempt={paymentAttempt}` — marks attempt as `failed`, redirects to order detail with retry prompt.
  - Both routes require authentication (buyer must be logged in).
  - Success callback: calls `DltPaymentService::getQuickLinkSession(token)` as tamper guard (verify token belongs to this attempt). If valid → delegate to `ProcessOrderPaymentAction`.
  - Idempotency: if payment_attempt is already `succeeded`, skip re-processing and redirect to order confirmation page.
  - Register web routes per design.md §6.1.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --name=orders.payment
    ```
    Shows `orders.payment.success` and `orders.payment.failure`.

- [ ] **E23.T5** Create `ProcessOrderPaymentAction`
  - File: `app/Actions/ProcessOrderPaymentAction.php`
  - Logic per design.md §7.3:
    - DB transaction with row-level lock on `payment_attempts` (SELECT FOR UPDATE).
    - If already `succeeded`, return early (idempotency).
    - Update `payment_attempts`: `status = succeeded`, `succeeded_at = now()`, store `gateway_response`.
    - Update order: `status = paid`, `paid_at = now()`.
    - Create `ledger_entries` row: `type = escrow_hold`, `amount = order_total`.
    - After commit: dispatch `OrderPaid` event (broadcasts on `order.{id}` channel).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=ProcessOrderPaymentAction
    ```

- [ ] **E23.T6** Create `PaymentPage` Livewire component (account/orders/payment-page)
  - Files:
    - `app/Livewire/Account/Orders/PaymentPage.php`
    - `resources/views/livewire/account/orders/payment-page.blade.php`
  - Component per design.md §5.2:
    - Displays order summary (items, fees, total).
    - "Pay Now" button: calls payment initiation API → redirects to DLT hosted checkout URL.
    - On return from failure: shows retry button and error message.
    - Listens on `order.{id}` Reverb channel for `OrderPaid` event to update UI if payment confirmed in another tab.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=PaymentPage
    ```

- [ ] **E23.T7** Create `OrderPaid` event and broadcaster
  - Files:
    - `app/Events/OrderPaid.php`
  - Event broadcasts on private channel `order.{id}` per design.md §8.3.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'new App\Events\OrderPaid(App\Models\Order::factory()->make());'
    ```

- [ ] **E23.T8** Create `OrderSubmittedForPayment` event
  - File: `app/Events/OrderSubmittedForPayment.php`
  - Event fired after order submission. Listener creates order thread + sends notification to buyer (see E26 for thread creation details).
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'new App\Events\OrderSubmittedForPayment(App\Models\Order::factory()->make());'
    ```

- [ ] **E23.T9** Register broadcast channel authorization for `order.{id}`
  - File: `routes/channels.php` (modify)
  - Add per design.md §8.3:
    ```php
    Broadcast::channel('order.{id}', function (User $user, int $id) {
        $order = Order::findOrFail($id);
        return $user->id === $order->buyer_user_id
            || $user->id === $order->shop->user_id;
    });
    ```
  - Verify:
    ```bash
    grep -n "order\." routes/channels.php
    ```

- [ ] **E23.T10** Feature tests for payment flow
  - File: `tests/Feature/OrderPaymentTest.php`
  - Test cases:
    - Payment initiation creates payment_attempt with DLT fields (mock DltPaymentService).
    - Payment initiation returns 409 if order is not `pending_payment`.
    - Success callback marks attempt as succeeded, order as paid, creates escrow_hold ledger entry (atomic).
    - Success callback is idempotent — duplicate call doesn't create duplicate ledger entries.
    - Failure callback marks attempt as failed, order stays `pending_payment`.
    - Non-buyer cannot initiate payment (403).
    - Rate limit blocks excessive payment attempts.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=OrderPaymentTest
    ```

- [ ] **E23.T11** Unit test for `DltPaymentService` (mocked HTTP)
  - File: `tests/Unit/DltPaymentServiceTest.php`
  - Test cases:
    - `getBearerToken()` caches token and returns it on subsequent calls.
    - `getBearerToken()` refreshes when cache is empty.
    - `createQuickLinkSession()` sends correct payload and parses response.
    - `getQuickLinkSession()` fetches session by token.
    - Handles DLT API error responses gracefully.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=DltPaymentServiceTest
    ```

---

## Epic 24 — Fulfilment

**Goal:** Fulfilment CRUD, ship/deliver/return actions, buyer tracking CTA, 7-day auto-accept job.
**Depends on:** E23

- [ ] **E24.T1** Create `FulfilmentPolicy`
  - File: `app/Policies/FulfilmentPolicy.php`
  - Rules per design.md §9.1:
    - `create`: seller owns the parent order's shop AND `order.status = paid` AND no existing fulfilment for this order.
    - `update`: seller owns AND fulfilment.status IN (`pending`, `shipped`).
    - `ship`: seller owns AND `fulfilment.status = pending`.
    - `deliver`: seller owns AND `fulfilment.status = shipped`.
    - `returnToSeller`: seller owns AND `fulfilment.status = shipped`.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=FulfilmentPolicyTest
    ```

- [ ] **E24.T2** Create `FulfilmentController` with store/update/ship/deliver/return endpoints
  - File: `app/Http/Controllers/Api/V1/FulfilmentController.php`
  - Endpoints per design.md §4.4:
    - `POST /api/v1/hub/orders/{order}/fulfilment` — create fulfilment (status=pending, optional tracking fields). Returns 201.
    - `PUT /api/v1/hub/orders/{order}/fulfilment` — update tracking fields (while pending/shipped). Returns 200.
    - `POST /api/v1/hub/orders/{order}/fulfilment/ship` — mark shipped, set `shipped_at`, optionally update tracking. Dispatches `OrderShipped` event. Returns 200.
    - `POST /api/v1/hub/orders/{order}/fulfilment/deliver` — mark delivered, set `delivered_at`. Dispatches `OrderDelivered` event. Returns 200.
    - `POST /api/v1/hub/orders/{order}/fulfilment/return` — mark returned_to_seller, set `returned_at`. Returns 200.
  - Register routes in `routes/api.php`.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/hub/orders
    ```
    Shows fulfilment routes.

- [ ] **E24.T3** Create `ConfirmReceiptAction`
  - File: `app/Actions/ConfirmReceiptAction.php`
  - Logic per design.md §7.4:
    - DB transaction with row-level lock on order.
    - Validate: `fulfilment.status = delivered`, no active dispute (non-terminal).
    - Set `order.status = completed`, `order.completed_at = now()`.
    - Create `ledger_entries`: `type = payout`, `amount = order_total` (minus platform commission — use `config('marketplace.commission_rate', 0.01)` placeholder).
    - After commit: dispatch `OrderCompleted` event.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=ConfirmReceiptAction
    ```

- [ ] **E24.T4** Add `confirmReceipt` action to `BuyerOrderController`
  - File: `app/Http/Controllers/Api/V1/BuyerOrderController.php` (extend)
  - `POST /api/v1/orders/{order}/confirm-receipt`:
    - Policy check: `confirmReceipt`.
    - Calls `ConfirmReceiptAction`.
    - Returns 200 with updated order.
    - Returns 409 if order not yet delivered or has active dispute.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/orders
    ```
    Shows confirm-receipt route.

- [ ] **E24.T5** Create `OrderAutoAcceptJob`
  - File: `app/Jobs/OrderAutoAcceptJob.php`
  - Logic per design.md §8.1:
    - Query orders WHERE `status = paid` AND fulfilment `status = delivered` AND `delivered_at` ≤ `now() - 7 days` AND no active dispute (WHERE NOT EXISTS subquery on disputes with non-terminal status).
    - For each qualifying order: call `ConfirmReceiptAction`.
    - Idempotency: `ConfirmReceiptAction` uses row-level lock and validates status; safe to re-run.
  - Register in scheduler (`app/Console/Kernel.php` or `routes/console.php`): daily at 04:00 UTC.
  - Verify:
    ```bash
    vendor/bin/sail artisan schedule:list
    ```
    Shows `OrderAutoAcceptJob` daily.

- [ ] **E24.T6** Create `FulfilmentPanel` Livewire component (company-hub/fulfilment/fulfilment-panel)
  - Files:
    - `app/Livewire/CompanyHub/Fulfilment/FulfilmentPanel.php`
    - `resources/views/livewire/company-hub/fulfilment/fulfilment-panel.blade.php`
  - Component per design.md §5.2:
    - Embedded in seller's order-detail page.
    - If no fulfilment exists and order is `paid`: "Create Fulfilment" button with optional tracking fields.
    - If fulfilment exists: shows status, tracking info, and action buttons (Mark Shipped / Mark Delivered / Mark Returned) based on current fulfilment status.
    - Confirmation modals for each action.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=FulfilmentPanel
    ```

- [ ] **E24.T7** Create fulfilment events: `OrderShipped`, `OrderDelivered`
  - Files:
    - `app/Events/OrderShipped.php`
    - `app/Events/OrderDelivered.php`
  - Both broadcast on private channel `order.{id}`.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'new App\Events\OrderShipped(App\Models\Order::factory()->make()); new App\Events\OrderDelivered(App\Models\Order::factory()->make());'
    ```

- [ ] **E24.T8** Create `OrderCompleted` and `OrderCancelled` events
  - Files:
    - `app/Events/OrderCompleted.php`
    - `app/Events/OrderCancelled.php`
  - Broadcast on `order.{id}`.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'new App\Events\OrderCompleted(App\Models\Order::factory()->make()); new App\Events\OrderCancelled(App\Models\Order::factory()->make());'
    ```

- [ ] **E24.T9** Add `marketplace.commission_rate` config
  - File: `config/marketplace.php` (create)
  - Contents:
    ```php
    return [
        'commission_rate' => env('MARKETPLACE_COMMISSION_RATE', 0.01), // 1% flat placeholder
        'auto_accept_days' => env('MARKETPLACE_AUTO_ACCEPT_DAYS', 7),
    ];
    ```
  - Verify:
    ```bash
    vendor/bin/sail artisan config:show marketplace
    ```

- [ ] **E24.T10** Feature tests for fulfilment flow
  - File: `tests/Feature/FulfilmentTest.php`
  - Test cases:
    - Seller can create fulfilment for paid order (POST → 201; status=pending).
    - Seller cannot create fulfilment for non-paid order (POST → 409).
    - Seller cannot create second fulfilment (unique constraint — POST → 409).
    - Seller can mark shipped with tracking (POST ship → 200; status=shipped, shipped_at set).
    - Seller cannot mark shipped if already shipped (POST ship → 409).
    - Seller can mark delivered (POST deliver → 200; status=delivered, delivered_at set).
    - Seller can mark returned to seller (POST return → 200; status=returned_to_seller).
    - Buyer confirms receipt → order completed, payout ledger entry created.
    - Buyer cannot confirm receipt before delivered (POST → 409).
    - `OrderAutoAcceptJob` auto-completes orders delivered 7+ days ago with no dispute.
    - `OrderAutoAcceptJob` skips orders with active disputes.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=FulfilmentTest
    ```

---

## Epic 25 — Disputes

**Goal:** Dispute creation, seller resolution, admin arbitration, escalation, withdrawal, timer pause logic.
**Depends on:** E24

- [ ] **E25.T1** Create `DisputePolicy`
  - File: `app/Policies/DisputePolicy.php`
  - Rules per design.md §9.1:
    - `create`: buyer owns the order AND fulfilment.status IN (`delivered`, `returned_to_seller`) AND no active dispute (non-terminal) already open.
    - `view`: user is buyer OR seller of the order.
    - `resolve`: seller owns the order's shop AND dispute.status IN appropriate pre-resolution states (`open` for refund_only; `return_received` for return_refund). For return_refund with status=`open`, only `confirmReturn` action allowed, not resolve.
    - `confirmReturn`: seller owns AND dispute.type = `return_refund` AND dispute.status IN (`open`, `awaiting_return`).
    - `escalate`: user is buyer OR seller AND dispute.status NOT IN terminal/escalated states.
    - `withdraw`: buyer opened the dispute AND dispute.status NOT IN terminal states.
    - `arbitrate`: admin role AND `dispute.status = escalated`.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=DisputePolicyTest
    ```

- [ ] **E25.T2** Create `DisputeController` with store/show/escalate/withdraw endpoints
  - File: `app/Http/Controllers/Api/V1/DisputeController.php`
  - Endpoints per design.md §4.5:
    - `POST /api/v1/orders/{order}/disputes` — buyer opens dispute. Validate type, reason. Creates dispute row, sets status (`open` for refund_only; `awaiting_return` for return_refund). Dispatches `DisputeOpened` event. Returns 201.
    - `GET /api/v1/orders/{order}/disputes/{dispute}` — dispute detail. Returns 200.
    - `POST /api/v1/orders/{order}/disputes/{dispute}/escalate` — either party escalates. Set `status = escalated`, `escalated_at = now()`. Dispatches `DisputeEscalated` event. Returns 200.
    - `POST /api/v1/orders/{order}/disputes/{dispute}/withdraw` — buyer withdraws. Set `status = withdrawn`. Returns 200.
  - Rate limiting: 5 dispute openings per day per user.
  - Register routes in `routes/api.php`.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/orders
    ```
    Shows dispute routes.

- [ ] **E25.T3** Add resolve and confirm-return endpoints to `DisputeController`
  - File: `app/Http/Controllers/Api/V1/DisputeController.php` (extend)
  - `POST /api/v1/orders/{order}/disputes/{dispute}/resolve`:
    - Seller resolves. Calls `ResolveDisputeAction`.
    - Validates `resolution_type` IN (`full_refund`, `partial_refund`, `denied`), `resolution_amount` ≤ `order_total` if partial.
    - For `return_refund` type: requires `dispute.status = return_received`.
    - Returns 200.
  - `POST /api/v1/orders/{order}/disputes/{dispute}/confirm-return`:
    - Seller confirms physical return received. Set `dispute.status = return_received`.
    - Returns 200.
  - Register routes.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/orders
    ```

- [ ] **E25.T4** Create `ResolveDisputeAction`
  - File: `app/Actions/ResolveDisputeAction.php`
  - Logic per design.md §7.5:
    - DB transaction with row-level lock on dispute + order.
    - Validate resolution_type and amount.
    - For `return_refund`: require `dispute.status = return_received`.
    - Update dispute: `status = resolved_refunded` or `resolved_denied`, `resolved_at`, `resolved_by_user_id`, `resolution_type`, `resolution_amount`, `resolution_notes`.
    - If refund (full or partial):
      - Create `ledger_entries`: `type = refund`, `amount = resolution_amount`.
      - If partial: create `ledger_entries`: `type = payout`, `amount = (order_total - resolution_amount - commission)`.
      - Set `order.status = refunded`.
    - If denied: set `order.status = completed` (order stands).
    - After commit: dispatch `DisputeResolved` event.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=ResolveDisputeAction
    ```

- [ ] **E25.T5** Create `AdminDisputeController` — arbitration endpoint + queue
  - Files:
    - `app/Http/Controllers/Api/V1/AdminDisputeController.php`
  - Endpoints per design.md §4.5:
    - `GET /api/v1/admin/disputes` — list escalated disputes. Scoped to `status = escalated`. Cursor-paginated. Returns 200.
    - `POST /api/v1/admin/disputes/{dispute}/arbitrate` — admin resolves. Calls `ResolveDisputeAction` with admin as resolver. Validates `resolution_type` required, `resolution_notes` required.
  - Middleware: `role:admin`.
  - Register routes.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/admin/disputes
    ```

- [ ] **E25.T6** Create dispute events: `DisputeOpened`, `DisputeResolved`, `DisputeEscalated`
  - Files:
    - `app/Events/DisputeOpened.php`
    - `app/Events/DisputeResolved.php`
    - `app/Events/DisputeEscalated.php`
  - All broadcast on `order.{id}` channel (the parent order's channel).
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'new App\Events\DisputeOpened(App\Models\Dispute::factory()->make());'
    ```

- [ ] **E25.T7** Create `DisputePanel` Livewire component (company-hub/disputes/dispute-panel)
  - Files:
    - `app/Livewire/CompanyHub/Disputes/DisputePanel.php`
    - `resources/views/livewire/company-hub/disputes/dispute-panel.blade.php`
  - Component per design.md §5.2:
    - Embedded in seller's order-detail page (when dispute exists).
    - Shows dispute status, type, reason, timeline.
    - Actions: Confirm Return (if return_refund + awaiting_return/open), Resolve (with refund form — type selector + amount + notes), Escalate.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=DisputePanel
    ```

- [ ] **E25.T8** Create buyer `DisputeForm` Livewire component (account/disputes/dispute-form)
  - Files:
    - `app/Livewire/Account/Disputes/DisputeForm.php`
    - `resources/views/livewire/account/disputes/dispute-form.blade.php`
  - Component per design.md §5.2:
    - Type selector (return_refund / refund_only).
    - Reason text area (max 2000 chars).
    - Optional photo/evidence upload (reuses existing media upload pipeline).
    - Submit → calls disputes API.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=DisputeForm
    ```

- [ ] **E25.T9** Create buyer `DisputeDetail` Livewire component (account/disputes/dispute-detail)
  - Files:
    - `app/Livewire/Account/Disputes/DisputeDetail.php`
    - `resources/views/livewire/account/disputes/dispute-detail.blade.php`
  - Component:
    - Shows dispute status, type, reason, resolution (if resolved), timeline.
    - Actions: Escalate, Withdraw.
    - Listens on `order.{id}` for real-time dispute status updates.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=DisputeDetail
    ```

- [ ] **E25.T10** Create admin `DisputeQueue` Livewire component (admin/disputes/dispute-queue)
  - Files:
    - `app/Livewire/Admin/Disputes/DisputeQueue.php`
    - `resources/views/livewire/admin/disputes/dispute-queue.blade.php`
  - Component per design.md §5.1:
    - Lists escalated disputes only.
    - Each row: order ID, buyer name, seller shop name, dispute type, escalated_at, link to arbitration view.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=DisputeQueue
    ```

- [ ] **E25.T11** Create admin `DisputeArbitration` Livewire component (admin/disputes/dispute-arbitration)
  - Files:
    - `app/Livewire/Admin/Disputes/DisputeArbitration.php`
    - `resources/views/livewire/admin/disputes/dispute-arbitration.blade.php`
  - Component per design.md §5.2:
    - Full order context: items, fees, fulfilment, dispute reason + thread history.
    - Arbitration form: resolution_type selector (full_refund / partial_refund / denied), resolution_amount (if partial), resolution_notes (required).
    - Submit → calls admin arbitrate API.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=DisputeArbitration
    ```

- [ ] **E25.T12** Register dispute web routes (buyer + admin)
  - File: `routes/web.php` (modify)
  - Buyer routes:
    - `/account/orders/{order}/dispute/create` → `DisputeForm`.
    - `/account/orders/{order}/dispute/{dispute}` → `DisputeDetail`.
  - Admin routes:
    - `/admin/disputes` → `DisputeQueue`.
    - `/admin/disputes/{dispute}` → `DisputeArbitration`.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=admin/disputes
    vendor/bin/sail artisan route:list --path=account/orders
    ```

- [ ] **E25.T13** Update `OrderAutoAcceptJob` to respect dispute timer pause
  - File: `app/Jobs/OrderAutoAcceptJob.php` (modify)
  - Ensure the job's query excludes orders with active disputes:
    ```sql
    WHERE NOT EXISTS (
        SELECT 1 FROM disputes
        WHERE disputes.order_id = orders.id
        AND disputes.status NOT IN ('resolved_refunded', 'resolved_denied', 'withdrawn')
    )
    ```
  - This was specified in E24.T5 but verify/reinforce here with explicit test.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter="OrderAutoAcceptJob.*dispute"
    ```

- [ ] **E25.T14** Feature tests for disputes
  - File: `tests/Feature/DisputeTest.php`
  - Test cases:
    - Buyer can open dispute on delivered order (POST → 201; dispute.status = open/awaiting_return based on type).
    - Buyer cannot open dispute before delivery (POST → 409).
    - Buyer cannot open dispute if one already active (POST → 409).
    - Seller can confirm return (POST → 200; status = return_received).
    - Seller can resolve with full refund (POST → 200; order status = refunded; ledger entry created).
    - Seller can resolve with partial refund (POST → 200; two ledger entries: refund + payout).
    - Seller can deny (POST → 200; order status = completed).
    - Seller cannot resolve return_refund before return_received (POST → 409).
    - Either party can escalate (POST → 200; status = escalated).
    - Admin can arbitrate escalated dispute (POST → 200; ledger entries created).
    - Admin cannot arbitrate non-escalated dispute (POST → 403/409).
    - Buyer can withdraw dispute (POST → 200; status = withdrawn).
    - Late dispute (after completion) works correctly.
    - Timer pause: auto-accept skips orders with active disputes.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=DisputeTest
    ```

---

## Epic 26 — Order Thread (Chat Integration)

**Goal:** Order thread in existing conversation, order/dispute card system messages, real-time updates.
**Depends on:** E25

- [ ] **E26.T1** Create listener to create order thread on submission
  - File: `app/Listeners/CreateOrderConversationThread.php`
  - Logic (mirrors Phase 2's `CreateRfqConversationThread`):
    - Triggered by `OrderSubmittedForPayment` event.
    - Find or create `conversations` row for `(buyer_user_id, shop_id)`.
    - Create `conversation_threads` row with `order_id` FK set, title = "Order #{order_id}".
    - Post a system message in the thread with body prefix `order:{order_id}` (for card rendering).
  - Register listener in `EventServiceProvider` (or event discovery).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=CreateOrderConversationThread
    ```

- [ ] **E26.T2** Create `x-shared.order-card` Blade component
  - File: `resources/views/components/shared/order-card.blade.php`
  - Component per design.md §6.1:
    - Renders order status at a glance: status pill, items summary (count + total), buyer/seller name, key dates.
    - Accepts `$order` prop (Eloquent model).
    - Uses design tokens, `x-hs-status-pill`.
  - Verify: component renders without error in a test view.

- [ ] **E26.T3** Create `x-shared.dispute-card` Blade component
  - File: `resources/views/components/shared/dispute-card.blade.php`
  - Component per design.md §6.1:
    - Renders dispute status at a glance: type, status pill, reason (truncated), resolution info if resolved.
    - Accepts `$dispute` prop.
  - Verify: component renders without error in a test view.

- [ ] **E26.T4** Update `MessageStream` Livewire component to detect and render order/dispute cards
  - File: `app/Livewire/Shared/ChatInbox.php` (or the component handling message rendering — verify actual component name)
  - Logic per design.md §6.1 (mirrors existing RFQ card pattern):
    - Detect messages with body prefix `order:{id}` → render `<x-shared.order-card :order="$order" />`.
    - Detect messages with body prefix `dispute:{id}` → render `<x-shared.dispute-card :dispute="$dispute" />`.
    - Load referenced order/dispute via regex extraction of ID from message body.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=MessageStream
    ```

- [ ] **E26.T5** Post system messages on key order/dispute status changes
  - Files: modify existing event listeners or create new ones:
    - `app/Listeners/PostOrderStatusMessage.php` — triggered by `OrderPaid`, `OrderShipped`, `OrderDelivered`, `OrderCompleted`, `OrderCancelled`. Posts system message with `order:{order_id}` body prefix in the order's thread.
    - `app/Listeners/PostDisputeStatusMessage.php` — triggered by `DisputeOpened`, `DisputeResolved`, `DisputeEscalated`. Posts system message with `dispute:{dispute_id}` body prefix.
  - Register listeners.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter="PostOrderStatusMessage|PostDisputeStatusMessage"
    ```

- [ ] **E26.T6** Feature tests for order thread
  - File: `tests/Feature/OrderThreadTest.php`
  - Test cases:
    - Submitting an order creates a conversation thread with `order_id` set.
    - System message with `order:{id}` prefix is posted on submission.
    - Status change events post system messages in the order thread.
    - Order card renders correctly inline (component test or Livewire test).
    - Dispute card renders correctly inline.
    - Reuses existing conversation (doesn't create duplicate).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=OrderThreadTest
    ```

---

## Epic 27 — Notifications

**Goal:** Email + broadcast notifications for all order lifecycle events.
**Depends on:** E26

- [ ] **E27.T1** Create order notifications
  - Files:
    - `app/Notifications/OrderPendingPaymentNotification.php` — sent to buyer on submission.
    - `app/Notifications/OrderPaidNotification.php` — sent to seller on payment.
    - `app/Notifications/OrderShippedNotification.php` — sent to buyer on ship.
    - `app/Notifications/OrderDeliveredNotification.php` — sent to buyer on deliver.
    - `app/Notifications/OrderCompletedNotification.php` — sent to seller on completion.
    - `app/Notifications/OrderCancelledNotification.php` — sent to counterpart on cancel.
  - Each notification: `via(['mail', 'database', 'broadcast'])`. Mail template with order details. Broadcast on `user.{userId}` channel (per Phase 1/2 convention).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=OrderNotification
    ```

- [ ] **E27.T2** Create dispute notifications
  - Files:
    - `app/Notifications/DisputeOpenedNotification.php` — sent to seller.
    - `app/Notifications/DisputeResolvedNotification.php` — sent to both parties.
    - `app/Notifications/DisputeEscalatedNotification.php` — sent to admin.
  - Each notification: `via(['mail', 'database', 'broadcast'])`.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=DisputeNotification
    ```

- [ ] **E27.T3** Create event listeners that dispatch notifications
  - Files:
    - `app/Listeners/NotifyBuyerOrderPending.php` — on `OrderSubmittedForPayment`.
    - `app/Listeners/NotifySellerOrderPaid.php` — on `OrderPaid`.
    - `app/Listeners/NotifyBuyerOrderShipped.php` — on `OrderShipped`.
    - `app/Listeners/NotifyBuyerOrderDelivered.php` — on `OrderDelivered`.
    - `app/Listeners/NotifySellerOrderCompleted.php` — on `OrderCompleted`.
    - `app/Listeners/NotifyCounterpartOrderCancelled.php` — on `OrderCancelled`.
    - `app/Listeners/NotifySellerDisputeOpened.php` — on `DisputeOpened`.
    - `app/Listeners/NotifyPartiesDisputeResolved.php` — on `DisputeResolved`.
    - `app/Listeners/NotifyAdminDisputeEscalated.php` — on `DisputeEscalated`.
  - Register in `EventServiceProvider` or via event discovery.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter="NotifyBuyer|NotifySeller|NotifyAdmin|NotifyCounterpart|NotifyParties"
    ```

- [ ] **E27.T4** Feature tests for notifications
  - File: `tests/Feature/OrderNotificationsTest.php`
  - Test cases:
    - Order submission sends notification to buyer.
    - Payment success sends notification to seller.
    - Fulfilment shipped/delivered sends notification to buyer.
    - Order completion sends notification to seller.
    - Order cancellation sends notification to counterpart.
    - Dispute opened sends notification to seller.
    - Dispute resolved sends notification to both parties.
    - Dispute escalated sends notification to admin.
  - Use `Notification::fake()` and assert correct notification classes + recipients.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=OrderNotificationsTest
    ```

---

## Epic 28 — Seller Hub (Dashboard, Customers, Reports, Leaderboards)

**Goal:** Seller Hub dashboard to-do widget, customers list, reports, and leaderboards.
**Depends on:** E24 (needs order/fulfilment data to query against)

- [ ] **E28.T1** Create `SellerHubPolicy` gate
  - File: `app/Policies/SellerHubPolicy.php` (or register as a Gate in `AppServiceProvider`)
  - Gate per design.md §9.1: `accessHub` — user owns an active shop (shop exists, status = 'active', not soft-deleted).
  - Apply as middleware to all `/hub/*` routes and Seller Hub Livewire components.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=SellerHubPolicyTest
    ```

- [ ] **E28.T2** Create `SellerHubController` API endpoints
  - File: `app/Http/Controllers/Api/V1/SellerHubController.php`
  - Endpoints per design.md §4.6:
    - `GET /api/v1/hub/dashboard` — returns to-do widget counts: new_rfqs, orders_in_progress, unfulfilled_orders, draft_orders, open_disputes, pending_quotations.
    - `GET /api/v1/hub/customers` — returns paginated customer list (buyers with orders/awarded quotations for this shop). Each row: user id, name, business_name, order_count, last_order_date.
    - `GET /api/v1/hub/reports?window=N&from=YYYY-MM-DD` — sales/fulfilment metrics per design.md §10.1.
    - `GET /api/v1/hub/leaderboards?rank_by=revenue&window=N&type=products&limit=10` — leaderboard data per design.md §10.2.
  - Rate limiting: 60 report/leaderboard queries per minute per user.
  - Register routes in `routes/api.php`.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/hub
    ```
    Shows dashboard, customers, reports, leaderboards.

- [ ] **E28.T3** Create `Dashboard` Livewire component (company-hub/hub/dashboard)
  - Files:
    - `app/Livewire/CompanyHub/Hub/Dashboard.php`
    - `resources/views/livewire/company-hub/hub/dashboard.blade.php`
  - Component per design.md §5.2:
    - Six action-count cards: New RFQs, Orders In Progress, Unfulfilled Orders, Draft Orders, Open Disputes, Pending Quotations.
    - Each card deep-links to the relevant filtered queue page.
    - Uses design tokens + card layout conventions.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter="CompanyHub\\\\Hub\\\\Dashboard"
    ```

- [ ] **E28.T4** Create `CustomersList` Livewire component (company-hub/hub/customers-list)
  - Files:
    - `app/Livewire/CompanyHub/Hub/CustomersList.php`
    - `resources/views/livewire/company-hub/hub/customers-list.blade.php`
  - Component per design.md §5.2:
    - Paginated list of buyers who have orders or awarded quotations with this shop.
    - Each row: buyer name, business name, order count, last order date, "New Order" button (navigates to OrderBuilder in standalone mode).
    - Search/filter by name.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=CustomersList
    ```

- [ ] **E28.T5** Create `Reports` Livewire component (company-hub/hub/reports)
  - Files:
    - `app/Livewire/CompanyHub/Hub/Reports.php`
    - `resources/views/livewire/company-hub/hub/reports.blade.php`
  - Component per design.md §5.2:
    - Date-range/window selector (last 7 / 30 / 90 days, or custom date range).
    - Metrics grid: new customers, returning customers, orders placed, items purchased, fulfilled quantity, gross sales, total refunds, platform commission, net sales.
    - Queries scoped to seller's shop, only completed/refunded orders within the window.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=Reports
    ```

- [ ] **E28.T6** Create `Leaderboards` Livewire component (company-hub/hub/leaderboards)
  - Files:
    - `app/Livewire/CompanyHub/Hub/Leaderboards.php`
    - `resources/views/livewire/company-hub/hub/leaderboards.blade.php`
  - Component per design.md §5.2:
    - Three tabs: Top Products / Top Customers / Top Categories.
    - Rank-by selector: revenue / order count / quantity sold.
    - Time window selector: all-time / last 30 days / last 90 days / custom.
    - Table with rank, name, revenue, order count, quantity columns.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=Leaderboards
    ```

- [ ] **E28.T7** Register Seller Hub web routes
  - File: `routes/web.php` (modify)
  - Routes under Company Hub area:
    - `/hub/dashboard` → `Dashboard` Livewire page.
    - `/hub/customers` → `CustomersList` Livewire page.
    - `/hub/reports` → `Reports` Livewire page.
    - `/hub/leaderboards` → `Leaderboards` Livewire page.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=hub/dashboard
    vendor/bin/sail artisan route:list --path=hub/customers
    vendor/bin/sail artisan route:list --path=hub/reports
    vendor/bin/sail artisan route:list --path=hub/leaderboards
    ```

- [ ] **E28.T8** Feature tests for Seller Hub
  - File: `tests/Feature/SellerHubTest.php`
  - Test cases:
    - Dashboard returns correct counts for RFQs, orders, disputes.
    - Customers list returns only buyers who have transacted with this shop.
    - Reports: gross sales = sum of order totals for completed/refunded orders in window.
    - Reports: net sales = gross - refunds - commission.
    - Reports: new vs returning customers counted correctly.
    - Leaderboards: top products ranked by revenue correctly.
    - Leaderboards: time window filter works.
    - Non-seller cannot access hub endpoints (403).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=SellerHubTest
    ```

---

## Epic 29 — Integration, Polish & Final Verification

**Goal:** End-to-end flow verification, nav integration, seeder updates, final test suite pass.
**Depends on:** E21–E28

- [ ] **E29.T1** Update navigation components (buyer + seller + admin)
  - Files (modify existing):
    - Buyer nav: add "My Orders" link to `/account/orders`.
    - Seller nav: add "Orders" link to `/hub/orders`, "Dashboard" to `/hub/dashboard`, "Customers" to `/hub/customers`, "Reports" to `/hub/reports`.
    - Admin nav: add "Disputes" link to `/admin/disputes`.
  - Verify: navigation renders correctly in browser (links present and functional).

- [ ] **E29.T2** Add "Create Order" action to awarded quotation card
  - File: modify existing quotation detail/card components (seller-facing)
  - When quotation is `awarded`: show a "Create Order" button/link that navigates to `OrderBuilder` with `quotationId` param.
  - If order already exists for this quotation: show "View Order" link instead.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=QuotationOrderIntegration
    ```

- [ ] **E29.T3** Create database seeders for Phase 3 entities
  - File: `database/seeders/Phase3Seeder.php` (new)
  - Creates sample data for demo/testing:
    - A few orders in various states (draft, pending_payment, paid, completed, refunded, cancelled).
    - Fulfilments at different stages.
    - Payment attempts (succeeded and failed).
    - At least one active dispute and one resolved dispute.
    - Ledger entries matching the order states.
  - Register in `DatabaseSeeder.php`.
  - Verify:
    ```bash
    vendor/bin/sail artisan db:seed --class=Phase3Seeder
    ```

- [ ] **E29.T4** Full test suite pass
  - Run the complete test suite to ensure Phase 3 doesn't break existing functionality:
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact
    ```
    All tests pass (0 failures, 0 errors).

- [ ] **E29.T5** Run Pint code formatter on all modified files
  - Verify:
    ```bash
    vendor/bin/sail bin pint --dirty --format agent
    ```
    No formatting issues remaining.

---

## Escalation Protocol

If during any task you encounter:
- A gap in `design.md` or `requirements.md` (e.g., a field, relationship, or behavior is needed but not specified).
- A conflict between `design.md` and `requirements.md`.
- An ambiguity that would result in different implementations depending on interpretation.

**STOP** work on that task, document the gap/conflict/ambiguity clearly, and flag it for human review. Do not guess or invent behavior not specified in the design documents.

---

## Summary — Epic Dependencies

```
E21 (Schema & Data Layer)
 └─► E22 (Order Creation & Draft Editing)
      └─► E23 (Payment & DLT Gateway)
           └─► E24 (Fulfilment)
                └─► E25 (Disputes)
                     └─► E26 (Order Thread)
                          └─► E27 (Notifications)
E24 ─► E28 (Seller Hub) [can start after E24; doesn't need E25-E27]
E21–E28 ─► E29 (Integration & Polish)
```
