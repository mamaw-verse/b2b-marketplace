# tasks.md
## Execution Plan — Phase 4: Payouts, Reviews & Analytics

| | |
|---|---|
| **Consumes** | `requirements.md` |
| **Audience** | AI Coding Agent (autonomous/semi-autonomous execution) |
| **Execution mode** | Sequential by Milestone; tasks within a Milestone may parallelize only where marked `[parallel-safe]` |
| **Stack** | PHP/Laravel · Livewire · Tailwind · Redis · PostgreSQL · Typesense · Minio/S3 |
| **Payment gateway** | DLT Disbursement API (contract TBD — BLOCKED) |
| **Precondition** | Phase 3 complete. All Phase 3 tables (`orders`, `order_items`, `fulfilments`, `disputes`, `ledger_entries`, `payment_attempts`) exist. |

---

## How to use this document

- Each task has a **stable ID** (`M<milestone>.T<task>`), a **status checkbox**, explicit **file paths**, and **acceptance criteria**.
- The agent MUST complete tasks in ID order within a Milestone unless marked `[parallel-safe]`, and MUST NOT start a Milestone until all of its listed **Depends on** milestones are complete.
- After each task, run the command(s) in **Verify** before marking it `[x]`. If verification fails, fix and re-verify — do not proceed.
- Do not invent schema, routes, or fields not present in `requirements.md`. If a gap is found, stop and flag it rather than guessing.
- Phase 4 Milestones start at **M1** and use the prefix `M` to distinguish from Phase 3's Epics (`E21`–`E30`).
- Follow existing patterns: Actions for business logic, Policies for authorization, Form Requests for validation, Pest for testing.

---

## Summary Table

| Milestone | Description | Depends on | Status |
|---|---|---|---|
| M1 | Seller Payout Account Registration | Phase 3 complete | ✅ Complete |
| M2 | Payout Settings (admin config) | Phase 3 complete | ✅ Complete |
| M3 | Reviews & Ratings | Phase 3 complete | ✅ Complete |
| M4 | Payment History Views | M1, M2 | ✅ Complete |
| M5 | Sales Analytics Expansion | M3, M4 | ✅ Complete |
| M6 | Payout Lifecycle & Disbursement | M1, M2 — **BLOCKED on DLT Disbursement API** | ✅ Complete (mocked gateway) |
| M7 | Seller Profit Calculator | M2 | ✅ Complete |

> **Note**: M1, M2, and M3 have no inter-dependencies and can be worked in parallel. M4 requires M1/M2 tables to exist for earnings views. M5 reads data from M3 (reviews) and M4 (payouts). M6 is blocked on external DLT API documentation. M7 only needs `payout_settings` from M2.

---

## Milestone 1 — Seller Payout Account Registration

**Goal:** Sellers register their bank account for receiving payouts. Admins verify/reject accounts.
**Depends on:** Phase 3 complete

- [x] **M1.T1** Create PostgreSQL enum type migration for `payout_account_verification_status`
  - File: `database/migrations/xxxx_create_payout_account_verification_status_enum.php`
  - Create enum type: `payout_account_verification_status` with values `('pending','verified','rejected')`.
  - Command: `vendor/bin/sail artisan make:migration create_payout_account_verification_status_enum --no-interaction`
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    vendor/bin/sail artisan tinker --execute 'DB::select("SELECT typname FROM pg_type WHERE typname = '"'"'payout_account_verification_status'"'"'");'
    ```

- [x] **M1.T2** Create `seller_payout_accounts` migration
  - File: `database/migrations/xxxx_create_seller_payout_accounts_table.php`
  - Command: `vendor/bin/sail artisan make:migration create_seller_payout_accounts_table --no-interaction`
  - Schema per requirements.md §3.1: `id` BIGINT PK, `shop_id` BIGINT FK → shops (UNIQUE), `bank_name` VARCHAR(150), `bank_code` VARCHAR(30), `account_holder_name` VARCHAR(255), `account_number` VARCHAR(50) (encrypted at rest), `branch` VARCHAR(150) nullable, `verification_status` using enum type (default `'pending'`), `verified_at` TIMESTAMP nullable, `verified_by_id` BIGINT FK → users nullable, `rejection_reason` TEXT nullable, timestamps.
  - Unique constraint on `shop_id`.
  - Index: `idx_seller_payout_accounts_status` on `(verification_status)`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **M1.T3** Create `SellerPayoutAccount` model with relationships and factory
  - Files:
    - `app/Models/SellerPayoutAccount.php`
    - `database/factories/SellerPayoutAccountFactory.php`
  - Command: `vendor/bin/sail artisan make:model SellerPayoutAccount --factory --no-interaction`
  - Model: `belongsTo(Shop::class)`, `belongsTo(User::class, 'verified_by_id')`.
  - Casts: `account_number` as `encrypted`, `verification_status` as string (or native enum if created), `verified_at` as `datetime`.
  - Fillable: `shop_id`, `bank_name`, `bank_code`, `account_holder_name`, `account_number`, `branch`, `verification_status`, `verified_at`, `verified_by_id`, `rejection_reason`.
  - Add `hasOne(SellerPayoutAccount::class)` to `Shop` model.
  - Factory: generates valid bank details with default status `pending`.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'App\Models\SellerPayoutAccount::factory()->make();'
    ```

- [x] **M1.T4** Create `SellerPayoutAccountPolicy`
  - File: `app/Policies/SellerPayoutAccountPolicy.php`
  - Command: `vendor/bin/sail artisan make:policy SellerPayoutAccountPolicy --model=SellerPayoutAccount --no-interaction`
  - Rules:
    - `view`: user owns the shop OR user has role `admin`.
    - `create`/`update`: user owns the shop AND shop status is `active`.
    - `verify`/`reject`: user has role `admin`.
    - `viewAny` (admin list): user has role `admin`.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute '(new App\Policies\SellerPayoutAccountPolicy);'
    ```

- [x] **M1.T5** Create Form Requests for payout account
  - Files:
    - `app/Http/Requests/UpsertPayoutAccountRequest.php`
    - `app/Http/Requests/Admin/RejectPayoutAccountRequest.php`
  - Command: `vendor/bin/sail artisan make:request UpsertPayoutAccountRequest --no-interaction`
  - `UpsertPayoutAccountRequest`: validates `bank_name` (required, in curated list), `bank_code` (required, max:30), `account_holder_name` (required, max:255), `account_number` (required, max:50), `branch` (optional, max:150). Authorization: user has active shop.
  - `RejectPayoutAccountRequest`: validates `rejection_reason` (required, string, max:1000). Authorization: user is admin.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'new App\Http\Requests\UpsertPayoutAccountRequest;'
    ```

