<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Validation\Rules\Password;

test('login page renders self-contained password toggle without flux viewable', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    // Self-contained Alpine toggle present
    $response->assertSee('x-data="{ show: false }"', false);
    $response->assertSee(':type="show ? \'text\' : \'password\'"', false);
    $response->assertSee('@click="show = !show"', false);
    // The broken Flux viewable attribute must be gone from the password field
    $response->assertDontSee('viewable', false);
});

test('register page renders password field with strength meter alpine component', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('name="password"', false);
    $response->assertSee('name="password_confirmation"', false);
    // Shared Alpine component wrapping both fields
    $response->assertSee('x-model="password"', false);
    $response->assertDontSee('viewable', false);
});

test('register page renders strength meter bar', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
    // Segmented bar (4 segments driven by Alpine)
    $response->assertSee('role="meter"', false);
    $response->assertSee('segment <= strength', false);
    // Labels
    $response->assertSee('Weak', false);
    $response->assertSee('Fair', false);
    $response->assertSee('Good', false);
    $response->assertSee('Strong', false);
});

test('register page renders all five production password requirement items', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('At least 12 characters', false);
    $response->assertSee('Uppercase letter', false);
    $response->assertSee('Lowercase letter', false);
    $response->assertSee('Number', false);
    $response->assertSee('Symbol', false);
});

test('register page shows checklist and meter only after typing begins via x-show', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
    // Both meter and checklist are wrapped in x-show="password.length > 0"
    $response->assertSee('x-show="password.length > 0"', false);
});

test('registration rejects a password shorter than 12 characters in production mode', function () {
    // Force production password policy for this test
    Password::defaults(fn (): Password => Password::min(12)
        ->mixedCase()
        ->letters()
        ->numbers()
        ->symbols()
        ->uncompromised()
    );

    $this->post(route('register'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'Short1!',
        'password_confirmation' => 'Short1!',
        'terms' => '1',
        'cf-turnstile-response' => 'valid-token',
    ])->assertSessionHasErrors('password');

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

test('registration rejects a password without symbols in production mode', function () {
    Password::defaults(fn (): Password => Password::min(12)
        ->mixedCase()
        ->letters()
        ->numbers()
        ->symbols()
        ->uncompromised()
    );

    $this->post(route('register'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'NoSymbolsHere1234',
        'password_confirmation' => 'NoSymbolsHere1234',
        'terms' => '1',
        'cf-turnstile-response' => 'valid-token',
    ])->assertSessionHasErrors('password');

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

test('registration rejects a password without mixed case in production mode', function () {
    Password::defaults(fn (): Password => Password::min(12)
        ->mixedCase()
        ->letters()
        ->numbers()
        ->symbols()
        ->uncompromised()
    );

    $this->post(route('register'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'alllowercase1234!',
        'password_confirmation' => 'alllowercase1234!',
        'terms' => '1',
        'cf-turnstile-response' => 'valid-token',
    ])->assertSessionHasErrors('password');

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

test('registration accepts a strong password meeting all production rules', function () {
    Password::defaults(fn (): Password => Password::min(12)
        ->mixedCase()
        ->letters()
        ->numbers()
        ->symbols()
        ->uncompromised()
    );

    $this->post(route('register'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'SecurePass@2026!',
        'password_confirmation' => 'SecurePass@2026!',
        'terms' => '1',
        'cf-turnstile-response' => 'valid-token',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'jane@example.com')->exists())->toBeTrue();
});
