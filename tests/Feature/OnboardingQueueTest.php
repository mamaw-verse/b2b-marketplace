<?php

declare(strict_types=1);

use App\Livewire\Admin\OnboardingQueue;
use App\Models\ShopOnboarding;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeQueueOnboarding(string $status): ShopOnboarding
{
    $user = User::factory()->create();
    $user->assignRole('seller');

    return ShopOnboarding::create([
        'user_id' => $user->id,
        'registered_business_name' => 'Queue Co '.$status,
        'storefront_name' => 'Queue Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-Q',
        'tax_identification_number' => 'TIN-Q',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => $status,
        'submitted_at' => now(),
    ]);
}

test('the all tab shows submitted, pending and requires_revision submissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    makeQueueOnboarding('submitted');
    makeQueueOnboarding('pending');
    makeQueueOnboarding('requires_revision');
    makeQueueOnboarding('approved');
    makeQueueOnboarding('rejected');

    $this->actingAs($admin);

    $component = Livewire::test(OnboardingQueue::class)
        ->call('filterByStatus', 'all');

    expect($component->get('onboardings')->total())->toBe(3);
});

test('tabCounts returns per-status counts including an all aggregate', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    makeQueueOnboarding('submitted');
    makeQueueOnboarding('submitted');
    makeQueueOnboarding('pending');
    makeQueueOnboarding('requires_revision');
    makeQueueOnboarding('approved');

    $this->actingAs($admin);

    $counts = Livewire::test(OnboardingQueue::class)->get('tabCounts');

    expect($counts['submitted'])->toBe(2)
        ->and($counts['pending'])->toBe(1)
        ->and($counts['requires_revision'])->toBe(1)
        ->and($counts['approved'])->toBe(1)
        ->and($counts['rejected'])->toBe(0)
        ->and($counts['all'])->toBe(4);
});

test('the queue renders the all tab and count badges', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    makeQueueOnboarding('submitted');

    $this->actingAs($admin);

    Livewire::test(OnboardingQueue::class)
        ->assertSee('All')
        ->assertSee('Submitted');
});
