<?php

declare(strict_types=1);

use App\Events\MessageSent;
use App\Events\RfqCreated;
use App\Models\BuyerProfile;
use App\Models\Conversation;
use App\Models\ConversationThread;
use App\Models\Message;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function createConversationPair(): array
{
    $buyer = User::factory()->create();
    BuyerProfile::factory()->create(['user_id' => $buyer->id]);
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $conversation = Conversation::factory()->create([
        'buyer_user_id' => $buyer->id,
        'shop_id' => $shop->id,
    ]);
    $thread = ConversationThread::factory()->default()->create([
        'conversation_id' => $conversation->id,
    ]);

    return [$buyer, $seller, $shop, $conversation, $thread];
}

describe('Conversation Listing', function () {
    test('buyer can list their conversations', function () {
        [$buyer, $seller, $shop, $conversation, $thread] = createConversationPair();

        $response = $this->actingAs($buyer)->getJson('/api/v1/conversations');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $conversation->id)
            ->assertJsonPath('data.0.counterpart.type', 'shop')
            ->assertJsonPath('data.0.counterpart.name', $shop->storefront_name);
    });

    test('seller can list their conversations', function () {
        [$buyer, $seller, $shop, $conversation, $thread] = createConversationPair();

        $response = $this->actingAs($seller)->getJson('/api/v1/conversations');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.counterpart.type', 'buyer');
    });

    test('user cannot see conversations they are not part of', function () {
        [$buyer, $seller, $shop, $conversation, $thread] = createConversationPair();
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)->getJson('/api/v1/conversations');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });
});

describe('Thread Management', function () {
    test('participant can list threads', function () {
        [$buyer, $seller, $shop, $conversation, $thread] = createConversationPair();

        $response = $this->actingAs($buyer)->getJson("/api/v1/conversations/{$conversation->id}/threads");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_default', true);
    });

    test('non-participant cannot list threads', function () {
        [$buyer, $seller, $shop, $conversation, $thread] = createConversationPair();
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)->getJson("/api/v1/conversations/{$conversation->id}/threads");

        $response->assertForbidden();
    });

    test('participant can create a thread', function () {
        [$buyer, $seller, $shop, $conversation, $thread] = createConversationPair();

        $response = $this->actingAs($buyer)->postJson("/api/v1/conversations/{$conversation->id}/threads", [
            'title' => 'Packaging Discussion',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Packaging Discussion')
            ->assertJsonPath('data.is_default', false);

        $this->assertDatabaseHas('conversation_threads', [
            'conversation_id' => $conversation->id,
            'title' => 'Packaging Discussion',
        ]);
    });

    test('non-participant cannot create a thread', function () {
        [$buyer, $seller, $shop, $conversation, $thread] = createConversationPair();
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)->postJson("/api/v1/conversations/{$conversation->id}/threads", [
            'title' => 'Hacked',
        ]);

        $response->assertForbidden();
    });
});

describe('Messaging', function () {
    test('participant can send a message', function () {
        Event::fake([MessageSent::class]);
        [$buyer, $seller, $shop, $conversation, $thread] = createConversationPair();

        $response = $this->actingAs($buyer)->postJson("/api/v1/threads/{$thread->id}/messages", [
            'body' => 'Hello, I have a question about your product.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.body', 'Hello, I have a question about your product.')
            ->assertJsonPath('data.sender.id', $buyer->id);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'sender_id' => $buyer->id,
            'body' => 'Hello, I have a question about your product.',
        ]);

        Event::assertDispatched(MessageSent::class);
    });

    test('non-participant cannot send a message', function () {
        [$buyer, $seller, $shop, $conversation, $thread] = createConversationPair();
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)->postJson("/api/v1/threads/{$thread->id}/messages", [
            'body' => 'Should not work.',
        ]);

        $response->assertForbidden();
    });

    test('participant can list messages in a thread', function () {
        [$buyer, $seller, $shop, $conversation, $thread] = createConversationPair();
        Message::factory()->count(3)->create(['thread_id' => $thread->id, 'sender_id' => $buyer->id]);

        $response = $this->actingAs($seller)->getJson("/api/v1/threads/{$thread->id}/messages");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    test('message body is required and max 10000 chars', function () {
        [$buyer, $seller, $shop, $conversation, $thread] = createConversationPair();

        $response = $this->actingAs($buyer)->postJson("/api/v1/threads/{$thread->id}/messages", [
            'body' => '',
        ]);

        $response->assertUnprocessable();
    });
});

describe('Conversation Reuse', function () {
    test('same buyer-seller pair reuses existing conversation', function () {
        $buyer = User::factory()->create();
        BuyerProfile::factory()->create(['user_id' => $buyer->id]);
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);

        // Create first conversation
        $conv1 = Conversation::firstOrCreate([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);

        // Try to create again — should return same
        $conv2 = Conversation::firstOrCreate([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);

        expect($conv1->id)->toBe($conv2->id);
        $this->assertDatabaseCount('conversations', 1);
    });
});

describe('RFQ Thread Auto-Creation', function () {
    test('RfqCreated event creates conversation and RFQ-linked thread', function () {
        $buyer = User::factory()->create();
        BuyerProfile::factory()->create(['user_id' => $buyer->id]);
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
        RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);

        // Dispatch the event (listener should handle it)
        Event::dispatch(new RfqCreated($rfq));

        // Verify conversation created
        $this->assertDatabaseHas('conversations', [
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);

        // Verify RFQ-linked thread created
        $conversation = Conversation::where('buyer_user_id', $buyer->id)
            ->where('shop_id', $shop->id)
            ->first();

        $this->assertDatabaseHas('conversation_threads', [
            'conversation_id' => $conversation->id,
            'rfq_id' => $rfq->id,
        ]);

        // Verify system message posted
        $thread = ConversationThread::where('rfq_id', $rfq->id)->first();
        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'sender_id' => $buyer->id,
        ]);
    });

    test('sending message in RFQ-linked thread updates rfq last_activity_at', function () {
        Event::fake([MessageSent::class]);
        [$buyer, $seller, $shop, $conversation, $thread] = createConversationPair();
        $rfq = Rfq::factory()->create([
            'user_id' => $buyer->id,
            'last_activity_at' => now()->subDays(3),
        ]);
        RfqRecipient::factory()->create([
            'rfq_id' => $rfq->id,
            'shop_id' => $shop->id,
            'last_activity_at' => now()->subDays(3),
        ]);
        $thread->update(['rfq_id' => $rfq->id]);

        $this->actingAs($buyer)->postJson("/api/v1/threads/{$thread->id}/messages", [
            'body' => 'Any update on the quotation?',
        ])->assertCreated();

        $rfq->refresh();
        expect($rfq->last_activity_at->diffInMinutes(now()))->toBeLessThan(1);
    });
});
