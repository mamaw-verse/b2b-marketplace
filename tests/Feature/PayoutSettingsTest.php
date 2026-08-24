<?php

declare(strict_types=1);

use App\Models\PayoutSetting;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\PayoutSettingsSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(PayoutSettingsSeeder::class);
});

test('admin can view all payout settings', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/payout-settings');

    $response->assertOk()
        ->assertJson([
            'data' => [
                'dispute_hold_days' => '7',
                'minimum_payout_threshold' => '500.00',
                'transaction_fee_amount' => '15.00',
                'commission_fee_rate' => '0.03',
            ],
        ]);
});

test('admin can update individual setting', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/payout-settings', [
            'dispute_hold_days' => 14,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.dispute_hold_days', '14');

    expect(PayoutSetting::getDisputeHoldDays())->toBe(14);
});

test('admin can bulk-update multiple settings', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/payout-settings', [
            'minimum_payout_threshold' => 1000.00,
            'transaction_fee_amount' => 25.00,
            'commission_fee_rate' => 0.05,
        ]);

    $response->assertOk();

    expect(PayoutSetting::getMinimumPayoutThreshold())->toBe(1000.0)
        ->and(PayoutSetting::getTransactionFeeAmount())->toBe(25.0)
        ->and(PayoutSetting::getCommissionFeeRate())->toBe(0.05);
});

test('non-admin cannot access payout settings GET endpoint', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $response = $this->actingAs($seller, 'sanctum')
        ->getJson('/api/v1/admin/payout-settings');

    $response->assertForbidden();
});

test('non-admin cannot access payout settings PUT endpoint', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $response = $this->actingAs($buyer, 'sanctum')
        ->putJson('/api/v1/admin/payout-settings', [
            'dispute_hold_days' => 30,
        ]);

    $response->assertForbidden();
});

test('unauthenticated user cannot access payout settings', function () {
    $response = $this->getJson('/api/v1/admin/payout-settings');

    $response->assertUnauthorized();
});

test('invalid values are rejected - negative threshold', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/payout-settings', [
            'minimum_payout_threshold' => -100,
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.fields.minimum_payout_threshold.0', 'Minimum payout threshold must be positive.');
});

test('invalid values are rejected - commission rate exceeds 1', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/payout-settings', [
            'commission_fee_rate' => 1.5,
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJson(fn (Illuminate\Testing\Fluent\AssertableJson $json) => $json->has('error.fields.commission_fee_rate')
            ->etc()
        );
});

test('invalid values are rejected - non-numeric dispute hold days', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/payout-settings', [
            'dispute_hold_days' => 'abc',
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJson(fn (Illuminate\Testing\Fluent\AssertableJson $json) => $json->has('error.fields.dispute_hold_days')
            ->etc()
        );
});

test('seller with active shop can access fee-rates endpoint', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    $response = $this->actingAs($seller, 'sanctum')
        ->getJson('/api/v1/hub/fee-rates');

    $response->assertOk()
        ->assertJson([
            'data' => [
                'transaction_fee_amount' => '15.00',
                'commission_fee_rate' => '0.03',
            ],
        ])
        ->assertJsonMissing(['dispute_hold_days'])
        ->assertJsonMissing(['minimum_payout_threshold']);
});

test('seller without active shop cannot access fee-rates endpoint', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $response = $this->actingAs($seller, 'sanctum')
        ->getJson('/api/v1/hub/fee-rates');

    $response->assertForbidden();
});

test('seeder produces correct default values', function () {
    expect(PayoutSetting::getDisputeHoldDays())->toBe(7)
        ->and(PayoutSetting::getMinimumPayoutThreshold())->toBe(500.0)
        ->and(PayoutSetting::getTransactionFeeAmount())->toBe(15.0)
        ->and(PayoutSetting::getCommissionFeeRate())->toBe(0.03);
});

test('update records the user who made the change', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/payout-settings', [
            'dispute_hold_days' => 10,
        ]);

    $setting = PayoutSetting::where('key', 'dispute_hold_days')->first();
    expect($setting->updated_by_id)->toBe($admin->id);
});
