<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

/**
 * Client for the DLT Payment Collection API v3.0.
 *
 * Endpoints:
 *  - POST /oauth/token
 *  - GET  /api/v1/collection/apn/brands
 *  - POST /api/v1/collection/apn/submit
 *  - POST /api/v1/quick-link/session (legacy, kept for reference)
 *  - GET  /api/v1/quick-link/session
 */
final class DltPaymentService
{
    private const TOKEN_CACHE_KEY = 'dlt:bearer_token';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly CacheRepository $cache,
    ) {}

    /**
     * Get a cached Bearer access token from DLT OAuth2.
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
            throw $this->unreachable($e);
        }

        if ($response->failed()) {
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
     * Returns available payment brands (cards + e-wallets).
     * Each brand has: value (display name), code, image (URL), is_card (bool).
     *
     * @return array<int, array{value: string, code: string, image: string, is_card: bool}>
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
            throw $this->unreachable($e);
        }

        $this->throwIfFailed($response);

        return $response->json('data', []);
    }

    /**
     * POST /api/v1/collection/apn/submit
     *
     * Submit a payment. Caller provides all fields except merchant_account_id
     * and service_code (injected here).
     *
     * For card payments (is_card_payment=true):
     *   - Response is synchronous: data.payment_status = PAID|PENDING|REJECTED
     *   - data.payment_url is null
     *
     * For e-wallet payments (is_card_payment=false):
     *   - Response includes data.payment_url to redirect the payer to
     *   - After completion, DLT redirects to success_url/failure_url
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, message: string, data: array<string, mixed>}
     */
    public function submitPayment(array $payload): array
    {
        $token = $this->getBearerToken();

        $payload['merchant_account_id'] = config('services.dlt.merchant_account_id');
        $payload['service_code'] = config('services.dlt.service_code');

        try {
            $response = $this->authorized($token)
                ->post($this->baseUrl().'/api/v1/collection/apn/submit', $payload);
        } catch (ConnectionException $e) {
            throw $this->unreachable($e);
        }

        $this->throwIfFailed($response);

        return $response->json();
    }

    /**
     * Create a Quick Link hosted checkout session (legacy — kept for reference).
     *
     * @return array{token: string, checkout_url: string, expires_at: int}
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
                    'quantity' => 1,
                    'success_url' => $successUrl,
                    'failure_url' => $failureUrl,
                ]);
        } catch (ConnectionException $e) {
            throw $this->unreachable($e);
        }

        if ($response->failed()) {
            throw new RuntimeException('DLT Quick Link session creation failed: '.$response->body());
        }

        $data = $response->json();

        if (! ($data['success'] ?? false)) {
            throw new RuntimeException('DLT Quick Link session failed: '.($data['message'] ?? 'Unknown error'));
        }

        $sessionData = $data['data'];

        $checkoutUrl = $sessionData['website'] ?? $this->buildFallbackCheckoutUrl(
            $merchantTransactionId,
            $amount,
        );

        return [
            'token' => $sessionData['token'],
            'checkout_url' => $checkoutUrl,
            'expires_at' => $sessionData['expires_at'] ?? 0,
        ];
    }

    /**
     * Fetch a Quick Link session to verify it exists (tamper guard).
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
            throw $this->unreachable($e);
        }

        if ($response->failed()) {
            throw new RuntimeException('DLT Get Quick Link session failed: '.$response->body());
        }

        $data = $response->json();

        if (! ($data['success'] ?? false)) {
            throw new RuntimeException('DLT session verification failed: '.($data['message'] ?? 'Unknown error'));
        }

        return $data['data'] ?? [];
    }

    private function authorized(string $token)
    {
        return $this->http->withToken($token)->asJson()->acceptJson();
    }

    private function baseUrl(): string
    {
        return mb_rtrim(config('services.dlt.base_url', 'https://checkout.dxp.dtic.com.ph'), '/');
    }

    /**
     * Translate a low-level HTTP connection failure (DNS resolution, timeout,
     * TLS handshake, network down) into the RuntimeException the callers
     * already handle, so the gateway being unreachable never surfaces as an
     * unhandled 500.
     */
    private function unreachable(ConnectionException $e): RuntimeException
    {
        return new RuntimeException('DLT payment gateway is unreachable: '.$e->getMessage(), 0, $e);
    }

    /**
     * Check response success and throw appropriately.
     */
    private function throwIfFailed($response): void
    {
        if ($response->successful() && $response->json('success') === true) {
            return;
        }

        if ($errors = $response->json('errors')) {
            throw new RuntimeException('DLT validation error: '.json_encode($errors));
        }

        throw new RuntimeException('DLT request failed: '.($response->json('message') ?? $response->body()));
    }

    private function buildFallbackCheckoutUrl(string $merchantTransactionId, float $amount): string
    {
        $merchantAccountId = config('services.dlt.merchant_account_id');
        $formattedAmount = number_format($amount, 2, '.', '');

        return "https://checkout-ql.dltc.com.ph/?merchant={$merchantAccountId}&transaction_id={$merchantTransactionId}&amount={$formattedAmount}&quantity=1";
    }
}
