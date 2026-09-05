<?php

declare(strict_types=1);

use App\Livewire\Admin\PayoutSettings;
use App\Models\PayoutSetting;
use App\Models\User;
use Database\Seeders\PayoutSettingsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(PayoutSettingsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('component mounts with current setting values from database', function () {
    $component = Livewire::actingAs($this->admin)->test(PayoutSettings::class);

    $component->assertSet('dispute_hold_days', '7')
        ->assertSet('minimum_payout_threshold', '500.00')
        ->assertSet('transaction_fee_amount', '15.00')
        ->assertSet('commission_fee_rate', '0.03');
});

test('admin can update dispute hold days', function () {
    Livewire::actingAs($this->admin)
        ->test(PayoutSettings::class)
        ->set('dispute_hold_days', '1')
        ->call('save')
        ->assertHasNoErrors();

    expect(PayoutSetting::getDisputeHoldDays())->toBe(1);
});

test('component reloads updated value after save', function () {
    $component = Livewire::actingAs($this->admin)
        ->test(PayoutSettings::class)
        ->set('dispute_hold_days', '14')
        ->call('save');

    $component->assertSet('dispute_hold_days', '14');
    expect(PayoutSetting::getDisputeHoldDays())->toBe(14);
});

test('save records the admin who made the change', function () {
    Livewire::actingAs($this->admin)
        ->test(PayoutSettings::class)
        ->set('dispute_hold_days', '30')
        ->call('save');

    $setting = PayoutSetting::where('key', 'dispute_hold_days')->first();
    expect($setting->updated_by_id)->toBe($this->admin->id);
});

test('save updates all four settings simultaneously', function () {
    Livewire::actingAs($this->admin)
        ->test(PayoutSettings::class)
        ->set('dispute_hold_days', '14')
        ->set('minimum_payout_threshold', '1000.00')
        ->set('transaction_fee_amount', '25.00')
        ->set('commission_fee_rate', '0.05')
        ->call('save')
        ->assertHasNoErrors();

    expect(PayoutSetting::getDisputeHoldDays())->toBe(14)
        ->and(PayoutSetting::getMinimumPayoutThreshold())->toBe(1000.0)
        ->and(PayoutSetting::getTransactionFeeAmount())->toBe(25.0)
        ->and(PayoutSetting::getCommissionFeeRate())->toBe(0.05);
});

test('validation rejects dispute hold days below 1', function () {
    Livewire::actingAs($this->admin)
        ->test(PayoutSettings::class)
        ->set('dispute_hold_days', '0')
        ->call('save')
        ->assertHasErrors(['dispute_hold_days']);

    expect(PayoutSetting::getDisputeHoldDays())->toBe(7);
});

test('validation rejects dispute hold days above 365', function () {
    Livewire::actingAs($this->admin)
        ->test(PayoutSettings::class)
        ->set('dispute_hold_days', '366')
        ->call('save')
        ->assertHasErrors(['dispute_hold_days']);

    expect(PayoutSetting::getDisputeHoldDays())->toBe(7);
});

test('validation rejects non-integer dispute hold days', function () {
    Livewire::actingAs($this->admin)
        ->test(PayoutSettings::class)
        ->set('dispute_hold_days', 'abc')
        ->call('save')
        ->assertHasErrors(['dispute_hold_days']);

    expect(PayoutSetting::getDisputeHoldDays())->toBe(7);
});

test('non-admin cannot access the payout settings page', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller)
        ->get('/admin/payout-settings')
        ->assertForbidden();
});
