<?php

declare(strict_types=1);

use App\Jobs\IndexProductJob;
use App\Livewire\Admin\Products\ProductModerationList;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\ProductFlaggedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
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
    Queue::fake();

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
    Queue::assertPushed(IndexProductJob::class, fn (IndexProductJob $job) => $job->product->id === $product->id);
});

it('shows the flag toggle for active products', function () {
    $shop = Shop::factory()->create();
    Product::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Toggle Check Product',
        'moderation_status' => 'active',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProductModerationList::class)
        ->assertOk()
        ->assertSee('Toggle Check Product')
        ->assertSee('Flag')
        ->assertDontSee('Restore');
});

it('shows the restore toggle for flagged products', function () {
    $shop = Shop::factory()->create();
    Product::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Toggle Check Product',
        'moderation_status' => 'flagged',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProductModerationList::class)
        ->assertOk()
        ->assertSee('Toggle Check Product')
        ->assertSee('Restore')
        ->assertDontSee('Flag Product');
});

it('can restore a product to active', function () {
    Queue::fake();

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

    Queue::assertPushed(IndexProductJob::class, fn (IndexProductJob $job) => $job->product->id === $product->id);
});
