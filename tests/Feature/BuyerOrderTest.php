<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function setupBuyerOrderScenario(): array
{
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $buyer = User::factory()->create();

    return [$seller, $shop, $buyer];
}

describe('Buyer Orders', function () {
    test('buyer can list orders filtered by tab', function () {
        [$seller, $shop, $buyer] = setupBuyerOrderScenario();

        $pendingOrder = Order::factory()->pendingPayment()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);

        $paidOrder = Order::factory()->paid()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);

        $completedOrder = Order::factory()->completed()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);

        // Tab: to_pay — only pending_payment
        $response = $this->actingAs($buyer)->getJson('/api/v1/orders?tab=to_pay');
        $response->assertOk();
        $orderIds = collect($response->json('data'))->pluck('id')->all();
        expect($orderIds)->toContain($pendingOrder->id)
            ->not->toContain($paidOrder->id)
            ->not->toContain($completedOrder->id);

        // Tab: completed — only completed
        $response = $this->actingAs($buyer)->getJson('/api/v1/orders?tab=completed');
        $response->assertOk();
        $orderIds = collect($response->json('data'))->pluck('id')->all();
        expect($orderIds)->toContain($completedOrder->id)
            ->not->toContain($pendingOrder->id)
            ->not->toContain($paidOrder->id);
    });

    test('buyer can view their order detail', function () {
        [$seller, $shop, $buyer] = setupBuyerOrderScenario();

        $order = Order::factory()->pendingPayment()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);

        $response = $this->actingAs($buyer)->getJson("/api/v1/orders/{$order->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $order->id]);
    });

    test('buyer cannot view another buyer\'s order', function () {
        [$seller, $shop, $buyerA] = setupBuyerOrderScenario();
        $buyerB = User::factory()->create();

        $order = Order::factory()->pendingPayment()->create([
            'buyer_user_id' => $buyerA->id,
            'shop_id' => $shop->id,
        ]);

        $response = $this->actingAs($buyerB)->getJson("/api/v1/orders/{$order->id}");

        $response->assertForbidden();
    });

    test('buyer can cancel pending_payment order', function () {
        [$seller, $shop, $buyer] = setupBuyerOrderScenario();

        $order = Order::factory()->pendingPayment()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertOk();
        expect($order->fresh()->status)->toBe('cancelled');
    });

    test('buyer cannot cancel paid order', function () {
        [$seller, $shop, $buyer] = setupBuyerOrderScenario();

        $order = Order::factory()->paid()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertForbidden();
    });

    test('buyer cannot cancel another buyer\'s order', function () {
        [$seller, $shop, $buyerA] = setupBuyerOrderScenario();
        $buyerB = User::factory()->create();

        $order = Order::factory()->pendingPayment()->create([
            'buyer_user_id' => $buyerA->id,
            'shop_id' => $shop->id,
        ]);

        $response = $this->actingAs($buyerB)->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertForbidden();
    });
});
