<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('s3');
});

test('unauthenticated users can view a document attached to a published product', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
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
