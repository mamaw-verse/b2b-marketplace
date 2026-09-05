<?php

declare(strict_types=1);

use App\Models\ConversationThread;
use App\Models\Dispute;
use App\Models\Fulfilment;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderFee;
use App\Models\OrderItem;
use App\Models\OrderPaymentTerm;
use App\Models\PaymentAttempt;
use App\Models\Quotation;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\QueryException;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Order Schema & Model Relationships', function () {
    test('order can be created via factory with all default states', function () {
        $order = Order::factory()->create();

        expect($order)->toBeInstanceOf(Order::class)
            ->and($order->id)->toBeGreaterThan(0)
            ->and($order->status)->toBe('draft')
            ->and($order->currency)->toBe('PHP')
            ->and($order->buyer_user_id)->toBeGreaterThan(0)
            ->and($order->shop_id)->toBeGreaterThan(0);
    });

    test('order has correct relationships to items, fees, payment terms, fulfilments, disputes, and ledger entries', function () {
        $order = Order::factory()->create();

        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        $fee = OrderFee::factory()->create(['order_id' => $order->id]);
        $paymentTerm = OrderPaymentTerm::factory()->create(['order_id' => $order->id]);
        $fulfilment = Fulfilment::factory()->create(['order_id' => $order->id]);
        $dispute = Dispute::factory()->create(['order_id' => $order->id]);
        $ledgerEntry = LedgerEntry::factory()->create(['order_id' => $order->id]);

        $order->refresh();

        expect($order->items)->toHaveCount(1)
            ->and($order->items->first()->id)->toBe($item->id)
            ->and($order->fees)->toHaveCount(1)
            ->and($order->fees->first()->id)->toBe($fee->id)
            ->and($order->paymentTerms)->toHaveCount(1)
            ->and($order->paymentTerms->first()->id)->toBe($paymentTerm->id)
            ->and($order->fulfilments)->toHaveCount(1)
            ->and($order->fulfilments->first()->id)->toBe($fulfilment->id)
            ->and($order->disputes)->toHaveCount(1)
            ->and($order->disputes->first()->id)->toBe($dispute->id)
            ->and($order->ledgerEntries)->toHaveCount(1)
            ->and($order->ledgerEntries->first()->id)->toBe($ledgerEntry->id);
    });

    test('order belongs to buyer user, shop, and optionally quotation', function () {
        $buyer = User::factory()->create();
        $shop = Shop::factory()->create();
        $quotation = Quotation::factory()->awarded()->create();

        $order = Order::factory()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'quotation_id' => $quotation->id,
        ]);

        expect($order->buyer->id)->toBe($buyer->id)
            ->and($order->shop->id)->toBe($shop->id)
            ->and($order->quotation->id)->toBe($quotation->id);
    });

    test('unique constraint on quotation_id prevents duplicate orders from same quotation', function () {
        $quotation = Quotation::factory()->awarded()->create();

        Order::factory()->create(['quotation_id' => $quotation->id]);

        expect(fn () => Order::factory()->create(['quotation_id' => $quotation->id]))
            ->toThrow(QueryException::class);
    });

    test('ON DELETE RESTRICT prevents deleting a user who has orders', function () {
        $buyer = User::factory()->create();
        Order::factory()->create(['buyer_user_id' => $buyer->id]);

        expect(fn () => $buyer->forceDelete())
            ->toThrow(QueryException::class);
    });

    test('ON DELETE RESTRICT prevents deleting a shop that has orders', function () {
        $shop = Shop::factory()->create();
        Order::factory()->create(['shop_id' => $shop->id]);

        expect(fn () => $shop->forceDelete())
            ->toThrow(QueryException::class);
    });

    test('ON DELETE CASCADE on items and fees removes children when draft order is deleted', function () {
        $order = Order::factory()->create(['status' => 'draft']);
        OrderItem::factory()->count(3)->create(['order_id' => $order->id]);
        OrderFee::factory()->count(2)->create(['order_id' => $order->id]);

        $orderId = $order->id;
        $order->delete();

        expect(OrderItem::where('order_id', $orderId)->count())->toBe(0)
            ->and(OrderFee::where('order_id', $orderId)->count())->toBe(0);
    });

    test('payment attempts belong to order payment terms', function () {
        $paymentTerm = OrderPaymentTerm::factory()->create();
        $attempt = PaymentAttempt::factory()->create(['order_payment_term_id' => $paymentTerm->id]);

        expect($attempt->paymentTerm->id)->toBe($paymentTerm->id)
            ->and($paymentTerm->fresh()->paymentAttempts)->toHaveCount(1);
    });

    test('fulfilment unique index enforces one fulfilment per order', function () {
        $order = Order::factory()->create();
        Fulfilment::factory()->create(['order_id' => $order->id]);

        expect(fn () => Fulfilment::factory()->create(['order_id' => $order->id]))
            ->toThrow(QueryException::class);
    });

    test('ledger entry has no updated_at column', function () {
        $entry = LedgerEntry::factory()->create();

        expect($entry->updated_at)->toBeNull();
    });

    test('dispute belongs to order and users (opened_by and resolved_by)', function () {
        $buyer = User::factory()->create();
        $resolver = User::factory()->create();
        $order = Order::factory()->create(['buyer_user_id' => $buyer->id]);

        $dispute = Dispute::factory()->resolvedRefunded()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'resolved_by_user_id' => $resolver->id,
        ]);

        expect($dispute->order->id)->toBe($order->id)
            ->and($dispute->openedBy->id)->toBe($buyer->id)
            ->and($dispute->resolvedBy->id)->toBe($resolver->id);
    });

    test('conversation thread can be linked to an order', function () {
        $order = Order::factory()->create();
        $thread = ConversationThread::factory()->create(['order_id' => $order->id]);

        expect($thread->order->id)->toBe($order->id)
            ->and($thread->isOrderLinked())->toBeTrue();
    });

    test('User hasMany orders as buyer', function () {
        $buyer = User::factory()->create();
        Order::factory()->count(3)->create(['buyer_user_id' => $buyer->id]);

        expect($buyer->orders)->toHaveCount(3);
    });

    test('Shop hasMany orders', function () {
        $shop = Shop::factory()->create();
        Order::factory()->count(2)->create(['shop_id' => $shop->id]);

        expect($shop->orders)->toHaveCount(2);
    });

    test('Quotation hasOne order', function () {
        $quotation = Quotation::factory()->awarded()->create();
        $order = Order::factory()->create(['quotation_id' => $quotation->id]);

        expect($quotation->order->id)->toBe($order->id);
    });

    test('order scopes filter correctly', function () {
        $buyer = User::factory()->create();
        $shop = Shop::factory()->create();

        Order::factory()->create(['buyer_user_id' => $buyer->id, 'shop_id' => $shop->id, 'status' => 'draft']);
        Order::factory()->create(['buyer_user_id' => $buyer->id, 'status' => 'paid']);
        Order::factory()->create(['shop_id' => $shop->id, 'status' => 'paid']);

        expect(Order::forBuyer($buyer->id)->count())->toBe(2)
            ->and(Order::forShop($shop->id)->count())->toBe(2)
            ->and(Order::inStatus('paid')->count())->toBe(2)
            ->and(Order::forBuyer($buyer->id)->inStatus('draft')->count())->toBe(1);
    });

    test('order factory states produce correct statuses', function () {
        $draft = Order::factory()->create();
        $pending = Order::factory()->pendingPayment()->create();
        $paid = Order::factory()->paid()->create();
        $completed = Order::factory()->completed()->create();
        $cancelled = Order::factory()->cancelled()->create();
        $refunded = Order::factory()->refunded()->create();

        expect($draft->status)->toBe('draft')
            ->and($pending->status)->toBe('pending_payment')
            ->and($pending->submitted_at)->not->toBeNull()
            ->and($paid->status)->toBe('paid')
            ->and($paid->paid_at)->not->toBeNull()
            ->and($completed->status)->toBe('completed')
            ->and($completed->completed_at)->not->toBeNull()
            ->and($cancelled->status)->toBe('cancelled')
            ->and($cancelled->cancelled_at)->not->toBeNull()
            ->and($refunded->status)->toBe('refunded');
    });
});
