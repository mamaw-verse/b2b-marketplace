<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;

test('unverified users receive the branded verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('the verification mail renders the branded template with a signed url', function () {
    $user = User::factory()->unverified()->create();

    $mail = (new VerifyEmailNotification)->toMail($user);
    $rendered = (string) $mail->render();

    expect($mail->subject)->toContain('Verify your email address')
        ->and($rendered)->toContain('/email/verify/'.$user->getKey())
        ->and($rendered)->toContain('signature=')
        ->and($rendered)->toContain('brand-logo.png')
        ->and($rendered)->toContain('Maslaha Halal Certification Board Inc.')
        ->and($rendered)->toContain('Verify Email Address');
});

test('the verification mail footer links to storefront routes and drops the about link', function () {
    $user = User::factory()->unverified()->create();

    $rendered = (string) (new VerifyEmailNotification)->toMail($user)->render();

    expect($rendered)->toContain('href="'.route('home').'"')
        ->and($rendered)->toContain('>Home</a>')
        ->and($rendered)->toContain('href="'.route('storefront.products').'"')
        ->and($rendered)->toContain('>Products</a>')
        ->and($rendered)->toContain('href="'.route('storefront.companies').'"')
        ->and($rendered)->toContain('>Verified Businesses</a>')
        ->and($rendered)->not->toContain('1halalstore.com')
        ->and($rendered)->not->toContain('>About</a>');
});
