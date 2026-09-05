<?php

declare(strict_types=1);

use App\Events\OrderReturned;
use App\Livewire\Account\Orders\OrderList;
use App\Livewire\CompanyHub\Fulfilment\FulfilmentPanel;
use App\Models\Dispute;
use App\Models\Fulfilment;
use App\Models\FulfilmentItem;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\OrderReturnedNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
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

test('Mark Returned button is hidden when a return_refund dispute is open', function () {
    [$seller, $order] = paidOrderWithItems();

    Fulfilment::factory()->shipped()->create(['order_id' => $order->id]);
    Dispute::factory()->returnRefund()->create(['order_id' => $order->id]);

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order->load('disputes')])
        ->assertSet('hasReturnDisputeInProgress', true)
        ->assertDontSee('Mark Returned');
});

test('Mark Returned button is hidden when a return_refund dispute is awaiting_return', function () {
    [$seller, $order] = paidOrderWithItems();

    Fulfilment::factory()->shipped()->create(['order_id' => $order->id]);
    Dispute::factory()->create([
        'order_id' => $order->id,
        'type' => 'return_refund',
        'status' => 'awaiting_return',
    ]);

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order->load('disputes')])
        ->assertSet('hasReturnDisputeInProgress', true)
        ->assertDontSee('Mark Returned');
});

test('Mark Returned button is visible when there is no return_refund dispute', function () {
    [$seller, $order] = paidOrderWithItems();

    Fulfilment::factory()->shipped()->create(['order_id' => $order->id]);

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order->load('disputes')])
        ->assertSet('hasReturnDisputeInProgress', false)
        ->assertSee('Mark Returned');
});

test('Mark Returned button is visible when the return_refund dispute is resolved', function () {
    [$seller, $order] = paidOrderWithItems();

    Fulfilment::factory()->shipped()->create(['order_id' => $order->id]);
    Dispute::factory()->resolvedRefunded()->create([
        'order_id' => $order->id,
        'type' => 'return_refund',
    ]);

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order->load('disputes')])
        ->assertSet('hasReturnDisputeInProgress', false)
        ->assertSee('Mark Returned');
});

test('markReturned is blocked server-side when a return_refund dispute is in progress', function () {
    [$seller, $order] = paidOrderWithItems();

    $fulfilment = Fulfilment::factory()->shipped()->create(['order_id' => $order->id]);
    Dispute::factory()->returnRefund()->create(['order_id' => $order->id]);

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order->load('disputes')])
        ->call('markReturned');

    expect($fulfilment->fresh()->status)->toBe('shipped');
});

test('Mark Returned button is hidden when a return_refund dispute is at return_received', function () {
    [$seller, $order] = paidOrderWithItems();

    Fulfilment::factory()->shipped()->create(['order_id' => $order->id]);
    Dispute::factory()->create([
        'order_id' => $order->id,
        'type' => 'return_refund',
        'status' => 'return_received',
    ]);

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order->load('disputes')])
        ->assertSet('hasReturnDisputeInProgress', true)
        ->assertDontSee('Mark Returned');
});

test('markReturned with no_refund sets fulfilment status and dispatches event', function () {
    Event::fake([OrderReturned::class]);

    [$seller, $order] = paidOrderWithItems();
    $fulfilment = Fulfilment::factory()->shipped()->create(['order_id' => $order->id]);

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order->load('disputes')])
        ->set('returnRefundType', 'no_refund')
        ->call('markReturned')
        ->assertHasNoErrors();

    expect($fulfilment->fresh()->status)->toBe('returned_to_seller')
        ->and($fulfilment->fresh()->returned_at)->not->toBeNull();

    // No_refund: order status unchanged, no ledger entries
    expect($order->fresh()->status)->toBe('paid');
    expect(LedgerEntry::where('order_id', $order->id)->count())->toBe(0);

    Event::assertDispatched(OrderReturned::class);
});

test('markReturned with full_refund creates ledger entry and sets order to refunded', function () {
    Event::fake([OrderReturned::class]);

    [$seller, $order] = paidOrderWithItems();
    Fulfilment::factory()->shipped()->create(['order_id' => $order->id]);

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order->load('disputes')])
        ->set('returnRefundType', 'full_refund')
        ->call('markReturned')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe('refunded');

    $refundEntry = LedgerEntry::where('order_id', $order->id)
        ->where('type', 'refund')
        ->first();

    expect($refundEntry)->not->toBeNull()
        ->and((float) $refundEntry->amount)->toBe((float) $order->order_total);

    Event::assertDispatched(OrderReturned::class);
});

test('markReturned with partial_refund creates refund and payout ledger entries', function () {
    Event::fake([OrderReturned::class]);

    [$seller, $order] = paidOrderWithItems();
    Fulfilment::factory()->shipped()->create(['order_id' => $order->id]);

    $partialAmount = round((float) $order->order_total / 2, 2);

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order->load('disputes')])
        ->set('returnRefundType', 'partial_refund')
        ->set('returnRefundAmount', (string) $partialAmount)
        ->call('markReturned')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe('refunded');

    $refundEntry = LedgerEntry::where('order_id', $order->id)->where('type', 'refund')->first();
    expect($refundEntry)->not->toBeNull()
        ->and((float) $refundEntry->amount)->toBe($partialAmount);

    // A payout ledger entry for the seller's remainder should also be created
    $payoutEntry = LedgerEntry::where('order_id', $order->id)->where('type', 'payout')->first();
    expect($payoutEntry)->not->toBeNull();

    Event::assertDispatched(OrderReturned::class);
});

test('markReturned partial_refund fails validation when amount exceeds order total', function () {
    [$seller, $order] = paidOrderWithItems();
    Fulfilment::factory()->shipped()->create(['order_id' => $order->id]);

    $overAmount = (float) $order->order_total + 1;

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order->load('disputes')])
        ->set('returnRefundType', 'partial_refund')
        ->set('returnRefundAmount', (string) $overAmount)
        ->call('markReturned')
        ->assertHasErrors(['returnRefundAmount']);

    expect($order->fresh()->status)->not->toBe('refunded');
});

test('markReturned notifies the buyer', function () {
    Notification::fake();

    [$seller, $order] = paidOrderWithItems();
    Fulfilment::factory()->shipped()->create(['order_id' => $order->id]);

    $buyer = User::factory()->create();
    $order->update(['buyer_user_id' => $buyer->id]);

    $this->actingAs($seller);

    Livewire::test(FulfilmentPanel::class, ['order' => $order->load(['disputes', 'buyer'])])
        ->set('returnRefundType', 'no_refund')
        ->call('markReturned');

    Notification::assertSentTo($buyer, OrderReturnedNotification::class);
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
