<?php

declare(strict_types=1);

use App\Actions\Staff\AcceptStaffInvitationAction;
use App\Actions\Staff\InviteStaffAction;
use App\Actions\Staff\LeaveShopAction;
use App\Actions\Staff\ResendStaffInvitationAction;
use App\Actions\Staff\RevokeStaffAction;
use App\Models\Shop;
use App\Models\ShopStaff;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    config()->set('marketplace.features.staff_enabled', true);
});

test('owner can invite a new account and only the token hash is persisted', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $staffEmail = 'new-staff@example.test';

    $membership = app(InviteStaffAction::class)->execute($shop, $owner, $staffEmail);

    expect($membership->email)->toBe($staffEmail)
        ->and($membership->status)->toBe('invited')
        ->and($membership->token_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($membership->expires_at->isFuture())->toBeTrue();
    $this->assertDatabaseHas('shop_staff', ['id' => $membership->id, 'email' => $staffEmail]);
});

test('invited user can accept with the correct verified email and token', function (): void {
    $owner = User::factory()->create();
    $staffUser = User::factory()->create(['email' => 'new-staff@example.test']);
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $token = str_repeat('a', 64);
    $membership = ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => $staffUser->email,
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDay(),
    ]);

    $accepted = app(AcceptStaffInvitationAction::class)->execute($token, $staffUser);

    expect($accepted->status)->toBe('active')
        ->and($accepted->user_id)->toBe($staffUser->id)
        ->and($accepted->token_hash)->toBeNull()
        ->and($staffUser->fresh()->activeShop->is($shop))->toBeTrue();
});

test('acceptance rejects an email mismatch', function (): void {
    $owner = User::factory()->create();
    $staffUser = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $token = str_repeat('b', 64);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => 'different@example.test',
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
    ]);

    expect(fn () => app(AcceptStaffInvitationAction::class)->execute($token, $staffUser))
        ->toThrow(ValidationException::class);
});

test('active staff and unexpired invitations count toward the configured seat limit', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    ShopStaff::factory()->create(['shop_id' => $shop->id, 'invited_by_id' => $owner->id]);
    ShopStaff::factory()->create(['shop_id' => $shop->id, 'invited_by_id' => $owner->id]);

    expect($shop->fresh()->staffSeatsUsed())->toBe(2)
        ->and(fn () => app(InviteStaffAction::class)->execute($shop, $owner, 'third@example.test'))
        ->toThrow(ValidationException::class);
});

test('expired invitations can be replaced and resend rotates the stored token hash', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $expired = ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => 'expired@example.test',
        'invited_by_id' => $owner->id,
        'expires_at' => now()->subDay(),
    ]);

    $replacement = app(InviteStaffAction::class)->execute($shop, $owner, $expired->email);
    expect($expired->fresh()->status)->toBe('revoked')
        ->and($replacement->status)->toBe('invited');

    $oldHash = $replacement->token_hash;
    app(ResendStaffInvitationAction::class)->execute($replacement, $owner);
    expect($replacement->fresh()->token_hash)->not->toBe($oldHash)
        ->and($replacement->fresh()->expires_at->isFuture())->toBeTrue();
});

test('owner can revoke staff and staff can leave the shop', function (): void {
    $owner = User::factory()->create();
    $staffUser = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $membership = ShopStaff::factory()->active($staffUser)->create([
        'shop_id' => $shop->id,
        'email' => $staffUser->email,
        'invited_by_id' => $owner->id,
    ]);

    app(RevokeStaffAction::class)->execute($membership, $owner);
    expect($membership->fresh()->status)->toBe('revoked')
        ->and($membership->fresh()->removed_by_id)->toBe($owner->id);

    $secondStaff = User::factory()->create();
    $secondMembership = ShopStaff::factory()->active($secondStaff)->create([
        'shop_id' => $shop->id,
        'email' => $secondStaff->email,
        'invited_by_id' => $owner->id,
    ]);
    app(LeaveShopAction::class)->execute($secondStaff);
    expect($secondMembership->fresh()->status)->toBe('left')
        ->and($secondMembership->fresh()->removed_by_id)->toBe($secondStaff->id);
});

test('invite action returns 404 when staff feature flag is disabled', function (): void {
    config()->set('marketplace.features.staff_enabled', false);
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);

    expect(fn () => app(InviteStaffAction::class)->execute($shop, $owner, 'someone@example.test'))
        ->toThrow(HttpException::class);
});

test('accept action returns 404 when staff feature flag is disabled', function (): void {
    config()->set('marketplace.features.staff_enabled', false);
    $staffUser = User::factory()->create(['email' => 'staff@example.test']);
    $token = str_repeat('c', 64);

    expect(fn () => app(AcceptStaffInvitationAction::class)->execute($token, $staffUser))
        ->toThrow(HttpException::class);
});

test('revoke action returns 404 when staff feature flag is disabled', function (): void {
    config()->set('marketplace.features.staff_enabled', false);
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $staffUser = User::factory()->create();
    $membership = ShopStaff::factory()->active($staffUser)->create([
        'shop_id' => $shop->id,
        'email' => $staffUser->email,
        'invited_by_id' => $owner->id,
    ]);

    expect(fn () => app(RevokeStaffAction::class)->execute($membership, $owner))
        ->toThrow(HttpException::class);
});

