<?php

declare(strict_types=1);

use App\Livewire\Account\ChatInbox as AccountChatInbox;
use App\Livewire\CompanyHub\ChatInbox as HubChatInbox;
use App\Models\BuyerProfile;
use App\Models\Conversation;
use App\Models\ConversationThread;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->buyerUser = User::factory()->create();
    $this->buyerUser->assignRole('buyer');

    $this->sellerUser = User::factory()->create();
    $this->sellerUser->assignRole('seller');

    $this->buyer = BuyerProfile::factory()->create(['user_id' => $this->buyerUser->id]);
    $this->shop = Shop::factory()->create(['user_id' => $this->sellerUser->id, 'status' => 'active']);

    $this->conversation = Conversation::create([
        'buyer_user_id' => $this->buyerUser->id,
        'shop_id' => $this->shop->id,
    ]);

    $this->thread = ConversationThread::factory()->default()->create([
        'conversation_id' => $this->conversation->id,
    ]);
});

it('renders the account chat inbox and selects conversation/thread', function () {
    actingAs($this->buyerUser);

    Livewire::test(AccountChatInbox::class)
        ->assertOk()
        ->assertSee(__('Messages'))
        ->call('selectConversation', $this->conversation->id)
        ->assertSet('selectedConversationId', $this->conversation->id)
        ->call('selectThread', $this->thread->id)
        ->assertSet('selectedThreadId', $this->thread->id);
});

it('renders the hub chat inbox and selects conversation/thread', function () {
    actingAs($this->sellerUser);

    Livewire::test(HubChatInbox::class)
        ->assertOk()
        ->assertSee(__('Messages'))
        ->call('selectConversation', $this->conversation->id)
        ->assertSet('selectedConversationId', $this->conversation->id)
        ->call('selectThread', $this->thread->id)
        ->assertSet('selectedThreadId', $this->thread->id);
});

it('allows sending a message', function () {
    actingAs($this->buyerUser);

    Livewire::test(AccountChatInbox::class)
        ->call('selectConversation', $this->conversation->id)
        ->call('selectThread', $this->thread->id)
        ->set('newMessage', 'Hello Seller!')
        ->call('sendMessage')
        ->assertSet('newMessage', '');

    $this->assertDatabaseHas('messages', [
        'thread_id' => $this->thread->id,
        'sender_id' => $this->buyerUser->id,
        'body' => 'Hello Seller!',
    ]);
});

it('clears computed messages cache on messageSent event', function () {
    actingAs($this->buyerUser);

    $component = Livewire::test(AccountChatInbox::class)
        ->call('selectConversation', $this->conversation->id)
        ->call('selectThread', $this->thread->id);

    $component->call('messageReceived')
        ->assertHasNoErrors();
});
