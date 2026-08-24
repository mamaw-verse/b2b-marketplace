---
paths:
  - resources/views/livewire/company-hub/rfq-detail.blade.php
  - resources/views/livewire/company-hub/quotation-builder.blade.php
---

# Livewire Company Hub

## Create Quotation button requires in_progress status
The "Create Quotation" button is hidden until the seller clicks "Start Working" (status transitions from `reviewed` → `in_progress`). This applies to all three places the button appears: the actions bar, the empty quotations state, and the revised quotation prompt.

## Quotation builder includes a payout estimate panel
When the running total is > 0, a "Payout Estimate" panel shows below the total, computed server-side using `PayoutSetting::getCommissionFeeRate()` and `PayoutSetting::getTransactionFeeAmount()`. It shows: total, commission deduction, transaction fee deduction, and net estimated payout. Updates live as user changes qty/price (wire:model.live.debounce.300ms).
