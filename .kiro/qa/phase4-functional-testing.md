# Functional Testing Guide — Phase 4: Payouts, Reviews & Analytics

| | |
|---|---|
| **Status** | 🟢 Ready — all Milestones (M1–M7) complete |
| **Last updated** | 2026-08-24 |
| **Prerequisite** | Phase 1–3 complete, queue worker running, frontend built, PayoutSettingsSeeder run |

---

## Setup & Rebuild

### Fresh Start (full rebuild)

```bash
# Start all services
vendor/bin/sail up -d

# Reset database with all migrations + seeders (includes PayoutSettingsSeeder)
vendor/bin/sail artisan migrate:fresh --seed

# Build frontend
vendor/bin/sail npm run build

# Start queue worker (required for RecalculateRatingsJob, IndexShopJob, etc.)
vendor/bin/sail artisan queue:listen &
```

### After Pulling New Code

```bash
vendor/bin/sail composer install
vendor/bin/sail artisan migrate
vendor/bin/sail npm run build
docker compose restart worker reverb
```

### Run Phase 4 Tests Only

```bash
vendor/bin/sail artisan test --compact --filter="SellerPayoutAccountTest|PayoutSettingsTest|ReviewTest|PaymentHistoryTest|AnalyticsTest|ProfitCalculatorTest|PayoutLifecycleTest|FeeCalculationTest"
# Expected: 91 passed (223 assertions)
```

### Run All Tests

```bash
vendor/bin/sail artisan test --compact
```

---

## Test Accounts

| Role | Email | Password | Notes |
|------|-------|----------|-------|
| Seller | `test@example.com` | `password` | Has active shop (from seeder) |
| Buyer 1 | `buyer-demo@example.com` | `password` | Has seeded orders |
| Buyer 2 | `buyer2-demo@example.com` | `password` | Has seeded orders |
| Admin | `admin@gmail.com` | `password` | Has admin role + `payout.release` permission |

### Create Accounts if Needed

```bash
# Admin with payout.release permission
vendor/bin/sail artisan tinker --execute '
$admin = App\Models\User::factory()->create([
    "first_name" => "Admin", "last_name" => "Finance",
    "email" => "finance-admin@example.com", "password" => "password",
    "email_verified_at" => now(),
]);
$admin->assignRole("admin");
echo "Admin: finance-admin@example.com / password\n";
'

# Seller with active shop + verified payout account
vendor/bin/sail artisan tinker --execute '
$seller = App\Models\User::where("email", "test@example.com")->first();
$shop = $seller->shop;
App\Models\SellerPayoutAccount::updateOrCreate(
    ["shop_id" => $shop->id],
    [
        "bank_name" => "BDO",
        "bank_code" => "BDO",
        "account_holder_name" => $seller->first_name . " " . $seller->last_name,
        "account_number" => "1234567890",
        "branch" => "Makati",
        "verification_status" => "verified",
        "verified_at" => now(),
    ]
);
echo "Payout account created for shop #{$shop->id}\n";
'
```

---

## Access Points

| Page | URL | Role |
|------|-----|------|
| **Payout Account** | http://localhost/hub/payout-account | Seller |
| **Seller Earnings** | http://localhost/hub/earnings | Seller |
| **Seller Payouts** | http://localhost/hub/payouts | Seller |
| **Seller Reviews** | http://localhost/hub/reviews | Seller |
| **Seller Reports** | http://localhost/hub/reports | Seller |
| **Buyer Payment History** | http://localhost/account/payments | Buyer |
| **Admin Payout Accounts** | http://localhost/admin/payout-accounts | Admin |
| **Admin Payout Settings** | http://localhost/admin/payout-settings | Admin |
| **Admin Payouts** | http://localhost/admin/payouts | Admin |
| **Admin Eligible Payouts** | http://localhost/admin/payouts/eligible | Admin |
| **Admin Financial Ledger** | http://localhost/admin/ledger | Admin |
| **Admin Analytics** | http://localhost/admin/analytics | Admin |
| **Admin Review Reports** | http://localhost/admin/review-reports | Admin |
| **Product Reviews (public)** | http://localhost/products/{id} | Public |
| **Company Reviews (public)** | http://localhost/companies/{slug} | Public |

---

## Test Flow 1: Seller Registers Payout Account

