<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\ShopProfileForm;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seller = User::factory()->create(['email_verified_at' => now()]);
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create([
        'user_id' => $this->seller->id,
        'status' => 'active',
        'storefront_name' => 'Original Name',
        'city' => 'Manila',
    ]);
});

test('the shop profile form hydrates from the current shop', function () {
    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->assertOk()
        ->assertSet('storefront_name', 'Original Name')
        ->assertSet('city', 'Manila');
});

test('saving updates the shop record', function () {
    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->set('storefront_name', 'Updated Storefront')
        ->set('city', 'Cebu')
        ->set('export_readiness_status', 'exporting_regionally')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('shops', [
        'id' => $this->shop->id,
        'storefront_name' => 'Updated Storefront',
        'city' => 'Cebu',
        'export_readiness_status' => 'exporting_regionally',
    ]);
});

test('validation fails when required fields are cleared', function () {
    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->set('storefront_name', '')
        ->set('city', '')
        ->call('save')
        ->assertHasErrors([
            'storefront_name' => 'required',
            'city' => 'required',
        ]);
});
