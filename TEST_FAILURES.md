# Test Failure Report — 2026-09-10

**Summary:** 530 tests, **72 failures**, 416 passed, 1 risky (36.9s)

---

## 1. Livewire Snapshot Structure Invalid (36 tests)

**Error:** `Invalid Livewire snapshot structure: expected [data], [memo], [checksum], [memo.id], and [memo.name].`

**Root cause:** Livewire components have stale or corrupted snapshot state, likely from a Livewire version upgrade or a change in how components serialize state for testing.

**Affected test files:**
| Test file | Count |
|---|---|
| `tests/Feature/HubRfqDetailTest.php` | 14 |
| `tests/Feature/DisputeFormTest.php` | 8 |
| `tests/Feature/DisputeDetailTest.php` | 3 |
| `tests/Feature/QuotationBuilderTest.php` | 7 |
| `tests/Feature/OrderBuilderTest.php` | 3 |
| `tests/Feature/BuyerOrderDetailTest.php` | 2 |
| `tests/Feature/AdminRfqManagementTest.php` | 1 |
| `tests/Feature/SubscriptionManagementTest.php` | (see errors) |

---

## 2. HTTP 404 — Route Not Found (25 tests)

**Error:** Expected status 200/422/403/500 but received **404**.

**Root cause:** Routes have been moved, renamed, or removed without updating corresponding tests.

**Subcategories:**

- **14 tests expecting 200 → 404:** Missing routes for buyer orders, payout lifecycle, payout settings, reviews, subscription management, portal navigation, order detail
- **6 tests expecting 403 → 404:** Missing routes for admin payout release, seller payout scoping, reviews, payout account
- **4 tests expecting 422 → 404:** Missing routes for payout settings validation, duplicate review
- **1 test expecting 500 → 404:** Review double-response route missing

**Affected tests include:** `BuyerOrderDetailTest`, `PayoutLifecycleTest`, `PayoutSettingsTest`, `ReviewTest`, `SellerPayoutAccountTest`, `SubscriptionManagementTest`, `PortalNavigationTest`, `OrderCancelApiTest`, `HubOrderUpdateTest`, `PaymentPageTest`

---

## 3. HTTP 403 — Authorization Failures (10 tests)

**Error:** Expected status 200 but received **403**.

**Root cause:** Missing or incorrect authorization permissions, role assignments, or middleware in test setup.

**Affected tests:**
- `Admin\MerchantManagementTest::it_can_suspend_and_reactivate_a_merchant`
- `HubOrderUpdateTest` (2 tests) — `update_bulk` and `update_replaces_existing_items`
- `OrderCancelApiTest` (2 tests) — buyer and seller cancel endpoints
- `SellerOrderDetailTest` (3 tests) — seller order operations
- `SubscriptionManagementTest::merchant_can_update_their_subscription_plan`
- `BuyerOrderDetailTest::cancel_order_button_is_hidden_for_non_pending_orders`

---

## 4. Livewire Blade Assertion Failures (12 tests)

**Error:** `Failed asserting that '<div ...' contains/does not contain expected string`

**Root cause:** Blade template output has changed — likely from a UI refactor. Missing expected text/links in rendered HTML.

**Subcategories:**

- **5 tests:** Missing expected content in storefront/homepage/portal navigation (branding, nav links, country portals)
- **4 tests:** Missing expected content in Livewire components (order detail, dispute form, RFQ detail)
- **2 tests:** Admin dashboard Livewire rendering issues (merchant list, analytics)
- **1 test:** Product list toggle status returns wrong value

**Affected tests:**
- `BrandingTest` (3 tests) — auth dropdown, buyer/seller hub links, storefront sections
- `PortalNavigationTest` (2 tests) — buyer portal nav, company hub nav
- `StorefrontHomeTest` — custom sourcing section
- `BuyerDashboardTest` — Start Selling CTA
- `BuyerOrderDetailTest` — back link
- `DisputeFormTest` — advisory message
- `HubRfqDetailTest` — Close button
- `OrderDetailTest` — disputes section
- `RfqCreateFormTest` — edit profile link
- `ShopProfileFormTest` — verified details
- `SourcingQueueTest` — download button
- `SubscriptionManagementTest` — subscription queue rows
- `DocumentManagerTest` — poll refresh scan status
- `ProductListTest` — toggle publish status

---

## 5. Missing PaymentAttempt Records (3 tests)

**Error:** `No query results for model [App\Models\PaymentAttempt]`

**Affected tests:**
- `SubscriptionManagementTest::non_ph_merchants_complete_the_payment_success_flow`
- `SubscriptionManagementTest::non_ph_merchants_are_cancelled_when_payment_session_fails`
- `SubscriptionManagementTest::merchants_can_renew_an_active_subscription_before_it_expires`

**Root cause:** Subscription payment flow doesn't create the expected `PaymentAttempt` record, or the test queries for it before/after it exists.

---

## 6. Undefined Column: `publish_status` (2 tests)

**Error:** `column "publish_status" does not exist`

**Affected tests:**
- `SellerDashboardTest::the_seller_dashboard_shows_a_View_My_Shop_link_for_an_active_shop`
- `SellerDashboardTest::the_shop_computed_property_returns_the_sellers_shop`

**Root cause:** The `products` table column was likely renamed (e.g., to `listing_status`) but the seller dashboard query/view still references `publish_status`.

---

## 7. Null Property: `account_number` on null (1 test)

**Error:** `Attempt to read property "account_number" on null`

**Affected test:** `SellerPayoutAccountTest::it_encrypts_account_number_at_rest`

**Root cause:** The payout account model/relationship returns null — either the factory doesn't create the record or the relationship lookup fails.

---

## 8. Dispute Resolution Validation & Redirect (3 tests)

**Affected tests:**
- `DisputeResolutionTest::partial_refund_requires_the_amount_to_be_filled_in` — validation not triggered
- `DisputeResolutionTest::partial_refund_requires_an_amount_within_the_order_total` — validation not triggered
- `DisputeResolutionTest::resolving_a_dispute_redirects_to_the_order_page_to_refresh_it` — no redirect occurred

**Root cause:** Livewire component action behavior has changed — validation rules or redirect logic may need updating.

---

## 9. Miscellaneous Failures (4 tests)

| Test | Error |
|---|---|
| `ApproveOnboardingActionTest::approval_fails_when_a_certificate_is_missing_its_uploaded_file` | Expected `ValidationException` not thrown |
| `ApproveOnboardingActionTest::approval_succeeds_and_creates_an_active_shop_when_certificates_are_complete` | Longitude assertion fails (-179 vs 179) |
| `OrderBuilderTest::fillBillingFromBuyerProfile_populates_empty_billing_fields` | Expected `'Acme Trading'`, got `null` |
| `VerifyEmailNotificationTest::the_verification_mail_footer_links_to_storefront_routes` | Expected `'Verified Companies'` not found in email HTML |
| `SubscriptionManagementTest::publish_is_blocked_when_the_shop_has_no_subscription` | Expected `AuthorizationException` not thrown |
| `SubscriptionManagementTest::the_admin_subscription_queue_is_scoped_to_the_configured_instance_country` | Expected `'Nolan LLC Store'` not found |
