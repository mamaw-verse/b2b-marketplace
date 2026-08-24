<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\ProfitCalculator;
use App\Models\PayoutSetting;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\PayoutSettingsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(PayoutSettingsSeeder::class);
});

test('calculator computes correct net for a given sale price', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    Shop::factory()->create(['user_id' => $seller->id]);

    $this->actingAs($seller);

    // Transaction fee: 15.00, Commission rate: 0.03
    // For sale_price of 1000: net = 1000 - 15 - (1000 * 0.03) = 1000 - 15 - 30 = 955.00
    Livewire::test(ProfitCalculator::class)
        ->set('sale_price', '1000')
        ->assertSet('sale_price', '1000')
        ->assertSee('955.00');
});

test('calculator uses current payout_settings values', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    Shop::factory()->create(['user_id' => $seller->id]);

    // Update the fee settings
    PayoutSetting::where('key', 'transaction_fee_amount')->update(['value' => '20.00']);
    PayoutSetting::where('key', 'commission_fee_rate')->update(['value' => '0.05']);

    $this->actingAs($seller);

    // For sale_price of 2000: net = 2000 - 20 - (2000 * 0.05) = 2000 - 20 - 100 = 1880.00
    Livewire::test(ProfitCalculator::class)
        ->set('sale_price', '2000')
        ->assertSee('1,880.00');
});

test('calculator shows dashes when sale price is empty', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    Shop::factory()->create(['user_id' => $seller->id]);

    $this->actingAs($seller);

    Livewire::test(ProfitCalculator::class)
        ->set('sale_price', '')
        ->assertSeeHtml('—');
});

test('calculator accepts prefill amount prop', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    Shop::factory()->create(['user_id' => $seller->id]);

    $this->actingAs($seller);

    // prefillAmount of 5000: net = 5000 - 15 - (5000 * 0.03) = 5000 - 15 - 150 = 4835.00
    Livewire::test(ProfitCalculator::class, ['prefillAmount' => 5000.00])
        ->assertSet('sale_price', '5000.00')
        ->assertSee('4,835.00');
});

test('fee-rates API endpoint returns correct values for authenticated seller', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    Shop::factory()->create(['user_id' => $seller->id]);

    $response = $this->actingAs($seller, 'sanctum')
        ->getJson('/api/v1/hub/fee-rates');

    $response->assertOk()
        ->assertJsonPath('data.transaction_fee_amount', '15.00')
        ->assertJsonPath('data.commission_fee_rate', '0.03');
});

test('fee-rates endpoint rejects unauthenticated request', function () {
    $response = $this->getJson('/api/v1/hub/fee-rates');

    $response->assertUnauthorized();
});
