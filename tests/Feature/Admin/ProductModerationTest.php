<?php

declare(strict_types=1);

use App\Livewire\Admin\Products\ProductModerationList;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\ProductFlaggedNotification;
use App\Notifications\ProductUnpublishedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'product.moderate', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->givePermissionTo('product.moderate');
});

it('can flag a product and notifies the seller', function () {
    Notification::fake();

    $shop = Shop::factory()->create();
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'moderation_status' => 'active',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProductModerationList::class)
        ->assertOk()
        ->call('flagProduct', $product->id);

    expect($product->fresh()->moderation_status)->toBe('flagged');

    Notification::assertSentTo($shop->user, ProductFlaggedNotification::class);
});

it('can unpublish a product and notifies the seller', function () {
    Notification::fake();

    $shop = Shop::factory()->create();
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'moderation_status' => 'active',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProductModerationList::class)
        ->assertOk()
        ->call('unpublishProduct', $product->id);

    expect($product->fresh()->moderation_status)->toBe('admin_unpublished');

    Notification::assertSentTo($shop->user, ProductUnpublishedNotification::class);
});

it('can restore a product to active', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'moderation_status' => 'flagged',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProductModerationList::class)
        ->assertOk()
        ->call('restoreProduct', $product->id);

    expect($product->fresh()->moderation_status)->toBe('active');
});
