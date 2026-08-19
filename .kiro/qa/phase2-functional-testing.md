# Functional Testing Guide — Phase 2: Negotiation (RFQ, Quotation, Chat)

| | |
|---|---|
| **Status** | 🟢 Ready — all Epics (E11–E18) complete |
| **Last updated** | 2026-08-18 |
| **Prerequisite** | Phase 1 seeded data, queue worker running, frontend built |

---

## Setup & Rebuild

### Fresh Start (full rebuild)

```bash
# Start all services (app, redis, postgres, reverb, worker, etc.)
vendor/bin/sail up -d

# Reset database with all Phase 1 + Phase 2 migrations
vendor/bin/sail artisan migrate:fresh --seed

# Build frontend (required for Livewire/Tailwind/Echo changes)
vendor/bin/sail npm run build

# Verify all required containers are running
docker compose ps
# Should show: laravel.test, pgsql, redis, reverb, worker (all "Up")
```

### After Pulling New Code

```bash
vendor/bin/sail composer install
vendor/bin/sail artisan migrate
vendor/bin/sail npm run build
# Restart worker + reverb to pick up code changes
docker compose restart worker reverb
```

### Required Services for Full Functionality

| Service | Purpose | Start command |
|---------|---------|---------------|
| `laravel.test` | Main app | `vendor/bin/sail up -d` |
| `redis` | Queue + cache + sessions | Auto-started with sail |
| `pgsql` | Database | Auto-started with sail |
| `reverb` | WebSocket server (real-time chat + notifications) | Auto-started via compose |
| `worker` | Queue processing (email notifications, background jobs) | `docker compose up worker -d` |

### Verify Services

```bash
# All containers running
docker compose ps

# Reverb accepting connections
curl -s -o /dev/null -w "%{http_code}" http://localhost:8080
# Expected: 404 (WS server, doesn't serve HTTP)

# Worker processing jobs
docker logs b2b-marketplace-worker-1 --since 1m
# Should show "Processing:" lines when jobs come in

# Redis queue empty (jobs being processed)
vendor/bin/sail artisan tinker --execute 'echo \Illuminate\Support\Facades\Redis::llen("queues:default");'
# Expected: 0 (or very low if jobs just arrived)
```

### Run Phase 2 Tests Only

```bash
vendor/bin/sail artisan test --compact --filter="BuyerProfileTest|RfqTest|SellerRfqQueueTest|QuotationTest|QuotationActionsTest|ChatTest|TimerJobsTest|NotificationDeliveryTest|NegotiationPipelineTest"
```

### Run All Tests

```bash
vendor/bin/sail artisan test --compact
```

### Verify Scheduler

```bash
vendor/bin/sail artisan schedule:list
# Should show: SellerNoBidTimeoutJob (03:00), RfqAutoCloseJob (03:15), QuotationExpiryJob (03:30)
```

---

## Test Accounts

| Role | Email | Password | Notes |
|------|-------|----------|-------|
| Seeded buyer/user | `test@example.com` | `password` | Has no buyer profile yet (create during test) |
| Admin | Create via tinker (see below) | `password` | Not needed for Phase 2 flows |
| Seller | Any user who completed onboarding | `password` | Must have an active shop |

### Create a Seller Account Quickly

```bash
vendor/bin/sail artisan tinker --execute '
$seller = App\Models\User::factory()->create([
    "first_name" => "Seller",
    "last_name" => "One",
    "email" => "seller@example.com",
    "password" => "password",
]);
$shop = App\Models\Shop::factory()->create([
    "user_id" => $seller->id,
    "storefront_name" => "Halal Foods Co.",
    "status" => "active",
]);
echo "Seller: seller@example.com / password (Shop: {$shop->storefront_name})\n";
'
```

### Create a Buyer Account Quickly

```bash
vendor/bin/sail artisan tinker --execute '
$buyer = App\Models\User::factory()->create([
    "first_name" => "Buyer",
    "last_name" => "One",
    "email" => "buyer@example.com",
    "password" => "password",
]);
App\Models\BuyerProfile::factory()->create([
    "user_id" => $buyer->id,
    "business_name" => "Manila Supplies Inc.",
]);
echo "Buyer: buyer@example.com / password (Profile: Manila Supplies Inc.)\n";
'
```