1. Login as seller (`test@example.com`)
2. Navigate to http://localhost/hub/payout-account
3. ✅ Form displays (bank name dropdown, account number, etc.)
4. Select Bank: **BDO**
5. Enter Account Holder: "Juan Dela Cruz"
6. Enter Account Number: "1234567890"
7. Enter Branch: "Makati Main"
8. Click **"Save"**
9. ✅ Success flash: account saved
10. ✅ Status shows "Pending Verification" badge (yellow)
11. Edit account number to "9876543210", save again
12. ✅ Status resets to "Pending Verification" (re-verification required)

---

## Test Flow 2: Admin Verifies Payout Account

1. Login as admin (`admin@gmail.com`)
2. Navigate to http://localhost/admin/payout-accounts
3. ✅ List shows pending payout accounts
4. Filter by status: "Pending"
5. ✅ Seller's account visible
6. Click **"Verify"** on the account
7. ✅ Status changes to "Verified" (green badge)
8. ✅ `verified_at` timestamp set

### Rejection Flow

9. Click **"Reject"** on another account
10. ✅ Modal prompts for rejection reason
11. Enter: "Account holder name doesn't match business registration"
12. Confirm → ✅ Status changes to "Rejected" (red badge)
13. ✅ Rejection reason visible to seller on their form

---

## Test Flow 3: Admin Configures Payout Settings

1. Login as admin
2. Navigate to http://localhost/admin/payout-settings
3. ✅ Shows 4 settings with current values:
   - Dispute Hold Days: 7
   - Minimum Payout Threshold: ₱500.00
   - Transaction Fee: ₱15.00
   - Commission Rate: 3% (0.03)
4. Change Transaction Fee to "20.00"
5. Click **"Save"**
6. ✅ Success flash, value updated
7. ✅ "Last updated by: Admin User at [timestamp]" shown
8. Try entering "-5" for threshold → ✅ validation error

### Verify via API

```bash
curl http://localhost/api/v1/admin/payout-settings \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq
# Expected: {"dispute_hold_days":"7","minimum_payout_threshold":"500.00","transaction_fee_amount":"20.00","commission_fee_rate":"0.03"}
```

---

## Test Flow 4: Buyer Submits Reviews (Product + Shop)

**Prerequisite:** A completed order exists.

### Setup (if no completed order)

```bash
vendor/bin/sail artisan tinker --execute '
$order = App\Models\Order::where("status", "completed")->first();
if (!$order) {
    echo "No completed order. Use Phase 3 Flow 4 (Confirm Receipt) first.\n";
} else {
    echo "Completed order #{$order->id} ready for review.\n";
}
'
```

### Browser Flow

1. Login as buyer (`buyer-demo@example.com`)
2. Navigate to http://localhost/account/orders
3. Click on a **completed** order
4. ✅ "Write a Review" section visible
5. **Product Review:**
   - Select rating: ⭐⭐⭐⭐⭐ (5 stars)
   - Enter title: "Excellent quality halal flour"
   - Enter body: "Fast delivery and great packaging."
   - Click **"Submit Review"**
   - ✅ Success, review appears in the list below
6. **Shop Review:**
   - Select rating: ⭐⭐⭐⭐ (4 stars)
   - Enter body: "Good seller, responsive communication."
   - Click **"Submit Review"**
   - ✅ Shop review submitted
7. Try reviewing the same product again for the same order
   - ✅ Error: "You have already reviewed this product for this order."

### Verify on Storefront

8. Navigate to the product page (public)
9. ✅ Average rating shown (e.g., "5.0 ★ · 1 review")
10. ✅ Review text visible with buyer name and date
11. Navigate to the company profile page
12. ✅ Shop average rating shown ("4.0 ★ · 1 review")

---

## Test Flow 5: Seller Responds to Review

1. Login as seller (`test@example.com`)
2. Navigate to http://localhost/hub/reviews
3. ✅ Reviews for seller's products/shop listed
4. Click **"Respond"** on a review
5. Enter: "Thank you for the positive feedback! We're glad you enjoyed our product."
6. Submit → ✅ Response visible
7. Try responding to the same review again → ✅ "Edit" mode (not duplicate)

### Verify on Storefront

8. Navigate to the product page
9. ✅ Seller response shown below the buyer's review

---

## Test Flow 6: Report & Moderate Review

### Buyer/User Reports

