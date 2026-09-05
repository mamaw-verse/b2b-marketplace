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

## DLT Quick Link: product_description is required despite docs saying optional
DLT's /api/v1/quick-link/session endpoint returns a 500 "Undefined array key product_description" if product_description is omitted, even though the official docs mark it as optional. Always send product_description — use product_name as the fallback value if you don't have a separate description. Same applies to merchant_transaction_id: must be strictly alphanumeric (no hyphens/dashes), max 45 chars, despite docs saying dash/underscore are allowed.

## DLT Quick Link: data.website is the merchant site, not the checkout URL
data.website in the Quick Link session response is the merchant's own website URL (e.g. 1halastore.com), NOT the DLT-hosted checkout page. The actual payor checkout URL is always: https://checkout-ql.dltc.com.ph/?merchant={merchant_account_id}&transaction_id={merchant_transaction_id}&amount={amount}&quantity=1. Never use data.website as the redirect target.
