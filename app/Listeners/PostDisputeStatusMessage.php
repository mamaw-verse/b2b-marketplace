<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DisputeEscalated;
use App\Events\DisputeOpened;
use App\Events\DisputeResolved;
use App\Models\ConversationThread;
use App\Models\Dispute;
use App\Models\Message;
use Illuminate\Events\Dispatcher;

final class PostDisputeStatusMessage
{
    public function handleDisputeOpened(DisputeOpened $event): void
    {
        $this->postMessage($event->dispute);
    }

    public function handleDisputeResolved(DisputeResolved $event): void
    {
        $this->postMessage($event->dispute);
    }

    public function handleDisputeEscalated(DisputeEscalated $event): void
    {
        $this->postMessage($event->dispute);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(DisputeOpened::class, [self::class, 'handleDisputeOpened']);
        $events->listen(DisputeResolved::class, [self::class, 'handleDisputeResolved']);
        $events->listen(DisputeEscalated::class, [self::class, 'handleDisputeEscalated']);
    }

    private function postMessage(Dispute $dispute): void
    {
        $thread = ConversationThread::where('order_id', $dispute->order_id)->first();

        if (! $thread) {
            return;
        }

        Message::create([
            'thread_id' => $thread->id,
            'sender_id' => $dispute->opened_by_user_id,
            'body' => 'dispute:'.$dispute->id,
        ]);
    }
}
