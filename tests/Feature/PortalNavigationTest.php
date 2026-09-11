<?php

declare(strict_types=1);

use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('account routes render the portal nav even for shop owners', function () {
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
        // Unified portal entry appears in the auth dropdown.
        ->assertSee('My Portal');
});

test('hub.hub-dashboard redirects to the unified portal dashboard', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('seller');
    Shop::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'storefront_name' => 'Acme Halal Storefront',
    ]);

    $response = $this->actingAs($user)->get(route('hub.hub-dashboard'));

    // Phase 6: old hub-dashboard route now redirects to the unified portal dashboard.
    $response->assertRedirect(route('portal.dashboard'));
});
