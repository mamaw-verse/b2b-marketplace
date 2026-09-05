<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Client for the DLT Payment Collection API v4.0 (Hosted Checkout).
 *
 * All payment types — card and e-wallet — go through DLT's own hosted
 * checkout page. The merchant never collects card numbers or CVVs.
 *
 * Endpoints used:
 *  - POST /oauth/token                   — machine client credentials
 *  - GET  /api/v1/collection/apn/brands  — available payment brands
 *  - POST /api/v1/collection/apn/submit  — create a hosted checkout transaction
 *  - POST /api/v1/collection/apn/sync    — recovery: re-check a PENDING transaction
 *  - POST /api/v1/quick-link/session     — create a Quick Link hosted checkout session
 *  - GET  /api/v1/quick-link/session     — read back a Quick Link session
 *
 * Trust model: DLT derives the merchant from the OAuth token, not from
 * `merchant_account_id` in the request body. Do not treat `merchant_account_id`
 * as a security boundary — it is only a compatibility assertion.
 */
final class DltPaymentService
{
    private const TOKEN_CACHE_KEY = 'dlt:bearer_token';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly CacheRepository $cache,
    ) {}

    /**
     * Fetch (or return cached) Bearer access token via OAuth2 client credentials.
     */
    public function getBearerToken(): string
    {
        $cached = $this->cache->get(self::TOKEN_CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = $this->http
                ->baseUrl($this->baseUrl())
                ->asJson()
                ->acceptJson()
                ->post('/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => config('services.dlt.client_id'),
                    'client_secret' => config('services.dlt.client_secret'),
                ]);
        } catch (ConnectionException $e) {
            Log::error('DLT: OAuth token request — connection failed', [
                'endpoint' => $this->baseUrl().'/oauth/token',
                'error' => $e->getMessage(),
            ]);
            throw $this->unreachable($e);
        }

        if ($response->failed()) {
            Log::error('DLT: OAuth token request — HTTP error', [
                'endpoint' => $this->baseUrl().'/oauth/token',
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('DLT OAuth token request failed: '.$response->body());
        }

        $data = $response->json();
        $token = $data['access_token'];
        $ttl = max(($data['expires_in'] ?? 3600) - 60, 60);

        $this->cache->put(self::TOKEN_CACHE_KEY, $token, $ttl);

        return $token;
    }

    /**
     * GET /api/v1/collection/apn/brands
     *
     * Returns available payment brands (Visa, GCash, etc.).
     * `code` is opaque and time-sensitive — copy it verbatim into
     * `payment_brand_code` on Submit Payment; never cache it long-term.
     *
     * @return array<int, array{value: string, code: string, image: string|null, is_card: bool}>
     */
    public function getBrands(): array
    {
        $token = $this->getBearerToken();

        try {
            $response = $this->authorized($token)
                ->get($this->baseUrl().'/api/v1/collection/apn/brands', [
                    'merchant_account_id' => config('services.dlt.merchant_account_id'),
                    'service_code' => config('services.dlt.service_code'),
                ]);
        } catch (ConnectionException $e) {
            Log::error('DLT: Get brands — connection failed', [
                'endpoint' => $this->baseUrl().'/api/v1/collection/apn/brands',
                'error' => $e->getMessage(),
            ]);
            throw $this->unreachable($e);
        }

        $this->throwIfFailed($response);

        return $response->json('data', []);
    }

    /**
     * POST /api/v1/collection/apn/submit
     *
     * Creates a Hosted Checkout transaction. DLT returns `data.payment_url`;
     * redirect the payor there unconditionally — DLT handles card entry, OTP,
     * and wallet QR on its hosted page. Never send card numbers, CVVs, or
     * device/3DS fields — DLT rejects them.
     *
     * After submission the transaction status is PENDING. The authoritative
     * status update comes via the signed merchant webhook or a `/sync` call.
     * Do NOT trust the payor's browser return as payment confirmation.
     *
     * Required caller-supplied fields (inject merchant_account_id and
     * service_code automatically):
     *   merchant_transaction_id (max 45, ASCII alphanumeric/dash/underscore)
     *   time_offset (+/-HH:MM, e.g. "+08:00")
     *   channel (1 = API)
     *   amount (>0, ≤500,000.00)
     *   payment_brand (value from getBrands)
     *   payment_brand_code (code from getBrands — opaque, copy verbatim)
     *   first_name, last_name, email
     *   address_line_one, city_municipality, state_province_region,
     *   country_code, postal_code
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, message: string, data: array<string, mixed>}
     */
    public function submitPayment(array $payload): array
    {
        $token = $this->getBearerToken();

        $payload['merchant_account_id'] = config('services.dlt.merchant_account_id');
        $payload['service_code'] = config('services.dlt.service_code');

        $endpoint = $this->baseUrl().'/api/v1/collection/apn/submit';

        // Log as curl for easy debugging
        $safePayload = $payload;
        Log::info('DLT: Submit payment — request', [
            'curl' => sprintf(
                "curl -s -X POST '%s' \\\n  -H 'Authorization: Bearer %s' \\\n  -H 'Content-Type: application/json' \\\n  -H 'Accept: application/json' \\\n  -d '%s'",
                $endpoint,
                mb_substr($token, 0, 20).'...',
                json_encode($safePayload, JSON_PRETTY_PRINT)
            ),
        ]);

        try {
            $response = $this->authorized($token)
                ->post($endpoint, $payload);
        } catch (ConnectionException $e) {
            Log::error('DLT: Submit payment — connection failed', [
                'endpoint' => $endpoint,
                'merchant_transaction_id' => $payload['merchant_transaction_id'] ?? null,
                'amount' => $payload['amount'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $this->unreachable($e);
        }

        Log::info('DLT: Submit payment — response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $this->throwIfFailed($response);

        return $response->json();
    }

    /**
     * POST /api/v1/collection/apn/sync
     *
     * Authenticated recovery check for a stuck or inconclusive transaction.
     * Use when a transaction remains PENDING after the payor's browser return,
     * or when the webhook has not arrived in a reasonable time.
     *
     * PAID and REJECTED are terminal — /sync will never downgrade them.
     * Do NOT use this for routine polling; call it only for recovery.
     *
     * @return array{merchant_transaction_id: string, payment_status: string, provider_message: string|null, timestamp: string}
     */
    public function syncPaymentStatus(string $merchantTransactionId): array
    {
        $token = $this->getBearerToken();

        try {
            $response = $this->authorized($token)
                ->post($this->baseUrl().'/api/v1/collection/apn/sync', [
                    'merchant_transaction_id' => $merchantTransactionId,
                ]);
        } catch (ConnectionException $e) {
            Log::error('DLT: Sync payment status — connection failed', [
                'endpoint' => $this->baseUrl().'/api/v1/collection/apn/sync',
                'merchant_transaction_id' => $merchantTransactionId,
                'error' => $e->getMessage(),
            ]);
            throw $this->unreachable($e);
        }

        $this->throwIfFailed($response);

        return $response->json('data', []);
    }

    /**
     * POST /api/v1/quick-link/session
     *
     * Creates a Quick Link hosted checkout session. Returns an opaque token
     * (expires in 30 minutes) and a `website` URL — redirect the payor there.
     *
     * NOTE: `product_description` is required by DLT despite being documented
     * as optional — omitting it causes a 500 server error.
     *
     * OAuth credentials must never be placed in the payor's browser.
     * Status authority is the signed merchant webhook or `/sync`, not the
     * browser return.
     *
     * @return array{token: string, checkout_url: string, expires_at: string}
     */
    public function createQuickLinkSession(
        string $merchantTransactionId,
        float $amount,
        string $productName,
        string $successUrl,
        string $failureUrl,
    ): array {
        $token = $this->getBearerToken();

        try {
            $response = $this->authorized($token)
                ->post($this->baseUrl().'/api/v1/quick-link/session', [
                    'service_code' => config('services.dlt.service_code'),
                    'merchant_account_id' => config('services.dlt.merchant_account_id'),
                    'merchant_transaction_id' => $merchantTransactionId,
                    'amount' => round($amount, 2),
                    'currency' => 'PHP',
                    'product_name' => $productName,
                    'product_description' => $productName,
                    'quantity' => 1,
                    'success_url' => $successUrl,
                    'failure_url' => $failureUrl,
                ]);
        } catch (ConnectionException $e) {
            Log::error('DLT: Create Quick Link session — connection failed', [
                'endpoint' => $this->baseUrl().'/api/v1/quick-link/session',
                'merchant_transaction_id' => $merchantTransactionId,
                'error' => $e->getMessage(),
            ]);
            throw $this->unreachable($e);
        }

        $this->throwIfFailed($response);

        $sessionData = $response->json('data');

        // data.website is the merchant's own website — not the DLT-hosted checkout page.
        // The actual checkout URL is always built from the merchant_account_id, the
        // merchant_transaction_id, and the amount via checkout-ql.dltc.com.ph.
        return [
            'token' => $sessionData['token'],
            'checkout_url' => $this->buildCheckoutUrl(
                $sessionData['merchant_transaction_id'],
                (float) $sessionData['amount'],
            ),
            'expires_at' => $sessionData['expires_at'] ?? '',
        ];
    }

    /**
     * GET /api/v1/quick-link/session
     *
     * Reads back a Quick Link session by its opaque token. Use as a
     * convenience read after the payor returns from hosted checkout, but
     * treat `/sync` or the signed webhook as the authoritative status source.
     *
     * @return array<string, mixed>
     */
    public function getQuickLinkSession(string $sessionToken): array
    {
        $token = $this->getBearerToken();

        try {
            $response = $this->authorized($token)
                ->get($this->baseUrl().'/api/v1/quick-link/session', [
                    'token' => $sessionToken,
                ]);
        } catch (ConnectionException $e) {
            Log::error('DLT: Get Quick Link session — connection failed', [
                'endpoint' => $this->baseUrl().'/api/v1/quick-link/session',
                'session_token' => $sessionToken,
                'error' => $e->getMessage(),
            ]);
            throw $this->unreachable($e);
        }

        $this->throwIfFailed($response);

        return $response->json('data', []);
    }

    private function authorized(string $token): \Illuminate\Http\Client\PendingRequest
    {
        return $this->http->withToken($token)->asJson()->acceptJson();
    }

    private function baseUrl(): string
    {
        return mb_rtrim(config('services.dlt.base_url', 'https://checkout.dxp.dtic.com.ph'), '/');
    }

    /**
     * Wrap a connection-level failure in a RuntimeException so callers can
     * handle gateway-unreachable the same way as API errors.
     */
    private function unreachable(ConnectionException $e): RuntimeException
    {
        return new RuntimeException('DLT payment gateway is unreachable: '.$e->getMessage(), 0, $e);
    }

    /**
     * Assert the response is successful ({success: true}) and throw a
     * descriptive RuntimeException otherwise, logging the raw body.
     *
     * Branches: validation errors (errors array present) vs. general errors.
     */
    private function throwIfFailed(\Illuminate\Http\Client\Response $response): void
    {
        if ($response->successful() && $response->json('success') === true) {
            return;
        }

        if ($errors = $response->json('errors')) {
            Log::error('DLT: API validation error', [
                'status' => $response->status(),
                'message' => $response->json('message'),
                'errors' => $errors,
            ]);
            throw new RuntimeException('DLT validation error: '.json_encode($errors));
        }

        Log::error('DLT: API request failed', [
            'status' => $response->status(),
            'success_field' => $response->json('success'),
            'message' => $response->json('message'),
            'body' => $response->body(),
        ]);
        throw new RuntimeException('DLT request failed: '.($response->json('message') ?? $response->body()));
    }

    /**
     * Build the DLT Quick Link hosted checkout URL.
     *
     * data.website in the Quick Link response is the merchant's own website,
     * not the DLT checkout page. The actual payor-facing URL is always this
     * checkout-ql.dltc.com.ph pattern.
     */
    private function buildCheckoutUrl(string $merchantTransactionId, float $amount): string
    {
        $merchantAccountId = config('services.dlt.merchant_account_id');
        $formattedAmount = number_format($amount, 2, '.', '');

        return "https://checkout-ql.dltc.com.ph/?merchant={$merchantAccountId}&transaction_id={$merchantTransactionId}&amount={$formattedAmount}&quantity=1";
    }
}
