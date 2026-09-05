<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Events\OrderCompleted;
use App\Events\OrderDelivered;
use App\Events\OrderPaid;
use App\Events\OrderReturned;
use App\Events\OrderShipped;
use App\Models\ConversationThread;
use App\Models\Message;
use App\Models\Order;
use Illuminate\Events\Dispatcher;

final class PostOrderStatusMessage
{
    public function handleOrderPaid(OrderPaid $event): void
    {
        $this->postMessage($event->order);
    }

    public function handleOrderShipped(OrderShipped $event): void
    {
        $this->postMessage($event->order);
    }

    public function handleOrderDelivered(OrderDelivered $event): void
    {
        $this->postMessage($event->order);
    }

    public function handleOrderCompleted(OrderCompleted $event): void
    {
        $this->postMessage($event->order);
    }

    public function handleOrderCancelled(OrderCancelled $event): void
    {
        $this->postMessage($event->order);
    }

    public function handleOrderReturned(OrderReturned $event): void
    {
        $this->postMessage($event->order);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(OrderPaid::class, [self::class, 'handleOrderPaid']);
        $events->listen(OrderShipped::class, [self::class, 'handleOrderShipped']);
        $events->listen(OrderDelivered::class, [self::class, 'handleOrderDelivered']);
        $events->listen(OrderCompleted::class, [self::class, 'handleOrderCompleted']);
        $events->listen(OrderCancelled::class, [self::class, 'handleOrderCancelled']);
        $events->listen(OrderReturned::class, [self::class, 'handleOrderReturned']);
    }

    private function postMessage(Order $order): void
    {
        $thread = ConversationThread::where('order_id', $order->id)->first();

        if (! $thread) {
            return;
        }

        Message::create([
            'thread_id' => $thread->id,
            'sender_id' => $order->shop->user_id,
            'body' => 'order:'.$order->id,
        ]);
    }
}