- [x] **M1.T6** Create `VerifyPayoutAccountAction` and `RejectPayoutAccountAction`
  - Files:
    - `app/Actions/VerifyPayoutAccountAction.php`
    - `app/Actions/RejectPayoutAccountAction.php`
  - `VerifyPayoutAccountAction`: sets `verification_status = 'verified'`, `verified_at = now()`, `verified_by_id = admin user id`. Wraps in DB transaction.
  - `RejectPayoutAccountAction`: sets `verification_status = 'rejected'`, `rejection_reason`. Wraps in DB transaction.
  - Business rule: updating account details resets status to `pending` (handled in controller/component on update).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=PayoutAccount
    ```

- [x] **M1.T7** Create API controller and resource for seller payout account
  - Files:
    - `app/Http/Controllers/Api/V1/HubPayoutAccountController.php`
    - `app/Http/Controllers/Api/V1/Admin/PayoutAccountController.php`
    - `app/Http/Resources/SellerPayoutAccountResource.php`
  - Seller endpoints:
    - `GET /api/v1/hub/payout-account` — show seller's account (or 404).
    - `PUT /api/v1/hub/payout-account` — create or update (resets status to `pending` on update).
  - Admin endpoints:
    - `GET /api/v1/admin/payout-accounts` — list all, filterable by `verification_status`.
    - `POST /api/v1/admin/payout-accounts/{sellerPayoutAccount}/verify` — verify.
    - `POST /api/v1/admin/payout-accounts/{sellerPayoutAccount}/reject` — reject with reason.
  - Register routes in `routes/api.php`.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/hub/payout-account
    vendor/bin/sail artisan route:list --path=api/v1/admin/payout-accounts
    ```

- [x] **M1.T8** Create Livewire component: Seller Payout Account Form
  - Files:
    - `app/Livewire/CompanyHub/PayoutAccountForm.php`
    - `resources/views/livewire/company-hub/payout-account-form.blade.php`
  - Full-page Livewire component at route `hub/payout-account`.
  - Features: display current account (if exists), form to create/update bank details, show verification status badge, show rejection reason if rejected.
  - On save: resets `verification_status` to `pending` if account already exists and is being modified.
  - Bank name select from curated Philippine bank list.
  - Add route: `Route::livewire('payout-account', 'company-hub.payout-account-form')->name('payout-account');` in hub group.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=hub/payout-account
    ```

- [x] **M1.T9** Create Livewire component: Admin Payout Account Queue
  - Files:
    - `app/Livewire/Admin/PayoutAccounts/PayoutAccountQueue.php`
    - `resources/views/livewire/admin/payout-accounts/payout-account-queue.blade.php`
  - Full-page Livewire component at route `admin/payout-accounts`.
  - Features: list all accounts with status filter, search by shop name, verify/reject actions (modal for rejection reason).
  - Add route: `Route::livewire('payout-accounts', 'admin.payout-accounts.payout-account-queue')->name('payout-accounts.index');` in admin group.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=admin/payout-accounts
    ```

- [x] **M1.T10** Write feature tests for Seller Payout Account
  - File: `tests/Feature/SellerPayoutAccountTest.php`
  - Command: `vendor/bin/sail artisan make:test SellerPayoutAccountTest --pest --no-interaction`
  - Test cases:
    - Seller with active shop can create payout account.
    - Seller without active shop cannot create payout account (403).
    - Updating account resets verification_status to `pending`.
    - Admin can verify an account (status transitions to `verified`, `verified_at` set).
    - Admin can reject an account (status transitions to `rejected`, reason stored).
    - Non-admin cannot verify/reject (403).
    - `account_number` is encrypted at rest (check DB raw value differs from input).
    - Unique constraint: cannot have two accounts for same shop.
    - Buyer cannot access payout account endpoints (403).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=SellerPayoutAccountTest
    ```

---

## Milestone 2 — Payout Settings (Admin Config)

**Goal:** Admin-configurable payout release rules and fee rates. Seeded with defaults.
**Depends on:** Phase 3 complete (no dependency on M1)

- [x] **M2.T1** Create `payout_settings` migration
  - File: `database/migrations/xxxx_create_payout_settings_table.php`
  - Command: `vendor/bin/sail artisan make:migration create_payout_settings_table --no-interaction`
  - Schema per requirements.md §3.3: `id` BIGINT PK, `key` VARCHAR(50) UNIQUE, `value` VARCHAR(100), `updated_by_id` BIGINT FK → users nullable, `updated_at` TIMESTAMP. No `created_at` needed (seed once).
  - Note: Include `created_at` for Eloquent compatibility but it's only set on seed.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **M2.T2** Create `PayoutSetting` model
  - File: `app/Models/PayoutSetting.php`
  - Command: `vendor/bin/sail artisan make:model PayoutSetting --no-interaction`
  - Fillable: `key`, `value`, `updated_by_id`.
  - `belongsTo(User::class, 'updated_by_id')`.
  - Static helper methods:
    - `PayoutSetting::get(string $key): string` — returns value for key.
    - `PayoutSetting::getDisputeHoldDays(): int`
    - `PayoutSetting::getMinimumPayoutThreshold(): float`
    - `PayoutSetting::getTransactionFeeAmount(): float`
    - `PayoutSetting::getCommissionFeeRate(): float`
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'new App\Models\PayoutSetting;'
    ```

- [x] **M2.T3** Create seeder for default payout settings
  - File: `database/seeders/PayoutSettingsSeeder.php`
  - Command: `vendor/bin/sail artisan make:seeder PayoutSettingsSeeder --no-interaction`
  - Seeds 4 rows with defaults from requirements.md §3.3:
    - `dispute_hold_days` → `7`
    - `minimum_payout_threshold` → `500.00`
    - `transaction_fee_amount` → `15.00`
    - `commission_fee_rate` → `0.03`
  - Use `updateOrCreate` to be idempotent.
  - Run seeder in the payout_settings migration (or call from `DatabaseSeeder`).
  - Verify:
    ```bash
    vendor/bin/sail artisan db:seed --class=PayoutSettingsSeeder
    vendor/bin/sail artisan tinker --execute 'App\Models\PayoutSetting::pluck("value", "key")->toArray();'
    ```

- [x] **M2.T4** Create Form Request and API controller for payout settings
  - Files:
    - `app/Http/Requests/Admin/UpdatePayoutSettingsRequest.php`
    - `app/Http/Controllers/Api/V1/Admin/PayoutSettingsController.php`
  - `UpdatePayoutSettingsRequest`: validates each setting key individually (numeric ranges, positive values). Authorization: user is admin.
  - Endpoints:
    - `GET /api/v1/admin/payout-settings` — returns all settings as key-value object.
    - `PUT /api/v1/admin/payout-settings` — bulk update one or more settings (only updates provided keys).
  - Register routes in `routes/api.php` under admin group.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/admin/payout-settings
    ```

- [x] **M2.T5** Create Livewire component: Admin Payout Settings
  - Files:
    - `app/Livewire/Admin/PayoutSettings.php`
    - `resources/views/livewire/admin/payout-settings.blade.php`
  - Full-page Livewire component at route `admin/payout-settings`.
  - Features: display all 4 settings in editable form, save button, show last updated by/at, validation feedback.
  - Add route: `Route::livewire('payout-settings', 'admin.payout-settings')->name('payout-settings');` in admin group.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=admin/payout-settings
    ```

