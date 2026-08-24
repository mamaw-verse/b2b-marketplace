<?php

declare(strict_types=1);

use App\Actions\ProcessOrderPaymentAction;
use App\Events\OrderPaid;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentTerm;
use App\Models\PaymentAttempt;
use App\Models\Shop;
use App\Models\User;
use App\Services\DltPaymentService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Order Payment Flow', function () {
    test('payment initiation creates payment_attempt with DLT fields', function () {
        $buyer = User::factory()->create();
        $shop = Shop::factory()->create(['status' => 'active']);

        $order = Order::factory()->pendingPayment()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'order_total' => 50000.00,
        ]);

        OrderItem::factory()->create(['order_id' => $order->id]);
        OrderPaymentTerm::factory()->create([
            'order_id' => $order->id,
            'amount' => 50000.00,
        ]);

        // Fake HTTP for the final DltPaymentService (cannot be mocked with Mockery)
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'fake-bearer-token',
                'expires_in' => 3600,
            ]),
            '*/api/v1/quick-link/session' => Http::response([
                'success' => true,
                'data' => [
                    'token' => 'test-token',
                    'website' => 'https://example.com/pay',
                    'expires_at' => time() + 3600,
                ],
            ]),
        ]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/payment");

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['payment_attempt_id', 'checkout_url', 'expires_at']]);
        expect($response->json('data.checkout_url'))->toBe('https://example.com/pay');

        $attempt = PaymentAttempt::first();
        expect($attempt)->not->toBeNull();
        expect($attempt->dlt_merchant_transaction_id)->not->toBeNull();
        expect($attempt->dlt_quick_link_token)->toBe('test-token');
    });

    test('payment initiation returns 409 if order is not pending_payment', function () {
        $buyer = User::factory()->create();
        $shop = Shop::factory()->create(['status' => 'active']);

        $order = Order::factory()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/payment");

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'CONFLICT');
    });

    test('success callback marks attempt as succeeded, order as paid, creates escrow_hold ledger entry', function () {
        $buyer = User::factory()->create();
        $shop = Shop::factory()->create(['status' => 'active']);

        $order = Order::factory()->pendingPayment()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'order_total' => 71500.00,
        ]);

        $paymentTerm = OrderPaymentTerm::factory()->create([
            'order_id' => $order->id,
            'amount' => 71500.00,
        ]);

        $attempt = PaymentAttempt::factory()->create([
            'order_payment_term_id' => $paymentTerm->id,
            'status' => 'pending',
            'dlt_quick_link_token' => 'ql-token-123',
        ]);

        $action = app(ProcessOrderPaymentAction::class);
        $action->execute($attempt, $order);

        $attempt->refresh();
        $order->refresh();

        expect($attempt->status)->toBe('succeeded');
        expect($attempt->succeeded_at)->not->toBeNull();
        expect($order->status)->toBe('paid');
        expect($order->paid_at)->not->toBeNull();

        $ledger = LedgerEntry::where('order_id', $order->id)->first();
        expect($ledger)->not->toBeNull();
        expect($ledger->type)->toBe('escrow_hold');
        expect((float) $ledger->amount)->toBe(71500.00);
    });

    test('success callback is idempotent — duplicate call does not create duplicate ledger entries', function () {
        $buyer = User::factory()->create();
        $shop = Shop::factory()->create(['status' => 'active']);

        $order = Order::factory()->paid()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'order_total' => 30000.00,
        ]);

        $paymentTerm = OrderPaymentTerm::factory()->create([
            'order_id' => $order->id,
            'amount' => 30000.00,
        ]);

        $attempt = PaymentAttempt::factory()->succeeded()->create([
            'order_payment_term_id' => $paymentTerm->id,
            'dlt_quick_link_token' => 'ql-token-456',
        ]);

        // Pre-create the ledger entry (simulates first successful processing)
        LedgerEntry::factory()->escrowHold()->create([
            'order_id' => $order->id,
            'amount' => 30000.00,
        ]);

        $action = app(ProcessOrderPaymentAction::class);
        $action->execute($attempt, $order);

        expect(LedgerEntry::where('order_id', $order->id)->count())->toBe(1);
    });

    test('failure callback marks attempt as failed, order stays pending_payment', function () {
        $buyer = User::factory()->create();
        $shop = Shop::factory()->create(['status' => 'active']);

        $order = Order::factory()->pendingPayment()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'order_total' => 25000.00,
        ]);

        $paymentTerm = OrderPaymentTerm::factory()->create([
            'order_id' => $order->id,
            'amount' => 25000.00,
        ]);

        $attempt = PaymentAttempt::factory()->create([
            'order_payment_term_id' => $paymentTerm->id,
            'status' => 'pending',
            'dlt_quick_link_token' => 'ql-token-789',
        ]);

        $response = $this->actingAs($buyer)
            ->get("/orders/{$order->id}/payment/failure?attempt={$attempt->id}");

        $response->assertRedirect();

        $attempt->refresh();
        $order->refresh();

        expect($attempt->status)->toBe('failed');
        expect($attempt->failed_at)->not->toBeNull();
        expect($order->status)->toBe('pending_payment');
    });

    test('non-buyer cannot initiate payment', function () {
        $buyer = User::factory()->create();
        $otherUser = User::factory()->create();
        $shop = Shop::factory()->create(['status' => 'active']);

        $order = Order::factory()->pendingPayment()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);

        OrderPaymentTerm::factory()->create(['order_id' => $order->id]);

        $response = $this->actingAs($otherUser)->postJson("/api/v1/orders/{$order->id}/payment");

        $response->assertForbidden();
    });

    test('OrderPaid event is dispatched on successful payment processing', function () {
        Event::fake([OrderPaid::class]);

        $buyer = User::factory()->create();
        $shop = Shop::factory()->create(['status' => 'active']);

        $order = Order::factory()->pendingPayment()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'order_total' => 10000.00,
        ]);

        $paymentTerm = OrderPaymentTerm::factory()->create([
            'order_id' => $order->id,
            'amount' => 10000.00,
        ]);

        $attempt = PaymentAttempt::factory()->create([
            'order_payment_term_id' => $paymentTerm->id,
            'status' => 'pending',
            'dlt_quick_link_token' => 'ql-token-event',
        ]);

        $action = app(ProcessOrderPaymentAction::class);
        $action->execute($attempt, $order);

        Event::assertDispatched(OrderPaid::class);
    });
});
