<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Validation\Rules\Password;

function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'long passphrase',
        'terms' => '1',
        'cf-turnstile-response' => 'valid-token',
    ], $overrides);
}

test('login password toggle is accessible and self contained', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('x-data="{ showPassword: false }"', false)
        ->assertSee('x-bind:aria-pressed="showPassword"', false)
        ->assertSee('Show password')
        ->assertDontSee('viewable', false);
});

test('registration renders one password field with persistent guidance', function () {
    $response = $this->get(route('register'));

    $response->assertOk()
        ->assertSee('name="password"', false)
        ->assertDontSee('name="password_confirmation"', false)
        ->assertSee('At least 6 characters. Longer passphrases are welcome.')
        ->assertSee('minlength="6"', false)
        ->assertSee('role="meter"', false)
        ->assertSee('data-flux-control data-flux-group-target', false)
        ->assertSee('appearance-none', false)
        ->assertSee('shadow-xs', false);

    expect(substr_count($response->getContent(), 'By creating an account, you agree to our'))->toBe(1);
});

test('client validation does not flag untouched blank fields on blur', function () {
    $script = file_get_contents(resource_path('js/auth.js'));

    expect($script)
        ->toContain('validateOnBlur(field)')
        ->toContain("if (!this.submitted && value === '')")
        ->toContain("if (!this.submitted && values[field] === '')");
});

test('registration rejects passwords shorter than six characters', function () {
    $this->post(route('register'), registrationPayload(['password' => 'short']))
        ->assertSessionHasErrors('password');

    expect(User::query()->where('email', 'jane@example.com')->exists())->toBeFalse();
});

test('registration accepts a six character lowercase password without composition requirements or confirmation', function () {
    $script = file_get_contents(resource_path('js/auth.js'));

    expect($script)
        ->not->toContain('/[A-Z]/')
        ->not->toContain('mixedCase()');

    $this->post(route('register'), registrationPayload(['password' => 'abcdef']))
        ->assertSessionHasNoErrors();

    expect(User::query()->where('email', 'jane@example.com')->exists())->toBeTrue();
});

test('wrong login credentials show a generic message', function () {
    User::factory()->create(['email' => 'buyer@example.com']);

    $this->followingRedirects()->from(route('login'))->post(route('login.store'), [
        'email' => 'buyer@example.com',
        'password' => 'incorrect-password',
        'cf-turnstile-response' => 'valid-token',
    ])->assertSee('Email or password is incorrect.')
        ->assertDontSee('These credentials do not match our records.');
});

test('verification screen shows the email and allows correcting it', function () {
    $user = User::factory()->unverified()->create(['email' => 'mistyped@example.com']);

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('mistyped@example.com')
        ->assertSee('Change email')
        ->assertSee(route('profile.edit'), false);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk();
});

test('registration rejects a known breached password when breach protection is enabled', function () {
    Password::defaults(fn (): Password => Password::min(6)->uncompromised());
    $this->app->bind(UncompromisedVerifier::class, fn () => new class implements UncompromisedVerifier
    {
        public function verify($data): bool
        {
            return false;
        }
    });

    $this->post(route('register'), registrationPayload(['password' => 'breached password']))
        ->assertSessionHasErrors('password');

    expect(User::query()->where('email', 'jane@example.com')->exists())->toBeFalse();
});