- [x] **M2.T6** Create seller-facing fee rates endpoint
  - File: `app/Http/Controllers/Api/V1/HubFeeRatesController.php`
  - Endpoint: `GET /api/v1/hub/fee-rates` — returns `transaction_fee_amount` and `commission_fee_rate` only (read-only subset for sellers).
  - Authorization: authenticated seller with active shop.
  - Register route in `routes/api.php` under hub group.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/hub/fee-rates
    ```

- [x] **M2.T7** Write feature tests for Payout Settings
  - File: `tests/Feature/PayoutSettingsTest.php`
  - Command: `vendor/bin/sail artisan make:test PayoutSettingsTest --pest --no-interaction`
  - Test cases:
    - Admin can view all settings.
    - Admin can update individual settings.
    - Admin can bulk-update multiple settings.
    - Non-admin cannot access settings endpoints (403).
    - Invalid values are rejected (negative threshold, rate > 1, non-numeric).
    - Seller can access fee-rates endpoint and sees only `transaction_fee_amount` and `commission_fee_rate`.
    - Seeder produces correct default values.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=PayoutSettingsTest
    ```

---

## Milestone 3 — Reviews & Ratings

**Goal:** Buyers can rate/review products and shops after completed orders. Sellers can respond. Admins moderate. Ratings appear on storefront and in search.
**Depends on:** Phase 3 complete (no dependency on M1 or M2)

- [x] **M3.T1** Create PostgreSQL enum types for reviews
  - File: `database/migrations/xxxx_create_review_enum_types.php`
  - Command: `vendor/bin/sail artisan make:migration create_review_enum_types --no-interaction`
  - Create enum: `review_report_status` with values `('pending','dismissed','actioned')`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **M3.T2** Create `reviews` migration
  - File: `database/migrations/xxxx_create_reviews_table.php`
  - Command: `vendor/bin/sail artisan make:migration create_reviews_table --no-interaction`
  - Schema per requirements.md §3.5: `id` BIGINT PK, `order_id` FK → orders (RESTRICT), `reviewer_user_id` FK → users (RESTRICT), `reviewable_type` VARCHAR(50), `reviewable_id` BIGINT, `rating` SMALLINT (1–5), `title` VARCHAR(255) nullable, `body` TEXT nullable, `is_hidden` BOOLEAN DEFAULT FALSE, timestamps.
  - Unique constraint: `(order_id, reviewer_user_id, reviewable_type, reviewable_id)`.
  - Indexes: `idx_reviews_reviewable` on `(reviewable_type, reviewable_id)`, `idx_reviews_order`, `idx_reviews_reviewer`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **M3.T3** Create `review_responses` migration `[parallel-safe with M3.T4]`
  - File: `database/migrations/xxxx_create_review_responses_table.php`
  - Command: `vendor/bin/sail artisan make:migration create_review_responses_table --no-interaction`
  - Schema: `id` BIGINT PK, `review_id` FK → reviews (CASCADE) UNIQUE, `responder_user_id` FK → users (RESTRICT), `body` TEXT, timestamps.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **M3.T4** Create `review_reports` migration `[parallel-safe with M3.T3]`
  - File: `database/migrations/xxxx_create_review_reports_table.php`
  - Command: `vendor/bin/sail artisan make:migration create_review_reports_table --no-interaction`
  - Schema: `id` BIGINT PK, `review_id` FK → reviews (CASCADE), `reporter_user_id` FK → users (RESTRICT), `reason` TEXT, `status` using `review_report_status` enum (default `'pending'`), `resolved_by_id` FK → users nullable, `resolved_at` TIMESTAMP nullable, timestamps.
  - Index: `idx_review_reports_status` on `(status)`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **M3.T5** Add `average_rating` and `total_reviews` columns to `shops` and `products`
  - File: `database/migrations/xxxx_add_rating_columns_to_shops_and_products.php`
  - Command: `vendor/bin/sail artisan make:migration add_rating_columns_to_shops_and_products --no-interaction`
  - Add to `shops`: `average_rating NUMERIC(3,2) NULL`, `total_reviews INTEGER DEFAULT 0`.
  - Add to `products`: `average_rating NUMERIC(3,2) NULL`, `total_reviews INTEGER DEFAULT 0`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    vendor/bin/sail artisan tinker --execute 'Schema::hasColumn("shops", "average_rating") && Schema::hasColumn("products", "average_rating");'
    ```

- [x] **M3.T6** Create `Review`, `ReviewResponse`, `ReviewReport` models with factories
  - Files:
    - `app/Models/Review.php`
    - `app/Models/ReviewResponse.php`
    - `app/Models/ReviewReport.php`
    - `database/factories/ReviewFactory.php`
    - `database/factories/ReviewResponseFactory.php`
    - `database/factories/ReviewReportFactory.php`
  - Commands:
    - `vendor/bin/sail artisan make:model Review --factory --no-interaction`
    - `vendor/bin/sail artisan make:model ReviewResponse --factory --no-interaction`
    - `vendor/bin/sail artisan make:model ReviewReport --factory --no-interaction`
  - `Review`: `belongsTo(Order)`, `belongsTo(User, 'reviewer_user_id')`, `morphTo('reviewable')` (Product or Shop), `hasOne(ReviewResponse)`, `hasMany(ReviewReport)`.
  - Casts: `rating` as integer, `is_hidden` as boolean.
  - `ReviewResponse`: `belongsTo(Review)`, `belongsTo(User, 'responder_user_id')`.
  - `ReviewReport`: `belongsTo(Review)`, `belongsTo(User, 'reporter_user_id')`, `belongsTo(User, 'resolved_by_id')`. Cast `status` as string, `resolved_at` as datetime.
  - Add inverse relationships:
    - `Product`: `morphMany(Review::class, 'reviewable')`.
    - `Shop`: `morphMany(Review::class, 'reviewable')`.
    - `Order`: `hasMany(Review::class)`.
    - `User`: `hasMany(Review::class, 'reviewer_user_id')`.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'App\Models\Review::factory()->make(); App\Models\ReviewResponse::factory()->make(); App\Models\ReviewReport::factory()->make();'
    ```

