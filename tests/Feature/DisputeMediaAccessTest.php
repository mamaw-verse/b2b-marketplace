<?php

declare(strict_types=1);

use App\Models\Dispute;
use App\Models\Media;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('s3');

    $this->buyer = User::factory()->create(['email_verified_at' => now()]);
    $this->buyer->assignRole('buyer');

    $this->seller = User::factory()->create(['email_verified_at' => now()]);
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);

    $this->order = Order::factory()->completed()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);

    $this->dispute = Dispute::factory()->create([
        'order_id' => $this->order->id,
        'opened_by_user_id' => $this->buyer->id,
    ]);

    // Photo uploaded by the buyer, attached to the dispute.
    $this->photo = Media::factory()->create(['uploaded_by_id' => $this->buyer->id]);
    Storage::disk('s3')->put($this->photo->storage_path, 'fake-image-bytes');
    $this->dispute->photos()->attach($this->photo->id);
});

test('the seller (shop owner) can view a dispute photo uploaded by the buyer', function () {
    $this->actingAs($this->seller)
        ->get(route('media.view', $this->photo))
        ->assertOk();
});

test('the buyer who uploaded the dispute photo can view it', function () {
    $this->actingAs($this->buyer)
        ->get(route('media.view', $this->photo))
        ->assertOk();
});

test('an unrelated user cannot view a dispute photo', function () {
    $stranger = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($stranger)
        ->get(route('media.view', $this->photo))
        ->assertForbidden();
});