test('resend action returns 404 when staff feature flag is disabled', function (): void {
    config()->set('marketplace.features.staff_enabled', false);
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $membership = ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => 'invited@example.test',
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDay(),
    ]);

    expect(fn () => app(ResendStaffInvitationAction::class)->execute($membership, $owner))
        ->toThrow(HttpException::class);
});

test('existing user without a shop can accept an invitation', function (): void {
    $owner = User::factory()->create();
    $existingUser = User::factory()->create(['email' => 'existing@example.test']);
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $token = str_repeat('d', 64);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => $existingUser->email,
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDay(),
    ]);

    $accepted = app(AcceptStaffInvitationAction::class)->execute($token, $existingUser);

    expect($accepted->status)->toBe('active')
        ->and($accepted->user_id)->toBe($existingUser->id)
        ->and($existingUser->fresh()->isShopStaff())->toBeTrue()
        ->and($existingUser->fresh()->activeShop->is($shop))->toBeTrue();
});

test('accepting an expired invitation is rejected', function (): void {
    $owner = User::factory()->create();
    $staffUser = User::factory()->create(['email' => 'staff@example.test']);
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $token = str_repeat('e', 64);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => $staffUser->email,
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->subDay(),
    ]);

    expect(fn () => app(AcceptStaffInvitationAction::class)->execute($token, $staffUser))
        ->toThrow(ValidationException::class);
});

test('accepting a revoked invitation is rejected', function (): void {
    $owner = User::factory()->create();
    $staffUser = User::factory()->create(['email' => 'staff@example.test']);
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $token = str_repeat('f', 64);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => $staffUser->email,
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDay(),
        'status' => 'revoked',
    ]);

    expect(fn () => app(AcceptStaffInvitationAction::class)->execute($token, $staffUser))
        ->toThrow(ValidationException::class);
});

test('resend throws when the row is active (not invited)', function (): void {
    $owner = User::factory()->create();
    $staffUser = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $membership = ShopStaff::factory()->active($staffUser)->create([
        'shop_id' => $shop->id,
        'email' => $staffUser->email,
        'invited_by_id' => $owner->id,
    ]);

    expect(fn () => app(ResendStaffInvitationAction::class)->execute($membership, $owner))
        ->toThrow(ValidationException::class);
});

test('resend throws when the row is revoked', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $membership = ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => 'revoked@example.test',
        'invited_by_id' => $owner->id,
        'status' => 'revoked',
        'expires_at' => now()->subDay(),
    ]);

    expect(fn () => app(ResendStaffInvitationAction::class)->execute($membership, $owner))
        ->toThrow(ValidationException::class);
});

test('revoke transitions an invited row to revoked and clears the token', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $membership = ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => 'pending@example.test',
        'token_hash' => hash('sha256', str_repeat('g', 64)),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDay(),
        'status' => 'invited',
    ]);

    app(RevokeStaffAction::class)->execute($membership, $owner);

    expect($membership->fresh()->status)->toBe('revoked')
        ->and($membership->fresh()->token_hash)->toBeNull()
        ->and($membership->fresh()->removed_by_id)->toBe($owner->id);
});

test('owner inviting themselves receives the same generic ineligibility message', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);

    expect(fn () => app(InviteStaffAction::class)->execute($shop, $owner, $owner->email))
        ->toThrow(ValidationException::class)
        ->and(function () use ($shop, $owner): void {
            try {
                app(InviteStaffAction::class)->execute($shop, $owner, $owner->email);
            } catch (ValidationException $e) {
                expect($e->errors()['email'][0])->toBe(__('This email can\'t be invited to your team.'));
            }
        });
});

test('concurrent seat allocation is serialised by lock preventing over-invitation', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    config()->set('marketplace.limits.staff_per_shop', 1);

    // Fill the seat with a pending invite.
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => 'seat1@example.test',
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDays(7),
        'status' => 'invited',
    ]);

    // A second invite while the seat is taken must be rejected.
    expect(fn () => app(InviteStaffAction::class)->execute($shop->fresh(), $owner, 'seat2@example.test'))
        ->toThrow(ValidationException::class);

    // Only one row exists for this shop.
    expect($shop->fresh()->staffSeatsUsed())->toBe(1);
});

test('owner, admin, and staff accounts receive the same generic ineligibility message', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $existingOwner = User::factory()->create();
    Shop::factory()->create(['user_id' => $existingOwner->id]);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $existingStaff = User::factory()->create();
    $otherShop = Shop::factory()->create();
    ShopStaff::factory()->active($existingStaff)->create([
        'shop_id' => $otherShop->id,
        'email' => $existingStaff->email,
        'invited_by_id' => $otherShop->user_id,
    ]);

    $messages = [];
    foreach ([$existingOwner, $admin, $existingStaff] as $ineligible) {
        try {
            app(InviteStaffAction::class)->execute($shop, $owner, $ineligible->email);
        } catch (ValidationException $exception) {
            $messages[] = $exception->errors()['email'][0];
        }
    }

    expect($messages)->toHaveCount(3)
        ->and(array_unique($messages))->toBe([__('This email can\'t be invited to your team.')]);
});
