<?php

declare(strict_types=1);

use App\Events\OrderPaid;
use App\Events\OrderSubmittedForPayment;
use App\Listeners\CreateOrderConversationThread;
use App\Listeners\PostOrderStatusMessage;
use App\Models\Conversation;
use App\Models\ConversationThread;
use App\Models\Message;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Order Thread', function () {
    test('submitting an order creates a conversation thread with order_id set', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
        $buyer = User::factory()->create();
        $order = Order::factory()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'status' => 'draft',
        ]);

        $event = new OrderSubmittedForPayment($order);
        $listener = new CreateOrderConversationThread;
        $listener->handle($event);

        // Conversation created
        $conversation = Conversation::where('buyer_user_id', $buyer->id)
            ->where('shop_id', $shop->id)
            ->first();
        expect($conversation)->not->toBeNull();

        // Thread created with order_id
        $thread = ConversationThread::where('order_id', $order->id)->first();
        expect($thread)->not->toBeNull()
            ->and($thread->title)->toBe('Order #'.$order->id)
            ->and($thread->is_default)->toBeFalse()
            ->and($thread->isOrderLinked())->toBeTrue();
    });

    test('system message with order prefix is posted on submission', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
        $buyer = User::factory()->create();
        $order = Order::factory()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'status' => 'draft',
        ]);

        $event = new OrderSubmittedForPayment($order);
        $listener = new CreateOrderConversationThread;
        $listener->handle($event);

        $thread = ConversationThread::where('order_id', $order->id)->first();
        $message = Message::where('thread_id', $thread->id)->first();

        expect($message)->not->toBeNull()
            ->and($message->body)->toBe('order:'.$order->id)
            ->and($message->sender_id)->toBe($seller->id);
    });

    test('status change events post system messages in the order thread', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
        $buyer = User::factory()->create();
        $order = Order::factory()->paid()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);

        // Create a conversation and thread for this order
        $conversation = Conversation::create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);
        ConversationThread::create([
            'conversation_id' => $conversation->id,
            'title' => 'Order #'.$order->id,
            'order_id' => $order->id,
            'is_default' => false,
        ]);

        // Trigger the subscriber
        $subscriber = new PostOrderStatusMessage;
        $subscriber->handleOrderPaid(new OrderPaid($order));

        $thread = ConversationThread::where('order_id', $order->id)->first();
        $messages = Message::where('thread_id', $thread->id)->get();

        expect($messages)->toHaveCount(1)
            ->and($messages->first()->body)->toBe('order:'.$order->id);
    });

    test('reuses existing conversation (does not create duplicate)', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
        $buyer = User::factory()->create();

        // Pre-existing conversation
        Conversation::create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);

        $order = Order::factory()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'status' => 'draft',
        ]);

        $event = new OrderSubmittedForPayment($order);
        $listener = new CreateOrderConversationThread;
        $listener->handle($event);

        // Should still only have 1 conversation
        $conversationCount = Conversation::where('buyer_user_id', $buyer->id)
            ->where('shop_id', $shop->id)
            ->count();
        expect($conversationCount)->toBe(1);

        // But a new thread is created for the order
        $thread = ConversationThread::where('order_id', $order->id)->first();
        expect($thread)->not->toBeNull();
    });
});
