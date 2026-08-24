<?php

declare(strict_types=1);

use App\Models\SellerPayoutAccount;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('allows seller with active shop to create a payout account', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/hub/payout-account', [
            'bank_name' => 'BDO',
            'bank_code' => 'BDO',
            'account_holder_name' => 'Juan Dela Cruz',
            'account_number' => '1234567890',
            'branch' => 'Manila Main',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.bank_name', 'BDO')
        ->assertJsonPath('data.verification_status', 'pending');

    $this->assertDatabaseHas('seller_payout_accounts', [
        'bank_name' => 'BDO',
        'bank_code' => 'BDO',
        'account_holder_name' => 'Juan Dela Cruz',
    ]);
});

it('denies seller without active shop from creating a payout account', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    Shop::factory()->create(['user_id' => $user->id, 'status' => 'inactive']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/hub/payout-account', [
            'bank_name' => 'BDO',
            'bank_code' => 'BDO',
            'account_holder_name' => 'Juan Dela Cruz',
            'account_number' => '1234567890',
        ]);

    $response->assertForbidden();
});

it('resets verification_status to pending when updating an existing account', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    SellerPayoutAccount::factory()->verified()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/hub/payout-account', [
            'bank_name' => 'BPI',
            'bank_code' => 'BPI',
            'account_holder_name' => 'Updated Name',
            'account_number' => '9876543210',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.verification_status', 'pending')
        ->assertJsonPath('data.bank_name', 'BPI');
});

it('allows admin to verify a payout account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $account = SellerPayoutAccount::factory()->create(['verification_status' => 'pending']);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/payout-accounts/{$account->id}/verify");

    $response->assertOk()
        ->assertJsonPath('data.verification_status', 'verified');

    $account->refresh();
    expect($account->verification_status)->toBe('verified')
        ->and($account->verified_at)->not->toBeNull()
        ->and($account->verified_by_id)->toBe($admin->id);
});

it('allows admin to reject a payout account with reason', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $account = SellerPayoutAccount::factory()->create(['verification_status' => 'pending']);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/payout-accounts/{$account->id}/reject", [
            'rejection_reason' => 'Invalid account details provided.',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.verification_status', 'rejected')
        ->assertJsonPath('data.rejection_reason', 'Invalid account details provided.');
});

it('denies non-admin from verifying a payout account', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $account = SellerPayoutAccount::factory()->create(['verification_status' => 'pending']);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/admin/payout-accounts/{$account->id}/verify");

    $response->assertForbidden();
});

it('denies non-admin from rejecting a payout account', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $account = SellerPayoutAccount::factory()->create(['verification_status' => 'pending']);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/admin/payout-accounts/{$account->id}/reject", [
            'rejection_reason' => 'Bad account.',
        ]);

    $response->assertForbidden();
});

it('encrypts account_number at rest', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/hub/payout-account', [
            'bank_name' => 'BDO',
            'bank_code' => 'BDO',
            'account_holder_name' => 'Juan Dela Cruz',
            'account_number' => '9999888877776666',
        ]);

    // Check that raw DB value is NOT the plaintext number
    $raw = DB::table('seller_payout_accounts')
        ->where('shop_id', $shop->id)
        ->value('account_number');

    expect($raw)->not->toBe('9999888877776666');

    // But the model decrypts it correctly
    $account = $shop->payoutAccount;
    expect($account->account_number)->toBe('9999888877776666');
});

it('enforces unique constraint — cannot have two accounts for same shop', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    SellerPayoutAccount::factory()->create(['shop_id' => $shop->id]);

    // Attempting to create a second one should fail at DB level
    expect(fn () => SellerPayoutAccount::factory()->create(['shop_id' => $shop->id]))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('denies buyer from accessing payout account endpoints', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $response = $this->actingAs($buyer, 'sanctum')
        ->getJson('/api/v1/hub/payout-account');

    // Buyer has no shop, should get 404 (no shop found)
    $response->assertNotFound();

    $response = $this->actingAs($buyer, 'sanctum')
        ->putJson('/api/v1/hub/payout-account', [
            'bank_name' => 'BDO',
            'bank_code' => 'BDO',
            'account_holder_name' => 'Test',
            'account_number' => '12345',
        ]);

    // Buyer has no active shop — authorization fails
    $response->assertForbidden();
});
