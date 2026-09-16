<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'buyer@example.com',
        'password' => Hash::make('password'),
    ]);
});

test('login page renders the turnstile widget', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('cf-turnstile', false)
        ->assertSee('challenges.cloudflare.com/turnstile/v0/api.js', false);
});

test('register page renders the turnstile widget', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('cf-turnstile', false);
});

test('forgot password page renders the turnstile widget', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('cf-turnstile', false);
});

test('login requires a turnstile token', function () {
    $this->post(route('login'), [
        'email' => 'buyer@example.com',
        'password' => 'password',
        'cf-turnstile-response' => '',
    ])->assertSessionHasErrors([
        'cf-turnstile-response' => 'Please complete the security check.',
    ]);

    $this->assertGuest();
});

test('login rejects an invalid turnstile token without authenticating', function () {
    $this->post(route('login'), [
        'email' => 'another@example.com',
        'password' => 'password',
        'cf-turnstile-response' => 'invalid-token',
    ])->assertSessionHasErrors('cf-turnstile-response');

    $this->assertGuest();
});

test('login succeeds when the turnstile token is valid', function () {
    $this->post(route('login'), [
        'email' => 'buyer@example.com',
        'password' => 'password',
        'cf-turnstile-response' => 'valid-token',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($this->user);
});

test('registration requires a turnstile token', function () {
    $this->post(route('register'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'newbuyer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
        'cf-turnstile-response' => '',
    ])->assertSessionHasErrors([
        'cf-turnstile-response' => 'Please complete the security check.',
    ]);

    expect(User::where('email', 'newbuyer@example.com')->exists())->toBeFalse();
});

test('registration rejects an invalid turnstile token without creating a user', function () {
    $this->post(route('register'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'newbuyer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
        'cf-turnstile-response' => 'invalid-token',
    ])->assertSessionHasErrors('cf-turnstile-response');

    expect(User::where('email', 'newbuyer@example.com')->exists())->toBeFalse();
    $this->assertGuest();
});

test('registration succeeds when the turnstile token is valid', function () {
    $this->post(route('register'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'newbuyer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
        'cf-turnstile-response' => 'valid-token',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'newbuyer@example.com')->exists())->toBeTrue();
});

test('forgot password requires a turnstile token', function () {
    $this->post(route('password.email'), [
        'email' => 'buyer@example.com',
        'cf-turnstile-response' => '',
    ])->assertSessionHasErrors([
        'cf-turnstile-response' => 'Please complete the security check.',
    ]);

    expect(DB::table('password_reset_tokens')->count())->toBe(0);
});

test('forgot password rejects an invalid turnstile token without sending a reset link', function () {
    $this->post(route('password.email'), [
        'email' => 'buyer@example.com',
        'cf-turnstile-response' => 'invalid-token',
    ])->assertSessionHasErrors('cf-turnstile-response');

    expect(DB::table('password_reset_tokens')->count())->toBe(0);
});

test('forgot password sends a reset link when the turnstile token is valid', function () {
    $this->post(route('password.email'), [
        'email' => 'buyer@example.com',
        'cf-turnstile-response' => 'valid-token',
    ])->assertSessionHasNoErrors();

    expect(DB::table('password_reset_tokens')->where('email', 'buyer@example.com')->count())->toBe(1);
});
