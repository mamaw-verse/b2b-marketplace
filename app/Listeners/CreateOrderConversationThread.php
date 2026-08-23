<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderSubmittedForPayment;
use App\Models\Conversation;
use App\Models\Message;

final class CreateOrderConversationThread
{
    public function handle(OrderSubmittedForPayment $event): void
    {
        $order = $event->order;

        // Find or create conversation for this buyer-seller pair
        $conversation = Conversation::firstOrCreate([
            'buyer_user_id' => $order->buyer_user_id,
            'shop_id' => $order->shop_id,
        ]);

        // Create a default thread if none exists
        if (! $conversation->threads()->where('is_default', true)->exists()) {
            $conversation->threads()->create([
                'title' => 'General',
                'is_default' => true,
            ]);
        }

        // Create an order-linked thread
        $thread = $conversation->threads()->create([
            'title' => 'Order #'.$order->id,
            'order_id' => $order->id,
            'is_default' => false,
        ]);

        // Post a system message with order card prefix
        Message::create([
            'thread_id' => $thread->id,
            'sender_id' => $order->shop->user_id,
            'body' => 'order:'.$order->id,
        ]);
    }
}
