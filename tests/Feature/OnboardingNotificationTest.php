<?php

declare(strict_types=1);

use App\Models\ShopOnboarding;
use App\Models\User;
use App\Notifications\OnboardingApprovedNotification;
use App\Notifications\OnboardingRejectedNotification;
use App\Notifications\OnboardingRevisionRequestedNotification;
use App\Notifications\OnboardingSubmittedNotification;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->onboarding = ShopOnboarding::create([
        'user_id' => $this->user->id,
        'registered_business_name' => 'Notif Co',
        'storefront_name' => 'Notif Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-9',
        'tax_identification_number' => 'TIN-9',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'requires_revision',
        'admin_notes' => 'Please fix.',
    ]);
});

test('approved notification links to the hub dashboard route', function () {
    $data = (new OnboardingApprovedNotification($this->onboarding))->toArray($this->user);

    expect($data['url'])->toBe(route('hub.hub-dashboard'))
        ->and($data['url'])->not->toContain('/hub/shop');
});

test('rejected notification links to the account onboarding route', function () {
    $data = (new OnboardingRejectedNotification($this->onboarding))->toArray($this->user);

    expect($data['url'])->toBe(route('account.onboarding'));
});

test('revision requested notification links to the account onboarding route', function () {
    $data = (new OnboardingRevisionRequestedNotification($this->onboarding))->toArray($this->user);

    expect($data['url'])->toBe(route('account.onboarding'));
});

test('onboarding mail renders the 1Halal Store brand logo in the header', function () {
    $mail = (new OnboardingApprovedNotification($this->onboarding))->toMail($this->user);
    $rendered = (string) $mail->render();

    expect(str_contains($rendered, 'images/brand-logo.png'))->toBeTrue()
        ->and(str_contains($rendered, '1Halal Store'))->toBeTrue();
});

test('submitted notification mail and array use registered_business_name not storefront_name', function () {
    // storefront_name is nullable on onboardings — the notification must never rely on it.
    $this->onboarding->update(['storefront_name' => null]);
    $this->onboarding->refresh();

    $notification = new OnboardingSubmittedNotification($this->onboarding);

    $mailBody = $notification->toMail($this->user)->render();
    expect((string) $mailBody)->toContain($this->onboarding->registered_business_name);

    $data = $notification->toArray($this->user);
    expect($data['message'])->toContain($this->onboarding->registered_business_name)
        ->and($data['storefront_name'])->toBe($this->onboarding->registered_business_name);
});
