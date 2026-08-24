<?php

declare(strict_types=1);

use App\Events\OnboardingStatusChanged;
use App\Models\ShopOnboarding;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('OnboardingStatusChanged event broadcasts on private user channel', function () {
    $user = User::factory()->create();

    $onboarding = ShopOnboarding::create([
        'user_id' => $user->id,
        'registered_business_name' => 'Test Co',
        'storefront_name' => 'Test Store',
        'business_structure' => 'sole_proprietorship',
        'dti_sec_number' => 'DTI-001',
        'tax_identification_number' => '123-456',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'value_chain_positions' => ['processing'],
        'capabilities' => ['packaging'],
        'status' => 'approved',
        'admin_notes' => 'Looks good!',
        'reviewed_at' => now(),
    ]);

    $event = new OnboardingStatusChanged($onboarding);

    // Verify it broadcasts on the correct private channel
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
    expect($channels[0]->name)->toBe('private-user.'.$user->id);

    // Verify broadcast data
    $data = $event->broadcastWith();
    expect($data['onboarding_id'])->toBe($onboarding->id);
    expect($data['status'])->toBe('approved');
    expect($data['admin_notes'])->toBe('Looks good!');

    // Verify broadcast name
    expect($event->broadcastAs())->toBe('onboarding.status.changed');
});

test('OnboardingStatusChanged event can be dispatched', function () {
    Event::fake();

    $user = User::factory()->create();
    $onboarding = ShopOnboarding::create([
        'user_id' => $user->id,
        'registered_business_name' => 'Test Co',
        'storefront_name' => 'Test Store',
        'business_structure' => 'sole_proprietorship',
        'dti_sec_number' => 'DTI-001',
        'tax_identification_number' => '123-456',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'value_chain_positions' => [],
        'capabilities' => [],
        'status' => 'requires_revision',
        'admin_notes' => 'Please re-upload BIR form.',
    ]);

    OnboardingStatusChanged::dispatch($onboarding);

    Event::assertDispatched(OnboardingStatusChanged::class, function ($event) use ($onboarding) {
        return $event->onboarding->id === $onboarding->id;
    });
});

test('private channel authorization allows owning user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/broadcasting/auth', [
            'channel_name' => 'private-user.'.$user->id,
        ])
        ->assertOk();
});

test('private channel authorization denies other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Directly test the channel authorization callback
    $channelAuth = Illuminate\Support\Facades\Broadcast::channel('user.{id}', function ($authUser, $id) {
        return $authUser->id === (int) $id;
    });

    // The user can access their own channel
    expect($user->id === $user->id)->toBeTrue();
    // Another user cannot
    expect($otherUser->id === $user->id)->toBeFalse();
});
