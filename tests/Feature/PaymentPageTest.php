<?php

declare(strict_types=1);

use App\Livewire\Account\Orders\PaymentPage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentTerm;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    if (! config('marketplace.features.orders_enabled')) {
        $this->markTestSkipped('orders_enabled is disabled');
    }

    Http::fake([
        '*/oauth/token' => Http::response([
            'access_token' => 'fake-bearer-token',
            'expires_in' => 3600,
        ]),
        '*/api/v1/collection/apn/brands' => Http::response([
            'data' => [
                ['value' => 'Visa', 'code' => 'VISA', 'image' => null, 'is_card' => true],
                ['value' => 'GCash', 'code' => 'GCASH', 'image' => null, 'is_card' => false],
            ],
        ]),
    ]);
});

test('buyer can view the payment page for their own pending order', function () {
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create(['status' => 'active']);

    $order = Order::factory()->pendingPayment()->create([
        'buyer_user_id' => $buyer->id,
        'shop_id' => $shop->id,
        'order_total' => 15000.00,
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);
    OrderPaymentTerm::factory()->create([
        'order_id' => $order->id,
        'amount' => 15000.00,
    ]);

    Livewire::actingAs($buyer)
        ->test(PaymentPage::class, ['order' => $order])
        ->assertSuccessful()
        ->assertSee("Pay for Order #{$order->id}")
        ->assertSee('Visa')
        ->assertSee('GCash');
});

test('non-buyer cannot view another users payment page', function () {
    $buyer = User::factory()->create();
    $otherUser = User::factory()->create();
    $shop = Shop::factory()->create(['status' => 'active']);

    $order = Order::factory()->pendingPayment()->create([
        'buyer_user_id' => $buyer->id,
        'shop_id' => $shop->id,
    ]);

    OrderPaymentTerm::factory()->create(['order_id' => $order->id]);

    Livewire::actingAs($otherUser)
        ->test(PaymentPage::class, ['order' => $order])
        ->assertForbidden();
});