---

## Access Points

| Page | URL | Role |
|------|-----|------|
| Buyer Profile | http://localhost/account/profile | Buyer |
| My RFQs (buyer) | http://localhost/account/rfqs | Buyer |
| Create RFQ | http://localhost/account/rfqs/create?shop_id=1&title=Test | Buyer |
| Buyer Messages | http://localhost/account/messages | Buyer |
| Seller RFQ Queue | http://localhost/hub/rfqs | Seller |
| Seller Messages | http://localhost/hub/messages | Seller |
| Product Detail (Request Quote) | http://localhost/products/{id} | Public/Buyer |
| Mailpit (emails) | http://localhost:8025 | — |

---

## Test Flow 1: Buyer Profile Creation

1. Login as `buyer@example.com` (or any user without a buyer profile)
2. Navigate to http://localhost/account/profile
3. Fill in:
   - Business name: "Manila Bakery Supplies"
   - Billing address: address, city, province, zip
   - Shipping address: check "same as billing" or enter different
   - Contact phone/email (optional)
4. Click "Save Profile"
5. ✅ Toast confirmation "Buyer profile saved."
6. ✅ Refresh page → data persisted

---

## Test Flow 2: Send RFQ from Product Page

**Prerequisite:** Buyer profile exists, at least one published product with an active shop.

1. Navigate to http://localhost/products
2. Click on any product
3. See the green "Request Quote" section at the bottom
4. Click **"Request Quote"** button
   - If not logged in → redirected to login, then back
   - If no buyer profile → redirected to /account/profile with flash message
5. RFQ form opens:
   - Title pre-filled from product name
   - Shop auto-selected (hidden field)
6. Edit title, add description, optionally attach files
7. Click **"Send RFQ"**
8. ✅ Redirected to RFQ detail page, status = "open"
9. ✅ Appears in buyer's list at http://localhost/account/rfqs

---

## Test Flow 3: Seller Reviews RFQ

1. Login as the seller whose shop received the RFQ
2. Navigate to http://localhost/hub/rfqs
3. ✅ New RFQ appears with "New" (received) status badge
4. Click the RFQ
5. ✅ Status auto-transitions to "Reviewed" on first view
6. See: buyer business name, shipping address, RFQ title, description, attachments
7. Click **"Start Working"** → status becomes "In Progress"
8. OR click **"Decline"** → enter reason → confirm → status becomes "No Bid"

---

## Test Flow 4: Seller Submits Quotation

**Prerequisite:** An RFQ in "Reviewed" or "In Progress" status.

1. From the seller RFQ detail page, click **"Create Quotation"**
2. Quotation builder opens:
   - Add line items (description, quantity, unit, unit price) — subtotal auto-calculated
   - Click "+ Add Item" for more rows
   - Add notes/terms (optional)
