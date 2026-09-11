<?php

declare(strict_types=1);

use App\Events\RfqCreated;
use App\Jobs\RfqAutoCloseJob;
use App\Listeners\NotifyAdminRfqReceived;
use App\Livewire\Admin\Rfq\RfqDetail;
use App\Livewire\Admin\Rfq\RfqList;
use App\Livewire\Storefront\CompanyProfile;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\RfqStatusUpdatedNotification;
use App\Notifications\RfqSubmittedAdminNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
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

test('admin RFQ detail shows the product the quotation is being requested for', function () {
    $product = Product::factory()->published()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Halal Frozen Whole Chicken',
        'featured_image_path' => 'products/chicken.jpg',
    ]);

    $rfq = Rfq::factory()->create(['user_id' => $this->buyer->id, 'product_id' => $product->id]);
    RfqRecipient::factory()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $this->shop->id,
        'status' => 'received',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.rfqs.show', $rfq))
        ->assertOk()
        ->assertSee('Product for Quotation')
        ->assertSee('Halal Frozen Whole Chicken')
        ->assertSee($product->featured_image_url, false)
        ->assertSee(route('storefront.product', $product), false);
});

test('admin RFQ detail omits the product block when no product is linked', function () {
    $rfq = Rfq::factory()->create(['user_id' => $this->buyer->id]);
    RfqRecipient::factory()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $this->shop->id,
        'status' => 'received',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.rfqs.show', $rfq))
        ->assertOk()
        ->assertDontSee('Product for Quotation');
});

test('changing the admin status notifies the buyer', function () {
    Notification::fake();

    $rfq = Rfq::factory()->create(['user_id' => $this->buyer->id]);
    $recipient = RfqRecipient::factory()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $this->shop->id,
        'status' => 'received',
        'admin_status' => 'pending',
    ]);

    Livewire::actingAs($this->admin)
        ->test(RfqDetail::class, ['rfq' => $rfq])
        ->set('adminStatus', 'in_progress')
        ->call('saveAdminDetails');

    Notification::assertSentTo(
        $this->buyer,
        RfqStatusUpdatedNotification::class,
        function (RfqStatusUpdatedNotification $notification) use ($recipient) {
            return $notification->recipient->is($recipient)
                && $notification->previousStatus === 'pending'
                && $notification->currentStatus === 'in_progress';
        }
    );
});

test('saving without a status change does not notify the buyer', function () {
    Notification::fake();

    $rfq = Rfq::factory()->create(['user_id' => $this->buyer->id]);
    RfqRecipient::factory()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $this->shop->id,
        'status' => 'received',
        'admin_status' => 'pending',
    ]);

    Livewire::actingAs($this->admin)
        ->test(RfqDetail::class, ['rfq' => $rfq])
        ->set('adminNotes', 'Just adding a note. Status unchanged.')
        ->call('saveAdminDetails');

    Notification::assertNotSentTo($this->buyer, RfqStatusUpdatedNotification::class);
});

test('RfqStatusUpdatedNotification sends an in-app and email notification to the buyer', function () {
    $rfq = Rfq::factory()->create(['user_id' => $this->buyer->id, 'title' => 'Bulk Halal Flour']);
    $recipient = RfqRecipient::factory()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $this->shop->id,
        'status' => 'received',
    ]);

    $notification = new RfqStatusUpdatedNotification($recipient, 'pending', 'in_progress');
    $mail = $notification->toMail($this->buyer);
    $data = $notification->toArray($this->buyer);

    expect($notification->via($this->buyer))->toBe(['database', 'mail', 'broadcast'])
        ->and($mail->subject)->toContain('Bulk Halal Flour')
        ->and($mail->actionUrl)->toBe(route('account.rfqs.show', $rfq->id))
        ->and($data['type'])->toBe('rfq_status_updated')
        ->and($data['message'])->toContain('In Progress');
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
    event(new RfqCreated($rfq));

    // Verify conversation was NOT created
    $this->assertDatabaseMissing('conversations', [
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);
});

test('direct message this company creates a conversation thread', function () {
    Config::set('marketplace.features.chat_enabled', true);

    // CompanyProfile requires an active/trialing subscription to be visible when
    // subscriptions_enabled is true. Give the shop a trial subscription.
    Subscription::factory()->trialing()->create(['shop_id' => $this->shop->id]);

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
    (new RfqAutoCloseJob)->handle();

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

test('RfqCreated event notifies all admins via RfqSubmittedAdminNotification', function () {
    Notification::fake();

    $rfq = Rfq::factory()->create(['user_id' => $this->buyer->id]);
    RfqRecipient::factory()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $this->shop->id,
    ]);

    // Second admin to verify fan-out
    $admin2 = User::factory()->create();
    $admin2->assignRole('admin');

    (new NotifyAdminRfqReceived)->handle(new RfqCreated($rfq));

    Notification::assertSentTo($this->admin, RfqSubmittedAdminNotification::class);
    Notification::assertSentTo($admin2, RfqSubmittedAdminNotification::class);
    Notification::assertNotSentTo($this->buyer, RfqSubmittedAdminNotification::class);
    Notification::assertNotSentTo($this->seller, RfqSubmittedAdminNotification::class);
});

test('RfqSubmittedAdminNotification mail references the admin RFQ route', function () {
    $rfq = Rfq::factory()->create(['user_id' => $this->buyer->id, 'title' => 'Bulk Halal Flour']);

    $notification = new RfqSubmittedAdminNotification($rfq);
    $mail = $notification->toMail($this->admin);

    expect($mail->subject)->toContain('Bulk Halal Flour')
        ->and($mail->actionUrl)->toBe(route('admin.rfqs.show', $rfq->id));
});
