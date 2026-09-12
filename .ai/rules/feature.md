---
paths:
  - 'tests/Feature/Subscription*'
---

# Feature

## DLT token cache and payment_method config must be reset in each test file's beforeEach
DltPaymentService caches the bearer token in the Laravel cache under 'dlt:bearer_token'. If a previous test (or test file) populated this cache entry, subsequent Http::fake() calls for /oauth/token will never fire because the cached token is reused. Always call Cache::forget('dlt:bearer_token') in beforeEach for any test file that uses Http::fake with DLT endpoints. Similarly, Config::set('marketplace.payment_method', ...) in one test persists across tests in the same process. Any test file that tests the DLT payment page (SubscriptionPaymentPage) must add Config::set('marketplace.payment_method', 'dlt') in beforeEach to guard against pollution from manual-payment tests. Never set Http::fake in beforeEach AND in per-test functions for the same URL pattern — the stacking behaviour is unreliable; use per-test-only fakes instead.
