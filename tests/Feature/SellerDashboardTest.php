<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\Hub\Dashboard;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seller = User::factory()->create(['email_verified_at' => now()]);
    $this->seller->assignRole('seller');
});

test('the seller dashboard shows a View My Shop link for an active shop', function () {
    $shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);

    Livewire::actingAs($this->seller)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('View My Shop')
        ->assertSee(route('storefront.company', $shop), false);
});

test('the shop computed property returns the sellers shop', function () {
    $shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);

    $component = Livewire::actingAs($this->seller)->test(Dashboard::class);

    expect($component->instance()->shop()->id)->toBe($shop->id);
});