- [x] **M3.T7** Create `ReviewPolicy`
  - File: `app/Policies/ReviewPolicy.php`
  - Command: `vendor/bin/sail artisan make:policy ReviewPolicy --model=Review --no-interaction`
  - Rules:
    - `create`: user is the order's buyer AND order status is `completed` AND order has no active dispute with resolution `refund_full`.
    - `update`: user is the reviewer (`review->reviewer_user_id === auth()->id()`).
    - `respond`: user is the shop owner of the reviewed entity.
    - `report`: any authenticated user (not the reviewer themselves).
    - `hide`/`dismiss`: user is admin.
    - `viewAny` (public): always true (non-hidden reviews).
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute '(new App\Policies\ReviewPolicy);'
    ```

- [x] **M3.T8** Create `RecalculateRatingsJob`
  - File: `app/Jobs/RecalculateRatingsJob.php`
  - Command: `vendor/bin/sail artisan make:job RecalculateRatingsJob --no-interaction`
  - Queued job that accepts a `reviewable_type` and `reviewable_id`.
  - Computes AVG(rating) and COUNT(*) from non-hidden reviews for the entity.
  - Updates `average_rating` and `total_reviews` on the entity (Shop or Product).
  - Dispatched on: review create, review update (rating changed), review hidden/unhidden.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'new App\Jobs\RecalculateRatingsJob("shop", 1);'
    ```

- [x] **M3.T9** Create Form Requests for reviews
  - Files:
    - `app/Http/Requests/StoreReviewRequest.php`
    - `app/Http/Requests/UpdateReviewRequest.php`
    - `app/Http/Requests/StoreReviewResponseRequest.php`
    - `app/Http/Requests/ReportReviewRequest.php`
  - Commands: `vendor/bin/sail artisan make:request StoreReviewRequest --no-interaction` (etc.)
  - `StoreReviewRequest`: validates `reviewable_type` (in: product,shop), `reviewable_id` (exists), `rating` (required, integer, 1–5), `title` (optional, max:255), `body` (optional, max:5000).
  - `UpdateReviewRequest`: validates `rating` (required, 1–5), `title` (optional), `body` (optional).
  - `StoreReviewResponseRequest`: validates `body` (required, max:5000).
  - `ReportReviewRequest`: validates `reason` (required, max:1000).
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'new App\Http\Requests\StoreReviewRequest;'
    ```

- [x] **M3.T10** Create `CreateReviewAction`
  - File: `app/Actions/CreateReviewAction.php`
  - Logic:
    - Validates eligibility: order `completed`, no existing review for this (order, user, reviewable_type, reviewable_id) combo, order not fully refunded.
    - Creates Review record.
    - Dispatches `RecalculateRatingsJob`.
    - Dispatches `IndexShopJob` (if reviewable is shop) to update Typesense.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=CreateReviewAction
    ```

- [x] **M3.T11** Create API controllers for reviews
  - Files:
    - `app/Http/Controllers/Api/V1/ReviewController.php`
    - `app/Http/Controllers/Api/V1/ReviewResponseController.php`
    - `app/Http/Controllers/Api/V1/Admin/ReviewReportController.php`
    - `app/Http/Resources/ReviewResource.php`
    - `app/Http/Resources/ReviewResponseResource.php`
  - Endpoints per requirements.md §3.5:
    - `POST /api/v1/orders/{order}/reviews` — create review.
    - `PUT /api/v1/reviews/{review}` — edit review.
    - `GET /api/v1/products/{product}/reviews` — list product reviews (public, paginated).
    - `GET /api/v1/companies/{shop}/reviews` — list shop reviews (public, paginated).
    - `POST /api/v1/reviews/{review}/respond` — seller posts response.
    - `PUT /api/v1/review-responses/{reviewResponse}` — seller edits response.
    - `POST /api/v1/reviews/{review}/report` — report a review.
    - `GET /api/v1/admin/review-reports` — admin list pending reports.
    - `POST /api/v1/admin/review-reports/{reviewReport}/dismiss` — admin dismisses.
    - `POST /api/v1/admin/review-reports/{reviewReport}/action` — admin hides the review.
  - Rate limiting: 10 review creations per hour per user.
  - Register routes in `routes/api.php`.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/orders
    vendor/bin/sail artisan route:list --path=api/v1/reviews
    vendor/bin/sail artisan route:list --path=api/v1/admin/review-reports
    ```

- [x] **M3.T12** Update Typesense index configuration for ratings
  - Files:
    - Update Shop searchable array (`app/Models/Shop.php` or Scout config) to include `average_rating` (float) and `total_reviews` (int32).
    - Update Product searchable array to include `average_rating` (float) and `total_reviews` (int32).
    - Update Typesense collection schema (if managed via config/code) to add these fields.
  - Enable sorting by `average_rating:desc` on companies collection.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'App\Models\Shop::first()?->toSearchableArray();'
    ```

- [x] **M3.T13** Create Livewire component: Review Form (buyer submits review)
  - Files:
    - `app/Livewire/Account/Orders/ReviewForm.php`
    - `resources/views/livewire/account/orders/review-form.blade.php`
  - Inline or modal component on buyer's order detail page.
  - Features: star rating selector (1–5), optional title, optional body textarea, submit for product review and/or shop review.
  - Shows existing reviews for the order if already submitted (edit mode).
  - Dispatches `RecalculateRatingsJob` + index job after save.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute '(new App\Livewire\Account\Orders\ReviewForm);'
    ```

- [x] **M3.T14** Create Livewire component: Review List (public display)
  - Files:
    - `app/Livewire/Storefront/ReviewList.php`
    - `resources/views/livewire/storefront/review-list.blade.php`
  - Reusable component embedded on product detail and company profile pages.
  - Features: displays non-hidden reviews with rating stars, title, body, buyer name, date, seller response (if any). Paginated.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute '(new App\Livewire\Storefront\ReviewList);'
    ```

- [x] **M3.T15** Update storefront views to display ratings
  - Files (modify existing):
    - `resources/views/livewire/storefront/product-detail.blade.php` — add average rating stars + total reviews count, embed ReviewList component.
    - `resources/views/livewire/storefront/company-profile.blade.php` — add average rating stars + total reviews, embed ReviewList component.
    - `resources/views/livewire/storefront/company-directory.blade.php` — add rating display on company cards, add sort-by-rating option.
    - `resources/views/livewire/storefront/product-directory.blade.php` — add rating display on product cards.
  - Update corresponding Livewire PHP classes to load rating data and support sort-by-rating.
  - Verify: Visual inspection + existing tests still pass.
    ```bash
    vendor/bin/sail artisan test --compact --filter=Storefront
    ```

- [x] **M3.T16** Create Livewire component: Admin Review Reports Queue
  - Files:
    - `app/Livewire/Admin/Reviews/ReviewReportQueue.php`
    - `resources/views/livewire/admin/reviews/review-report-queue.blade.php`
  - Full-page Livewire component at route `admin/review-reports`.
  - Features: list pending reports, view review content, dismiss or action (hide review) buttons.
  - Add route: `Route::livewire('review-reports', 'admin.reviews.review-report-queue')->name('review-reports.index');` in admin group.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=admin/review-reports
    ```

- [x] **M3.T17** Create Livewire component: Seller Review Response
  - Files:
    - `app/Livewire/CompanyHub/Reviews/ReviewResponseForm.php`
    - `resources/views/livewire/company-hub/reviews/review-response-form.blade.php`
  - Inline component or modal for seller to respond to reviews on their products/shop.
  - Accessible from a seller's "Reviews" section in the hub.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute '(new App\Livewire\CompanyHub\Reviews\ReviewResponseForm);'
    ```

