<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\PayoutAccountForm;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);
});

it('auto-fills the bank code when a bank with an established code is selected', function () {
    Livewire::actingAs($this->seller)
        ->test(PayoutAccountForm::class)
        ->set('bank_name', 'Metrobank')
        ->assertSet('bank_code', 'MBTC')
        ->set('bank_name', 'UnionBank')
        ->assertSet('bank_code', 'UBP');
});

it('leaves a manually entered bank code alone for banks without an established code', function () {
    Livewire::actingAs($this->seller)
        ->test(PayoutAccountForm::class)
        ->set('bank_code', 'CUSTOM')
        ->set('bank_name', 'Robinsons Bank')
        ->assertSet('bank_code', 'CUSTOM');
});
