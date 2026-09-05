<?php

declare(strict_types=1);

use App\Livewire\Admin\SubscriptionQueue;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SubscriptionPlanSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('is accessible to admins', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.subscriptions.index'))
        ->assertOk();
});

it('is inaccessible to non-admins', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.subscriptions.index'))
        ->assertForbidden();
});

it('opens settings modal and loads plan data', function (): void {
    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->assertSet('showSettingsModal', false)
        ->call('openSettings')
        ->assertSet('showSettingsModal', true)
        ->assertSet('settingsPlans.0.name', 'Annual Membership Plan');
});

it('saves updated price via settings modal', function (): void {
    $plan = SubscriptionPlan::first();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('openSettings')
        ->set('settingsPlans.0.price', '4999.00')
        ->call('saveSettings')
        ->assertSet('showSettingsModal', false);

    expect($plan->fresh()->price)->toBe('4999.00');
});

it('saves empty trial days as null', function (): void {
    $plan = SubscriptionPlan::first();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('openSettings')
        ->set('settingsPlans.0.trial_days', '')
        ->call('saveSettings');

    expect($plan->fresh()->trial_days)->toBeNull();
});

it('can deactivate a plan', function (): void {
    $plan = SubscriptionPlan::first();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('openSettings')
        ->set('settingsPlans.0.is_active', false)
        ->call('saveSettings');

    expect($plan->fresh()->is_active)->toBeFalse();
});

it('validates price is non-negative', function (): void {
    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('openSettings')
        ->set('settingsPlans.0.price', '-1')
        ->call('saveSettings')
        ->assertHasErrors(['settingsPlans.0.price']);
});
