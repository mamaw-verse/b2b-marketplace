---
paths:
  - app/Livewire/CompanyHub/Orders/OrderBuilder.php
---

# Company Hub Orders

## OrderBuilder must preserve variant_id and variant_snapshot on save
When saving order items, the OrderBuilder must persist both `variant_id` (FK to the product variant) and `variant_snapshot` (JSON snapshot of variant attributes at time of order). A previous bug dropped variant_id because only a subset of fields were passed to create/update. Always include variant_id in the item save payload alongside price, quantity, and snapshot data.