- [x] **M3.T18** Create Livewire component: Seller Reviews List
  - Files:
    - `app/Livewire/CompanyHub/Reviews/ReviewList.php`
    - `resources/views/livewire/company-hub/reviews/review-list.blade.php`
  - Full-page Livewire component at route `hub/reviews`.
  - Features: list all reviews for seller's products and shop, filter by rating, respond button per review.
  - Add route: `Route::livewire('reviews', 'company-hub.reviews.review-list')->name('reviews.index');` in hub group.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=hub/reviews
    ```

- [x] **M3.T19** Write feature tests for Reviews & Ratings
  - File: `tests/Feature/ReviewTest.php`
  - Command: `vendor/bin/sail artisan make:test ReviewTest --pest --no-interaction`
  - Test cases:
    - Buyer can create product review for completed order.
    - Buyer can create shop review for completed order.
    - Buyer cannot review non-completed order (403).
    - Buyer cannot review same entity+order twice (409 or validation error).
    - Buyer can edit their own review.
    - Buyer cannot edit another user's review (403).
    - Seller can respond to a review on their product/shop.
    - Seller cannot respond twice to the same review (unique constraint).
    - Seller can edit their response.
    - User can report a review (not their own).
    - Admin can dismiss a report.
    - Admin can action a report (review becomes hidden).
    - Hidden reviews excluded from public listing.
    - `RecalculateRatingsJob` correctly updates `average_rating` and `total_reviews`.
    - Rating fields appear in shop's `toSearchableArray()`.
    - Rate limit: 11th review creation in an hour is rejected (429).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=ReviewTest
    ```

---

## Milestone 4 — Payment History Views

**Goal:** Dedicated transaction-history views for buyers, sellers, and admins.
**Depends on:** M1 (seller payout account data referenced), M2 (payout settings for earnings computation)

- [x] **M4.T1** Create API controller for Buyer Payment History
  - Files:
    - `app/Http/Controllers/Api/V1/TransactionController.php`
    - `app/Http/Resources/TransactionResource.php`
  - Endpoint: `GET /api/v1/transactions` — buyer's payment history.
  - Aggregates `payment_attempts` (succeeded) and `ledger_entries` (type = `refund`) across buyer's orders.
  - Filterable by: date range (`from`, `to`), status (`paid`, `refunded`), amount range (`min_amount`, `max_amount`).
  - Cursor-paginated.
  - Authorization: authenticated buyer.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/transactions
    ```

- [x] **M4.T2** Create API controller for Seller Earnings
  - Files:
    - `app/Http/Controllers/Api/V1/HubEarningsController.php`
    - `app/Http/Resources/EarningsResource.php`
  - Endpoint: `GET /api/v1/hub/earnings` — seller earnings summary + transaction list.
  - Summary figures (computed from `payout_items`/`payouts`, NOT from `ledger_entries.type = 'payout'`):
    - `total_earned`: sum of `payout_items.net_amount` for completed payouts.
    - `total_released`: sum of `payouts.net_amount` WHERE status = `completed`.
    - `pending_balance`: eligible escrowed amounts not yet in any `payout_items` row.
    - `total_fees_paid`: sum of transaction + commission fees from completed payouts.
  - Transaction list: `ledger_entries` + `payout_items` for the seller's shop, cursor-paginated.
  - Filterable by date range and order.
  - Authorization: authenticated seller with active shop.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/hub/earnings
    ```

- [x] **M4.T3** Create API controller for Admin Financial Ledger
  - Files:
    - `app/Http/Controllers/Api/V1/Admin/LedgerController.php`
    - `app/Http/Resources/LedgerEntryResource.php`
  - Endpoint: `GET /api/v1/admin/ledger` — platform-wide financial ledger.
  - Aggregates `ledger_entries` + `payouts` + `payout_disbursements`.
  - Filterable by: type (`escrow_hold`, `payout`, `refund`, `disbursement`), date range, seller/shop, status.
  - Cursor-paginated.
  - Authorization: admin role.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/admin/ledger
    ```

- [x] **M4.T4** Create Livewire component: Buyer Payment History
  - Files:
    - `app/Livewire/Account/PaymentHistory.php`
    - `resources/views/livewire/account/payment-history.blade.php`
  - Full-page Livewire component at route `account/payments`.
  - Features: table of payments and refunds, date range filter, status filter, amount displayed with PHP currency.
  - Add route: `Route::livewire('payments', 'account.payment-history')->name('payments');` in account group.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=account/payments
    ```

- [x] **M4.T5** Create Livewire component: Seller Earnings & Payouts
  - Files:
    - `app/Livewire/CompanyHub/Earnings/EarningsOverview.php`
    - `resources/views/livewire/company-hub/earnings/earnings-overview.blade.php`
  - Full-page Livewire component at route `hub/earnings`.
  - Features: summary cards (total earned, pending balance, total released, total fees), transaction list table with date filter, links to individual payout details.
  - Add route: `Route::livewire('earnings', 'company-hub.earnings.earnings-overview')->name('earnings');` in hub group.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=hub/earnings
    ```

- [x] **M4.T6** Create Livewire component: Admin Financial Ledger
  - Files:
    - `app/Livewire/Admin/Finance/FinancialLedger.php`
    - `resources/views/livewire/admin/finance/financial-ledger.blade.php`
  - Full-page Livewire component at route `admin/ledger`.
  - Features: platform-wide transaction table with type/date/seller/status filters, summary totals at top.
  - Add route: `Route::livewire('ledger', 'admin.finance.financial-ledger')->name('ledger');` in admin group.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=admin/ledger
    ```

- [x] **M4.T7** Write feature tests for Payment History
  - File: `tests/Feature/PaymentHistoryTest.php`
  - Command: `vendor/bin/sail artisan make:test PaymentHistoryTest --pest --no-interaction`
  - Test cases:
    - Buyer sees their own payment attempts (succeeded) in transaction list.
    - Buyer sees refunds in transaction list.
    - Buyer cannot see other users' transactions.
    - Date range filter works correctly.
    - Seller earnings summary computes correctly from `payout_items`/`payouts`.
    - Seller earnings does NOT sum `ledger_entries.type = 'payout'` for `total_released`.
    - Admin ledger shows platform-wide entries.
    - Admin can filter by seller/type/date.
    - Non-admin cannot access admin ledger (403).
    - Cursor pagination works correctly.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=PaymentHistoryTest
    ```

---

## Milestone 5 — Sales Analytics Expansion

**Goal:** Add payout totals and review averages to seller reports. New admin analytics dashboard with platform-wide metrics.
**Depends on:** M3 (reviews data), M4 (payout/earnings data)

- [x] **M5.T1** Extend existing Seller Reports with payout & review metrics
  - Files (modify existing):
    - `app/Livewire/CompanyHub/Hub/Reports.php` — add payout metrics (`total_earned`, `pending_balance`, `total_released`) and review metrics (`average_rating`, `total_reviews`) to the report data.
    - `resources/views/livewire/company-hub/hub/reports.blade.php` — add new metric cards/sections.
  - Metrics computed per requirements.md §3.6:
    - `total_earned`: sum of `payout_items.net_amount` for completed payouts in selected window.
    - `pending_balance`: eligible but not-yet-released escrowed amounts.
    - `total_released`: sum of `payouts.net_amount` WHERE status = `completed` in window.
    - `average_rating`: from denormalized `shops.average_rating`.
    - `total_reviews`: from denormalized `shops.total_reviews`.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=Reports
    ```

