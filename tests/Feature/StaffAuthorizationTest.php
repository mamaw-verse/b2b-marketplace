<?php

declare(strict_types=1);

use App\Models\Dispute;
use App\Models\Fulfilment;
use App\Models\Media;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductGallery;
use App\Models\ProductVariant;
use App\Models\Quotation;
use App\Models\Review;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\ShopStaff;
use App\Models\User;
use App\Notifications\ProductFlaggedNotification;
use App\Notifications\ReviewReceivedNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config()->set('marketplace.features.staff_enabled', true);
});

// ---------------------------------------------------------------------------
// Policy access — basic product + hub gates
// ---------------------------------------------------------------------------

test('active staff can manage products and reach the hub but cannot manage the shop', function (): void {
    $owner = User::factory()->create();
    $staff = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    expect(Gate::forUser($staff)->allows('accessHub'))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('update', $product))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('createProduct', $shop))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('manageStaff', $shop))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('manageShop'))->toBeFalse();
});

test('revoked staff and staff of a suspended shop lose seller access', function (): void {
    $owner = User::factory()->create();
    $staff = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $membership = ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    // Revoked staff has no activeShop → all gates fail.
    $membership->update(['status' => 'revoked']);
    expect(Gate::forUser($staff->fresh())->allows('accessHub'))->toBeFalse()
        ->and(Gate::forUser($staff->fresh())->allows('update', $product->fresh()))->toBeFalse();

    // Reactivate, then suspend the shop.
    $membership->update(['status' => 'active']);
    $shop->update(['status' => 'suspended']);
    expect(Gate::forUser($staff->fresh())->allows('accessHub'))->toBeFalse()
        ->and(Gate::forUser($staff->fresh())->allows('update', $product->fresh()))->toBeFalse();
});

test('staff cannot edit products from another shop and cannot open owner-only pages', function (): void {
    $shopOwner = User::factory()->create(['email_verified_at' => now()]);
    $otherOwner = User::factory()->create();
    $staff = User::factory()->create(['email_verified_at' => now()]);
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $otherShop = Shop::factory()->create(['user_id' => $otherOwner->id]);
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $otherShop->id,
        'email' => $staff->email,
        'invited_by_id' => $otherOwner->id,
    ]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $this->actingAs($staff)->get(route('hub.products.edit', $product))->assertForbidden();
    $this->get(route('hub.shop.profile'))->assertForbidden();
});

// ---------------------------------------------------------------------------
// Full permission matrix — every policy method
// ---------------------------------------------------------------------------

