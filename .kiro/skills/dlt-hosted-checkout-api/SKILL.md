---
name: dlt-hosted-checkout-api
description: Integrate with the DLT Payment Collection API v4.0 — a Philippine payment gateway where ALL payment types (card and e-wallet/APN like GCash) go through DLT's own Hosted Checkout page, not a merchant-built form. Uses OAuth2 client-credentials auth (merchant-bound, not just a passed-in ID), "Submit Payment" to create a transaction and get a redirect payment_url, an authenticated "/sync" endpoint for status recovery, and signed merchant webhooks for payment status. Also supports "Quick Link" hosted checkout sessions. Use this skill whenever the user asks to integrate DLT payments, DLT Collection API v4, DXP hosted checkout, build a redirect-based checkout flow against checkout.dxp.dtic.com.ph, add GCash/card/e-wallet collection via DLT without building their own card form, verify/handle DLT signed payment webhooks, reconcile payment status via /sync, generate a Quick Link session, or write client code (any language) calling /oauth/token, /api/v1/collection/apn/brands, /api/v1/collection/apn/submit, /api/v1/collection/apn/sync, or /api/v1/quick-link/session. Also use for migrating an existing DLT integration off client-hosted card forms onto Hosted Checkout, debugging DLT API errors, or explaining the DLT v4.0 collection/status lifecycle.
---

# DLT Payment Collection API — Hosted Checkout (v4.0)

Integration skill for DLT's Collection API v4.0, where **DLT hosts the checkout page for every payment type** — card and e-wallet alike. The merchant never collects card numbers, CVVs, or 3DS device fingerprints; it only creates a transaction and redirects the payor to a DLT-hosted URL.

Read `references/api-reference.md` for full endpoint specs, field tables, and sample payloads before writing integration code — it is the authoritative source for request/response schemas, which fields are mandatory, the status lifecycle, webhook signature verification, and the version history vs. earlier (non-hosted-checkout) releases.

**If the user's request or existing code has card fields, `is_card_payment: true` branches, or 3DS `device.*` fields in a Submit Payment payload, that's a v3.0-era (client-hosted card form) pattern — flag it and offer to migrate it to this Hosted Checkout model rather than silently keeping it.**

## Quick orientation

**Base URL:** `https://checkout.dxp.dtic.com.ph/`
**Auth:** OAuth 2.0 Client Credentials Grant → `POST /oauth/token` → `Authorization: Bearer <access_token>` on every subsequent call. Cache and refresh proactively; never fetch a new token per request.
**Trust model:** DLT maps each machine client to exactly one merchant account. `merchant_account_id` in a request body is only a **compatibility assertion** — DLT authorizes based on the OAuth token, not the ID you send. Don't design your code as if `merchant_account_id` is the security boundary.
**Content type:** All requests/responses are `application/json`.
**No card data ever leaves the merchant's own systems.** Card entry happens on DLT's hosted page. Submitting PAN/CVV/expiry/device fingerprints to `/submit` gets rejected by DLT — don't build a form for them.

## The one integration path (all payment types)

There is no "pick a mode" branching by card vs. e-wallet in this version — every payment follows the same shape:

