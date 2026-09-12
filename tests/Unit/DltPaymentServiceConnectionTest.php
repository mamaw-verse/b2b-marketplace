<?php

declare(strict_types=1);

use App\Services\DltPaymentService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

describe('DltPaymentService connection failures', function () {
    beforeEach(function () {
        Config::set('services.dlt', [
            'base_url' => 'https://checkout.dxp.dtic.com.ph',
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'merchant_account_id' => 'ACCT-TEST',
            'service_code' => 'APN-COLLECTION',
        ]);
    });

    test('getBrands() translates a connection failure into a RuntimeException', function () {
        Cache::store()->put('dlt:bearer_token', 'cached-bearer-token', 3600);

        Http::fake([
            'checkout.dxp.dtic.com.ph/api/v1/collection/apn/brands*' => Http::failedConnection(),
        ]);

        $service = new DltPaymentService(Http::getFacadeRoot(), Cache::store());

        expect(fn () => $service->getBrands())
            ->toThrow(RuntimeException::class, 'DLT payment gateway is unreachable');
    });

    test('getBrands() preserves the ConnectionException as the previous exception', function () {
        Cache::store()->put('dlt:bearer_token', 'cached-bearer-token', 3600);

        Http::fake([
            'checkout.dxp.dtic.com.ph/api/v1/collection/apn/brands*' => Http::failedConnection(),
        ]);

        $service = new DltPaymentService(Http::getFacadeRoot(), Cache::store());

        try {
            $service->getBrands();
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            expect($e->getPrevious())->toBeInstanceOf(ConnectionException::class);
        }
    });

    test('submitPayment() translates a connection failure into a RuntimeException', function () {
        Cache::store()->put('dlt:bearer_token', 'cached-bearer-token', 3600);

        Http::fake([
            'checkout.dxp.dtic.com.ph/api/v1/collection/apn/submit' => Http::failedConnection(),
        ]);

        $service = new DltPaymentService(Http::getFacadeRoot(), Cache::store());

        expect(fn () => $service->submitPayment([
            'merchant_transaction_id' => 'order1abc',
            'is_card_payment' => true,
            'amount' => '100.00',
        ]))->toThrow(RuntimeException::class, 'DLT payment gateway is unreachable');
    });

    test('getBearerToken() translates a connection failure into a RuntimeException', function () {
        Cache::store()->forget('dlt:bearer_token');

        Http::fake([
            'checkout.dxp.dtic.com.ph/oauth/token' => Http::failedConnection(),
        ]);

        $service = new DltPaymentService(Http::getFacadeRoot(), Cache::store());

        expect(fn () => $service->getBearerToken())
            ->toThrow(RuntimeException::class, 'DLT payment gateway is unreachable');
    });
});
