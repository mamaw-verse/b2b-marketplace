# DLT Payment Collection API — Reference (v3.0, published 08/15/2026)

Source: DLT Payment API Documentation, Collection API 3.0.

## Table of Contents
1. [Authentication](#authentication)
2. [Get Payment Brands](#get-payment-brands)
3. [Submit Payment](#submit-payment)
4. [Payment Collection Journey](#payment-collection-journey)
5. [Quick Link Process Flow](#quick-link-process-flow)
6. [Create Quick Link Session](#create-quick-link-session)
7. [Get Quick Link Session](#get-quick-link-session)
8. [Response Payload Shapes](#response-payload-shapes)
9. [Data Tables](#data-tables)
10. [Version History](#version-history)

---

## Authentication

OAuth 2.0 Client Credentials Grant. All protected endpoints require `Authorization: Bearer <access_token>`.

### Common Settings (Basic)

| Field | Value | Notes |
|---|---|---|
| Base URL | `https://checkout.dxp.dtic.com.ph/` | Listed as a plain static value in the source doc's Common Settings table — not explicitly labeled a "variable" there. However, the doc's own Postman example screenshots reference it as `{{base_url}}`, i.e. a Postman collection variable, in request URLs (e.g. `{{base_url}}/oauth/token`). This usage is only shown in screenshots, not stated in prose. |
| Client ID | To be issued by DLT | Static placeholder text only — never templated as a variable anywhere in the doc (including screenshots, where it appears as a literal sample string, e.g. `"client_id": "3"`). |
| Client Secret | To be issued by DLT | Same as Client ID — plain placeholder, never shown as a template variable. |

**Common headers (all requests):**
```
Content-Type: application/json
Accept: application/json
```

### Get Client Access Token

`POST /oauth/token`

Request body:

| Field | Sample Value | Mandatory | Type |
|---|---|---|---|
| grant_type | `client_credentials` (always this literal value) | Yes | String |
| client_id | issued by DLT | Yes | String |
| client_secret | issued by DLT | Yes | String |
| scope | — | No | String |

Response body:

| Field | Type |
|---|---|
| token_type | String (e.g. `Bearer`) |
| expires_in | Numeric (seconds until expiry, e.g. `31536000`) |
| access_token | String (JWT) |

Example response:
```json
{
  "token_type": "Bearer",
  "expires_in": 31536000,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9. ..."
}
```

Cache the token and its computed expiry; refresh before it lapses rather than fetching a new token per request.

---

## Get Payment Brands

`GET /api/v1/collection/apn/brands`

Request (query params):

| Field | Type | Required | Remarks |
|---|---|---|---|
| merchant_account_id | String | Yes | Reference ID issued by DLT to a merchant |
| service_code | String | Yes | Issued by DLT |

Response:

| Field | Type |
|---|---|
| success | Boolean |
| message | String |
| data | Array — list of brand objects |

Each item in `data` (observed shape from sample response — not in the PDF's formal response table, only visible in the Postman example screenshot):

| Field | Type | Notes |
|---|---|---|
| value | String | Display name, e.g. `"Discover"`, `"Diners"` |
| code | String | Opaque token to use as `payment_brand_code` in Submit Payment |
| image | String (URL) | Brand logo |
| is_card | Boolean | Whether this brand is a card brand (true) vs e-wallet (false) |

---

## Submit Payment

`POST /api/v1/collection/apn/submit`

### Request

**General fields**

| Parameter | Mandatory | Type | Notes |
|---|---|---|---|
| service_code | Yes | String | e.g. `APN-COLLECTION` |
| merchant_account_id | Yes | String | |
| merchant_transaction_id | Yes | String | Merchant-generated unique ID |
| is_card_payment | Yes | Boolean | Branches the whole flow |
| time_offset | Yes | String | e.g. `+08:00` |
| channel | Yes | Integer | See [Channel table](#channel-integer) |
| success_url | No | String | For custom integration |
| failure_url | No | String | For custom integration |

**Payment details**

| Parameter | Mandatory | Type | Notes |
|---|---|---|---|
| amount | Yes | String, Decimal | e.g. `"120.00"` |
| currency | Yes | String | e.g. `PHP` |
| payment_brand | Yes | String | e.g. `GCash` |
| payment_brand_code | Yes | String | Token from Get Payment Brands `code` |

**Product/service details** (all optional)

| Parameter | Mandatory | Type | Notes |
|---|---|---|---|
| product_name | No | String | e.g. `Bamboo Shampoo` |
| product_description | No | String | e.g. `For men/women 100ml` |
| product_reference_id | No | String | |

**Payor details**

| Parameter | Mandatory | Type |
|---|---|---|
| first_name | Yes | String |
| last_name | Yes | String |
| email | Yes | String |
| phone | No | String |
| address_line_one | Yes | String |
| address_line_two | Yes | String |
| city_municipality | Yes | String |
| state_province_region | Yes | String |
| country_code | Yes | String (e.g. `PH`) |

**Card details** — required only if `is_card_payment` is `true`:

| Parameter | Type |
|---|---|
| card_holder_name | String |
| card_number | Numeric |
| expiration_month | Numeric |
| expiration_year | Numeric |
| ccv | Numeric |

**3D Secure (3DS) details** — required only if `is_card_payment` is `true`:

| Parameter | Type |
|---|---|
| device.timezone | String |
| device.browser_color_depth | String |
| device.browser_language | String |
| device.browser_screen_height | String, Numeric |
| device.browser_screen_width | String, Numeric |
| device.os | String |
| device.browser_accept_header | String |
| device.user_agent | String |
| device.browser_javascript_enabled | Boolean |
| device.browser_java_enabled | Boolean |
| device.accept_content | String |

### Response

| Field | Type | Notes/e.g. |
|---|---|---|
| success | Boolean | `true` |
| message | String | `Successful` |
| data.is_card_payment | Boolean | `true` |
| data.payment_status | String | `PAID` \| `PENDING` \| `REJECTED` |
| data.merchant_transaction_id | String | e.g. `TEST001` |
| data.amount | String, Decimal | e.g. `1,299.00` |
| data.payment_url | String, Null | Redirect target for e-wallet/non-card flows |
| data.success_url | String, Null | e.g. `https://website.com/thanks?transaction_id=TEST001` |
| data.failure_url | String, Null | e.g. `https://website.com/error?transaction_id=TEST001` |
| data.timestamp | Unix timestamp | e.g. `2026-07-12 20:35:42` |

### Behavior by `is_card_payment`

- **`true` (card):** Response is synchronous — `payment_status` reflects the final/near-final outcome (`PAID`/`PENDING`/`REJECTED`) immediately. `payment_url` is null.
- **`false` (e-wallet/APN, e.g. GCash):** Response includes a `payment_url` to redirect the payer to (a payment wallet page / QR code). After completion, if `success_url`/`failure_url` were supplied, DLT redirects the payer there; otherwise DLT shows its own thank-you page.

---

## Payment Collection Journey

High-level flow for the direct/custom checkout integration (see Figure 1 in the source doc).

1. Client redirects the payer to the **Checkout Page**, which displays available payment brands (from `GET /api/v1/collection/apn/brands`).
2. Payer selects a payment option → shown a form for **KYC, Card & Payment Details**.
3. Form is submitted → sent to **Gateway Processing** (`POST /api/v1/collection/apn/submit`).
4. Gateway returns a response, branching on `is_card_payment`:

   - **Card payment (`is_card_payment: true`):**
     - API responds `200` — *"Request processed successfully"* — synchronously, with `data.payment_status` (`PAID`/`PENDING`/`REJECTED`).
     - Payer is redirected to the **Merchant Thank-You Page**.
     - Flow ends.

   - **Non-card / e-wallet payment (`is_card_payment: false`):**
     - API responds `200` with a `data.payment_url` redirect target.
     - Payer is sent to the external **Payment Wallet** (e.g. GCash) to complete payment.
     - After completion, the wallet redirects back, branching on whether a callback URL was supplied:
       - **`success_url`/`failure_url` provided:** payer is redirected to the **Merchant Thank-You Page**.
       - **Not provided:** payer is redirected to **DLT's own Thank-You Page**.
     - Flow ends.

**Implementation note:** card payments resolve synchronously in the Submit Payment response. Non-card/e-wallet payments are asynchronous — redirect the payer to `payment_url` and rely on `success_url`/`failure_url` (if supplied) to route them back; otherwise DLT shows its own generic thank-you page.

---

## Quick Link Process Flow

Quick Link lets DLT host the entire checkout UI so you don't build a payment form yourself.

High-level flow (see Figure 2 in the source doc):

1. Client app calls `POST /api/v1/quick-link/session` to create a session → receives a `token` and hosted Quick Link URL (e.g. `https://checkout-ql.dltc.com.ph/?merchant=ACCT-12345678&transaction_id=shop_test_12345678&amount=1299&quantity=1`).
2. Payer is redirected to that URL; the Quick Link UI loads the session, shows the checkout form, and validates + submits the payment on your behalf by internally calling `POST /api/v1/collection/apn/submit`.
3. If it's a card payment, the payer sees a success/error page directly.
4. If it's not a card payment, the payer is redirected to the external payment wallet (e.g. QR code), then back via callback — to your `success_url`/`failure_url` if provided, or to DLT's own thank-you page otherwise.

---

## Create Quick Link Session

`POST /api/v1/quick-link/session`

### Request

| Parameter | Mandatory | Type | Notes (e.g.) |
|---|---|---|---|
| service_code | Yes | String | Issued by DLT |
| merchant_account_id | Yes | String | Max 20 characters |
| merchant_transaction_id | Yes | String | Max 50 characters |
| product_name | No | String | Max 100 characters |
| product_description | No | String | Max 255 characters |
| amount | Yes | Numeric, Decimal | e.g. `99`, `99.00` |
| quantity | No | Integer | e.g. `1`, `2`, `3` |
| currency | Yes | String | 3 characters, e.g. `PHP` |
| success_url | No | String | Max 255 characters |
| failure_url | No | String | Max 255 characters |
| custom_fields | No | Array | |
| custom_fields.*.key | No | String | Max 50 characters |
| custom_fields.*.value | No | String | Max 255 characters |

### Response

| Field | Type |
|---|---|
| success | Boolean |
| message | String |
| data.token | String |
| data.merchant | String |
| data.website | String |
| data.service_code | String |
| data.amount | Decimal, String |
| data.currency | String, Null |
| data.product_name | String, Null |
| data.product_description | String, Null |
| data.quantity | Integer, Null |
| data.payment_brands | Array, Null |
| data.custom_fields | Array, Null |
| data.success_url | String, Null |
| data.failure_url | String, Null |
| data.expires_at | Unix timestamp |

Example response payload (from source doc, abbreviated):
```json
{
  "success": true,
  "message": "Successful",
  "data": {
    "token": "<UUID>",
    "service_code": "APN-COLLECTION",
    "amount": "1,299.00",
    "currency": "PHP",
    "product_name": "Bamboo Shampoo",
    "product_description": "<string>",
    "quantity": 1,
    "payment_brands": "<array>"
  }
}
```

---

## Get Quick Link Session

`GET /api/v1/quick-link/session`

### Request

| Field | Mandatory | Type | Notes (e.g.) |
|---|---|---|---|
| token | Yes | String, UUID | The token returned by Create Quick Link Session |

### Response

Same shape as the Create Quick Link Session response `data` object:

| Field | Type |
|---|---|
| success | Boolean |
| message | String |
| data.token | String |
| data.service_code | String |
| data.amount | Decimal, String |
| data.currency | String, Null |
| data.product_name | String, Null |
| data.product_description | String, Null |
| data.quantity | Integer, Null |
| data.payment_brands | Array, Null |
| data.custom_fields | Array, Null |
| data.success_url | String, Null |
| data.failure_url | String, Null |
| data.expires_at | Unix timestamp |

Use this to poll/check session and payment state (e.g. after redirect back from the hosted checkout).

---

## Response Payload Shapes

All endpoints follow one of these three shapes.

**Successful**

| Field | Type | Notes/e.g. |
|---|---|---|
| success | Boolean | `true` |
| message | String | `Successful` |
| data | Array, Null | Endpoint-specific payload |

**General Error**

| Field | Type | Notes/e.g. |
|---|---|---|
| success | Boolean | `false` |
| message | String | `Something went wrong` |
| data | Array, Null | |

**Validation Error**

| Field | Type | Notes/e.g. |
|---|---|---|
| success | Boolean | `false` |
| message | String | `Validation error` |
| errors | Array | Per-field validation error details |

**Client branching logic:** check `success` first; if `false`, check for an `errors` array to distinguish a validation error (fix the request) from a general error (retry/alert).

---

## Data Tables

### Channel (Integer)

Used in the `channel` field of Submit Payment.

| Value | Definition | Notes/e.g. |
|---|---|---|
| 1 | API | Direct/custom integration |
| 2 | Quick Link | |
| 3 | WooCommerce | |
| 4 | Custom | |

---

## Version History

| Version | Description | Updated by | Date |
|---|---|---|---|
| 1.0.0 | Initial release | Arnel Miraflores | 09/26/2025 |
| 1.2.0 | Payment journey; Submit payment endpoint | Arnel Miraflores | 10/06/2025 |
| 1.2.1 | Transaction fee details added on payment submission response payload | Arne Miraflores *(sic — spelled without "l" in source PDF)* | 10/09/2025 |
| 1.3 | Added "v1" to the API base route; added `service_code` as required; updated query params for payment brand endpoint | Arnel Miraflores | 06/29/2026 |
| 2.0 | Added quick-link session + response payload formats | Arnel Miraflores | 07/08/2026 |
| 2.1 | Added Get Quick Link Session endpoint | Arnel Miraflores | 07/08/2026 |
| 2.2 | Renamed `redirect_url` to `payment_url`; updated Submit Payment response payload | Arnel Miraflores | 07/12/2026 |
| 3.0 (current) | Updated Submit Payment request payload; added `channel`, `success_url`, `failure_url`; added Data Tables | Arnel Miraflores | 08/15/2026 |

**Note:** If integrating against an older deployment, verify whether the `v1` route prefix, `service_code` requirement, `payment_url` field name (vs. legacy `redirect_url`), and `channel`/`success_url`/`failure_url` fields are actually present — these were all introduced/renamed across versions 1.3–3.0.