3. Click **"Save Draft"** → quotation saved but NOT visible to buyer
4. Edit line items if needed (only while draft)
5. Click **"Submit Quotation"** → confirmation modal appears (immutability warning)
6. Click **"Confirm & Submit"**
7. ✅ Quotation becomes "submitted", valid for 14 days
8. ✅ Buyer snapshot captured (business name + addresses at submission time)
9. ✅ Buyer receives notification (check Mailpit at http://localhost:8025)
10. ✅ Quotation visible to buyer on their RFQ detail page
11. ✅ Quotation appears on seller's RFQ detail under "Your Quotations" with status

---

## Test Flow 5: Buyer Awards/Rejects Quotation

1. Login as buyer
2. Navigate to http://localhost/account/rfqs/{id}
3. See submitted quotation(s) with: total amount, line items table, validity date
4. Click **"Award"** on a quotation
5. ✅ Status → "awarded" (green card on seller side with confirmation message)
6. ✅ Seller receives notification (check Mailpit)
7. Alternatively: click **"Reject"** → status → "rejected"
8. ✅ Seller sees rejection feedback + "Submit Revised Quotation" prompt

---

## Test Flow 6: Close RFQ

1. Login as buyer on RFQ detail page
2. Click **"Close RFQ"** → confirmation modal
3. Confirm
4. ✅ RFQ status → "closed"
5. ✅ Any remaining "submitted" quotations → "not_awarded"
6. ✅ Already awarded/rejected quotations unchanged
7. ✅ Seller receives notification

---

## Test Flow 7: Chat / Messaging (Real-Time)

**Prerequisite:** An RFQ has been sent (conversation auto-created). `reverb` container running.

1. Login as buyer → http://localhost/account/messages (or /hub/messages)
2. ✅ Conversation with the seller's shop appears in left panel
3. Click conversation → threads appear as tabs (General + RFQ-linked)
4. Click RFQ thread → see RFQ card rendered inline
5. Type a message → click Send (or press Enter)
6. ✅ Message appears instantly for sender
7. **Open second browser** logged in as seller → http://localhost/hub/messages
8. Select same conversation → same thread
9. ✅ Buyer's message visible
10. Seller types reply → sends
11. ✅ **Buyer sees reply in real-time WITHOUT page refresh**
12. ✅ Unread badge shows on conversations with new messages

**Real-time architecture:**
- Chat uses `ShouldBroadcastNow` → instant delivery, no queue worker needed
- Broadcasts on private channel `conversation.{id}` via Reverb WebSocket
- Alpine.js subscribes when a thread is selected, calls Livewire to refresh
- Notification bell uses queued notifications → requires `worker` container

**If real-time chat doesn't work, check:**
1. Console: `window.Echo.connector.pusher.connection.state` → must be `"connected"`
2. Network tab → WS filter → should see `ws://localhost:8080/app/local-reverb-key`
3. Reverb running: `docker compose ps | grep reverb`
4. `.env` has explicit values: `VITE_REVERB_PORT=8080` (NOT `"${REVERB_PORT}"`)
5. Rebuilt after env changes: `vendor/bin/sail npm run build` + hard refresh

---

## Test Flow 8: Timer Jobs (Auto-Close, No-Bid, Expiry)

### Test No-Bid Timeout (7 days)

```bash
# Set a recipient's last_activity to 8 days ago
vendor/bin/sail artisan tinker --execute '
App\Models\RfqRecipient::where("status", "received")->update(["last_activity_at" => now()->subDays(8)]);
echo "Set recipient activity to 8 days ago.\n";
'

# Run the job
vendor/bin/sail artisan tinker --execute '(new App\Jobs\SellerNoBidTimeoutJob)->handle();'

# Check result
vendor/bin/sail artisan tinker --execute '
echo App\Models\RfqRecipient::pluck("status", "id")->toJson() . "\n";
'
```

### Test RFQ Auto-Close (7 days)

```bash
# Set an RFQ's last_activity to 8 days ago
vendor/bin/sail artisan tinker --execute '
App\Models\Rfq::where("status", "open")->update(["last_activity_at" => now()->subDays(8)]);
echo "Set RFQ activity to 8 days ago.\n";
'

# Run the job
vendor/bin/sail artisan tinker --execute '(new App\Jobs\RfqAutoCloseJob)->handle();'

# Check
vendor/bin/sail artisan tinker --execute '
echo App\Models\Rfq::pluck("status", "id")->toJson() . "\n";
'
```

### Test Quotation Expiry (14 days)

```bash
# Set a quotation's valid_until to yesterday
vendor/bin/sail artisan tinker --execute '
App\Models\Quotation::where("status", "submitted")->update(["valid_until" => now()->subDay()]);
echo "Set quotation valid_until to yesterday.\n";
'

# Run the job
vendor/bin/sail artisan tinker --execute '(new App\Jobs\QuotationExpiryJob)->handle();'

# Check
vendor/bin/sail artisan tinker --execute '
echo App\Models\Quotation::pluck("status", "id")->toJson() . "\n";
'
```

---

## Test Flow 9: Notifications (Email + Bell)

**Prerequisite:** `worker` container running (`docker compose up worker -d`).

All notifications are queued — they require the worker to process them.

### Bell Notification (Real-Time Badge)
1. Have seller's page open with the notification bell visible
2. As buyer: award a quotation, or send a new RFQ
3. ✅ Seller's bell badge updates in real-time (number increments)
4. Click bell → dropdown shows recent notifications
5. Click "Mark all read" → badge clears

### Email Notifications
Check Mailpit at http://localhost:8025

| Event | Recipient | Email Subject |
|-------|-----------|---------------|
| RFQ created | Seller | "New RFQ Received: [title]" |
| Seller no-bid (timeout) | Buyer | "Seller did not respond to your RFQ" |
| Quotation submitted | Buyer | "New Quotation Received" |
| Quotation awarded | Seller | "Your Quotation Was Awarded!" |
| Quotation rejected | Seller | "Quotation Rejected" |
| Quotation expired | Both | "Quotation Expired" |
| RFQ closed | Seller | "RFQ Closed: [title]" |
| New message | Counterpart | "New message from [name]" |

**If notifications don't arrive:**
- Worker must be running: `docker compose ps | grep worker`
- Check failed jobs: `vendor/bin/sail artisan queue:failed`
- Check Redis queue length: `vendor/bin/sail artisan tinker --execute 'echo \Illuminate\Support\Facades\Redis::llen("queues:default");'`
- Restart worker: `docker compose restart worker`

---

## Test Flow 10: API Endpoints (curl)

```bash
# Login (get session cookie)
curl -c cookies.txt -X POST http://localhost/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"buyer@example.com","password":"password"}'

# Get buyer profile
curl -b cookies.txt http://localhost/api/v1/buyer-profile | jq

# Create RFQ
curl -b cookies.txt -X POST http://localhost/api/v1/rfqs \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"title":"Test RFQ","description":"Need 100 units","shop_id":1}' | jq

# List buyer's RFQs
curl -b cookies.txt http://localhost/api/v1/rfqs | jq

# Close an RFQ
curl -b cookies.txt -X POST http://localhost/api/v1/rfqs/1/close | jq

# Seller: List received RFQs
curl -b cookies.txt http://localhost/api/v1/hub/rfqs | jq

# Seller: Decline
curl -b cookies.txt -X POST http://localhost/api/v1/hub/rfqs/1/decline \
  -H "Content-Type: application/json" \
  -d '{"decline_reason":"Cannot fulfil"}' | jq

# Award a quotation
curl -b cookies.txt -X POST http://localhost/api/v1/quotations/1/award | jq

# List conversations
curl -b cookies.txt http://localhost/api/v1/conversations | jq

# Send a message
curl -b cookies.txt -X POST http://localhost/api/v1/threads/1/messages \
  -H "Content-Type: application/json" \
  -d '{"body":"Hello, any update?"}' | jq
```

---

## Troubleshooting

| Issue | Fix |
|---|---|
| "Request Quote" button not showing | Product must have a shop attached and be published |
| "Buyer profile required" error on RFQ | Complete /account/profile first |
| RFQ not in seller queue | Verify shop_id in the RFQ matches the seller's shop |
| Cannot create quotation | Seller must have an active shop; RFQ recipient must not be in no_bid state |
| Quotation submit fails | Must have at least 1 line item |
| Cannot award/reject | Only works on "submitted" quotations; check for race condition (expired/withdrawn) |
| Bell notifications not updating | `worker` container must be running: `docker compose up worker -d` |
| Chat not real-time | Check WS connection in browser Network tab; Reverb must be running |
| No WebSocket in Network tab | `.env` VITE_REVERB_PORT must be `8080` (not `"${REVERB_PORT}"`); rebuild JS |
| WS connects but no messages | Verify you're on the correct conversation/thread; check Console for errors |
| Timer jobs not running | Run manually: `vendor/bin/sail artisan schedule:run` or dispatch via tinker |
| Dark mode not working | Run `vendor/bin/sail npm run build` and hard refresh (Cmd+Shift+R) |
| Blank page / Vite error | `vendor/bin/sail npm run build` |
| Tests failing on fresh DB | Run `vendor/bin/sail artisan migrate:fresh` (uses testing DB for tests) |
| Worker not processing | `docker compose restart worker`; check `docker logs b2b-marketplace-worker-1` |
