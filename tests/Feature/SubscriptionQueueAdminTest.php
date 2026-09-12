<?php

declare(strict_types=1);

use App\Livewire\Admin\SubscriptionQueue;
use App\Models\PaymentAttempt;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Event::fake();

    // Clear the cached DLT bearer token so each test's Http::fake is
    // used for the /oauth/token call instead of the cached value.
    Cache::forget('dlt:bearer_token');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

// ──────────────────────────────────────────────────────────────
// One-per-company query
// ──────────────────────────────────────────────────────────────

test('subscriptions tab shows only the latest subscription per shop', function () {
    $plan = SubscriptionPlan::factory()->create();
    $shop = Shop::factory()->create(['status' => 'active', 'country_code' => config('marketplace.instance_country')]);

    // Two subscriptions for the same shop — only the latest should appear.
    $older = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'lapsed',
    ]);
    $latest = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class);

    $ids = $component->get('subscriptions')->pluck('id')->all();

    expect($ids)->toContain($latest->id)
        ->and($ids)->not->toContain($older->id);
});

test('subscriptions tab shows one row per shop even across different plans', function () {
    $plan = SubscriptionPlan::factory()->create();
    $shopA = Shop::factory()->create(['status' => 'active', 'country_code' => config('marketplace.instance_country'), 'registered_business_name' => 'Alpha Corp']);
    $shopB = Shop::factory()->create(['status' => 'active', 'country_code' => config('marketplace.instance_country'), 'registered_business_name' => 'Beta Corp']);

    Subscription::factory()->create(['shop_id' => $shopA->id, 'subscription_plan_id' => $plan->id]);
    Subscription::factory()->create(['shop_id' => $shopA->id, 'subscription_plan_id' => $plan->id]); // 2nd for shopA
    Subscription::factory()->create(['shop_id' => $shopB->id, 'subscription_plan_id' => $plan->id]);

    $component = Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class);

    $shopIds = $component->get('subscriptions')->pluck('shop_id')->all();

    // No duplicates for the same shop.
    expect(array_unique($shopIds))->toHaveCount(count($shopIds));
});

// ──────────────────────────────────────────────────────────────
// Pending payments tab
// ──────────────────────────────────────────────────────────────

test('pending payments tab shows pending_payment subscriptions that have reached DLT', function () {
    $plan = SubscriptionPlan::factory()->create();
    $shop = Shop::factory()->create(['status' => 'active', 'country_code' => config('marketplace.instance_country'), 'registered_business_name' => 'Stuck Corp']);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'pending_payment',
    ]);
    // Must have an attempt with a transaction ID to appear in the reconciliation tab.
    PaymentAttempt::factory()->forSubscription($subscription)->syncPending(1)->create();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('switchTab', 'pending_payments')
        ->assertSee('Stuck Corp');
});

test('pending payments tab does not show subscriptions that never reached DLT', function () {
    $plan = SubscriptionPlan::factory()->create();
    $shop = Shop::factory()->create(['status' => 'active', 'country_code' => config('marketplace.instance_country'), 'registered_business_name' => 'No Attempt Corp']);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'pending_payment',
    ]);
    // No PaymentAttempt created — user never reached the payment wall.

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('switchTab', 'pending_payments')
        ->assertDontSee('No Attempt Corp');
});

test('pending payments tab does not show active subscriptions', function () {
    $plan = SubscriptionPlan::factory()->create();
    $shop = Shop::factory()->create(['status' => 'active', 'country_code' => config('marketplace.instance_country'), 'registered_business_name' => 'Active Corp']);
    Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('switchTab', 'pending_payments')
        ->assertDontSee('Active Corp');
});

// ──────────────────────────────────────────────────────────────
// confirmActivate — /sync PAID branch
// ──────────────────────────────────────────────────────────────

test('confirmActivate activates immediately when /sync returns PAID', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => true, 'data' => ['payment_status' => 'PAID']]),
    ]);

    [$subscription, $attempt] = makePendingSetup();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('confirmActivate', $subscription->id);

    expect($subscription->fresh()->status)->toBe('active');
    expect($attempt->fresh()->status)->toBe('succeeded');
    expect($attempt->fresh()->dlt_payment_status)->toBe('PAID');
});

test('confirmActivate lapses existing active subscription when /sync returns PAID', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => true, 'data' => ['payment_status' => 'PAID']]),
    ]);

    $plan = SubscriptionPlan::factory()->create(['price' => '1200.00']);
    $shop = Shop::factory()->create(['status' => 'active', 'country_code' => config('marketplace.instance_country')]);

    $existing = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
    ]);
    $pending = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'pending_payment',
    ]);
    PaymentAttempt::factory()->forSubscription($pending)->syncPending(1)->create();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('confirmActivate', $pending->id);

    expect($existing->fresh()->status)->toBe('lapsed');
    expect($pending->fresh()->status)->toBe('active');
});

// ──────────────────────────────────────────────────────────────
// confirmActivate — /sync PENDING branch → forceActivateOverride
// ──────────────────────────────────────────────────────────────

