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

test('the auth dropdown shows My Portal for a seller', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('seller');
    Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk()
        ->assertSee('My Portal')
        ->assertDontSee('Buyer Hub')
        ->assertDontSee('Seller Hub')
        ->assertDontSee('Company Hub');
});

test('the auth dropdown shows My Portal for a non-seller buyer', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('buyer');

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk()
        ->assertSee('My Portal')
        ->assertDontSee('Buyer Hub');
});

test('storefront homepage shows global halal network country portals section', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Global Halal Network')
        ->assertSee('1HalalStore across the Halal world')
        ->assertSee('Coming Soon')
        ->assertSee('Malaysia')
        ->assertSee('Indonesia')
        ->assertSee('/images/countries/my.jpg', false)
        ->assertSee('/images/countries/id.jpg', false);
});
