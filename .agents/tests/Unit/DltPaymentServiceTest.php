<?php

declare(strict_types=1);

use App\Services\DltPaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

describe('DltPaymentService', function () {
    beforeEach(function () {
        Config::set('services.dlt', [
            'base_url' => 'https://checkout.dxp.dtic.com.ph',
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'merchant_account_id' => 'ACCT-TEST',
            'service_code' => 'APN-COLLECTION',
        ]);
    });

    test('getBearerToken() caches token and returns it on subsequent calls', function () {
        Http::fake([
            'checkout.dxp.dtic.com.ph/oauth/token' => Http::response([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => 'fake-token',
            ]),
        ]);

        $service = new DltPaymentService(Http::getFacadeRoot(), Cache::store());

        $token1 = $service->getBearerToken();
        $token2 = $service->getBearerToken();

        expect($token1)->toBe('fake-token');
        expect($token2)->toBe('fake-token');

        Http::assertSentCount(1);
    });

    test('getBearerToken() refreshes when cache is empty', function () {
        Cache::store()->forget('dlt:bearer_token');

        Http::fake([
            'checkout.dxp.dtic.com.ph/oauth/token' => Http::response([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => 'fresh-token',
            ]),
        ]);

        $service = new DltPaymentService(Http::getFacadeRoot(), Cache::store());

        $token = $service->getBearerToken();

        expect($token)->toBe('fresh-token');

        Http::assertSentCount(1);
    });

    test('createQuickLinkSession() sends correct payload and parses response', function () {
        Cache::store()->put('dlt:bearer_token', 'cached-bearer-token', 3600);

        Http::fake([
            'checkout.dxp.dtic.com.ph/api/v1/quick-link/session' => Http::response([
                'success' => true,
                'message' => 'Successful',
                'data' => [
                    'token' => 'session-uuid',
                    'merchant' => 'ACCT-TEST',
                    'website' => 'https://checkout-ql.dltc.com.ph/?test',
                    'service_code' => 'APN-COLLECTION',
                    'amount' => 1299.00,
                    'currency' => 'PHP',
                    'expires_at' => 1756000000,
                ],
            ]),
        ]);

        $service = new DltPaymentService(Http::getFacadeRoot(), Cache::store());

        $result = $service->createQuickLinkSession(
            'txn-001',
            1299.00,
            'Test Product',
            'https://app/success',
            'https://app/failure',
        );

        expect($result)
            ->token->toBe('session-uuid')
            ->checkout_url->toContain('checkout-ql.dltc.com.ph')
            ->expires_at->toBe(1756000000);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://checkout.dxp.dtic.com.ph/api/v1/quick-link/session'
                && $request->hasHeader('Authorization', 'Bearer cached-bearer-token')
                && $request['service_code'] === 'APN-COLLECTION'
                && $request['merchant_account_id'] === 'ACCT-TEST'
                && $request['merchant_transaction_id'] === 'txn-001'
                && $request['amount'] === 1299.00
                && $request['currency'] === 'PHP'
                && $request['product_name'] === 'Test Product'
                && $request['quantity'] === 1
                && $request['success_url'] === 'https://app/success'
                && $request['failure_url'] === 'https://app/failure';
        });
    });

    test('getQuickLinkSession() fetches session by token', function () {
        Cache::store()->put('dlt:bearer_token', 'cached-bearer-token', 3600);

        Http::fake([
            'checkout.dxp.dtic.com.ph/api/v1/quick-link/session*' => Http::response([
                'success' => true,
                'message' => 'Successful',
                'data' => [
                    'token' => 'session-uuid',
                    'service_code' => 'APN-COLLECTION',
                    'amount' => 1299.00,
                ],
            ]),
        ]);

        $service = new DltPaymentService(Http::getFacadeRoot(), Cache::store());

        $result = $service->getQuickLinkSession('session-uuid');

        expect($result)
            ->token->toBe('session-uuid')
            ->service_code->toBe('APN-COLLECTION')
            ->and((float) $result['amount'])->toBe(1299.00);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/quick-link/session')
                && $request->hasHeader('Authorization', 'Bearer cached-bearer-token')
                && $request['token'] === 'session-uuid';
        });
    });

    test('handles DLT API error responses gracefully', function () {
        Cache::store()->put('dlt:bearer_token', 'cached-bearer-token', 3600);

        Http::fake([
            'checkout.dxp.dtic.com.ph/api/v1/quick-link/session' => Http::response([
                'success' => false,
                'message' => 'Something went wrong',
            ], 500),
        ]);

        $service = new DltPaymentService(Http::getFacadeRoot(), Cache::store());

        expect(fn () => $service->createQuickLinkSession(
            'txn-fail',
            500.00,
            'Fail Product',
            'https://app/success',
            'https://app/failure',
        ))->toThrow(RuntimeException::class);
    });
});
