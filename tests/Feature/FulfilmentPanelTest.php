<?php

declare(strict_types=1);

use App\Livewire\Account\Orders\OrderList;
use App\Livewire\CompanyHub\Fulfilment\FulfilmentPanel;
use App\Models\Fulfilment;
use App\Models\FulfilmentItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function paidOrderWithItems(int $itemCount = 2): array
{
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $order = Order::factory()->paid()->create(['shop_id' => $shop->id]);
    $items = OrderItem::factory()->count($itemCount)->create(['order_id' => $order->id]);

    return [$seller, $order, $items];
}

test('creating a fulfilment seeds fulfilment items from the order items with default full quantity', function () {
    [$seller, $order, $items] = paidOrderWithItems(3);

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order])
        ->set('carrier', 'J&T Express')
        ->set('trackingCode', 'JT1234567890')
        ->set('trackingUrl', 'https://track.example.com/JT1234567890')
        ->call('createFulfilment')
        ->assertHasNoErrors();

    $fulfilment = $order->fulfilments()->firstOrFail();

    expect($fulfilment->fulfilmentItems)->toHaveCount(3);

    foreach ($items as $orderItem) {
        $fulfilmentItem = FulfilmentItem::where('fulfilment_id', $fulfilment->id)
            ->where('order_item_id', $orderItem->id)
            ->first();

        expect($fulfilmentItem)->not->toBeNull()
            ->and($fulfilmentItem->quantity)->toBe($orderItem->quantity);
    }
});

test('validation failure does not create a fulfilment or any fulfilment items', function () {
    [$seller, $order] = paidOrderWithItems();

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order])
        ->set('carrier', '')
        ->set('trackingCode', '')
        ->set('trackingUrl', '')
        ->call('createFulfilment')
        ->assertHasErrors(['carrier', 'trackingCode', 'trackingUrl']);

    expect($order->fulfilments()->count())->toBe(0)
        ->and(FulfilmentItem::count())->toBe(0);
});

test('buyer To Receive tab shows the fulfilled quantity per line item', function () {
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create(['status' => 'active']);
    $order = Order::factory()->paid()->create([
        'buyer_user_id' => $buyer->id,
        'shop_id' => $shop->id,
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'description' => 'Premium Arabica Beans',
        'quantity' => 50,
    ]);

    // Order is shipped → lands in the To Receive tab. Fulfilled 20 of 50.
    $fulfilment = Fulfilment::factory()->shipped()->create(['order_id' => $order->id]);
    FulfilmentItem::factory()->create([
        'fulfilment_id' => $fulfilment->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 20,
    ]);

    $this->actingAs($buyer);

    Livewire::test(OrderList::class)
        ->set('tab', 'to_receive')
        ->assertSee('Premium Arabica Beans')
        ->assertSee('20x')
        ->assertSee('of 50 shipped');
});
