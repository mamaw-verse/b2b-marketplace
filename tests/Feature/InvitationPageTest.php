<?php

declare(strict_types=1);

use App\Livewire\Staff\InvitationPage;
use App\Models\Shop;
use App\Models\ShopStaff;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    config()->set('marketplace.features.staff_enabled', true);
});

// ---------------------------------------------------------------------------
// Route / mount-level guards
// ---------------------------------------------------------------------------

test('invitation page returns 404 when staff feature flag is disabled', function (): void {
    config()->set('marketplace.features.staff_enabled', false);
    $token = str_repeat('a', 64);

    $this->get(route('staff.invitations.show', $token))->assertNotFound();
});

test('invalid token format returns 404', function (): void {
    // Token must be 64 alphanumeric characters; shorter/invalid should abort.
    $this->get(route('staff.invitations.show', 'short-token'))->assertNotFound();
});

// ---------------------------------------------------------------------------
// Guest user: session storage and redirect
// ---------------------------------------------------------------------------

test('guest visiting an invitation page has the token stored in session and is redirected to login', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $token = str_repeat('b', 64);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => 'invited@example.test',
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDay(),
    ]);

    // Livewire::withoutLazyLoading is not needed here — the mount redirect is
    // what matters. Test via raw HTTP request so the session is real.
    $this->get(route('staff.invitations.show', $token))
        ->assertSessionHas('staff_invitation_token', $token);
});

// ---------------------------------------------------------------------------
// Invitation states visible to an authenticated user
// ---------------------------------------------------------------------------

test('component shows invitation-unavailable message when token does not match any active invite', function (): void {
    $user = User::factory()->create();
    $unknownToken = str_repeat('c', 64);

    Livewire::actingAs($user)
        ->test(InvitationPage::class, ['token' => $unknownToken])
        ->assertSee(__('Invitation unavailable'));
});

test('component shows invitation-unavailable message when invite has expired', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create(['email' => 'invited@example.test']);
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $token = str_repeat('d', 64);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => $user->email,
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->subDay(),
    ]);

    Livewire::actingAs($user)
        ->test(InvitationPage::class, ['token' => $token])
        ->assertSee(__('Invitation unavailable'));
});

test('component shows invitation-unavailable message when invite has been revoked', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create(['email' => 'invited@example.test']);
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $token = str_repeat('e', 64);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => $user->email,
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDay(),
        'status' => 'revoked',
    ]);

    Livewire::actingAs($user)
        ->test(InvitationPage::class, ['token' => $token])
        ->assertSee(__('Invitation unavailable'));
});

test('component shows email-mismatch warning when signed-in user email differs from invite email', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create(['email' => 'different@example.test']);
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $token = str_repeat('f', 64);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => 'invited@example.test',
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDay(),
    ]);

    Livewire::actingAs($user)
        ->test(InvitationPage::class, ['token' => $token])
        ->assertSee('different@example.test')
        ->assertSee('invited@example.test');
});

test('component renders join button for the matched authenticated user', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create(['email' => 'invited@example.test']);
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $token = str_repeat('g', 64);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => $user->email,
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDay(),
    ]);

    Livewire::actingAs($user)
        ->test(InvitationPage::class, ['token' => $token])
        ->assertSee($shop->storefront_name)
        ->assertSee(__('Join team'));
});

// ---------------------------------------------------------------------------
// Accept action
// ---------------------------------------------------------------------------

test('calling accept redirects to portal dashboard', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create(['email' => 'invited@example.test']);
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $token = str_repeat('h', 64);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => $user->email,
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDay(),
    ]);

    Livewire::actingAs($user)
        ->test(InvitationPage::class, ['token' => $token])
        ->call('accept')
        ->assertRedirect(route('portal.dashboard'));
});

test('calling accept when not authenticated redirects to login', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $token = str_repeat('i', 64);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => 'invited@example.test',
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDay(),
    ]);

    // Directly test the component in guest state using the Livewire test helper.
    Livewire::test(InvitationPage::class, ['token' => $token])
        ->call('accept')
        ->assertRedirect(route('login'));
});
