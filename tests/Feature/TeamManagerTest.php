<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\TeamManager;
use App\Models\Shop;
use App\Models\ShopStaff;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    config()->set('marketplace.features.staff_enabled', true);
});

test('owner sees seat usage and invite form is disabled at the seat limit', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    ShopStaff::factory()->active(User::factory()->create())->create([
        'shop_id' => $shop->id,
        'invited_by_id' => $owner->id,
    ]);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'invited_by_id' => $owner->id,
        'status' => 'invited',
        'expires_at' => now()->addDay(),
    ]);

    Livewire::actingAs($owner)
        ->test(TeamManager::class)
        ->assertSee('2 of 2 seats used')
        ->assertSeeHtml('disabled')
        ->set('email', 'third@example.test')
        ->call('invite')
        ->assertHasErrors('email');
});

test('only a shop owner can open team management', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    $staff = User::factory()->create();
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);

    $this->actingAs($staff)->get(route('hub.team'))->assertForbidden();

    $this->actingAs($owner)->get(route('hub.team'))->assertOk();
});

test('team management route is hidden when the staff flag is disabled', function (): void {
    config()->set('marketplace.features.staff_enabled', false);
    $owner = User::factory()->create();
    Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);

    $this->actingAs($owner)->get(route('hub.team'))->assertNotFound();
});
