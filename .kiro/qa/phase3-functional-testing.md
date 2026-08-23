# Functional Testing Guide — Phase 3: Orders & Payments

| | |
|---|---|
| **Status** | 🟢 Ready — all Epics (E21–E29) complete |
| **Last updated** | 2026-08-23 |
| **Prerequisite** | Phase 1 + 2 complete, queue worker running, frontend built, Phase3Seeder run |

---

## Setup & Rebuild

### Fresh Start (full rebuild)

```bash
# Start all services
vendor/bin/sail up -d

# Reset database with all migrations + seeders (includes Phase3Seeder)
vendor/bin/sail artisan migrate:fresh --seed

# Build frontend
vendor/bin/sail npm run build

# Start queue worker (required for notifications, events)
vendor/bin/sail artisan queue:listen &
```

### After Pulling New Code

```bash
vendor/bin/sail composer install
vendor/bin/sail artisan migrate
vendor/bin/sail npm run build
docker compose restart worker reverb
```

### Run Phase 3 Tests Only

```bash
vendor/bin/sail artisan test --compact --filter="OrderSchemaTest|OrderCreationTest|BuyerOrderTest|OrderPaymentTest|DltPaymentServiceTest|FulfilmentTest|DisputeTest|OrderThreadTest|OrderNotificationsTest|SellerHubTest"
```

### Run All Tests

```bash
vendor/bin/sail artisan test --compact
# Expected: 382 passed (1142 assertions)
```

---

## Test Accounts

| Role | Email | Password | Notes |
|------|-------|----------|-------|
| Seller | `test@example.com` | `password` | Has active shop (from seeder) |
| Buyer 1 | `buyer-demo@example.com` | `password` | Has seeded orders |
| Buyer 2 | `buyer2-demo@example.com` | `password` | Has seeded orders |
| Admin | `admin@gmail.com` | `password` | Has admin role |

### Create Accounts if Needed

```bash
# Seller with active shop
vendor/bin/sail artisan tinker --execute '
$seller = App\Models\User::factory()->create([
    "first_name" => "Seller", "last_name" => "Test",
    "email" => "seller-test@example.com", "password" => "password",
    "email_verified_at" => now(),
]);
App\Models\Shop::factory()->create(["user_id" => $seller->id, "status" => "active", "storefront_name" => "Test Halal Foods"]);
echo "Seller: seller-test@example.com / password\n";
'

# Buyer with profile
vendor/bin/sail artisan tinker --execute '
$buyer = App\Models\User::factory()->create([
    "first_name" => "Buyer", "last_name" => "Test",
    "email" => "buyer-test@example.com", "password" => "password",
    "email_verified_at" => now(),
]);
App\Models\BuyerProfile::factory()->create(["user_id" => $buyer->id, "business_name" => "Test Buyer Corp."]);
echo "Buyer: buyer-test@example.com / password\n";
'
```

---

## Access Points

| Page | URL | Role |
|------|-----|------|
| Seller Orders | http://localhost/hub/orders | Seller |
| Seller Order Builder | http://localhost/hub/orders/create | Seller |
| Seller Hub Dashboard | http://localhost/hub/dashboard | Seller |
| Seller Customers | http://localhost/hub/customers | Seller |
| Seller Reports | http://localhost/hub/reports | Seller |
| Buyer My Orders | http://localhost/account/orders | Buyer |
| Admin Disputes | http://localhost/admin/disputes | Admin |
| Mailpit (emails) | http://localhost:8025 | — |

---

## Test Flow 1: Seller Creates Order from Awarded Quotation

**Prerequisite:** An awarded quotation exists (complete Phase 2 flow first, or use API).

### Setup via API (if no awarded quotation exists)

```bash
# Create a quotation and award it (as buyer)
# First, ensure seller-buyer RFQ pipeline exists from Phase 2
# Or use the Phase3Seeder which creates orders directly
```

### Browser Flow

