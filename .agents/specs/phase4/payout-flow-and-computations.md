# Payouts — Flow & Computations

Explains how money moves from a buyer's payment to a seller's bank account, the
ledger entries involved, and every computation. Source of truth: Phase 3
requirements §4.5/§4.8 (escrow, notional payout) and Phase 4 requirements §2.2
/ §3 (payout batches, fees, disbursement).

---

## 1. Money lifecycle at a glance

```
Buyer pays ──► ESCROW (funds held) ──► buyer confirms receipt ──► order COMPLETED
                                                                       │
                                          (after dispute-hold window)  ▼
                                                            ADMIN creates payout BATCH
                                                                       │
                                                            releases via DLT disbursement
                                                                       ▼
                                                              seller's bank account
```

A **dispute** at any point before completion can divert funds back to the buyer
as a **refund** (full or partial).

---

## 2. The ledger (`ledger_entries`)

An append-only audit log (no `updated_at`). One order can have many entries.
There are three `type`s, and their meanings are **not interchangeable**:

| type          | Written when                             | Amount stored                              | Meaning |
|---------------|------------------------------------------|--------------------------------------------|---------|
| `escrow_hold` | Payment succeeds (`ProcessOrderPaymentAction`) | **GROSS** — the full `order_total`     | Real funds captured from the buyer and held. **This is the entry the payout batch releases.** |
| `payout`      | Buyer confirms receipt (`ConfirmReceiptAction`) | **NET** — `order_total × (1 − reporting_commission)` | **Notional / reporting only.** Marks the amount recognized as the seller's for net-sales reporting. It is **not** a real disbursement and no bank transfer has happened. |
| `refund`      | Dispute resolved with a refund (`ResolveDisputeAction`) | Refund amount (full or partial)     | Buyer reversal. |

> ⚠️ **Never sum `ledger_entries.type = 'payout'` as "amount released."** It is
> notional and already net of the reporting commission. Real disbursement is
> tracked by `payouts` / `payout_items` / `payout_disbursements`. Summing the
> `payout` ledger type double-counts commission (Phase 4 requirements §3.4).

### Why two commission-looking numbers exist
- **Reporting commission** (`config('marketplace.commission_rate')`, default
  **1%**) — applied once at confirm-receipt, only to compute the notional
  `payout` ledger entry and net-sales reports.
- **Payout commission fee** (`PayoutSetting::commission_fee_rate`, default
  **3%**) — the real fee deducted from the gross escrow when an admin creates a
  payout batch. This, plus the flat transaction fee, is what the seller actually
  pays to be disbursed.

These are independent knobs. The disbursement math below uses only the **payout
commission fee** and the **transaction fee**, computed from the **gross
`escrow_hold`** amount.

---

## 3. Fee & payout computation

Settings live in `payout_settings` (see `PayoutSettingsSeeder`):

| Setting                     | Default   | Meaning |
|-----------------------------|-----------|---------|
| `dispute_hold_days`         | `7`       | Days after `completed_at` before funds become releasable. |
| `minimum_payout_threshold`  | `500.00`  | Minimum **net** releasable balance before a batch can be created. |
| `transaction_fee_amount`    | `15.00`   | Flat fee per order. |
| `commission_fee_rate`       | `0.03`    | Percentage fee per order, on the gross escrowed amount. |

### Per-order formula (in `CreatePayoutBatchAction`)

```
escrowed_amount = <the order's escrow_hold ledger entry amount>   (= order_total)
transaction_fee = transaction_fee_amount                          (flat, e.g. 15.00)
commission_fee  = round(escrowed_amount × commission_fee_rate, 2, HALF_UP)
net_amount      = round(escrowed_amount − transaction_fee − commission_fee, 2)
```

`net_amount` must be **> 0** or the batch is rejected for that order.

### Batch totals

```
gross_amount            = Σ escrowed_amount
total_transaction_fees  = Σ transaction_fee
total_commission_fees   = Σ commission_fee
net_amount              = Σ net_amount              ← the amount disbursed to the bank
orders_count            = number of orders in the batch
```

The batch is rejected unless `net_amount ≥ minimum_payout_threshold`.

### Worked example

Order total **₱8,500.00**, defaults (₱15 flat, 3%):

| Step            | Value |
|-----------------|-------|
| escrowed_amount | 8,500.00 |
| transaction_fee | 15.00 |
| commission_fee  | 8,500.00 × 0.03 = 255.00 |
| **net_amount**  | 8,500.00 − 15.00 − 255.00 = **8,230.00** |

Three eligible orders of ₱8,500 / ₱3,200 / ₱4,750:

```
gross            = 16,450.00
transaction_fees = 45.00            (3 × 15)
commission_fees  = 493.50           (3% of each, summed)
net (disbursed)  = 15,911.50
```

(The notional `payout` ledger entries for those same orders would read 8,415.00
/ 3,168.00 / 4,702.50 — net of the 1% *reporting* commission — and are ignored
by the batch.)

---

## 4. Eligibility rules

An order is eligible for a payout batch when **all** hold:

