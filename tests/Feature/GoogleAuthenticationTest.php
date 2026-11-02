<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function fakeGoogleUser(array $attributes = []): SocialiteUser
{
    return SocialiteUser::fake(array_merge([
        'id' => 'google-123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'given_name' => 'Jane',
        'family_name' => 'Doe',
        'verified_email' => true,
    ], $attributes));
}

test('login page shows password fields before Google and passkey alternatives', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSeeInOrder([
            'name="email"',
            'name="password"',
            'data-test="login-button"',
            'data-test="google-auth-button"',
            'Continue with a passkey',
        ], false)
        ->assertSee('autocomplete="username webauthn"', false);
});

test('register page shows Google before the email registration form', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSeeInOrder([
            'data-test="google-auth-button"',
            'name="first_name"',
            'name="last_name"',
            'name="email"',
            'name="password"',
        ], false)
        ->assertSee(route('legal.terms'), false)
        ->assertSee(route('legal.privacy'), false)
        ->assertDontSee('By continuing with Google, you agree to our');
});

test('guests can start Google authentication', function () {
    Socialite::fake('google');

    $this->get(route('auth.google.redirect'))->assertRedirect();
});

test('new users can register with a verified Google account', function () {
    Socialite::fake('google', fakeGoogleUser());

    $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

    expect($user->first_name)->toBe('Jane')
        ->and($user->last_name)->toBe('Doe')
        ->and($user->google_id)->toBe('google-123')
        ->and($user->password)->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

test('a verified Google email is linked to an existing account without overwriting it', function () {
    $password = Hash::make('existing-password');
    $user = User::factory()->create([
        'first_name' => 'Existing',
        'last_name' => 'Person',
        'email' => 'jane@example.com',
        'password' => $password,
    ]);
    Socialite::fake('google', fakeGoogleUser([
        'name' => 'Changed Name',
        'given_name' => 'Changed',
        'family_name' => 'Name',
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

    $user->refresh();

    expect(User::query()->where('email', 'jane@example.com')->count())->toBe(1)
        ->and($user->google_id)->toBe('google-123')
        ->and($user->first_name)->toBe('Existing')
        ->and($user->last_name)->toBe('Person')
        ->and($user->password)->toBe($password);
    $this->assertAuthenticatedAs($user);
});

test('returning users are matched by their Google identity', function () {
    $user = User::factory()->create([
        'email' => 'original@example.com',
        'google_id' => 'google-123',
    ]);
    Socialite::fake('google', fakeGoogleUser(['email' => 'updated@example.com']));

    $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

    expect(User::query()->count())->toBe(1)
        ->and($user->fresh()->email)->toBe('original@example.com');
    $this->assertAuthenticatedAs($user);
});

test('Google authentication rejects an unverified email', function () {
    Socialite::fake('google', fakeGoogleUser(['verified_email' => false]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');

    expect(User::query()->count())->toBe(0);
    $this->assertGuest();
});

test('Google authentication rejects a missing email', function () {
    Socialite::fake('google', fakeGoogleUser(['email' => null]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');

    expect(User::query()->count())->toBe(0);
    $this->assertGuest();
});

test('a Google identity cannot replace a different linked identity', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'google_id' => 'different-google-id',
    ]);
    Socialite::fake('google', fakeGoogleUser());

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');

    $this->assertGuest();
});

test('authenticated users cannot start another Google authentication flow', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('auth.google.redirect'))
        ->assertRedirect(route('dashboard'));
});

test('passwordless Google users can choose Google identity confirmation', function () {
    $user = User::factory()->create([
        'google_id' => 'google-123',
        'password' => null,
    ]);

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertSee('Confirm with Google')
        ->assertDontSee('Confirm with password');
});

test('matching Google reauthentication confirms the current session', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'google_id' => 'google-123',
        'password' => null,
    ]);
    Socialite::fake('google', fakeGoogleUser());

    $this->actingAs($user)
        ->withSession(['url.intended' => route('profile.edit').'#security'])
        ->get(route('auth.google.confirm.callback'))
        ->assertRedirect(route('profile.edit').'#security')
        ->assertSessionHas('auth.password_confirmed_at');

    $this->assertAuthenticatedAs($user);
});

test('a different Google identity cannot confirm the current session', function () {
    $user = User::factory()->create([
        'google_id' => 'google-123',
        'password' => null,
    ]);
    Socialite::fake('google', fakeGoogleUser(['id' => 'google-456']));

    $this->actingAs($user)
        ->get(route('auth.google.confirm.callback'))
        ->assertRedirect(route('password.confirm'))
        ->assertSessionHasErrors('google')
        ->assertSessionMissing('auth.password_confirmed_at');
});
