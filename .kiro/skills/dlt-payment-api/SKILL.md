---
name: dlt-payment-api
description: Integrate with the DLT Payment Collection API v3.0 — a Philippine payment gateway supporting card and e-wallet/APN payments (GCash, etc.), OAuth2 client-credentials auth, direct "Submit Payment" collection, and hosted "Quick Link" checkout sessions. Use this skill whenever the user asks to integrate DLT payments, DLT Collection API, DXP checkout, build a checkout/payment flow against checkout.dxp.dtic.com.ph, add GCash/card/e-wallet collection via DLT, generate a Quick Link payment session, handle DLT payment callbacks/webhooks, or write client code (any language) that calls /oauth/token, /api/v1/collection/apn/brands, /api/v1/collection/apn/submit, or /api/v1/quick-link/session. Also use for debugging DLT API errors, mapping DLT response payloads, or explaining the DLT payment collection journey.
---

# DLT Payment Collection API (v3.0)

Integration skill for DLT's Collection API — a payment gateway for the Philippines. Supports two collection modes: **direct API submission** (you build your own checkout UI) and **Quick Link** (DLT hosts a checkout session/UI for you).

Read `references/api-reference.md` for full endpoint specs, field tables, and sample payloads before writing integration code — it contains the authoritative request/response schemas extracted from the official documentation, including field types, which fields are conditionally required (e.g. card fields only when `is_card_payment` is true), and known quirks/version history.

## Quick orientation

**Base URL:** `https://checkout.dxp.dtic.com.ph/`
**Auth:** OAuth 2.0 Client Credentials Grant. Get a Bearer token from `/oauth/token`, then send it as `Authorization: Bearer <access_token>` on every subsequent call. Tokens expire (`expires_in` seconds) — always cache and refresh, never fetch a new token per-request.
**Content type:** All requests/responses are `application/json`.
**Required on every endpoint:** `service_code` and `merchant_account_id` are mandatory on all four API endpoints (brands, submit, create quick-link session, get quick-link session) — both are issued by DLT, not guessable.

## Two integration paths — pick one based on what the user wants

1. **Direct/custom checkout** (you build the payment form):
   `GET /api/v1/collection/apn/brands` → show available payment brands →
   `POST /api/v1/collection/apn/submit` → process payment (card fields required only if `is_card_payment: true`, plus full 3DS `device.*` fields for card payments) →
   handle response: if `is_card_payment` is true you get an immediate `payment_status` (PAID/PENDING/REJECTED); if false you get a `payment_url` to redirect the payer to (e.g. GCash QR/wallet), followed by a callback to `success_url`/`failure_url` if provided.

2. **Quick Link** (DLT hosts the checkout UI — less integration work):
   `POST /api/v1/quick-link/session` → get a `token` and a hosted checkout URL → redirect the payer there → DLT handles brand selection, card/KYC entry, and submission internally (via its own internal call to Submit Payment) → optionally `GET /api/v1/quick-link/session?token=...` to poll session/payment state.

If the user hasn't specified which mode, ask, or default to Quick Link for the fastest integration (it needs far fewer fields and no card/3DS handling on your side).

## Before writing code

1. Confirm which language/framework/HTTP client the user wants (fetch, axios, requests, curl, etc.) — this skill is language-agnostic.
2. Confirm whether the integration needs card payments (triggers the mandatory 3DS `device.*` block) or e-wallet only.
3. Check whether the user already has `client_id`, `client_secret`, `merchant_account_id`, and `service_code` — these are issued by DLT and are not guessable; never invent real-looking placeholder values, use obvious placeholders like `YOUR_CLIENT_ID`.
4. Look up exact field names/types/required-ness in `references/api-reference.md` rather than relying on memory — several fields are conditionally required and the API has been revised multiple times (see version history in the reference doc).

## Implementation guidance

- **Token caching**: store `access_token` + compute an expiry timestamp from `expires_in`; refresh proactively before it lapses (e.g. refresh when <60s remaining) rather than on 401.
- **`amount` type differs by endpoint — do not assume one shape for both**:
  - Submit Payment: `amount` is **String, Decimal** — send it quoted, e.g. `"120.00"`.
  - Create Quick Link Session: `amount` is **Numeric, Decimal** — send it unquoted, e.g. `99` or `99.00`.
  Sending the wrong JSON type to either endpoint is a likely source of validation errors — check this explicitly when generating request-building code.