1. `GET /api/v1/collection/apn/brands` → show available payment brands (Visa, GCash, etc.) so the payor can pick one.
2. `POST /api/v1/collection/apn/submit` with `payment_brand` + `payment_brand_code` (copied verbatim from step 1) and the rest of the transaction/payor/billing fields — **no card fields, no device/3DS fields.**
3. Response includes `data.payment_url` — **redirect the payor there unconditionally.** DLT's hosted page handles card entry, OTP/3DS, or wallet QR/redirect internally.
4. Payor completes payment on DLT's page → DLT resolves the browser return via `callback_url` → merchant's default return URL → DLT's own fallback page (in that priority order).
5. The merchant's source of truth is the **signed webhook** (or an authenticated `/sync` call for recovery) — **never the payor's browser return**, and never a generic "poll by transaction ID" endpoint (there isn't one in this version).

**Quick Link** is a separate, optional shortcut for when you want a shareable hosted-checkout link without building the Submit Payment call yourself: `POST /api/v1/quick-link/session` → opaque token (30 min expiry) → send the payor to the DLT-hosted URL built from that token. It's still Hosted Checkout under the hood — same no-card-data rule applies, and OAuth credentials must never be placed in the payor's browser.

## Before writing code

1. Confirm which language/framework/HTTP client the user wants — this skill is language-agnostic.
2. Check whether the user already has `client_id`, `client_secret`, `merchant_account_id`, and `service_code` (currently `APNCOLLECTION`) — these are DLT-issued and not guessable. Use obvious placeholders like `YOUR_CLIENT_ID`; never invent real-looking values.
3. Ask (or infer from context) whether they need: (a) direct Submit Payment integration, (b) Quick Link, or (c) both — plus whether they need the webhook receiver built too, since that's a separate piece of server code from the checkout-initiation flow.
4. Look up exact field names/types/required-ness in `references/api-reference.md` rather than relying on memory, especially around required billing-address fields and the `amount` upper bound (500,000.00).

## Implementation guidance

- **Token caching:** store `access_token` + a computed expiry from `expires_in`; refresh before it lapses (e.g. at <60s remaining) rather than reacting to 401s.
- **`payment_brand_code` is opaque and time-sensitive** — fetch brands fresh per checkout session rather than caching codes long-term; copy the value unchanged into the submit request, never decode or persist it separately.
- **`callback_url` vs. the webhook notification URL — these are two different things, don't conflate them:**
  - `callback_url` (request field on Submit Payment) — where the payor's *browser* is redirected after completing payment on DLT's hosted page.
  - The merchant payment-notification URL (configured separately in Web Integration, test and live) — where DLT POSTs the *signed server-to-server webhook*.
  Only the webhook is authoritative for updating order state; `callback_url`/`success_url`/`failure_url` are UX-only.
- **Idempotency:** `merchant_transaction_id` is the merchant-side unique reference — generate one per payment attempt. Reusing it with an identical request replays the prior result; reusing it with different content returns HTTP 409 `TRANSACTION_REFERENCE_CONFLICT`. If DLT returns HTTP 503 `SUBMISSION_UNKNOWN`, **do not auto-resubmit** — call `/sync` or contact DLT support instead.
- **`/sync` is for recovery, not routine polling:** use it when a transaction is stuck `PENDING` or the browser return was inconclusive. It never downgrades a terminal (`PAID`/`REJECTED`) status. Pass `merchant_transaction_id`, not `data.transaction_id`.
- **Webhook signature verification is mandatory** before trusting any webhook payload:
  1. `HMAC-SHA256(secret, "<X-DLT-Timestamp>.<raw body>")`, formatted as `sha256=<hex>`.
  2. Compare to `X-DLT-Signature` in **constant time**.
  3. Reject stale timestamps (define a max age).
  4. Dedupe by `X-DLT-Event-Id` (or payload `event_id`).
  5. Only then update the order and return HTTP 2xx. Non-2xx/network errors get retried by DLT — a failed delivery does not roll back the payment, so don't assume "no webhook yet" means "not paid," reconcile with `/sync` if it's been a while.
- **Channel field:** Submit Payment requires integer `channel` — 1=API, 2=Quick Link, 3=WooCommerce, 4=Custom. **This project uses `channel: 4` (Custom).** Quick Link sets its own channel internally when it calls Submit Payment on your behalf, so Create/Get Quick Link Session don't take this field.
- **Amount bounds:** greater than 0, max 500,000.00, 0–2 decimal places, sent as a decimal (see reference doc for exact type per endpoint — Submit Payment vs. Quick Link session creation may differ in how strict the JSON type needs to be; check the reference before assuming).
- **Currency is optional** on Submit Payment and falls back merchant currency → DLT default `PHP` — don't hardcode `PHP` if the user's merchant account uses something else; just omit the field and let DLT resolve it, or pass it explicitly if the user wants to be unambiguous.
- **Billing address fields are mostly required** in v4.0 (`address_line_one`, `city_municipality`, `state_province_region`, `country_code`, `postal_code` — only `address_line_two` is optional). Don't treat this as a thin/optional block.
- **Error handling:** all endpoints return `{success, message, data}` on success, `{success: false, message, data: null}` for general errors, or `{success: false, message, errors: [...]}` for validation errors. Branch on `success` first, then on presence of `errors`.
- **Never log or hardcode `client_secret` or the webhook signing secret** in generated code — pull from env vars/secrets manager, and flag it to the user if they paste real credentials into the conversation.

## When generating code

Produce a small, focused client covering: token fetch+cache, get brands, submit payment (redirect-based), `/sync`, and — if the user needs it — a separate webhook receiver with signature verification. Add typed request/response shapes where the target language supports them (TS interfaces, Python dataclasses/TypedDicts, etc.). Pull exact field lists from `references/api-reference.md` rather than guessing at undocumented fields.

If the user's prompt or pasted code mixes in v3.0 patterns (card fields, `is_card_payment: true` branches, 3DS `device.*`), call that out explicitly and offer the Hosted Checkout equivalent instead of quietly merging both approaches.
