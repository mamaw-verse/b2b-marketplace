<?php

declare(strict_types=1);

use App\Events\DisputeEscalated;
use App\Events\DisputeOpened;
use App\Events\DisputeResolved;
use App\Events\OrderCancelled;
use App\Events\OrderCompleted;
use App\Events\OrderDelivered;
use App\Events\OrderPaid;
use App\Events\OrderShipped;
use App\Events\OrderSubmittedForPayment;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\DisputeEscalatedNotification;
use App\Notifications\DisputeOpenedNotification;
use App\Notifications\DisputeResolvedNotification;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderCompletedNotification;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderPaidNotification;
use App\Notifications\OrderPendingPaymentNotification;
use App\Notifications\OrderShippedNotification;
use Illuminate\Support\Facades\Notification;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function setupNotificationScenario(): array
{
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $buyer = User::factory()->create();
    $order = Order::factory()->paid()->create([
        'buyer_user_id' => $buyer->id,
        'shop_id' => $shop->id,
    ]);

    return [$seller, $shop, $buyer, $order];
}

describe('Order Notifications', function () {
    test('order submission sends notification to buyer', function () {
        Notification::fake();
        [$seller, $shop, $buyer, $order] = setupNotificationScenario();

        OrderSubmittedForPayment::dispatch($order);

        Notification::assertSentTo($buyer, OrderPendingPaymentNotification::class);
    });

    test('payment success sends notification to seller', function () {
        Notification::fake();
        [$seller, $shop, $buyer, $order] = setupNotificationScenario();

        OrderPaid::dispatch($order);

        Notification::assertSentTo($seller, OrderPaidNotification::class);
    });

    test('fulfilment shipped sends notification to buyer', function () {
        Notification::fake();
        [$seller, $shop, $buyer, $order] = setupNotificationScenario();

        OrderShipped::dispatch($order);

        Notification::assertSentTo($buyer, OrderShippedNotification::class);
    });

    test('fulfilment delivered sends notification to buyer', function () {
        Notification::fake();
        [$seller, $shop, $buyer, $order] = setupNotificationScenario();

        OrderDelivered::dispatch($order);

        Notification::assertSentTo($buyer, OrderDeliveredNotification::class);
    });

    test('order completion sends notification to seller', function () {
        Notification::fake();
        [$seller, $shop, $buyer, $order] = setupNotificationScenario();

        OrderCompleted::dispatch($order);

        Notification::assertSentTo($seller, OrderCompletedNotification::class);
    });

    test('order cancellation sends notification to counterpart', function () {
        Notification::fake();
        [$seller, $shop, $buyer, $order] = setupNotificationScenario();

        OrderCancelled::dispatch($order);

        Notification::assertSentTo($buyer, OrderCancelledNotification::class);
        Notification::assertSentTo($seller, OrderCancelledNotification::class);
    });

    test('dispute opened sends notification to seller', function () {
        Notification::fake();
        [$seller, $shop, $buyer, $order] = setupNotificationScenario();

        $dispute = Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
        ]);

        DisputeOpened::dispatch($dispute);

        Notification::assertSentTo($seller, DisputeOpenedNotification::class);
    });

    test('dispute resolved sends notification to both parties', function () {
        Notification::fake();
        [$seller, $shop, $buyer, $order] = setupNotificationScenario();

        $dispute = Dispute::factory()->resolvedRefunded()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
        ]);

        DisputeResolved::dispatch($dispute);

        Notification::assertSentTo($buyer, DisputeResolvedNotification::class);
        Notification::assertSentTo($seller, DisputeResolvedNotification::class);
    });

    test('dispute escalated sends notification to admin', function () {
        Notification::fake();
        [$seller, $shop, $buyer, $order] = setupNotificationScenario();

        Spatie\Permission\Models\Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $dispute = Dispute::factory()->escalated()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
        ]);

        DisputeEscalated::dispatch($dispute);

        Notification::assertSentTo($admin, DisputeEscalatedNotification::class);
    });
});
