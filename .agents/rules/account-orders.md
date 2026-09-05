---
paths:
  - 'app/Livewire/CompanyHub/Fulfilment/**, resources/views/livewire/account/orders/**'
---

# Account Orders

## Fulfilment seeds fulfilment_items at full qty; buyer To Receive shows fulfilled qty
Partial fulfilment has no form yet, but the plumbing exists. FulfilmentPanel::createFulfilment() creates one FulfilmentItem per order item at the order item's FULL quantity, inside a DB::transaction with the Fulfilment header. This is the deferred half of spec S8.T3. The buyer OrderList eager-loads `fulfilments.fulfilmentItems`; the To Receive tab (order-list.blade.php) displays the fulfilled quantity per line (keyed by order_item_id), falling back to ordered qty, and shows "of {ordered} shipped" when a fulfilment item exists. The API FulfilmentController::store() does NOT yet seed items — if partial fulfilment ships, unify both paths via a shared action.
