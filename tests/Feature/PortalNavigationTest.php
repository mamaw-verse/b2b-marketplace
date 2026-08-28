<?php

declare(strict_types=1);

use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('account routes render the buyer portal nav even for shop owners', function () {
    // A user who owns an active shop but is visiting an /account/* page.
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('seller');
    Shop::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'storefront_name' => 'Acme Halal Storefront',
    ]);

    $response = $this->actingAs($user)->get(route('account.profile'));

    $response->assertOk()
        // Buyer context header appears in the account portal's mobile drawer.
        ->assertSee('Buyer Hub');
});

test('hub routes render the company hub portal nav', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('seller');
    Shop::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'storefront_name' => 'Acme Halal Storefront',
    ]);

    $response = $this->actingAs($user)->get(route('hub.hub-dashboard'));

    $response->assertOk()
        ->assertSee('Acme Halal Storefront');
});