test('staff pass the full seller-side permission matrix and fail owner-only checks', function (): void {
    config()->set('marketplace.features.orders_enabled', true);
    config()->set('marketplace.features.disputes_enabled', true);
    config()->set('marketplace.features.reviews_enabled', true);
    config()->set('marketplace.features.seller_rfq_queue_enabled', true);
    config()->set('marketplace.features.quotations_enabled', true);
    config()->set('marketplace.features.subscriptions_enabled', false);

    $owner = User::factory()->create();
    $staff = User::factory()->create();
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);

    $product = Product::factory()->create(['shop_id' => $shop->id, 'status' => 'published']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $order = Order::factory()->create(['shop_id' => $shop->id, 'buyer_user_id' => $buyer->id, 'status' => 'draft']);
    $paidOrder = Order::factory()->paid()->create(['shop_id' => $shop->id, 'buyer_user_id' => $buyer->id]);
    $fulfilment = Fulfilment::factory()->create(['order_id' => $paidOrder->id, 'status' => 'pending']);
    $dispute = Dispute::factory()->create(['order_id' => $paidOrder->id, 'opened_by_user_id' => $buyer->id, 'type' => 'refund_only', 'status' => 'open']);
    $review = Review::factory()->forShop($shop)->create(['reviewer_user_id' => $buyer->id]);

    $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
    $rfqRecipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);
    $quotation = Quotation::factory()->create(['rfq_recipient_id' => $rfqRecipient->id, 'rfq_id' => $rfq->id, 'status' => 'draft']);

    $staffGate = Gate::forUser($staff);

    // ProductPolicy — staff can view, update, delete (shared); publish requires active shop
    expect($staffGate->allows('view', $product))->toBeTrue()
        ->and($staffGate->allows('update', $product))->toBeTrue()
        ->and($staffGate->allows('delete', $product))->toBeTrue()
        ->and($staffGate->allows('publish', $product))->toBeTrue();

    // ProductVariantPolicy — staff can manage variants
    expect($staffGate->allows('view', $variant))->toBeTrue()
        ->and($staffGate->allows('update', $variant))->toBeTrue()
        ->and($staffGate->allows('delete', $variant))->toBeTrue();

    // ShopPolicy — createProduct shared; update/delete/manageStaff owner-only
    expect($staffGate->allows('createProduct', $shop))->toBeTrue()
        ->and($staffGate->allows('update', $shop))->toBeFalse()
        ->and($staffGate->allows('delete', $shop))->toBeFalse()
        ->and($staffGate->allows('manageStaff', $shop))->toBeFalse();

    // OrderPolicy — seller-side methods shared
    expect($staffGate->allows('create', Order::class))->toBeTrue()
        ->and($staffGate->allows('view', $order))->toBeTrue()
        ->and($staffGate->allows('update', $order))->toBeTrue()    // draft
        ->and($staffGate->allows('submit', $order))->toBeTrue()
        ->and($staffGate->allows('delete', $order))->toBeTrue()
        ->and($staffGate->allows('cancelAsSeller', $paidOrder))->toBeTrue()
        ->and($staffGate->allows('manageDispute', $paidOrder))->toBeTrue();

    // FulfilmentPolicy — staff can manage fulfilments
    expect($staffGate->allows('create', $paidOrder))->toBeTrue()
        ->and($staffGate->allows('update', $fulfilment))->toBeTrue()
        ->and($staffGate->allows('ship', $fulfilment))->toBeTrue();

    // DisputePolicy — staff can view, resolve, confirmReturn, escalate
    expect($staffGate->allows('view', $dispute))->toBeTrue()
        ->and($staffGate->allows('resolve', $dispute))->toBeTrue()
        ->and($staffGate->allows('escalate', $dispute))->toBeTrue();

    // ReviewPolicy — staff can respond to reviews on their shop
    expect($staffGate->allows('respond', $review))->toBeTrue();

    // RfqRecipientPolicy — staff can manage RFQ recipients
    expect($staffGate->allows('viewAny', RfqRecipient::class))->toBeTrue()
        ->and($staffGate->allows('view', $rfqRecipient))->toBeTrue()
        ->and($staffGate->allows('review', $rfqRecipient))->toBeTrue()
        ->and($staffGate->allows('close', $rfqRecipient))->toBeTrue();

    // QuotationPolicy — staff can manage quotations
    // create() takes a RfqRecipient, so the policy is resolved via [Quotation::class, $rfqRecipient]
    expect($staffGate->allows('create', [Quotation::class, $rfqRecipient]))->toBeTrue()
        ->and($staffGate->allows('update', $quotation))->toBeTrue()
        ->and($staffGate->allows('submit', $quotation))->toBeFalse() // no items yet
        ->and($staffGate->allows('view', $quotation))->toBeTrue();

    // Gate manageShop — owner-only
    expect($staffGate->allows('manageShop'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Operational recipients
// ---------------------------------------------------------------------------

test('operational recipients include active staff only while the feature is enabled', function (): void {
    $owner = User::factory()->create();
    $activeStaff = User::factory()->create();
    $revokedStaff = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    ShopStaff::factory()->active($activeStaff)->create([
        'shop_id' => $shop->id,
        'email' => $activeStaff->email,
        'invited_by_id' => $owner->id,
    ]);
    ShopStaff::factory()->active($revokedStaff)->create([
        'shop_id' => $shop->id,
        'email' => $revokedStaff->email,
        'invited_by_id' => $owner->id,
        'status' => 'revoked',
    ]);

    expect($shop->operationalRecipients()->pluck('id')->all())->toBe([$owner->id, $activeStaff->id]);

    config()->set('marketplace.features.staff_enabled', false);
    expect($shop->operationalRecipients()->pluck('id')->all())->toBe([$owner->id]);
});

// ---------------------------------------------------------------------------
// Notification routing split — operational vs. owner-only
// ---------------------------------------------------------------------------

test('operational notifications reach both owner and active staff', function (): void {
    Notification::fake();

    $owner = User::factory()->create();
    $staff = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);
    $review = Review::factory()->forShop($shop)->create();

    // Simulate what CreateReviewAction does (operational → operationalRecipients).
    Notification::send($shop->operationalRecipients(), new ReviewReceivedNotification($review));

    Notification::assertSentTo($owner, ReviewReceivedNotification::class);
    Notification::assertSentTo($staff, ReviewReceivedNotification::class);
});

test('owner-only notifications do not reach staff', function (): void {
    Notification::fake();

    $owner = User::factory()->create();
    $staff = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    // Simulate what ProductModerationList does (owner-only → $shop->user->notify).
    $product->shop->user->notify(new ProductFlaggedNotification($product));

    Notification::assertSentTo($owner, ProductFlaggedNotification::class);
    Notification::assertNotSentTo($staff, ProductFlaggedNotification::class);
});

// ---------------------------------------------------------------------------
// Media access — staff can view draft product media, revoked staff cannot
// ---------------------------------------------------------------------------

test('staff can view their shop draft product gallery images via media.view', function (): void {
    Storage::fake('s3');
    $this->seed(RolePermissionSeeder::class);

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $staff = User::factory()->create(['email_verified_at' => now()]);
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'status' => 'archived']);
    $media = Media::factory()->create([
        'storage_path' => 'media/draft-image.jpg',
        'original_filename' => 'draft-image.jpg',
        'scan_status' => 'clean',
        'uploaded_by_id' => $owner->id,
    ]);
    Storage::disk('s3')->put('media/draft-image.jpg', 'dummy');
    ProductGallery::create([
        'product_id' => $product->id,
        'media_id' => $media->id,
        'sort_order' => 0,
    ]);

    $this->actingAs($staff)->get(route('media.view', $media))->assertOk();
});