1. Login as a different user (not the reviewer)
2. Navigate to a product page with reviews
3. Click **"Report"** on a review
4. Enter reason: "Contains misleading information"
5. Submit → ✅ Report submitted

### Admin Moderates

6. Login as admin
7. Navigate to http://localhost/admin/review-reports
8. ✅ Pending report visible
9. Click **"View"** → see review content + report reason
10. **Option A:** Click "Dismiss" → ✅ report status → "dismissed", review unchanged
11. **Option B:** Click "Hide Review" → ✅ report status → "actioned", review hidden from public
12. ✅ Hidden review no longer appears on storefront
13. ✅ Average rating recalculated

---

## Test Flow 7: Buyer Payment History

1. Login as buyer with paid orders
2. Navigate to http://localhost/account/payments
3. ✅ Table shows all payments (succeeded payment attempts)
4. ✅ Refunds also shown (from disputed/refunded orders)
5. Filter by date range → ✅ results filtered
6. Filter by status "Refunded" → ✅ only refunds shown
7. ✅ Amounts displayed in PHP currency format (₱)

---

## Test Flow 8: Seller Earnings Overview

1. Login as seller
2. Navigate to http://localhost/hub/earnings
3. ✅ Summary cards show:
   - Total Earned: ₱0.00 (until payouts are processed)
   - Pending Balance: sum of escrowed amounts from completed orders
   - Total Released: ₱0.00 (until payouts disbursed)
   - Total Fees Paid: ₱0.00
4. ✅ Transaction list shows ledger entries (escrow_hold, payout, refund)
5. Filter by date range → ✅ results filtered

---

## Test Flow 9: Admin Creates & Releases Payout

**Prerequisite:**
- Seller has verified payout account
- Seller has completed orders older than 7 days (dispute_hold_days)
- No active disputes on those orders

### Setup

```bash
# Backdate a completed order to make it payout-eligible
vendor/bin/sail artisan tinker --execute '
$seller = App\Models\User::where("email", "test@example.com")->first();
$shop = $seller->shop;
$order = App\Models\Order::where("shop_id", $shop->id)->where("status", "completed")->first();
if ($order) {
    $order->update(["completed_at" => now()->subDays(10)]);
    echo "Order #{$order->id} backdated to 10 days ago.\n";
} else {
    echo "No completed order for this shop.\n";
}
'
```

### Browser Flow

1. Login as admin
2. Navigate to http://localhost/admin/payouts/eligible
3. ✅ Seller appears with releasable balance ≥ ₱500 threshold
4. Click **"Create Batch"** for the seller
5. ✅ Payout batch created, status "Pending"
6. ✅ Shows: gross amount, transaction fees, commission fees, net amount
7. Navigate to http://localhost/admin/payouts
8. ✅ New payout batch visible in list
9. Click on the batch → detail page
10. ✅ Shows line items (orders included), fee breakdown per order
11. Click **"Release"**
12. ✅ Status → "Processing" → "Completed" (mock DLT succeeds)
13. ✅ Disbursement record created with reference ID
14. ✅ Seller's earnings page now shows updated "Total Released" figure

### Verify via API

```bash
# Admin: list payouts
curl http://localhost/api/v1/admin/payouts \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq '.data[0].status'
# Expected: "completed"

# Seller: check balance
curl http://localhost/api/v1/hub/payouts/balance \
  -H "Authorization: Bearer $SELLER_TOKEN" | jq
```

---

## Test Flow 10: Payout Failure & Retry

1. To test failure, temporarily set DLT_DISBURSEMENT_ENABLED=true in .env (real stub always fails)
2. Create a new payout batch and attempt release
3. ✅ Status → "Failed", failure_reason shown
4. ✅ Disbursement record with status "failed"
5. Fix the issue (set DLT_DISBURSEMENT_ENABLED back to false for mock)
6. Click **"Retry"**
7. ✅ Status → "Processing" → "Completed"

---

## Test Flow 11: Seller Views Payout History

1. Login as seller
2. Navigate to http://localhost/hub/payouts
3. ✅ List of past payout batches with status badges
4. Click on a completed payout
5. ✅ Detail shows: order breakdown, fees per order, net per order, total
6. ✅ Disbursement info shown (reference ID, timestamp)

---

## Test Flow 12: Seller Profit Calculator