- [x] **M5.T2** Create API endpoint for Admin Analytics
  - File: `app/Http/Controllers/Api/V1/Admin/AnalyticsController.php`
  - Endpoint: `GET /api/v1/admin/analytics` — platform-wide metrics.
  - Metrics per requirements.md §3.6:
    - `gmv`: sum of `orders.order_total` WHERE status IN (`completed`, `refunded`) in window.
    - `total_payouts_disbursed`: sum of `payouts.net_amount` WHERE status = `completed` in window.
    - `total_transaction_fees_collected`: sum of `payouts.total_transaction_fees` in window.
    - `total_commission_fees_collected`: sum of `payouts.total_commission_fees` in window.
    - `platform_average_rating`: AVG of all `shops.average_rating` WHERE `total_reviews > 0`.
    - `total_active_sellers`: count of shops with status = `active`.
    - `total_orders`: count of orders in window.
  - Accepts `from` and `to` date params for windowed queries.
  - Authorization: admin role.
  - Register route in `routes/api.php`.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/admin/analytics
    ```

- [x] **M5.T3** Create Livewire component: Admin Analytics Dashboard
  - Files:
    - `app/Livewire/Admin/Finance/AnalyticsDashboard.php`
    - `resources/views/livewire/admin/finance/analytics-dashboard.blade.php`
  - Full-page Livewire component at route `admin/analytics`.
  - Features: metric cards for all platform-wide figures, date range selector, currency-formatted amounts.
  - Add route: `Route::livewire('analytics', 'admin.finance.analytics-dashboard')->name('analytics');` in admin group.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=admin/analytics
    ```

- [x] **M5.T4** Write feature tests for Analytics
  - File: `tests/Feature/AnalyticsTest.php`
  - Command: `vendor/bin/sail artisan make:test AnalyticsTest --pest --no-interaction`
  - Test cases:
    - Admin analytics returns correct GMV for given window.
    - Admin analytics returns correct total payouts disbursed.
    - Admin analytics returns correct fee totals.
    - Platform average rating computed correctly (excludes shops with 0 reviews).
    - Date range filtering works (orders outside window excluded).
    - Non-admin cannot access analytics endpoint (403).
    - Seller reports include payout metrics for selected window.
    - Seller reports include review averages.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=AnalyticsTest
    ```

---

## Milestone 6 — Payout Lifecycle & Disbursement

**Goal:** Admin-triggered payout batches with real fund disbursement to sellers via DLT Disbursement API.
**Depends on:** M1 (payout accounts), M2 (payout settings/fees)
**⚠️ BLOCKED on DLT Disbursement API contract — build application layer, mock gateway integration.**

- [x] **M6.T1** Create PostgreSQL enum types for payouts
  - File: `database/migrations/xxxx_create_payout_enum_types.php`
  - Command: `vendor/bin/sail artisan make:migration create_payout_enum_types --no-interaction`
  - Create enums:
    - `payout_status`: `('pending','processing','completed','failed')`
    - `payout_disbursement_status`: `('pending','succeeded','failed')`
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **M6.T2** Create `payouts` migration
  - File: `database/migrations/xxxx_create_payouts_table.php`
  - Command: `vendor/bin/sail artisan make:migration create_payouts_table --no-interaction`
  - Schema per requirements.md §3.2: `id` BIGINT PK, `shop_id` FK → shops (RESTRICT), `status` using `payout_status` enum (default `'pending'`), `currency` CHAR(3) DEFAULT `'PHP'`, `gross_amount` NUMERIC(12,2), `total_transaction_fees` NUMERIC(12,2), `total_commission_fees` NUMERIC(12,2), `net_amount` NUMERIC(12,2), `orders_count` INTEGER, `released_by_id` FK → users nullable, `released_at` TIMESTAMP nullable, `completed_at` TIMESTAMP nullable, `failed_at` TIMESTAMP nullable, `failure_reason` TEXT nullable, timestamps.
  - Indexes: `idx_payouts_shop` on `(shop_id)`, `idx_payouts_status` on `(status)`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **M6.T3** Create `payout_items` migration
  - File: `database/migrations/xxxx_create_payout_items_table.php`
  - Command: `vendor/bin/sail artisan make:migration create_payout_items_table --no-interaction`
  - Schema per requirements.md §3.2: `id` BIGINT PK, `payout_id` FK → payouts (CASCADE), `order_id` FK → orders (RESTRICT), `ledger_entry_id` FK → ledger_entries (RESTRICT), `escrowed_amount` NUMERIC(12,2), `transaction_fee` NUMERIC(12,2), `commission_fee` NUMERIC(12,2), `net_amount` NUMERIC(12,2), `created_at` TIMESTAMP. No `updated_at`.
  - Unique constraint on `order_id` (an order can only be in one payout batch).
  - Indexes: `idx_payout_items_payout`, `idx_payout_items_order`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **M6.T4** Create `payout_disbursements` migration
  - File: `database/migrations/xxxx_create_payout_disbursements_table.php`
  - Command: `vendor/bin/sail artisan make:migration create_payout_disbursements_table --no-interaction`
  - Schema per requirements.md §3.2: `id` BIGINT PK, `payout_id` FK → payouts (CASCADE), `status` using `payout_disbursement_status` enum (default `'pending'`), `dlt_reference_id` VARCHAR(100) nullable, `dlt_transaction_id` VARCHAR(50) nullable, `amount` NUMERIC(12,2), `gateway_response` JSONB nullable, `attempted_at` TIMESTAMP nullable, `succeeded_at` TIMESTAMP nullable, `failed_at` TIMESTAMP nullable, timestamps.
  - Index: `idx_payout_disbursements_payout`.
  - Verify:
    ```bash
    vendor/bin/sail artisan migrate
    ```

- [x] **M6.T5** Create `Payout`, `PayoutItem`, `PayoutDisbursement` models with factories
  - Files:
    - `app/Models/Payout.php`
    - `app/Models/PayoutItem.php`
    - `app/Models/PayoutDisbursement.php`
    - `database/factories/PayoutFactory.php`
    - `database/factories/PayoutItemFactory.php`
    - `database/factories/PayoutDisbursementFactory.php`
  - Commands:
    - `vendor/bin/sail artisan make:model Payout --factory --no-interaction`
    - `vendor/bin/sail artisan make:model PayoutItem --factory --no-interaction`
    - `vendor/bin/sail artisan make:model PayoutDisbursement --factory --no-interaction`
  - `Payout`: `belongsTo(Shop)`, `belongsTo(User, 'released_by_id')`, `hasMany(PayoutItem)`, `hasMany(PayoutDisbursement)`. Casts: financial columns as `decimal:2`, `status` as string, timestamps as `datetime`, `gateway_response` as `array` (on disbursement).
  - `PayoutItem`: `belongsTo(Payout)`, `belongsTo(Order)`, `belongsTo(LedgerEntry)`. Casts: financial columns as `decimal:2`. Set `const UPDATED_AT = null;`.
  - `PayoutDisbursement`: `belongsTo(Payout)`. Casts: `amount` as `decimal:2`, `gateway_response` as `array`, timestamps as `datetime`.
  - Add inverse relationships:
    - `Shop`: `hasMany(Payout::class)`.
    - `Order`: `hasOne(PayoutItem::class)`.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'App\Models\Payout::factory()->make(); App\Models\PayoutItem::factory()->make(); App\Models\PayoutDisbursement::factory()->make();'
    ```

