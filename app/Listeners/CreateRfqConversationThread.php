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
        // Phase 6 M5: RFQ auto-threading is removed from the RFQ creation path
        // when admin_rfq_management_enabled is on. Chat stays enabled — a buyer
        // can still message a seller directly via "Message this company" on the
        // storefront company page. This listener is retained (not deleted) per the
        // additive-only principle so toggling the flag off restores prior behaviour.
        if (config('marketplace.features.admin_rfq_management_enabled')) {
            return;
        }

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
