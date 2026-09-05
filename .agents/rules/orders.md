---
paths:
  - 'resources/views/livewire/company-hub/orders/**'
---

# Orders

## Orders require an awarded quotation — no standalone creation
The OrderBuilder component requires either `quotationId` or `buyerUserId` in mount(). If neither is provided, it redirects to orders index with an error. The orders index page (OrderQueue) has no "New Order" button — instead it shows an "Awarded Quotations" table at the top for quotations awaiting order creation. The "Create Order" action is available from: (1) the seller's RFQ detail page on awarded quotations, (2) the orders index awarded table.