1. Login as seller (`test@example.com`)
2. Navigate to http://localhost/hub/orders
3. Click **"New Order"**
4. ✅ Redirected to Order Builder
5. ✅ If creating from awarded quotation: items are pre-populated
6. Edit items (change quantity, add new item via "+ Add Item")
7. Add a fee (click "+ Add Fee", label: "Shipping", amount: 1500)
8. Edit customer info / shipping address
9. Click **"Save Draft"**
10. ✅ Success flash: "Order saved."
11. ✅ Totals recalculated
12. Click **"Submit for Payment"** → confirmation modal appears
13. Confirm → ✅ Success flash: "Order submitted for payment."
14. ✅ Redirected to order detail, status shows "pending_payment"
15. ✅ Buyer receives notification (check Mailpit at http://localhost:8025)

---

## Test Flow 2: Buyer Views and Pays for Order

**Prerequisite:** Order in `pending_payment` status (from Flow 1 or seeder).

1. Login as buyer (`buyer-demo@example.com`)
2. Navigate to http://localhost/account/orders
3. ✅ "To Pay" tab shows the pending order
4. Click on the order → detail page
5. ✅ Shows items, fees, total, "Pay Now" button
6. Click **"Pay Now"**
7. ✅ Redirected to DLT hosted checkout (or error if DLT credentials not configured)

> **Note:** Without real DLT credentials, the payment redirect will fail with a gateway error. To simulate a successful payment for testing:

```bash
# Manually simulate payment success
vendor/bin/sail artisan tinker --execute '
$order = App\Models\Order::where("status", "pending_payment")->first();
$term = $order->paymentTerms()->first();
$attempt = App\Models\PaymentAttempt::create([
    "order_payment_term_id" => $term->id,
    "status" => "succeeded",
    "dlt_merchant_transaction_id" => "manual-test-" . now()->timestamp,
    "succeeded_at" => now(),
    "dlt_payment_status" => "PAID",
]);
(new App\Actions\ProcessOrderPaymentAction)->execute($attempt, $order);
echo "Order #{$order->id} marked as paid.\n";
'
```

8. After payment: ✅ Order moves to "To Ship" tab
9. ✅ Seller receives "Order Paid" notification (Mailpit)

---

## Test Flow 3: Seller Fulfils Order (Ship → Deliver)

**Prerequisite:** Order in `paid` status.

1. Login as seller
2. Navigate to http://localhost/hub/orders
3. Filter by **"To Fulfil"** tab
4. Click on a paid order
5. ✅ Fulfilment panel shows "Create Fulfilment" form
6. Enter carrier: "JRS Express", tracking code: "JRS123456"
7. Click **"Create Fulfilment"** → ✅ created with status "pending"
8. Click **"Mark Shipped"** → ✅ status changes to "shipped"
9. ✅ Buyer receives "Order Shipped" notification
10. Click **"Mark Delivered"** → ✅ status changes to "delivered"
11. ✅ Buyer receives "Order Delivered" notification
12. ✅ Buyer's order moves to "To Receive" tab with "Confirm Receipt" CTA

---

## Test Flow 4: Buyer Confirms Receipt

**Prerequisite:** Fulfilment marked as `delivered`.

1. Login as buyer
2. Navigate to http://localhost/account/orders
3. Go to **"To Receive"** tab
4. Click on the delivered order
5. ✅ "Confirm Receipt" button visible
6. Click **"Confirm Receipt"** → confirm prompt
7. ✅ Order status → "completed"
8. ✅ Order moves to "Completed" tab
9. ✅ Seller receives "Order Completed" notification
10. ✅ Ledger entry created (payout with commission deducted)

### Verify Ledger

```bash
vendor/bin/sail artisan tinker --execute '
$order = App\Models\Order::where("status", "completed")->latest()->first();
echo "Order #{$order->id} ledger:\n";
$order->ledgerEntries->each(fn ($e) => printf("  %s: %s (%s)\n", $e->type, $e->amount, $e->note));
'
```

---

## Test Flow 5: Auto-Accept (7-day timer)

**Prerequisite:** Order delivered, buyer takes no action.

```bash
# Simulate: set delivered_at to 8 days ago
vendor/bin/sail artisan tinker --execute '
$order = App\Models\Order::where("status", "paid")->whereHas("fulfilments", fn($q) => $q->where("status", "delivered"))->first();
if ($order) {
    $order->fulfilments()->update(["delivered_at" => now()->subDays(8)]);
    echo "Set delivered_at to 8 days ago for order #{$order->id}\n";
} else {
    echo "No eligible order found.\n";
}
'

# Run the auto-accept job
vendor/bin/sail artisan tinker --execute '
(new App\Jobs\OrderAutoAcceptJob)->handle(app(App\Actions\ConfirmReceiptAction::class));
'
```

✅ Order auto-completes, payout ledger entry created.

---

## Test Flow 6: Buyer Cancels Unpaid Order

1. Login as buyer
2. Navigate to a `pending_payment` order
3. Click **"Cancel"** → confirm
4. ✅ Order status → "cancelled"
5. ✅ No payment was taken, no ledger entries needed

---

## Test Flow 7: Seller Recalls Order to Draft

1. Login as seller
2. Navigate to a `pending_payment` order detail
3. Click **"Recall"** → confirm
4. ✅ Order returns to "draft" status
5. ✅ Seller can edit items/fees again

---

## Test Flow 8: Dispute — Refund Only (Seller Resolves)

**Prerequisite:** Order with fulfilment `delivered`.

1. Login as buyer
2. Navigate to delivered order detail
3. Click **"Open Dispute"**
4. Select type: **"Refund Only"**
5. Enter reason: "Wrong item received"
6. Submit → ✅ dispute created, status "open"
7. ✅ Seller receives "Dispute Opened" notification

### Seller Resolves

8. Login as seller
9. Navigate to the disputed order (shows dispute panel)
10. Select resolution: "Full Refund"
11. Add notes: "Confirmed wrong item. Full refund issued."
12. Click **"Resolve"**
13. ✅ Dispute status → "resolved_refunded"
14. ✅ Order status → "refunded"
15. ✅ Ledger entry: refund created
16. ✅ Both parties notified

### Verify via API

```bash
curl -s http://localhost/api/v1/orders/{ORDER_ID}/disputes/{DISPUTE_ID} \
  -H "Authorization: Bearer {TOKEN}" | jq '.data.status'
# Expected: "resolved_refunded"
```

---

## Test Flow 9: Dispute — Return & Refund

1. Buyer opens dispute with type **"Return & Refund"**
2. ✅ Dispute status → "awaiting_return"
3. Buyer ships item back (off-platform)
4. Seller clicks **"Confirm Return Received"**
5. ✅ Dispute status → "return_received"
6. Seller resolves with "Partial Refund", amount: 5000
7. ✅ Dispute → "resolved_refunded"
8. ✅ Two ledger entries: refund (5000) + payout (remainder minus commission)

---

## Test Flow 10: Dispute Escalation → Admin Arbitration

1. Buyer opens dispute
2. Buyer (or seller) clicks **"Escalate to Admin"**
3. ✅ Dispute status → "escalated"
4. ✅ Admin receives notification

### Admin Arbitrates

5. Login as admin (`admin@gmail.com`)
6. Navigate to http://localhost/admin/disputes
7. ✅ Escalated dispute visible in queue
8. Click **"Review"** → arbitration page
9. ✅ Shows order context, dispute reason
10. Select resolution: "Full Refund"
11. Enter notes: "Buyer evidence conclusive. Full refund."
12. Click **"Submit Decision"**
13. ✅ Dispute resolved, order refunded, parties notified

---

## Test Flow 11: Seller Hub Dashboard & Reports

1. Login as seller
2. Navigate to http://localhost/hub/dashboard
3. ✅ Shows action-count cards (New RFQs, Orders In Progress, Unfulfilled, etc.)
4. Click on a card → navigates to relevant filtered list

### Customers

5. Navigate to http://localhost/hub/customers
6. ✅ Shows buyers who have orders with this shop
7. Click "New Order" next to a customer → Order Builder opens in standalone mode

### Reports

8. Navigate to http://localhost/hub/reports
9. ✅ Shows metrics: orders placed, gross sales, refunds, commission, net sales
10. Change time window (7/30/90 days) → ✅ metrics update

---

## Test Flow 12: Order Thread in Chat

**Prerequisite:** Order submitted for payment (creates thread automatically).

1. Login as seller
2. Navigate to http://localhost/hub/messages
3. ✅ Order thread visible in conversation (titled "Order #X")
4. ✅ System message with order card rendered inline
5. Login as buyer → same thread visible in http://localhost/account/messages
6. ✅ Status changes post new system messages in the thread

---

## API Testing (curl)

### Create Order from Quotation

```bash
# Get a token first
TOKEN=$(curl -s http://localhost/api/v1/... | jq -r '.token')

# Create order (as seller)
curl -X POST http://localhost/api/v1/hub/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"quotation_id": 1}' | jq
```

### List Buyer Orders by Tab

```bash
curl http://localhost/api/v1/orders?tab=to_pay \
  -H "Authorization: Bearer $TOKEN" | jq '.data[].status'
```

### Initiate Payment

```bash
curl -X POST http://localhost/api/v1/orders/1/payment \
  -H "Authorization: Bearer $TOKEN" | jq '.data.checkout_url'
```

### Create Fulfilment

```bash
curl -X POST http://localhost/api/v1/hub/orders/1/fulfilment \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"carrier":"JRS Express","tracking_code":"JRS123"}' | jq
```

### Mark Shipped

```bash
curl -X POST http://localhost/api/v1/hub/orders/1/fulfilment/ship \
  -H "Authorization: Bearer $TOKEN" | jq '.data.status'
```

### Open Dispute

```bash
curl -X POST http://localhost/api/v1/orders/1/disputes \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"type":"refund_only","reason":"Wrong item received"}' | jq
```

### Seller Hub Dashboard

```bash
curl http://localhost/api/v1/hub/dashboard \
  -H "Authorization: Bearer $TOKEN" | jq '.data'
```

### Leaderboards

```bash
curl "http://localhost/api/v1/hub/leaderboards?type=products&rank_by=revenue&window=30" \
  -H "Authorization: Bearer $TOKEN" | jq '.data'
```

---

## Verification Checklist

| # | Scenario | Status |
|---|----------|--------|
| 1 | Seller creates order from awarded quotation | ☐ |
| 2 | Seller creates standalone order from customer list | ☐ |
| 3 | Seller edits draft (items, fees, snapshot) | ☐ |
| 4 | Seller submits order for payment | ☐ |
| 5 | Seller recalls pending_payment order to draft | ☐ |
| 6 | Buyer sees order in "To Pay" tab | ☐ |
| 7 | Buyer pays (or simulate payment) | ☐ |
| 8 | Buyer cancels unpaid order | ☐ |
| 9 | Seller creates fulfilment with tracking | ☐ |
| 10 | Seller marks shipped | ☐ |
| 11 | Seller marks delivered | ☐ |
| 12 | Buyer confirms receipt → order completed | ☐ |
| 13 | Auto-accept fires after 7 days | ☐ |
| 14 | Buyer opens refund_only dispute | ☐ |
| 15 | Buyer opens return_refund dispute | ☐ |
| 16 | Seller confirms return received | ☐ |
| 17 | Seller resolves with full refund | ☐ |
| 18 | Seller resolves with partial refund | ☐ |
| 19 | Seller denies dispute | ☐ |
| 20 | Either party escalates to admin | ☐ |
| 21 | Admin arbitrates escalated dispute | ☐ |
| 22 | Buyer withdraws dispute | ☐ |
| 23 | Order thread created on submission | ☐ |
| 24 | Status messages appear in thread | ☐ |
| 25 | Notifications sent (email via Mailpit) | ☐ |
| 26 | Seller Hub dashboard shows correct counts | ☐ |
| 27 | Seller Hub customers list works | ☐ |
| 28 | Seller Hub reports show metrics | ☐ |
| 29 | Navigation links present (seller, buyer, admin) | ☐ |
| 30 | Non-seller cannot access hub endpoints | ☐ |

---

## Known Limitations (v1)

- **DLT Payment:** Requires real `DLT_CLIENT_ID`/`DLT_CLIENT_SECRET` in `.env` for live payment flow. Without them, use the manual simulation via tinker (see Flow 2).
- **One fulfilment per order:** Unique DB constraint prevents multiple shipments.
- **Payment is full upfront:** No deposit/partial payment support in v1.
- **Return tracking:** Off-platform only (seller manually confirms return receipt).
- **Leaderboards:** No caching in v1; may be slow with large datasets.
- **Broadcast (Reverb):** Real-time order status updates require Reverb running. Without it, pages need manual refresh to see status changes.