1. Login as seller
2. Navigate to http://localhost/hub/products/create (or edit an existing product)
3. ✅ Profit Calculator widget visible
4. Enter sale price: "1000"
5. ✅ Shows:
   - Transaction Fee: ₱15.00
   - Commission Fee (3%): ₱30.00
   - **Net Proceeds: ₱955.00**
6. Change price to "5000"
7. ✅ Updates live:
   - Transaction Fee: ₱15.00
   - Commission Fee: ₱150.00
   - **Net Proceeds: ₱4,835.00**
8. Clear the input → ✅ shows dashes (—)

### On Order Detail (completed order)

9. Navigate to http://localhost/hub/orders/{completed_order_id}
10. ✅ Profit calculator shows pre-filled with order's escrowed amount
11. If order has been paid out → ✅ shows **actual** recorded fees (from payout_items)
12. If order hasn't been paid out yet → ✅ shows projection with note "Estimated (actual fees applied at payout time)"

---

## Test Flow 13: Admin Analytics Dashboard

1. Login as admin
2. Navigate to http://localhost/admin/analytics
3. ✅ Platform-wide metrics displayed:
   - GMV (total order value for completed/refunded orders)
   - Total Payouts Disbursed
   - Transaction Fees Collected
   - Commission Fees Collected
   - Platform Average Rating
   - Total Active Sellers
   - Total Orders
4. Change date range → ✅ metrics update
5. ✅ All amounts in PHP currency format

---

## Test Flow 14: Seller Reports (Extended with Phase 4 metrics)

1. Login as seller
2. Navigate to http://localhost/hub/reports
3. ✅ Existing metrics still work (gross sales, refunds, net sales, etc.)
4. ✅ New sections visible:
   - **Payout Summary**: Total Earned, Pending Balance, Total Released
   - **Reviews**: Average Rating stars, Total Reviews count
5. Change time window → ✅ payout metrics update with window

---

## Test Flow 15: Sort by Rating on Storefront

1. Navigate to http://localhost/companies (public, no login required)
2. ✅ Rating displayed on company cards (if reviews exist)
3. Select sort: **"Highest Rated"**
4. ✅ Companies sorted by average_rating descending
5. Navigate to http://localhost/products
6. ✅ Rating displayed on product cards

---

## API Testing (curl)

### Payout Account

```bash
# Seller: get payout account
curl http://localhost/api/v1/hub/payout-account \
  -H "Authorization: Bearer $SELLER_TOKEN" | jq

# Seller: create/update payout account
curl -X PUT http://localhost/api/v1/hub/payout-account \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -d '{"bank_name":"BDO","bank_code":"BDO","account_holder_name":"Juan Dela Cruz","account_number":"1234567890","branch":"Makati"}' | jq

# Admin: verify
curl -X POST http://localhost/api/v1/admin/payout-accounts/1/verify \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq
```

### Reviews

```bash
# Create product review
curl -X POST http://localhost/api/v1/orders/1/reviews \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $BUYER_TOKEN" \
  -d '{"reviewable_type":"product","reviewable_id":1,"rating":5,"title":"Great product","body":"Loved it!"}' | jq

# List product reviews (public)
curl http://localhost/api/v1/products/1/reviews | jq

# Seller respond
curl -X POST http://localhost/api/v1/reviews/1/respond \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -d '{"body":"Thank you for your review!"}' | jq
```

### Payment History

```bash
# Buyer transactions
curl "http://localhost/api/v1/transactions?from=2026-01-01&to=2026-12-31" \
  -H "Authorization: Bearer $BUYER_TOKEN" | jq

# Seller earnings
curl http://localhost/api/v1/hub/earnings \
  -H "Authorization: Bearer $SELLER_TOKEN" | jq

# Admin ledger
curl "http://localhost/api/v1/admin/ledger?type=escrow_hold" \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq
```

### Payouts

```bash
# Admin: eligible sellers
curl http://localhost/api/v1/admin/payouts/eligible \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq

# Admin: create batch
curl -X POST http://localhost/api/v1/admin/payouts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"shop_id":1}' | jq

# Admin: release
curl -X POST http://localhost/api/v1/admin/payouts/1/release \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq

# Seller: my payouts
curl http://localhost/api/v1/hub/payouts \
  -H "Authorization: Bearer $SELLER_TOKEN" | jq

# Seller: releasable balance
curl http://localhost/api/v1/hub/payouts/balance \
  -H "Authorization: Bearer $SELLER_TOKEN" | jq
```