test('confirmActivate does not activate when /sync returns PENDING', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => true, 'data' => ['payment_status' => 'PENDING']]),
    ]);

    [$subscription, $attempt] = makePendingSetup();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('confirmActivate', $subscription->id);

    // Subscription should still be pending — waiting for admin override.
    expect($subscription->fresh()->status)->toBe('pending_payment');
    expect($attempt->fresh()->status)->toBe('pending');
});

test('forceActivateOverride activates after admin confirms the PENDING warning', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => true, 'data' => ['payment_status' => 'PENDING']]),
    ]);

    [$subscription, $attempt] = makePendingSetup();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('confirmActivate', $subscription->id)
        ->call('forceActivateOverride');

    expect($subscription->fresh()->status)->toBe('active');
    expect($attempt->fresh()->status)->toBe('succeeded');
    expect($attempt->fresh()->dlt_payment_status)->toBe('PAID');
});

// ──────────────────────────────────────────────────────────────
// confirmActivate — /sync REJECTED branch
// ──────────────────────────────────────────────────────────────

test('confirmActivate cancels immediately when /sync returns REJECTED', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => true, 'data' => ['payment_status' => 'REJECTED']]),
    ]);

    [$subscription, $attempt] = makePendingSetup();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('confirmActivate', $subscription->id);

    expect($subscription->fresh()->status)->toBe('cancelled');
    expect($attempt->fresh()->status)->toBe('failed');
    expect($attempt->fresh()->dlt_payment_status)->toBe('REJECTED');
});

// ──────────────────────────────────────────────────────────────
// confirmActivate — /sync error branch
// ──────────────────────────────────────────────────────────────

test('confirmActivate cancels immediately when /sync throws a runtime exception', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => false, 'message' => 'Server error'], 503),
    ]);

    [$subscription, $attempt] = makePendingSetup();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('confirmActivate', $subscription->id);

    expect($subscription->fresh()->status)->toBe('cancelled');
    expect($attempt->fresh()->status)->toBe('failed');
    expect($attempt->fresh()->dlt_payment_status)->toBe('ERROR');
});

// ──────────────────────────────────────────────────────────────
// confirmActivate — manual attempt skips /sync
// ──────────────────────────────────────────────────────────────

test('confirmActivate skips /sync for manual payment attempts and opens standard modal', function () {
    // No Http::fake needed — /sync should never be called for manual attempts.
    $plan = SubscriptionPlan::factory()->create(['price' => '1200.00']);
    $shop = Shop::factory()->create(['status' => 'active', 'country_code' => config('marketplace.instance_country')]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'pending_payment',
    ]);
    PaymentAttempt::factory()->forSubscription($subscription)->manualPending()->create();

    // confirmActivate should set confirmingActivateId without auto-activating or auto-cancelling.
    $component = Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('confirmActivate', $subscription->id);

    // Subscription is still pending — standard modal opened, awaiting forceActivate.
    expect($subscription->fresh()->status)->toBe('pending_payment');
    $component->assertSet('confirmingActivateId', $subscription->id);
});

test('forceActivate activates a manual attempt without setting dlt_payment_status', function () {
    $plan = SubscriptionPlan::factory()->create(['price' => '1200.00']);
    $shop = Shop::factory()->create(['status' => 'active', 'country_code' => config('marketplace.instance_country')]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'pending_payment',
    ]);
    $attempt = PaymentAttempt::factory()->forSubscription($subscription)->manualPending()->create();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('confirmActivate', $subscription->id)
        ->call('forceActivate');

    expect($subscription->fresh()->status)->toBe('active');
    expect($attempt->fresh()->status)->toBe('succeeded');
    expect($attempt->fresh()->dlt_payment_status)->toBeNull();
});

// ──────────────────────────────────────────────────────────────
// forceActivate — legacy / direct call guard
// ──────────────────────────────────────────────────────────────

test('forceActivate is rejected for a non-pending_payment subscription', function () {
    $plan = SubscriptionPlan::factory()->create();
    $shop = Shop::factory()->create(['status' => 'active', 'country_code' => config('marketplace.instance_country')]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->set('confirmingActivateId', $subscription->id)
        ->call('forceActivate')
        ->assertHasErrors();
})->throws(ModelNotFoundException::class);

// ──────────────────────────────────────────────────────────────
// Force cancel
// ──────────────────────────────────────────────────────────────

test('forceCancel cancels the subscription and marks the attempt failed', function () {
    [$subscription, $attempt] = makePendingSetup();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('confirmCancel', $subscription->id)
        ->call('forceCancel');

    expect($subscription->fresh()->status)->toBe('cancelled');
    expect($attempt->fresh()->status)->toBe('failed');
    expect($attempt->fresh()->dlt_payment_status)->toBe('REJECTED');
});

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

/**
 * @return array{0: Subscription, 1: PaymentAttempt}
 */
function makePendingSetup(): array
{
    $plan = SubscriptionPlan::factory()->create(['price' => '1200.00']);
    $shop = Shop::factory()->create([
        'status' => 'active',
        'country_code' => config('marketplace.instance_country'),
    ]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'pending_payment',
    ]);
    $attempt = PaymentAttempt::factory()
        ->forSubscription($subscription)
        ->syncPending(3)
        ->create();

    return [$subscription, $attempt];
}
