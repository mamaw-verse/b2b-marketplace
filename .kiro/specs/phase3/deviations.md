# Phase 3 — Implementation Deviations & Post-Design Changes

| | |
|---|---|
| **Last updated** | 2026-08-23 |
| **Context** | Changes made during implementation and functional testing that deviate from or extend the original `design.md` and `requirements.md` |

---

## 1. Payment Gateway: Direct API Instead of Quick Link

**Original design:** DLT Quick Link hosted checkout — redirect buyer to DLT's hosted payment page.

**What was built:** Direct API integration (`POST /api/v1/collection/apn/submit`) with an in-app checkout UI.

**Reason:** DLT does not provide a hosted checkout UI. The Quick Link flow documented in their API reference is not a standalone hosted page — it still requires the merchant to build the payment form. The Direct API is the only working integration path.

**Impact:**
- `PaymentPage` Livewire component now includes: brand selection grid, card payment form (with 3DS device fields), e-wallet billing form
- Card payments get synchronous response (PAID/REJECTED)
- E-wallet payments redirect buyer to wallet page (GCash, Maya, etc.) then back to success/failure URL
- `DltPaymentService` gained `getBrands()` and `submitPayment()` methods
- `dlt_payment_url` column changed from `VARCHAR(500)` to `TEXT` (e-wallet redirect URLs exceed 500 chars)

---

## 2. Product/Variant Snapshot on Order Items

**Original design:** `order_items` inherits only human-readable values (description, quantity, unit, unit_price). No variant link propagated.

**What was added:** `variant_id` (nullable FK) and `variant_snapshot` (JSONB) columns on `order_items`.

**Reason:** Needed to preserve product identity for display purposes (images, linking back to product page, tracking which variant was ordered).

**Impact:**
- Migration: `2026_08_23_041749_add_variant_snapshot_to_order_items.php`
- `OrderItem` model: added `variant_id`, `variant_snapshot` to fillable/casts, added `variant()` BelongsTo relationship
- `CreateOrderAction`: now copies `variant_id` and `variant_snapshot` from quotation items when creating order from awarded quotation

---

## 3. Notification URLs Are Recipient-Aware

**Original design:** Notifications link to a generic URL.

**What was changed:** `DisputeResolvedNotification` and `OrderCancelledNotification` (sent to both buyer and seller) now dynamically choose the URL based on the recipient:
- Buyer → `/account/orders/{id}`
- Seller → `/hub/orders/{id}`

**Reason:** Seller clicking a notification that links to `/account/orders/{id}` gets a 403 (buyer-only page).

---

## 4. OrderSubmittedForPayment Event Dispatch

**Original design:** `SubmitOrderAction` was designed to NOT dispatch the event (left to caller for testability).

**What was added:** Both `HubOrderController::submit()` and `OrderBuilder::submit()` now explicitly dispatch `OrderSubmittedForPayment::dispatch($order)` after successful submission.

**Reason:** Without this, the buyer never received the payment notification, and the order thread was never created in the conversation.

---

## 5. Messaging Tab Redesign

**Original design:** Simple scrollable thread tabs (all threads visible in a horizontal scroll).

**What was built:**
- "General" pinned as first tab
- Up to 2 active order tabs visible + 1 RFQ tab
- Remaining threads collapse into "More ▾" dropdown
- Status indicators on tabs (amber dot = needs attention, checkmark = resolved)
- Order summary strip below tab bar when order tab is active (item, price, status pill, "View order →" link)
- System messages with `order:{id}` / `dispute:{id}` now render proper card components inline

**Reason:** Tab bar grows unbounded with order history; raw `order:11` text was showing instead of cards.

---

## 6. Payment Race Condition Guard

**Original design:** Payment page checks order status only on mount.

**What was added:** Both `submitCardPayment()` and `submitEwalletPayment()` now call `$this->order->refresh()` and verify `status === 'pending_payment'` before proceeding.

**Reason:** If seller recalls the order while buyer is on the payment page, the payment could succeed against a recalled order.

---

## 7. DLT API Quirks (Undocumented Requirements)

These are not design deviations but critical implementation details not in the DLT docs:

| Field | Doc says | Reality |
|---|---|---|
| `product_description` | Optional | **Required** — server 500 without it |
| `merchant_transaction_id` | String, max 50 | Must be **alphanumeric only** (no hyphens/special chars) |
| `expiration_year` | Not specified | Must be **2 digits** (YY not YYYY) |
| `postal_code` | Not listed as mandatory | **Required** — validation error without it |
| `amount` (Submit Payment) | String, Decimal | Must be string `"100.00"` (not numeric) |
| E-wallet redirect URLs | — | Can exceed **1000+ characters** (GCash signed URLs) |

---

## 8. External Redirect Pattern (Livewire/Firefox Fix)

**Original approach:** `$this->redirect($url, navigate: false)` for external payment URLs.

**What was built:** Dispatch Livewire event + Alpine.js `window.location.href` handler.

**Reason:** Livewire's `redirect()` internally uses `window.open` which fails in Firefox with `"'open' called on an object that does not implement interface Window"`.

**Pattern:**
```php
// Component
$this->dispatch('redirect-to-payment', url: $paymentUrl);
```
```blade
<!-- View -->
<div x-data x-on:redirect-to-payment.window="window.location.href = $event.detail.url">
```

---

## 9. Confirm Receipt Button Visibility

**Original design:** Show "Confirm Receipt" when fulfilment status is `delivered`.

**What was added:** Also checks `$order->status === 'paid'` — hides the button after order is already completed.

---

## 10. Seller Escalate Action

**Original design:** Either party can escalate (documented in design.md §9.1).

**What was missing initially:** Seller's dispute panel had no escalate button.

**What was added:** `escalate()` method on `DisputePanel` Livewire component + "Escalate to Admin" button in the seller's dispute panel view.

---

## 11. Buyer Dispute Navigation

**Original design:** Buyer can view/escalate/withdraw disputes.

**What was missing initially:** No link from buyer's order detail to the dispute detail page.

**What was added:** Active dispute card in buyer's order detail view with "View Dispute" button linking to `/account/orders/{order}/dispute/{dispute}` where escalate/withdraw actions are available.

---

## 12. "Create Order" Button on Awarded Quotation

**Original design:** Described in design.md §4.1 — "Create Order" action on awarded quotation card.

**What was added:** In the seller's RFQ detail view, awarded quotations now show:
- "Create Order" button (if no order exists yet) → links to OrderBuilder with `?quotationId={id}`
- "View Order #{id}" button (if order already created)

---

## 13. Future Stub: Courier Delivery Confirmation

**Recorded as a future improvement** (not built in v1):

Currently seller self-certifies delivery ("Mark Delivered"). Future iteration should add a courier entity interface that can confirm delivery via:
- Webhook from courier API (JRS, LBC, J&T)
- QR code scan by delivery driver
- Buyer OTP/PIN at delivery point

See `.ai/rules/v1.md` for the full note.
