---
name: dlt-payment-api
description: Integrate with the DLT (Digital Treasures Corp) Payment Collection API v3.0 — a Philippine payment gateway supporting card and e-wallet/APN payments (GCash, etc.), OAuth2 client-credentials auth, direct "Submit Payment" collection, and hosted "Quick Link" checkout sessions. Use this skill whenever the user asks to integrate DLT payments, DLT Collection API, DXP checkout, build a checkout/payment flow against checkout.dxp.dtic.com.ph, add GCash/card/e-wallet collection via DLT, generate a Quick Link payment session, handle DLT payment callbacks/webhooks, or write client code (any language) that calls /oauth/token, /api/v1/collection/apn/brands, /api/v1/collection/apn/submit, or /api/v1/quick-link/session. Also use for debugging DLT API errors, mapping DLT response payloads, or explaining the DLT payment collection journey.
---

# DLT Payment Collection API (v3.0)

Integration skill for DLT's Collection API — a payment gateway for the Philippines. Supports two collection modes: **direct API submission** (you build your own checkout UI) and **Quick Link** (DLT hosts a checkout session/UI for you).

Read `references/api-reference.md` for full endpoint specs, field tables, and sample payloads before writing integration code — it contains the authoritative request/response schemas extracted from the official documentation, including field types, which fields are conditionally required (e.g. card fields only when `is_card_payment` is true), and known quirks/version history.

## Quick orientation

**Base URL:** `https://checkout.dxp.dtic.com.ph/`
**Auth:** OAuth 2.0 Client Credentials Grant. Get a Bearer token from `/oauth/token`, then send it as `Authorization: Bearer <access_token>` on every subsequent call. Tokens expire (`expires_in` seconds) — always cache and refresh, never fetch a new token per-request.
**Content type:** All requests/responses are `application/json`.

## Two integration paths — pick one based on what the user wants

1. **Direct/custom checkout** (you build the payment form):
   `GET /api/v1/collection/apn/brands` → show available payment brands →
   `POST /api/v1/collection/apn/submit` → process payment (card fields required only if `is_card_payment: true`, plus full 3DS `device.*` fields for card payments) →
   handle response: if `is_card_payment` is true you get an immediate `payment_status` (PAID/PENDING/REJECTED); if false you get a `payment_url` to redirect the payer to (e.g. GCash QR/wallet), followed by a callback to `success_url`/`failure_url` if provided.

2. **Quick Link** (DLT hosts the checkout UI — less integration work):
   `POST /api/v1/quick-link/session` → get a `token` and a hosted checkout URL → redirect the payer there → DLT handles brand selection, card/KYC entry, and submission internally → optionally `GET /api/v1/quick-link/session?token=...` to poll session/payment state.

If the user hasn't specified which mode, ask, or default to Quick Link for the fastest integration (it needs far fewer fields and no card/3DS handling on your side).

## Before writing code

1. Confirm which language/framework/HTTP client the user wants (fetch, axios, requests, curl, etc.) — this skill is language-agnostic.
2. Confirm whether the integration needs card payments (triggers the mandatory 3DS `device.*` block) or e-wallet only.
3. Check whether the user already has `client_id`, `client_secret`, `merchant_account_id`, and `service_code` — these are issued by DLT and are not guessable; never invent placeholder-looking real-looking values, use obvious placeholders like `YOUR_CLIENT_ID`.
4. Look up exact field names/types/required-ness in `references/api-reference.md` rather than relying on memory — several fields are conditionally required and the API has been revised multiple times (see version history in the reference doc).

## Implementation guidance

- **Token caching**: store `access_token` + compute an expiry timestamp from `expires_in`; refresh proactively before it lapses (e.g. refresh when <60s remaining) rather than on 401.
- **Idempotency**: `merchant_transaction_id` is the merchant-side identifier — generate a unique one per payment attempt and store it for reconciliation; it's echoed back in `data.merchant_transaction_id`.
- **Card vs e-wallet branching**: check `is_card_payment` in the request you send, and correspondingly expect either an immediate `payment_status` (card) or a `payment_url` to redirect to (e-wallet) in the response.
- **Callbacks**: if `success_url`/`failure_url` are supplied, DLT redirects the payer there after e-wallet completion; if omitted, DLT shows its own thank-you page. Card payments respond synchronously and don't need these.
- **Channel field**: `submit payment` requires an integer `channel` — 1=API, 2=Quick Link, 3=WooCommerce, 4=Custom. Use 1 for direct API integrations built from scratch.
- **Error handling**: all endpoints return `{success, message, data}` on success. Errors follow `{success: false, message, data: null}` (general) or `{success: false, message, errors: [...]}` (validation) — branch on `success` first, then on presence of `errors` to distinguish validation errors from general errors.
- **Never log or hardcode `client_secret`, full card numbers, or CCV** in generated code — pull from env vars/secrets manager and note this to the user if they paste real credentials into the conversation.

## When generating code

Produce a small, focused client (a class/module) covering: token fetch+cache, get brands, submit payment (or create/get quick-link session), and typed request/response shapes if the target language supports them (TS interfaces, Python dataclasses/TypedDicts, etc.). Pull exact field lists from `references/api-reference.md` — don't guess at fields not documented there.
