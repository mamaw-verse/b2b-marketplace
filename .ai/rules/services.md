---
paths:
  - app/Services/DltPaymentService.php
---

# Services

## DLT Payment API integration quirks and requirements
- DLT uses Direct API mode (POST /api/v1/collection/apn/submit), NOT Quick Link hosted checkout.
- `product_description` is required even though docs say optional — DLT server crashes without it.
- `merchant_transaction_id` must be alphanumeric only (no hyphens, dashes, or special chars).
- `expiration_year` must be 2 digits (YY), not 4 (YYYY).
- `postal_code` is required for all payments.
- `dlt_payment_url` column must be TEXT — e-wallet redirect URLs (GCash etc.) exceed 500 chars.
- E-wallet redirects must use Alpine.js `window.location.href` via dispatched event — Livewire's `$this->redirect()` fails in Firefox for external URLs.
- Card payments return synchronous `payment_status` (PAID/PENDING/REJECTED); e-wallet returns `payment_url` for redirect.
- All requests need `->asJson()->acceptJson()` headers.
- Amount is sent as string decimal for Submit Payment ("100.00"), numeric for Quick Link (100.00).
