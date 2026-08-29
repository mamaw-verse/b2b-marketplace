<?php

declare(strict_types=1);

use App\Events\OrderSubmittedForPayment;
use App\Livewire\CompanyHub\Orders\OrderBuilder;
use App\Models\BuyerProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\OrderPendingPaymentNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);
});

test('fillBillingFromBuyerProfile populates empty billing fields from buyer profile', function () {
    $buyer = User::factory()->create();
    BuyerProfile::factory()->create([
        'user_id' => $buyer->id,
        'business_name' => 'Acme Trading',
        'billing_address_1' => '123 Market St',
        'billing_city' => 'Manila',
        'billing_province' => 'Metro Manila',
        'billing_zip_code' => '1000',
        'contact_phone' => '09171234567',
        'contact_email' => 'buyer@acme.test',
    ]);

    $order = Order::factory()->create([
        'shop_id' => $this->shop->id,
        'buyer_user_id' => $buyer->id,
        'status' => 'draft',
        'customer_business_name' => null,
        'customer_contact_phone' => null,
        'customer_contact_email' => null,
        'billing_address_1' => null,
        'billing_city' => null,
    ]);

    Livewire::actingAs($this->seller)
        ->test(OrderBuilder::class, ['order' => $order])
        ->assertSet('customerBusinessName', 'Acme Trading')
        ->assertSet('billingAddress1', '123 Market St')
        ->assertSet('billingCity', 'Manila')
        ->assertSet('customerContactPhone', '09171234567')
        ->assertSet('customerContactEmail', 'buyer@acme.test');
});

test('searchResults returns this shop published variants matching product name or sku', function () {
    $order = Order::factory()->create([
        'shop_id' => $this->shop->id,
        'buyer_user_id' => $this->seller->id,
        'status' => 'draft',
    ]);

    $product = Product::factory()->create(['shop_id' => $this->shop->id, 'name' => 'Halal Chicken', 'status' => 'published']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'CHK-001', 'price' => '120.00', 'unit' => 'kg']);

    // Another shop's variant must never appear.
    $otherShop = Shop::factory()->create(['status' => 'active']);
    $otherProduct = Product::factory()->create(['shop_id' => $otherShop->id, 'name' => 'Halal Chicken', 'status' => 'published']);
    ProductVariant::factory()->create(['product_id' => $otherProduct->id, 'sku' => 'OTHER-1']);

    $component = Livewire::actingAs($this->seller)
        ->test(OrderBuilder::class, ['order' => $order]);

    // Match by product name
    $component->set('productSearch', 'Chicken');
    $results = $component->instance()->searchResults();
    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($variant->id);

    // Match by SKU
    $component->set('productSearch', 'CHK');
    expect($component->instance()->searchResults()->first()->id)->toBe($variant->id);
});

test('searchResults returns empty for short query', function () {
    $order = Order::factory()->create([
        'shop_id' => $this->shop->id,
        'buyer_user_id' => $this->seller->id,
        'status' => 'draft',
    ]);

    $component = Livewire::actingAs($this->seller)
        ->test(OrderBuilder::class, ['order' => $order])
        ->set('productSearch', 'a');

    expect($component->instance()->searchResults())->toHaveCount(0);
});

test('submitting an order notifies the buyer by email and in-app notification', function () {
    Notification::fake();

    $buyer = User::factory()->create();

    $order = Order::factory()->create([
        'shop_id' => $this->shop->id,
        'buyer_user_id' => $buyer->id,
        'status' => 'draft',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 1,
        'unit_price' => '100.00',
        'subtotal' => '100.00',
    ]);

    Livewire::actingAs($this->seller)
        ->test(OrderBuilder::class, ['order' => $order])
        ->call('submit');

    Notification::assertSentTo($buyer, OrderPendingPaymentNotification::class);
});

test('OrderSubmittedForPayment event triggers mail and database notification channels', function () {
    $buyer = User::factory()->create();

    $order = Order::factory()->create([
        'shop_id' => $this->shop->id,
        'buyer_user_id' => $buyer->id,
        'status' => 'pending_payment',
    ]);

    Event::dispatch(new OrderSubmittedForPayment($order));

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $buyer->id,
        'type' => OrderPendingPaymentNotification::class,
    ]);
});