1. `status = 'completed'`.
2. `completed_at ≤ now() − dispute_hold_days`.
3. No open dispute (`open`, `awaiting_return`, `return_received`, `escalated`).
4. Not already in a `payout_items` row (never disbursed before).
5. The seller's `SellerPayoutAccount.verification_status = 'verified'`.
6. The seller's cumulative **net** releasable ≥ `minimum_payout_threshold`.

The admin **eligible list** (`/admin/payouts/eligible`), the seller **balance**
endpoint (`/api/v1/hub/payouts/balance`), and `CreatePayoutBatchAction` all
compute the releasable figure the **same way**: from each order's `escrow_hold`
entry, minus fees, filtered and thresholded on **net**. This keeps the displayed
"Net Releasable" identical to what the batch will actually disburse.

---

## 5. Payout batch state machine

`payouts.status`:

```
pending ──release──► processing ──DLT success──► completed
   ▲                     │
   │                     └──DLT failure──► failed ──retry──► processing ──► …
   └───────────────────────────────────────────────┘
```

- **pending** — batch created, not yet released.
- **processing** — `ReleasePayoutAction` called; DLT disbursement in flight.
- **completed** — DLT confirmed; `payout_disbursements` row `succeeded`.
- **failed** — DLT rejected; `failure_reason` set; admin can **retry**.

Each release attempt writes a `payout_disbursements` row (`succeeded` /
`failed`) with the DLT reference/transaction IDs and raw gateway response.

Guards: only `pending` or `failed` batches can be released; the seller's payout
account must still be `verified`.

---

## 6. Disputes & refunds (interaction with payouts)

Handled by `ResolveDisputeAction`:

- **Full refund** — `refund` ledger entry for `order_total`; order → `refunded`.
- **Partial refund** — `refund` ledger entry for the refunded portion, **plus** a
  notional `payout` ledger entry for the remainder
  (`(order_total − refund) × (1 − commission)`); order → `refunded`.
- **Denied** — no fund movement; order stays `completed`.

A `return_refund` dispute can only be refunded once its status is
`return_received`; a denial can be issued at any time.

**Refund from payout (claw-back).** If a dispute is resolved *after* an order was
already disbursed (its `payout_items` row sits in a `completed` batch), a
`refund` ledger entry is recorded against the order. The order's net ledger
position (`escrow_hold − refund`) goes negative, surfacing on the admin
financial ledger as funds owed back. The original completed payout is left
untouched (it already happened).

---

## 7. Where each amount is authoritative

| Question                          | Read from |
|-----------------------------------|-----------|
| How much did we capture?          | `ledger_entries.escrow_hold` |
| Net sales for reporting?          | `ledger_entries` (`escrow_hold` − `refund` − reporting commission) |
| How much is releasable now?       | eligible `escrow_hold` entries − fees (net) |
| How much was actually disbursed?  | `payouts.net_amount` WHERE `status = completed` / `payout_items.net_amount` |
| Was this order paid out already?  | existence of a `payout_items` row for the order |

---

## 8. Try it with seeded data

`PayoutDemoSeeder` builds a self-contained playground (no RFQ→order needed):

```bash
vendor/bin/sail artisan db:seed --class=PayoutDemoSeeder
```

Then log in as admin (`admin@gmail.com` / `password`) and visit:

- **Queue:** `/admin/payouts` — batches in `pending` / `processing` / `completed` / `failed`.
- **Eligible:** `/admin/payouts/eligible` — Bismillah shows 3 eligible orders,
  **net ₱15,911.50**; click *Create Batch*, then *Release* on the detail page.

Seeded shops:

| Shop | Account | What it demonstrates |
|------|---------|----------------------|
| Bismillah Trading Co. | verified | Eligible orders (batchable); plus not-yet-eligible (inside hold window), open-dispute, escrow-only, full-refund, partial-refund. |
| Al-Barakah Provisions | verified | Existing batches in every status; **refund-from-payout** claw-back. |
| Sahaba Wholesale | pending | Qualifying order that is correctly **gated out** (unverified account). |

Ledger semantics in the seed match production exactly: `escrow_hold` = gross,
`payout` = notional net of the 1% reporting commission, and every
`payout_items.ledger_entry_id` references the order's `escrow_hold` entry.

---

## 9. Key files

| Concern | File |
|---------|------|
| Escrow capture | `app/Actions/ProcessOrderPaymentAction.php` |
| Notional payout on completion | `app/Actions/ConfirmReceiptAction.php` |
| Dispute refund / partial payout | `app/Actions/ResolveDisputeAction.php` |
| Batch creation & fees | `app/Actions/CreatePayoutBatchAction.php` |
| Disbursement / retry | `app/Actions/ReleasePayoutAction.php` |
| Admin queue / detail / eligible | `app/Livewire/Admin/Payouts/*` |
| Seller balance & payouts API | `app/Http/Controllers/Api/V1/HubPayoutController.php` |
| Settings | `app/Models/PayoutSetting.php`, `database/seeders/PayoutSettingsSeeder.php` |
| Demo data | `database/seeders/PayoutDemoSeeder.php` |
| Tests | `tests/Feature/PayoutLifecycleTest.php`, `tests/Unit/FeeCalculationTest.php` |