test('revoked staff cannot view draft product gallery images via media.view', function (): void {
    Storage::fake('s3');
    $this->seed(RolePermissionSeeder::class);

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $staff = User::factory()->create(['email_verified_at' => now()]);
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    $membership = ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'status' => 'archived']);
    $media = Media::factory()->create([
        'storage_path' => 'media/draft-revoked.jpg',
        'original_filename' => 'draft-revoked.jpg',
        'scan_status' => 'clean',
        'uploaded_by_id' => $owner->id,
    ]);
    Storage::disk('s3')->put('media/draft-revoked.jpg', 'dummy');
    ProductGallery::create([
        'product_id' => $product->id,
        'media_id' => $media->id,
        'sort_order' => 0,
    ]);

    $membership->update(['status' => 'revoked']);
    $this->actingAs($staff->fresh())->get(route('media.view', $media))->assertForbidden();
});

// ---------------------------------------------------------------------------
// Staff blocked from onboarding
// ---------------------------------------------------------------------------

test('staff visiting the onboarding wizard are redirected with a message', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $staff = User::factory()->create(['email_verified_at' => now()]);
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);

    $this->actingAs($staff)
        ->get(route('account.onboarding'))
        ->assertRedirect(route('portal.dashboard'))
        ->assertSessionHas('error');
});

// ---------------------------------------------------------------------------
// Staff denied owner-only hub routes
// ---------------------------------------------------------------------------

test('staff are denied access to owner-only hub routes', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $staff = User::factory()->create(['email_verified_at' => now()]);
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);

    // Routes behind shop.owner middleware.
    $this->actingAs($staff)->get(route('hub.shop.profile'))->assertForbidden();
    $this->actingAs($staff)->get(route('hub.certifications.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('hub.earnings'))->assertForbidden();
});

// ---------------------------------------------------------------------------
// API attribution — staff product mutations set updated_by_id
// ---------------------------------------------------------------------------

test('staff product mutations via api v1 are attributed to the staff user', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $owner = User::factory()->create();
    $staff = User::factory()->create(['email_verified_at' => now()]);
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'name' => 'Original Name']);

    $response = $this->actingAs($staff, 'sanctum')
        ->putJson("/api/v1/hub/products/{$product->id}", ['name' => 'Staff Edited Name']);

    $response->assertOk();
    expect($product->fresh()->updated_by_id)->toBe($staff->id);

    // Revoked staff can no longer mutate via API.
    ShopStaff::where('user_id', $staff->id)->update(['status' => 'revoked']);
    $this->actingAs($staff->fresh(), 'sanctum')
        ->putJson("/api/v1/hub/products/{$product->id}", ['name' => 'Should Fail'])
        ->assertForbidden();
});