### Analytics & Fee Rates

```bash
# Admin analytics
curl "http://localhost/api/v1/admin/analytics?from=2026-01-01&to=2026-12-31" \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq

# Seller fee rates
curl http://localhost/api/v1/hub/fee-rates \
  -H "Authorization: Bearer $SELLER_TOKEN" | jq
# Expected: {"transaction_fee_amount":"15.00","commission_fee_rate":"0.03"}
```

---

## Verification Checklist

| # | Scenario | Status |
|---|----------|--------|
| 1 | Seller registers payout account | ☐ |
| 2 | Seller updates account (status resets to pending) | ☐ |
| 3 | Admin verifies payout account | ☐ |
| 4 | Admin rejects payout account with reason | ☐ |
| 5 | Seller sees rejection reason | ☐ |
| 6 | Admin views/updates payout settings | ☐ |
| 7 | Invalid payout settings rejected (validation) | ☐ |
| 8 | Buyer submits product review (1–5 stars) | ☐ |
| 9 | Buyer submits shop review | ☐ |
| 10 | Buyer cannot double-review same entity/order | ☐ |
| 11 | Buyer can edit their review | ☐ |
| 12 | Buyer cannot review non-completed order | ☐ |
| 13 | Seller responds to review | ☐ |
| 14 | Seller edits response | ☐ |
| 15 | User reports a review | ☐ |
| 16 | Admin dismisses a report | ☐ |
| 17 | Admin hides a review (actioned) | ☐ |
| 18 | Hidden review excluded from public display | ☐ |
| 19 | Average rating recalculated on review/hide | ☐ |
| 20 | Rating appears on storefront (product/company) | ☐ |
| 21 | Sort by rating works on company directory | ☐ |
| 22 | Buyer payment history shows payments + refunds | ☐ |
| 23 | Buyer cannot see other users' transactions | ☐ |
| 24 | Seller earnings shows pending balance | ☐ |
| 25 | Admin financial ledger shows all entries | ☐ |
| 26 | Admin ledger filterable by type/date/shop | ☐ |
| 27 | Admin sees eligible payouts with releasable amounts | ☐ |
| 28 | Admin creates payout batch | ☐ |
| 29 | Payout batch shows correct fee calculation | ☐ |
| 30 | Admin releases payout (mock succeeds) | ☐ |
| 31 | Failed payout can be retried | ☐ |
| 32 | Seller sees payout history + detail | ☐ |
| 33 | Seller profit calculator shows correct net | ☐ |
| 34 | Calculator updates live on input change | ☐ |
| 35 | Calculator embedded in product form | ☐ |
| 36 | Calculator on order detail shows actual fees (if paid out) | ☐ |
| 37 | Admin analytics shows GMV + platform metrics | ☐ |
| 38 | Seller reports include payout/review metrics | ☐ |
| 39 | Non-admin cannot access admin endpoints (403) | ☐ |
| 40 | Buyer cannot access seller payout endpoints (403) | ☐ |
| 41 | Rate limit: >10 reviews/hour rejected (429) | ☐ |
| 42 | Encrypted account_number not readable in DB | ☐ |
| 43 | `payout.release` permission required for release | ☐ |

---

## Known Limitations (Phase 4)

- **DLT Disbursement:** Real disbursement requires the DLT Disbursement API contract (not yet available). Currently uses `MockDltDisbursementService` which always succeeds. Set `DLT_DISBURSEMENT_ENABLED=true` in `.env` to use the real stub (which always fails until API is integrated).
- **No auto-scheduled payouts:** All payout releases are manually triggered by admin. No cron/scheduled automatic release.
- **No seller-initiated payouts:** Sellers cannot request a payout — admin must initiate.
- **No review media:** Reviews are text + star rating only; no photo/video uploads.
- **No auto-moderation:** No profanity filter or minimum character count on reviews.
- **No CSV/PDF export:** Analytics and payment history are dashboard-only views.
- **No clawback:** Once funds are disbursed, they cannot be reversed. Late disputes are settled off-platform.
- **Commission rate is global:** No per-seller or per-category rate overrides (data model supports future override via `shop_payout_overrides` table, not built in Phase 4).
- **Profit calculator is a projection:** For orders not yet in a payout batch, the displayed fees use current rates which may change before actual disbursement.