- **Idempotency**: `merchant_transaction_id` is the merchant-side identifier — generate a unique one per payment attempt and store it for reconciliation; it's echoed back in `data.merchant_transaction_id`.
- **Card vs e-wallet branching**: check `is_card_payment` in the request you send, and correspondingly expect either an immediate `payment_status` (card) or a `payment_url` to redirect to (e-wallet) in the response.
- **Callbacks**: if `success_url`/`failure_url` are supplied, DLT redirects the payer there after e-wallet completion; if omitted, DLT shows its own thank-you page. Card payments respond synchronously and don't need these.
- **Channel field**: Submit Payment requires an integer `channel` — 1=API, 2=Quick Link, 3=WooCommerce, 4=Custom. Use 1 for direct API integrations built from scratch. This field belongs to Submit Payment only — Create/Get Quick Link Session don't take it (Quick Link sets its own channel value internally when it calls Submit Payment on your behalf).
- **Get Payment Brands item shape**: the `value`/`code`/`image`/`is_card` fields on each item in `data[]` come only from an example response in the source docs, not a formal schema table — treat as observed-but-not-contractually-guaranteed when writing parsing code (e.g. don't assume the field list is exhaustive or stable).
- **Error handling**: all endpoints return `{success, message, data}` on success. Errors follow `{success: false, message, data: null}` (general) or `{success: false, message, errors: [...]}` (validation) — branch on `success` first, then on presence of `errors` to distinguish validation errors from general errors.
- **Never log or hardcode `client_secret`, full card numbers, or CCV** in generated code — pull from env vars/secrets manager and note this to the user if they paste real credentials into the conversation.

## When generating code

Produce a small, focused client (a class/module) covering: token fetch+cache, get brands, submit payment (or create/get quick-link session), and typed request/response shapes if the target language supports them (TS interfaces, Python dataclasses/TypedDicts, etc.). Pull exact field lists from `references/api-reference.md` — don't guess at fields not documented there.

## Implementation Notes (discovered during integration — Aug 2026)

### Critical quirks NOT in the official docs

1. **`product_description` is REQUIRED** even though docs mark it optional. DLT server returns 500 `"Undefined array key \"product_description\""` without it.

2. **`merchant_transaction_id` must be alphanumeric only** — no hyphens, dashes, underscores, or special characters. Regex: `/^[a-zA-Z0-9]+$/`. ULIDs and UUIDs with hyphens will be rejected.

3. **`expiration_year` must be 2 digits** (e.g. `"26"` not `"2026"`). The docs don't specify this clearly.

4. **`postal_code` is REQUIRED** even though not listed as mandatory in the docs. Omitting it returns a validation error.

5. **E-wallet redirect URLs can exceed 1000+ characters** (GCash URLs contain long signed query parameters). Store in TEXT column, not VARCHAR(500).

6. **Amount format differs by endpoint:**
   - Submit Payment (`/api/v1/collection/apn/submit`): String decimal `"100.00"`
   - Quick Link (`/api/v1/quick-link/session`): Numeric `100.00`

### Config required (`.env`)

```
DLT_BASE_URL=https://checkout.dxp.dtic.com.ph
DLT_CLIENT_ID=<issued by DLT>
DLT_CLIENT_SECRET=<issued by DLT>
DLT_MERCHANT_ACCOUNT_ID=<issued by DLT>
DLT_SERVICE_CODE=APN-COLLECTION
```

### Test cards

| Brand | Number | Expiry | CVV |
|---|---|---|---|
| Visa | 4111 1111 1111 1111 | Any future | Any 3 digits |
| MasterCard | 5105 1051 0510 5100 | Any future | Any 3 digits |
| Diners | 3696 190300 0009 | Any future | 123 |

### Flow summary (Direct API — what this project uses)

1. `GET /api/v1/collection/apn/brands` → show payment brand grid
2. User selects brand → show card form OR e-wallet billing form
3. `POST /api/v1/collection/apn/submit` with all fields
4. **Card:** immediate response `payment_status: PAID|REJECTED` → show result
5. **E-wallet:** response has `payment_url` → redirect user to wallet → user returns to `success_url`/`failure_url`