- [x] **M6.T6** Create `PayoutPolicy`
  - File: `app/Policies/PayoutPolicy.php`
  - Command: `vendor/bin/sail artisan make:policy PayoutPolicy --model=Payout --no-interaction`
  - Rules:
    - `viewAny` (admin list): admin role.
    - `view`: admin OR seller owns the shop.
    - `create`: admin role.
    - `release`/`retry`: admin role + explicit `payout.release` permission.
    - `viewEligible`: admin role.
    - Seller can only view their own payouts.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute '(new App\Policies\PayoutPolicy);'
    ```

- [x] **M6.T7** Create `DltDisbursementService` interface and mock implementation
  - Files:
    - `app/Contracts/DltDisbursementServiceInterface.php`
    - `app/Services/DltDisbursementService.php` (real — stub/placeholder until API contract available)
    - `app/Services/MockDltDisbursementService.php` (mock for testing)
  - Interface methods:
    - `disburse(Payout $payout, SellerPayoutAccount $account): DisbursementResult`
    - `checkStatus(string $dltTransactionId): DisbursementStatus`
  - `DisbursementResult` value object: `success`, `dltReferenceId`, `dltTransactionId`, `errorMessage`, `rawResponse`.
  - Bind interface to mock in test environment, real (stub) in production.
  - Register in `AppServiceProvider`.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'app(App\Contracts\DltDisbursementServiceInterface::class);'
    ```

- [x] **M6.T8** Create `CreatePayoutBatchAction`
  - File: `app/Actions/CreatePayoutBatchAction.php`
  - Logic:
    - Accepts `shop_id` and optional array of `order_ids` (or "all eligible").
    - Validates all eligibility rules (§3.2):
      1. Order status is `completed` with no existing `payout_items` row.
      2. No active dispute on the order.
      3. At least `dispute_hold_days` since `completed_at`.
      4. Seller's payout account is `verified`.
      5. Cumulative releasable balance ≥ `minimum_payout_threshold`.
    - Calculates fees per order using current `payout_settings`:
      - `transaction_fee = transaction_fee_amount` (flat per order).
      - `commission_fee = escrowed_amount × commission_fee_rate` (rounded HALF_UP to 2dp).
      - `net_amount = escrowed_amount - transaction_fee - commission_fee`.
    - Creates `Payout` batch record + `PayoutItem` rows.
    - Wraps in DB transaction.
    - Returns the created Payout.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=CreatePayoutBatchAction
    ```

- [x] **M6.T9** Create `ReleasePayoutAction`
  - File: `app/Actions/ReleasePayoutAction.php`
  - Logic:
    - Accepts `Payout` (must be in `pending` or `failed` status).
    - Transitions payout to `processing`.
    - Calls `DltDisbursementServiceInterface::disburse()`.
    - On success: create `PayoutDisbursement` row (status `succeeded`), transition payout to `completed`, set `completed_at`.
    - On failure: create `PayoutDisbursement` row (status `failed`), transition payout to `failed`, set `failed_at` + `failure_reason`.
    - Sets `released_by_id` and `released_at` on first release.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=ReleasePayoutAction
    ```

- [x] **M6.T10** Create API controllers for Payout management
  - Files:
    - `app/Http/Controllers/Api/V1/Admin/PayoutController.php`
    - `app/Http/Controllers/Api/V1/HubPayoutController.php`
    - `app/Http/Resources/PayoutResource.php`
    - `app/Http/Resources/PayoutItemResource.php`
    - `app/Http/Resources/PayoutListResource.php`
  - Admin endpoints:
    - `GET /api/v1/admin/payouts` — list all payout batches (filterable by status, shop).
    - `GET /api/v1/admin/payouts/eligible` — list sellers with eligible orders + releasable amounts.
    - `POST /api/v1/admin/payouts` — create payout batch for a seller.
    - `POST /api/v1/admin/payouts/{payout}/release` — trigger disbursement.
    - `POST /api/v1/admin/payouts/{payout}/retry` — retry failed disbursement.
    - `GET /api/v1/admin/payouts/{payout}` — payout detail (items, disbursement history).
  - Seller endpoints:
    - `GET /api/v1/hub/payouts` — seller's payout history.
    - `GET /api/v1/hub/payouts/{payout}` — seller's payout detail.
    - `GET /api/v1/hub/payouts/balance` — seller's releasable balance + eligible orders.
  - Register routes in `routes/api.php`.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=api/v1/admin/payouts
    vendor/bin/sail artisan route:list --path=api/v1/hub/payouts
    ```

- [x] **M6.T11** Create Livewire component: Admin Payout Management
  - Files:
    - `app/Livewire/Admin/Payouts/PayoutQueue.php`
    - `resources/views/livewire/admin/payouts/payout-queue.blade.php`
    - `app/Livewire/Admin/Payouts/PayoutDetail.php`
    - `resources/views/livewire/admin/payouts/payout-detail.blade.php`
    - `app/Livewire/Admin/Payouts/EligiblePayouts.php`
    - `resources/views/livewire/admin/payouts/eligible-payouts.blade.php`
  - Routes:
    - `Route::livewire('payouts', 'admin.payouts.payout-queue')->name('payouts.index');`
    - `Route::livewire('payouts/eligible', 'admin.payouts.eligible-payouts')->name('payouts.eligible');`
    - `Route::livewire('payouts/{payout}', 'admin.payouts.payout-detail')->name('payouts.show');`
  - Features:
    - `PayoutQueue`: list all payout batches with status filter, links to detail.
    - `EligiblePayouts`: sellers with releasable balances, "Create Batch" button per seller.
    - `PayoutDetail`: shows batch info, items list, disbursement attempts, release/retry buttons.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=admin/payouts
    ```

