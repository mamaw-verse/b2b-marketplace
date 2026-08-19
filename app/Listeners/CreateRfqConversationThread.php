<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RfqCreated;
use App\Models\Conversation;
use App\Models\Message;

final class CreateRfqConversationThread
{
    public function handle(RfqCreated $event): void
    {
        $rfq = $event->rfq;
        $recipient = $rfq->recipients()->first();

        if (! $recipient) {
            return;
        }

        // Find or create conversation for this buyer-seller pair
        $conversation = Conversation::firstOrCreate([
            'buyer_user_id' => $rfq->user_id,
            'shop_id' => $recipient->shop_id,
        ]);

        // Create a default thread if none exists
        if (! $conversation->threads()->where('is_default', true)->exists()) {
            $conversation->threads()->create([
                'title' => 'General',
                'is_default' => true,
            ]);
        }

        // Create an RFQ-linked thread
        $thread = $conversation->threads()->create([
            'title' => $rfq->title,
            'rfq_id' => $rfq->id,
            'is_default' => false,
        ]);

        // Post a system message in the thread
        Message::create([
            'thread_id' => $thread->id,
            'sender_id' => $rfq->user_id,
            'body' => 'Request for Quotation opened: '.$rfq->title,
        ]);
    }
}
