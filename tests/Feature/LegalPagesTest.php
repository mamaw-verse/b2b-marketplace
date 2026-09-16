<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

test('terms page loads successfully', function () {
    $response = $this->get(route('legal.terms'));

    $response->assertOk();
    $response->assertSee('Terms of Service');
    $response->assertSee('Halal Integrity');
    $response->assertSee('Documents');
    $response->assertSee('On this page');
    $response->assertSee(route('legal.privacy'));
    $response->assertSee(route('help.faq'));
});

test('privacy page loads successfully', function () {
    $response = $this->get(route('legal.privacy'));

    $response->assertOk();
    $response->assertSee('Privacy Policy');
    $response->assertSee('Data Privacy Act of 2012');
    $response->assertSee('Documents');
    $response->assertSee('On this page');
    $response->assertSee(route('legal.terms'));
});

test('faq page loads successfully', function () {
    $response = $this->get(route('help.faq'));

    $response->assertOk();
    $response->assertSee('Frequently Asked Questions');
    $response->assertSee('Farm to Plate');
    $response->assertSee('Documents');
    $response->assertSee('Browse topics');
    $response->assertSee(route('legal.terms'));
    $response->assertSee(route('legal.privacy'));
});

test('storefront footer links to legal pages', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee(route('legal.terms'));
    $response->assertSee(route('legal.privacy'));
    $response->assertSee(route('help.faq'));
});

test('register page shows terms checkbox with full-document modal and gated agree button', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('name="terms"', false);
    $response->assertSee('Terms of Service and Privacy Policy');
    $response->assertSee('Limitation of Liability');
    $response->assertSee('Data Breaches and Security Incidents');
    $response->assertSee('Scroll to the bottom to enable Agree');
    $response->assertSee('Agree');
});

test('registration requires terms acceptance', function () {
    $this->post(route('register'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'termsbuyer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'cf-turnstile-response' => 'valid-token',
    ])->assertSessionHasErrors([
        'terms' => 'You must agree to the Terms of Service and Privacy Policy.',
    ]);

    expect(User::where('email', 'termsbuyer@example.com')->exists())->toBeFalse();
    $this->assertGuest();
});

test('registration succeeds when terms are accepted', function () {
    $this->post(route('register'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'termsbuyer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
        'cf-turnstile-response' => 'valid-token',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'termsbuyer@example.com')->exists())->toBeTrue();
});

test('privacy page shows controller details and hides phone when unset', function () {
    $response = $this->get(route('legal.privacy'));

    $response->assertOk();
    $response->assertSee('Controller: Ismail Abaya');
    $response->assertSee('BO. Taytay, El Salvador City, Misamis Oriental 9017');
    $response->assertSee('admin@gmail.com');
    $response->assertSee('Data Protection Officer: Ismail Abaya');
    $response->assertSee('Effective Date: September 16, 2026');
    $response->assertDontSee('To be confirmed');
    $response->assertDontSee('Telephone:');
});

test('privacy page shows admin phone when set on admin profile', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create(['phone' => '+639171234567']);
    $admin->assignRole('admin');

    $response = $this->get(route('legal.privacy'));

    $response->assertOk();
    $response->assertSee('Phone: +639171234567');
});
