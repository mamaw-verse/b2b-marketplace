---
paths:
  - 'app/Actions/{CreatePayoutBatchAction,ConfirmReceiptAction,ResolveDisputeAction}.php'
---

# Actions

## Payout batch releases the escrow_hold ledger entry, never the notional payout entry
Ledger has 3 types with fixed meanings (Phase 3 §4.5, Phase 4 §2.2): escrow_hold = GROSS order_total (real funds captured); payout = NOTIONAL, already net of the 1% reporting commission config('marketplace.commission_rate'), written at confirm-receipt for net-sales reporting ONLY; refund = buyer reversal. CreatePayoutBatchAction MUST read the escrow_hold entry (gross) and deduct transaction_fee + commission_fee_rate (PayoutSetting, 3%) once; payout_items.ledger_entry_id references the escrow_hold entry. Do NOT read/sum ledger_entries.type='payout' for releasable/disbursed amounts — that double-counts commission. EligiblePayouts and HubPayoutController::balance compute NET releasable the same way (escrow_hold minus fees) and threshold on net. Real disbursement is authoritative via payouts/payout_items/payout_disbursements, not the payout ledger type. Verified by tests/Feature/PayoutLifecycleTest + tests/Unit/FeeCalculationTest (76 pass). Docs: .kiro/specs/phase4/payout-flow-and-computations.md.
