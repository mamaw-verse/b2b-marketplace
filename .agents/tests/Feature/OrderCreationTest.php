<?php

declare(strict_types=1);

use App\Models\BuyerProfile;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function setupOrderScenario(): array
{
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $buyer = User::factory()->create();
    BuyerProfile::factory()->create(['user_id' => $buyer->id]);
    $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
    $recipient = RfqRecipient::factory()->inProgress()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $shop->id,
    ]);
    $quotation = Quotation::factory()->awarded()->create([
        'rfq_id' => $rfq->id,
        'rfq_recipient_id' => $recipient->id,
    ]);
    QuotationItem::factory()->count(2)->create(['quotation_id' => $quotation->id]);

    return [$seller, $shop, $buyer, $rfq, $recipient, $quotation];
}

describe('Order Creation & Draft Editing', function () {
    test('seller can create order from awarded quotation', function () {
        [$seller, $shop, $buyer, $rfq, $recipient, $quotation] = setupOrderScenario();

        $response = $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'quotation_id' => $quotation->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.quotation_id', $quotation->id);

        $order = Order::where('quotation_id', $quotation->id)->first();
        expect($order)->not->toBeNull();
        expect($order->items()->count())->toBe(2);
    });

    test('seller cannot create order from non-awarded quotation', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = setupOrderScenario();

        $quotation = Quotation::factory()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'quotation_id' => $quotation->id,
        ]);

        $response->assertStatus(409);
    });

    test('seller cannot create duplicate order from same quotation', function () {
        [$seller, $shop, $buyer, $rfq, $recipient, $quotation] = setupOrderScenario();

        $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'quotation_id' => $quotation->id,
        ])->assertStatus(201);

        $response = $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'quotation_id' => $quotation->id,
        ]);

        $response->assertStatus(409);
    });

    test('seller can create standalone order from customer list', function () {
        [$seller, $shop, $buyer, $rfq, $recipient, $quotation] = setupOrderScenario();

        // Create a prior order so buyer is a "customer" of this shop
        $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'quotation_id' => $quotation->id,
        ])->assertStatus(201);

        $response = $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'buyer_user_id' => $buyer->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.quotation_id', null);

        $order = Order::where('buyer_user_id', $buyer->id)
            ->whereNull('quotation_id')
            ->first();
        expect($order)->not->toBeNull();
        expect($order->items()->count())->toBe(0);
    });

    test('seller cannot create standalone order for non-customer buyer', function () {
        $seller = User::factory()->create();
        Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
        $nonCustomerBuyer = User::factory()->create();
        BuyerProfile::factory()->create(['user_id' => $nonCustomerBuyer->id]);

        $response = $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'buyer_user_id' => $nonCustomerBuyer->id,
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT');
    });

    test('seller can update draft order (items, fees, snapshot)', function () {
        [$seller, $shop, $buyer, $rfq, $recipient, $quotation] = setupOrderScenario();

        $order = $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'quotation_id' => $quotation->id,
        ])->assertStatus(201)->json('data');

        $response = $this->actingAs($seller)->putJson("/api/v1/hub/orders/{$order['id']}", [
            'items' => [
                [
                    'description' => fake()->sentence(),
                    'quantity' => 10,
                    'unit' => 'kg',
                    'unit_price' => '150.00',
                ],
                [
                    'description' => fake()->sentence(),
                    'quantity' => 5,
                    'unit' => 'pcs',
                    'unit_price' => '200.00',
                ],
            ],
            'fees' => [
                [
                    'label' => 'Shipping Fee',
                    'amount' => '250.00',
                ],
            ],
            'shipping_city' => fake()->city(),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'draft');

        $updatedOrder = Order::find($order['id']);
        expect($updatedOrder->items()->count())->toBe(2);
    });

    test('seller cannot update non-draft order', function () {
        [$seller, $shop, $buyer, $rfq, $recipient, $quotation] = setupOrderScenario();

        $order = $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'quotation_id' => $quotation->id,
        ])->assertStatus(201)->json('data');

        // Submit the order to move it to pending_payment
        $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order['id']}/submit")
            ->assertStatus(200);

        $response = $this->actingAs($seller)->putJson("/api/v1/hub/orders/{$order['id']}", [
            'items' => [
                [
                    'description' => fake()->sentence(),
                    'quantity' => 1,
                    'unit' => 'kg',
                    'unit_price' => '100.00',
                ],
            ],
        ]);

        $response->assertStatus(403);
    });

    test('seller can submit order with items', function () {
        [$seller, $shop, $buyer, $rfq, $recipient, $quotation] = setupOrderScenario();

        $order = $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'quotation_id' => $quotation->id,
        ])->assertStatus(201)->json('data');

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order['id']}/submit");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'pending_payment');

        $submittedOrder = Order::find($order['id']);
        expect($submittedOrder->submitted_at)->not->toBeNull();
    });

    test('seller cannot submit order with no items', function () {
        [$seller, $shop, $buyer, $rfq, $recipient, $quotation] = setupOrderScenario();

        // Create a standalone order (no items)
        $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'quotation_id' => $quotation->id,
        ])->assertStatus(201);

        $order = Order::where('quotation_id', $quotation->id)->first();
        // Remove all items to simulate empty order
        $order->items()->delete();

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order->id}/submit");

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    });

    test('seller can recall pending_payment order', function () {
        [$seller, $shop, $buyer, $rfq, $recipient, $quotation] = setupOrderScenario();

        $order = $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'quotation_id' => $quotation->id,
        ])->assertStatus(201)->json('data');

        // Submit first
        $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order['id']}/submit")
            ->assertStatus(200);

        // Then recall
        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order['id']}/recall");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'draft');

        $recalledOrder = Order::find($order['id']);
        expect($recalledOrder->submitted_at)->toBeNull();
    });

    test('seller can delete draft order', function () {
        [$seller, $shop, $buyer, $rfq, $recipient, $quotation] = setupOrderScenario();

        $order = $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'quotation_id' => $quotation->id,
        ])->assertStatus(201)->json('data');

        $response = $this->actingAs($seller)->deleteJson("/api/v1/hub/orders/{$order['id']}");

        $response->assertStatus(204);
        expect(Order::find($order['id']))->toBeNull();
    });

    test('seller cannot delete non-draft order', function () {
        [$seller, $shop, $buyer, $rfq, $recipient, $quotation] = setupOrderScenario();

        $order = $this->actingAs($seller)->postJson('/api/v1/hub/orders', [
            'quotation_id' => $quotation->id,
        ])->assertStatus(201)->json('data');

        // Submit to move out of draft
        $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order['id']}/submit")
            ->assertStatus(200);

        $response = $this->actingAs($seller)->deleteJson("/api/v1/hub/orders/{$order['id']}");

        $response->assertStatus(403);
    });
});
