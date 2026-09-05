<?php

declare(strict_types=1);

use App\Livewire\Admin\Rfq\RfqDetail;
use App\Livewire\Admin\Rfq\RfqList;
use App\Livewire\Storefront\CompanyProfile;
use App\Models\Conversation;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->buyer = User::factory()->create();
    $this->buyer->assignRole('buyer');

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);
});

test('admin can see RFQ management screen but seller cannot', function () {
    Config::set('marketplace.features.admin_rfq_management_enabled', true);

    // Admin can access
    $this->actingAs($this->admin)
        ->get(route('admin.rfqs.index'))
        ->assertOk()
        ->assertSeeLivewire(RfqList::class);

    // Seller cannot access (middleware protects /admin prefix)
    $this->actingAs($this->seller)
        ->get(route('admin.rfqs.index'))
        ->assertForbidden(); // or redirect, depending on auth middleware
});

test('admin status transitions save correctly', function () {
    $rfq = Rfq::factory()->create(['user_id' => $this->buyer->id]);
    $recipient = RfqRecipient::factory()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $this->shop->id,
        'status' => 'received',
    ]);

    Livewire::actingAs($this->admin)
        ->test(RfqDetail::class, ['rfq' => $rfq])
        ->set('adminStatus', 'in_progress')
        ->set('adminNotes', 'Working on matching this.')
        ->call('saveAdminDetails');

    $this->assertDatabaseHas('rfq_recipients', [
        'id' => $recipient->id,
        'admin_status' => 'in_progress',
        'admin_notes' => 'Working on matching this.',
        'managed_by' => $this->admin->id, // auto-assigned
    ]);
});

test('rfq submission does not create a conversation thread when admin management is enabled', function () {
    Config::set('marketplace.features.admin_rfq_management_enabled', true);

    $rfq = Rfq::factory()->create(['user_id' => $this->buyer->id, 'title' => 'Test Threading']);
    $recipient = RfqRecipient::factory()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $this->shop->id,
        'status' => 'received',
    ]);

    // Dispatch the event
    event(new App\Events\RfqCreated($rfq));

    // Verify conversation was NOT created
    $this->assertDatabaseMissing('conversations', [
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);
});

test('direct message this company creates a conversation thread', function () {
    Config::set('marketplace.features.chat_enabled', true);

    Livewire::actingAs($this->buyer)
        ->test(CompanyProfile::class, ['shop' => $this->shop])
        ->call('messageCompany')
        ->assertRedirect(route('account.messages'));

    // Verify conversation was created
    $this->assertDatabaseHas('conversations', [
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);

    $conversation = Conversation::where('buyer_user_id', $this->buyer->id)
        ->where('shop_id', $this->shop->id)
        ->first();

    // Verify default thread exists
    $this->assertDatabaseHas('conversation_threads', [
        'conversation_id' => $conversation->id,
        'is_default' => true,
    ]);
});

test('RfqAutoCloseJob closes recipient admin status when admin management enabled', function () {
    Config::set('marketplace.features.admin_rfq_management_enabled', true);

    $rfq = Rfq::factory()->create([
        'user_id' => $this->buyer->id,
        'status' => 'open',
        'last_activity_at' => now()->subDays(65),
    ]);

    $recipient = RfqRecipient::factory()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $this->shop->id,
        'status' => 'received',
        'admin_status' => 'pending',
    ]);

    // Run the job
    (new App\Jobs\RfqAutoCloseJob())->handle();

    // Should be closed based on our recent changes to RfqAutoCloseJob
    $this->assertDatabaseHas('rfq_recipients', [
        'id' => $recipient->id,
        'admin_status' => 'closed',
    ]);

    // And the main RFQ should be closed
    $this->assertDatabaseHas('rfqs', [
        'id' => $rfq->id,
        'status' => 'closed',
    ]);
});
