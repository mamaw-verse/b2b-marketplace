<?php

declare(strict_types=1);

use App\Events\OrderCompleted;
use App\Events\OrderDelivered;
use App\Events\OrderShipped;
use App\Jobs\OrderAutoAcceptJob;
use App\Models\Dispute;
use App\Models\Fulfilment;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentTerm;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function setupFulfilmentScenario(): array
{
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $buyer = User::factory()->create();
    $order = Order::factory()->paid()->create([
        'buyer_user_id' => $buyer->id,
        'shop_id' => $shop->id,
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);
    OrderPaymentTerm::factory()->create(['order_id' => $order->id, 'amount' => $order->order_total]);
    LedgerEntry::factory()->escrowHold()->create(['order_id' => $order->id, 'amount' => $order->order_total]);

    return [$seller, $shop, $buyer, $order];
}

describe('Fulfilment Flow', function () {
    test('seller can create fulfilment for paid order', function () {
        [$seller, $shop, $buyer, $order] = setupFulfilmentScenario();

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order->id}/fulfilment", [
            'carrier' => 'J&T Express',
            'tracking_code' => 'JT1234567890',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.carrier', 'J&T Express');
    });

    test('seller cannot create fulfilment for non-paid order', function () {
        [$seller, $shop, $buyer, $order] = setupFulfilmentScenario();

        $draftOrder = Order::factory()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$draftOrder->id}/fulfilment", [
            'carrier' => 'J&T Express',
            'tracking_code' => 'JT1234567890',
        ]);

        $response->assertStatus(403);
    });

    test('seller cannot create second fulfilment (unique constraint)', function () {
        [$seller, $shop, $buyer, $order] = setupFulfilmentScenario();

        Fulfilment::factory()->create(['order_id' => $order->id]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order->id}/fulfilment", [
            'carrier' => 'J&T Express',
            'tracking_code' => 'JT1234567890',
        ]);

        $response->assertStatus(403);
    });

    test('seller can mark shipped with tracking', function () {
        Event::fake([OrderShipped::class]);

        [$seller, $shop, $buyer, $order] = setupFulfilmentScenario();

        Fulfilment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order->id}/fulfilment/ship", [
            'tracking_code' => 'JT9876543210',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'shipped');

        expect($response->json('data.shipped_at'))->not->toBeNull();

        Event::assertDispatched(OrderShipped::class);
    });

    test('seller cannot mark shipped if already shipped', function () {
        [$seller, $shop, $buyer, $order] = setupFulfilmentScenario();

        Fulfilment::factory()->create([
            'order_id' => $order->id,
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order->id}/fulfilment/ship", [
            'tracking_code' => 'JT0000000000',
        ]);

        $response->assertStatus(403);
    });

    test('seller can mark delivered', function () {
        Event::fake([OrderDelivered::class]);

        [$seller, $shop, $buyer, $order] = setupFulfilmentScenario();

        Fulfilment::factory()->create([
            'order_id' => $order->id,
            'status' => 'shipped',
            'shipped_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order->id}/fulfilment/deliver");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'delivered');

        expect($response->json('data.delivered_at'))->not->toBeNull();

        Event::assertDispatched(OrderDelivered::class);
    });

    test('seller can mark returned to seller', function () {
        [$seller, $shop, $buyer, $order] = setupFulfilmentScenario();

        Fulfilment::factory()->create([
            'order_id' => $order->id,
            'status' => 'shipped',
            'shipped_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order->id}/fulfilment/return");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'returned_to_seller');

        expect($response->json('data.returned_at'))->not->toBeNull();
    });

    test('buyer confirms receipt → order completed, payout ledger entry created', function () {
        Event::fake([OrderCompleted::class]);

        [$seller, $shop, $buyer, $order] = setupFulfilmentScenario();

        Fulfilment::factory()->create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'shipped_at' => now()->subDays(3),
            'delivered_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/confirm-receipt");

        $response->assertStatus(200);

        $order->refresh();
        expect($order->status)->toBe('completed');
        expect($order->completed_at)->not->toBeNull();

        $payoutEntry = LedgerEntry::where('order_id', $order->id)
            ->where('type', 'payout')
            ->first();

        expect($payoutEntry)->not->toBeNull();
        $commissionRate = config('marketplace.commission_rate', 0.01);
        $expectedPayout = round((float) $order->order_total * (1 - $commissionRate), 2);
        expect((float) $payoutEntry->amount)->toBe($expectedPayout);
    });

    test('buyer cannot confirm receipt before delivered', function () {
        [$seller, $shop, $buyer, $order] = setupFulfilmentScenario();

        Fulfilment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/confirm-receipt");

        $response->assertForbidden();
    });

    test('OrderAutoAcceptJob auto-completes orders delivered 7+ days ago with no dispute', function () {
        [$seller, $shop, $buyer, $order] = setupFulfilmentScenario();

        Fulfilment::factory()->create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'shipped_at' => now()->subDays(10),
            'delivered_at' => now()->subDays(8),
        ]);

        (new OrderAutoAcceptJob)->handle(app(App\Actions\ConfirmReceiptAction::class));

        $order->refresh();
        expect($order->status)->toBe('completed');

        $payoutEntry = LedgerEntry::where('order_id', $order->id)
            ->where('type', 'payout')
            ->first();

        expect($payoutEntry)->not->toBeNull();
    });

    test('OrderAutoAcceptJob skips orders with active disputes', function () {
        [$seller, $shop, $buyer, $order] = setupFulfilmentScenario();

        Fulfilment::factory()->create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'shipped_at' => now()->subDays(10),
            'delivered_at' => now()->subDays(8),
        ]);

        Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'status' => 'open',
        ]);

        (new OrderAutoAcceptJob)->handle(app(App\Actions\ConfirmReceiptAction::class));

        $order->refresh();
        expect($order->status)->toBe('paid');
    });
});
