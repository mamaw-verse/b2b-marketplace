<?php

declare(strict_types=1);

use App\Models\Shop;
use App\Models\ShopStaff;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Auth;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    config()->set('marketplace.features.staff_enabled', true);
});

function makeInvitation(string $token, ?string $email = null, array $overrides = []): ShopStaff
{
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);

    return ShopStaff::factory()->create(array_merge([
        'shop_id' => $shop->id,
        'email' => $email ?? 'invited@example.test',
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'status' => 'invited',
        'expires_at' => now()->addDay(),
    ], $overrides));
}

// --- GET /staff/invitations/{token}/register ---

test('registration page renders for a valid invitation', function (): void {
    $token = str_repeat('a', 64);
    makeInvitation($token, 'invited@example.test');

    $this->get(route('staff.invitations.register', $token))
        ->assertOk()
        ->assertSee('invited@example.test');
});

test('registration page redirects to invitation page when token is invalid', function (): void {
    $token = str_repeat('z', 64);

    $this->get(route('staff.invitations.register', $token))
        ->assertRedirect(route('staff.invitations.show', $token));
});

test('registration page redirects to login when user already exists for the invited email', function (): void {
    $token = str_repeat('b', 64);
    $email = 'existing@example.test';
    User::factory()->create(['email' => $email]);
    makeInvitation($token, $email);

    $this->get(route('staff.invitations.register', $token))
        ->assertRedirect(route('login', ['email' => $email]));
});

test('registration page is inaccessible to authenticated users (guest middleware)', function (): void {
    $token = str_repeat('c', 64);
    makeInvitation($token);

    $this->actingAs(User::factory()->create())
        ->get(route('staff.invitations.register', $token))
        ->assertRedirect(route('dashboard'));
});

// --- POST /staff/invitations/{token}/register ---

test('valid submission creates a pre-verified user, accepts the invitation, and logs them in', function (): void {
    $token = str_repeat('d', 64);
    $email = 'newstaff@example.test';
    makeInvitation($token, $email);

    $this->post(route('staff.invitations.register.store', $token), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'password' => 'password123',
        'terms' => '1',
    ])->assertRedirect(route('portal.dashboard'));

    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->first_name)->toBe('Jane')
        ->and(Auth::id())->toBe($user->id);

    $this->assertDatabaseHas('shop_staff', [
        'email' => $email,
        'user_id' => $user->id,
        'status' => 'active',
    ]);
});

test('submission with an expired token fails validation', function (): void {
    $token = str_repeat('e', 64);
    makeInvitation($token, 'expired@example.test', ['expires_at' => now()->subDay()]);

    $this->post(route('staff.invitations.register.store', $token), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'password' => 'password123',
        'terms' => '1',
    ])->assertRedirect(route('staff.invitations.show', $token));
});

test('submission missing required fields returns validation errors', function (): void {
    $token = str_repeat('f', 64);
    makeInvitation($token);

    $this->post(route('staff.invitations.register.store', $token), [])
        ->assertSessionHasErrors(['first_name', 'last_name', 'password', 'terms']);
});

test('registration page returns 404 when staff feature is disabled', function (): void {
    config()->set('marketplace.features.staff_enabled', false);
    $token = str_repeat('g', 64);

    $this->get(route('staff.invitations.register', $token))->assertNotFound();
    $this->post(route('staff.invitations.register.store', $token), [])->assertNotFound();
});