- [x] **M6.T12** Create Livewire component: Seller Payout History
  - Files:
    - `app/Livewire/CompanyHub/Payouts/PayoutHistory.php`
    - `resources/views/livewire/company-hub/payouts/payout-history.blade.php`
    - `app/Livewire/CompanyHub/Payouts/PayoutDetail.php`
    - `resources/views/livewire/company-hub/payouts/payout-detail.blade.php`
  - Routes:
    - `Route::livewire('payouts', 'company-hub.payouts.payout-history')->name('payouts.index');`
    - `Route::livewire('payouts/{payout}', 'company-hub.payouts.payout-detail')->name('payouts.show');`
  - Features:
    - `PayoutHistory`: list of seller's payouts with status badges, net amounts, dates.
    - `PayoutDetail`: shows batch breakdown (items, fees, net per order).
  - Also add a "Releasable Balance" card accessible from earnings page.
  - Verify:
    ```bash
    vendor/bin/sail artisan route:list --path=hub/payouts
    ```

- [x] **M6.T13** Add `payout.release` permission
  - File: `database/seeders/RolesAndPermissionsSeeder.php` (modify existing)
  - Add permission `payout.release` and assign to `admin` role.
  - Run seeder or create a migration to add the permission.
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute 'Spatie\Permission\Models\Permission::where("name", "payout.release")->exists();'
    ```

- [x] **M6.T14** Write feature tests for Payout Lifecycle
  - File: `tests/Feature/PayoutLifecycleTest.php`
  - Command: `vendor/bin/sail artisan make:test PayoutLifecycleTest --pest --no-interaction`
  - Test cases:
    - **Eligibility rules** (unit-level within feature test):
      - Order not `completed` → not eligible.
      - Order already in `payout_items` → not eligible.
      - Order with active dispute → not eligible.
      - Order completed less than `dispute_hold_days` ago → not eligible.
      - Seller's payout account not `verified` → not eligible.
      - Total releasable below `minimum_payout_threshold` → cannot create batch.
      - All rules pass → eligible.
    - **Batch creation**:
      - Creates correct `Payout` + `PayoutItem` records.
      - Fee calculation correct (transaction_fee flat, commission_fee percentage, rounding).
      - Net amount = gross − transaction_fees − commission_fees.
      - `orders_count` matches number of items.
    - **Release/Retry**:
      - Release transitions `pending` → `processing` → `completed` (mocked success).
      - Release with mocked failure → status `failed`, `failure_reason` set.
      - Retry transitions `failed` → `processing` → `completed`.
      - Cannot release a `completed` payout.
      - Non-admin cannot release (403).
      - Admin without `payout.release` permission cannot release (403).
    - **Seller view**:
      - Seller can see their own payouts.
      - Seller cannot see another seller's payouts (404/403).
      - Balance endpoint returns correct releasable amount.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=PayoutLifecycleTest
    ```

- [x] **M6.T15** Write unit tests for fee calculation edge cases
  - File: `tests/Unit/FeeCalculationTest.php`
  - Command: `vendor/bin/sail artisan make:test FeeCalculationTest --pest --unit --no-interaction`
  - Test cases:
    - Zero orders in batch (should be prevented by validation).
    - Single order at minimum threshold.
    - Commission fee rounding: ₱333.33 × 0.03 = ₱10.00 (ROUND_HALF_UP).
    - Large amounts: precision maintained at NUMERIC(12,2).
    - Net amount never goes negative (transaction_fee + commission_fee < escrowed_amount — validated).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=FeeCalculationTest
    ```

---

## Milestone 7 — Seller Profit Calculator

**Goal:** Sellers can see projected net payout for a given sale price using current platform fee rates. Pre-filled on order detail for completed orders.
**Depends on:** M2 (payout_settings / fee-rates endpoint)

- [x] **M7.T1** Create Livewire component: Profit Calculator
  - Files:
    - `app/Livewire/CompanyHub/ProfitCalculator.php`
    - `resources/views/livewire/company-hub/profit-calculator.blade.php`
  - Inline/embeddable Livewire component (not full-page).
  - Features:
    - Input: sale price (numeric field).
    - Displays: transaction fee (flat), commission fee (percentage × price), net amount.
    - Formula: `net = sale_price - transaction_fee_amount - (sale_price × commission_fee_rate)`.
    - Reads current rates from `PayoutSetting` model.
    - Live calculation on input change (wire:model.live or debounced).
  - Verify:
    ```bash
    vendor/bin/sail artisan tinker --execute '(new App\Livewire\CompanyHub\ProfitCalculator);'
    ```

- [x] **M7.T2** Embed Profit Calculator in product create/edit page
  - Files (modify existing):
    - `resources/views/livewire/company-hub/product-form.blade.php` — embed `<livewire:company-hub.profit-calculator />` near price field.
  - Seller types a candidate price and sees estimated net proceeds live.
  - Verify: Visual inspection on product form page.

- [x] **M7.T3** Embed Profit Calculator in seller order detail / earnings page
  - Files (modify existing):
    - `resources/views/livewire/company-hub/orders/order-detail.blade.php` — for `completed` orders, show profit calculator pre-filled with order's `escrowed_amount`.
    - For orders already in `payout_items`, display **actual** recorded fees and net from the `payout_items` row (not recomputed).
  - Logic:
    - If order has `payout_items` row → show actual `transaction_fee`, `commission_fee`, `net_amount` from that row.
    - If order is `completed` but not yet in `payout_items` → show projection using current rates (with note that actual fees may differ if rates change before disbursement).
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=ProfitCalculator
    ```

- [x] **M7.T4** Write feature tests for Profit Calculator
  - File: `tests/Feature/ProfitCalculatorTest.php`
  - Command: `vendor/bin/sail artisan make:test ProfitCalculatorTest --pest --no-interaction`
  - Test cases:
    - Calculator displays correct net for a given sale price.
    - Calculator uses current `payout_settings` values (change settings, recalculate).
    - Fee-rates API endpoint returns correct values for authenticated seller.
    - Fee-rates endpoint rejects unauthenticated request (401).
    - For order with existing `payout_items` row, shows actual recorded values (not recomputed).
    - For completed order without `payout_items`, shows projection.
  - Verify:
    ```bash
    vendor/bin/sail artisan test --compact --filter=ProfitCalculatorTest
    ```

---

## Post-Implementation Checklist

- [x] Run full test suite: `vendor/bin/sail artisan test --compact`
- [x] Run Pint formatter: `vendor/bin/sail bin pint --dirty --format agent`
- [x] Verify all new routes registered: `vendor/bin/sail artisan route:list --except-vendor`
- [x] Verify all migrations run cleanly from scratch: `vendor/bin/sail artisan migrate:fresh --seed`
- [x] Verify Typesense index schema updated with rating fields
- [x] Verify `payout_settings` seeded with defaults on fresh migration
- [x] Verify `account_number` encryption works (store → read back decrypted)
- [x] Verify `payout.release` permission exists and is assigned to admin role
