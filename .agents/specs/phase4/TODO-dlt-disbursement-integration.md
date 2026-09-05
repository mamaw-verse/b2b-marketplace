# TODO: DLT Disbursement API Integration

| | |
|---|---|
| **Status** | 🔴 Blocked — awaiting DLT API documentation |
| **Priority** | High (required for real payouts) |
| **Depends on** | DLT providing Disbursement API contract |
| **Blocked since** | 2026-08-24 |

---

## Context

Phase 4 implemented the full payout lifecycle (batch creation, fee calculation, eligibility rules, admin UI, seller views, notifications) but the actual **bank transfer** is mocked. The `MockDltDisbursementService` always returns success with fake reference IDs. No real money moves.

DLT's **Collection API v3.0** (inbound payments from buyers) is fully documented and integrated. Their **Disbursement API** (outbound bank transfers to sellers) has no available documentation.

---

## Current Architecture (ready for integration)

```
app/Contracts/DltDisbursementServiceInterface.php   ← Interface (2 methods)
app/Services/MockDltDisbursementService.php         ← Always succeeds (active)
app/Services/DltDisbursementService.php             ← Stub that always fails
app/ValueObjects/DisbursementResult.php             ← Return type for disburse()
app/ValueObjects/DisbursementStatus.php             ← Return type for checkStatus()
```

**Interface contract:**
```php
interface DltDisbursementServiceInterface
{
    public function disburse(Payout $payout, SellerPayoutAccount $account): DisbursementResult;
    public function checkStatus(string $dltTransactionId): DisbursementStatus;
}
```

**Service binding** (`AppServiceProvider`):
```php
// Toggle via .env
DLT_DISBURSEMENT_ENABLED=false  // uses MockDltDisbursementService
DLT_DISBURSEMENT_ENABLED=true   // uses DltDisbursementService (real)
```

---

## Implementation Tasks (when API docs are available)

### 1. Obtain & Document API Contract

- [ ] Get DLT Disbursement API documentation (endpoints, auth, schemas)
- [ ] Confirm authentication method (OAuth2 client credentials? Same as Collection API?)
- [ ] Confirm base URL (sandbox + production)
- [ ] Document request/response payloads for:
  - Initiate disbursement (bank transfer)
  - Check disbursement status
  - Webhook/callback for async status updates (if supported)
- [ ] Confirm supported banks list matches our curated Philippine bank list
- [ ] Confirm bank_code format requirements

### 2. Add Environment Variables

- [ ] Add to `.env.example`:
  ```
  DLT_DISBURSEMENT_ENABLED=false
  DLT_DISBURSEMENT_BASE_URL=https://api.dxp.dtic.com.ph
  DLT_DISBURSEMENT_CLIENT_ID=
  DLT_DISBURSEMENT_CLIENT_SECRET=
  DLT_DISBURSEMENT_WEBHOOK_SECRET=
  ```
- [ ] Add to `config/services.php` (or dedicated `config/dlt.php`):
  ```php
  'dlt_disbursement' => [
      'enabled' => env('DLT_DISBURSEMENT_ENABLED', false),
      'base_url' => env('DLT_DISBURSEMENT_BASE_URL'),
      'client_id' => env('DLT_DISBURSEMENT_CLIENT_ID'),
      'client_secret' => env('DLT_DISBURSEMENT_CLIENT_SECRET'),
      'webhook_secret' => env('DLT_DISBURSEMENT_WEBHOOK_SECRET'),
  ],
  ```

### 3. Implement Real Service

- [ ] Implement `app/Services/DltDisbursementService.php`:
  - OAuth2 token acquisition (likely same pattern as `DltPaymentService`)
  - `disburse()`: POST to DLT's transfer endpoint with:
    - Seller bank details (from `SellerPayoutAccount`)
    - Amount (`Payout.net_amount`)
    - Unique transaction ID (`dlt_transaction_id` — generated UUID)
    - Callback URL for status updates
  - `checkStatus()`: GET/POST to DLT's status endpoint
  - Handle HTTP errors, timeouts, rate limits gracefully
  - Log all requests/responses for audit
