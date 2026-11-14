<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;

test('browser harness boots the app and sees database rows', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    $product = Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Smoke Test Product',
    ]);

    visit('/products/'.$product->slug)
        ->assertSee('Smoke Test Product')
        ->assertPathIs('/products/'.$product->slug);
});

test('browser harness shares the authenticated user with the app', function (): void {
    $owner = User::factory()->create();
    Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);

    $this->actingAs($owner);

    visit('/hub/products')
        ->assertSee('Products')
        ->assertDontSee('Log in');
});
