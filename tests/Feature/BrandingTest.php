<?php

declare(strict_types=1);

use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('the home page uses the 1Halal Store brand and new logo asset', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('1Halal Store')
        ->assertSee('/images/brand-logo.png', false)
        ->assertDontSee('One Halal Global');
});

test('the auth dropdown shows Buyer Hub and Seller Hub for a seller', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('seller');
    Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk()
        ->assertSee('Buyer Hub')
        ->assertSee('Seller Hub')
        ->assertDontSee('Company Hub');
});

test('the auth dropdown shows only Buyer Hub for a non-seller buyer', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('buyer');

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk()
        ->assertSee('Buyer Hub')
        ->assertDontSee('Seller Hub');
});

test('storefront homepage shows country portals coming soon section', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('More Halal business portals are coming soon')
        ->assertSee('Coming Soon')
        ->assertSee('India')
        ->assertSee('Vietnam')
        ->assertSee('Thailand')
        ->assertSee('Nigeria')
        ->assertSee('Connect with Indian Halal producers, manufacturers, exporters and buyers.')
        ->assertSee('/images/countries/india.jpg', false)
        ->assertSee('/images/countries/vietnam.jpg', false)
        ->assertSee('/images/countries/thailand.jpg', false)
        ->assertSee('/images/countries/nigeria.jpg', false);
});