- [ ] Update `AppServiceProvider` binding (already conditional on env)

### 4. Implement Webhook Handler (if DLT supports async callbacks)

- [ ] Create `POST /api/webhooks/dlt-disbursement` route
- [ ] Create `DltDisbursementWebhookController`:
  - Verify webhook signature/secret
  - Look up `PayoutDisbursement` by `dlt_transaction_id`
  - Update disbursement status (succeeded/failed)
  - Update parent `Payout` status accordingly
  - Dispatch `PayoutCompletedNotification` or `PayoutFailedNotification`
- [ ] Add route to `routes/api.php` (no auth middleware — webhook)
- [ ] Add webhook secret validation middleware

### 5. Handle Edge Cases

- [ ] **Timeout handling**: If DLT doesn't respond within X seconds, mark disbursement as `pending` (not failed) and implement a polling job
- [ ] **Idempotency**: Ensure `dlt_transaction_id` is unique per attempt — retries generate new IDs
- [ ] **Account changes mid-batch**: If seller updates bank details while payout is `pending`, use the **latest verified** account at disbursement time (already specified in requirements §6.6)
- [ ] **Partial failures**: If DLT rejects the transfer (invalid account, insufficient partner balance), store the error in `gateway_response` JSONB for admin investigation
- [ ] **Reconciliation**: Consider a scheduled job that polls DLT for status of `pending` disbursements older than X hours

### 6. Testing

- [ ] Create `tests/Feature/DltDisbursementIntegrationTest.php`:
  - Mock HTTP responses (Guzzle mock handler)
  - Test successful disbursement flow end-to-end
  - Test failure scenarios (invalid account, network error, timeout)
  - Test retry after failure
  - Test webhook processing (if applicable)
  - Test token refresh/expiry handling
- [ ] Test against DLT sandbox environment (manual QA)
- [ ] Test with real bank account in staging before production

### 7. Production Readiness

- [ ] Set `DLT_DISBURSEMENT_ENABLED=true` in production `.env`
- [ ] Configure real DLT credentials
- [ ] Set up monitoring/alerting for failed disbursements
- [ ] Document the bank transfer SLA (how long until funds arrive)
- [ ] Update the Phase 4 QA doc with real disbursement testing flow

---

## Data Flow (when implemented)

```
Admin clicks "Release" on /admin/payouts/{id}
    → ReleasePayoutAction::execute()
        → Payout status: pending → processing
        → DltDisbursementService::disburse()
            → POST to DLT API (bank_code, account_number, amount, reference)
            → DLT returns: reference_id, status
        → If success:
            → PayoutDisbursement created (status: succeeded)
            → Payout status: processing → completed
            → Notify seller (PayoutCompletedNotification)
        → If failure:
            → PayoutDisbursement created (status: failed)
            → Payout status: processing → failed
            → Notify admins (PayoutFailedNotification)
            → Admin can retry
```

---

## Related Files

| File | Purpose |
|------|---------|
| `app/Contracts/DltDisbursementServiceInterface.php` | Interface to implement |
| `app/Services/DltDisbursementService.php` | Real service (currently stub) |
| `app/Services/MockDltDisbursementService.php` | Mock (currently active) |
| `app/ValueObjects/DisbursementResult.php` | Return type |
| `app/ValueObjects/DisbursementStatus.php` | Status check return type |
| `app/Actions/ReleasePayoutAction.php` | Orchestrates the release |
| `app/Models/PayoutDisbursement.php` | Tracks each DLT attempt |
| `app/Providers/AppServiceProvider.php` | Service binding |
| `app/Services/DltPaymentService.php` | Reference — Collection API (already working) |

---

## Notes

- The Collection API integration (`app/Services/DltPaymentService.php`) can serve as a reference for auth patterns, HTTP client setup, and error handling.
- The DLT Payment Collection API uses OAuth2 client credentials (`/oauth/token`) — the Disbursement API likely uses the same auth server.
- Bank codes in `seller_payout_accounts.bank_code` should match whatever format DLT expects. Verify during integration.
- All amounts are in PHP (Philippine Peso). Confirm DLT accepts amounts in centavos or pesos.
