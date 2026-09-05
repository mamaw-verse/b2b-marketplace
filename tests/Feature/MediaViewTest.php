<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\Product;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('s3');
});

test('unauthenticated users can view a document attached to a published product', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    $plan = SubscriptionPlan::factory()->create();
    Subscription::factory()->trialing()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
    ]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'status' => 'published']);

    $media = Media::factory()->create([
        'storage_path' => 'media/doc.pdf',
        'original_filename' => 'spec-sheet.pdf',
        'scan_status' => 'clean',
    ]);
    Storage::disk('s3')->put('media/doc.pdf', 'dummy');
    $product->documents()->attach($media->id);

    $this->get(route('media.view', $media))->assertOk();
});

test('unauthenticated users cannot view a document not attached to a published product', function () {
    $media = Media::factory()->create([
        'storage_path' => 'media/private.pdf',
        'original_filename' => 'private.pdf',
        'scan_status' => 'clean',
    ]);
    Storage::disk('s3')->put('media/private.pdf', 'dummy');

    $this->get(route('media.view', $media))->assertForbidden();
});

test('documents on an archived product remain private to guests', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'status' => 'archived']);

    $media = Media::factory()->create([
        'storage_path' => 'media/archived.pdf',
        'original_filename' => 'archived.pdf',
        'scan_status' => 'clean',
    ]);
    Storage::disk('s3')->put('media/archived.pdf', 'dummy');
    $product->documents()->attach($media->id);

    $this->get(route('media.view', $media))->assertForbidden();
});

test('the original uploader can still view their own private document', function () {
    $owner = User::factory()->create();
    $media = Media::factory()->create([
        'uploaded_by_id' => $owner->id,
        'storage_path' => 'media/owned.pdf',
        'original_filename' => 'owned.pdf',
        'scan_status' => 'clean',
    ]);
    Storage::disk('s3')->put('media/owned.pdf', 'dummy');

    $this->actingAs($owner)->get(route('media.view', $media))->assertOk();
});

test('an RFQ recipient (seller) can view an RFQ attachment', function () {
    $buyer = User::factory()->create(['email_verified_at' => now()]);
    $seller = User::factory()->create(['email_verified_at' => now()]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
    RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);

    $media = Media::factory()->create([
        'uploaded_by_id' => $buyer->id,
        'storage_path' => 'media/rfq-attach.pdf',
        'original_filename' => 'rfq-attach.pdf',
        'scan_status' => 'clean',
    ]);
    Storage::disk('s3')->put('media/rfq-attach.pdf', 'dummy');
    $rfq->attachments()->attach($media->id);

    $this->actingAs($seller)->get(route('media.view', $media))->assertOk();
});

test('a non-recipient seller cannot view an RFQ attachment', function () {
    $buyer = User::factory()->create(['email_verified_at' => now()]);
    $seller = User::factory()->create(['email_verified_at' => now()]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);

    $media = Media::factory()->create([
        'uploaded_by_id' => $buyer->id,
        'storage_path' => 'media/rfq-private.pdf',
        'original_filename' => 'rfq-private.pdf',
        'scan_status' => 'clean',
    ]);
    Storage::disk('s3')->put('media/rfq-private.pdf', 'dummy');
    $rfq->attachments()->attach($media->id);

    $this->actingAs($seller)->get(route('media.view', $media))->assertForbidden();
});

test('the RFQ buyer can view their own RFQ attachment', function () {
    $buyer = User::factory()->create(['email_verified_at' => now()]);

    $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);

    $media = Media::factory()->create([
        'uploaded_by_id' => $buyer->id,
        'storage_path' => 'media/rfq-buyer.pdf',
        'original_filename' => 'rfq-buyer.pdf',
        'scan_status' => 'clean',
    ]);
    Storage::disk('s3')->put('media/rfq-buyer.pdf', 'dummy');
    $rfq->attachments()->attach($media->id);

    $this->actingAs($buyer)->get(route('media.view', $media))->assertOk();
});
