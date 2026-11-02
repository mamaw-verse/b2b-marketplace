<?php

declare(strict_types=1);

use App\Livewire\Admin\SubscriptionQueue;
use App\Models\PaymentAttempt;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Event::fake();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->plan = SubscriptionPlan::factory()->create();
});

// ──────────────────────────────────────────────────────────────
// Scenarios that MUST appear in the Pending Payments tab
// ──────────────────────────────────────────────────────────────

test('shows a pending_payment subscription with no attempt (user abandoned before payment page)', function () {
    $shop = Shop::factory()->create([
        'status' => 'active',
        'country_code' => config('marketplace.instance_country'),
        'registered_business_name' => 'Abandoned Before Payment',
    ]);
    Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $this->plan->id,
        'status' => 'pending_payment',
    ]);
    // Zero payment attempts — user chose a plan and immediately closed the browser.

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('switchTab', 'pending_payments')
        ->assertSee('Abandoned Before Payment');
});

test('shows a pending_payment subscription with a DLT attempt that has no merchant transaction ID (closed before redirect)', function () {
    $shop = Shop::factory()->create([
        'status' => 'active',
        'country_code' => config('marketplace.instance_country'),
        'registered_business_name' => 'Closed Before Redirect',
    ]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $this->plan->id,
        'status' => 'pending_payment',
    ]);
    // Attempt was created but /submit was never called (no merchant transaction ID).
    PaymentAttempt::factory()->forSubscription($subscription)->state([
        'payment_method' => 'dlt',
        'status' => 'pending',
        'dlt_merchant_transaction_id' => null,
        'dlt_payment_status' => null,
    ])->create();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('switchTab', 'pending_payments')
        ->assertSee('Closed Before Redirect');
});

test('shows a pending_payment subscription with a DLT attempt that has a merchant transaction ID (redirected but did not complete)', function () {
    $shop = Shop::factory()->create([
        'status' => 'active',
        'country_code' => config('marketplace.instance_country'),
        'registered_business_name' => 'DLT Redirect Incomplete',
    ]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $this->plan->id,
        'status' => 'pending_payment',
    ]);
    PaymentAttempt::factory()->forSubscription($subscription)->syncPending(1)->create();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('switchTab', 'pending_payments')
        ->assertSee('DLT Redirect Incomplete');
});

test('shows a pending_payment subscription with a manual attempt awaiting admin', function () {
    $shop = Shop::factory()->create([
        'status' => 'active',
        'country_code' => config('marketplace.instance_country'),
        'registered_business_name' => 'Manual Transfer Pending',
    ]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $this->plan->id,
        'status' => 'pending_payment',
    ]);
    PaymentAttempt::factory()->forSubscription($subscription)->manualPending()->create();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('switchTab', 'pending_payments')
        ->assertSee('Manual Transfer Pending');
});

// ──────────────────────────────────────────────────────────────
// Scenarios that must NOT appear in the Pending Payments tab
// ──────────────────────────────────────────────────────────────

test('does not show an active subscription', function () {
    $shop = Shop::factory()->create([
        'status' => 'active',
        'country_code' => config('marketplace.instance_country'),
        'registered_business_name' => 'Active Subscriber',
    ]);
    Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $this->plan->id,
        'status' => 'active',
    ]);

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('switchTab', 'pending_payments')
        ->assertDontSee('Active Subscriber');
});

test('does not show a cancelled subscription', function () {
    $shop = Shop::factory()->create([
        'status' => 'active',
        'country_code' => config('marketplace.instance_country'),
        'registered_business_name' => 'Cancelled Subscriber',
    ]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $this->plan->id,
        'status' => 'cancelled',
    ]);
    PaymentAttempt::factory()->forSubscription($subscription)->failed()->create();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('switchTab', 'pending_payments')
        ->assertDontSee('Cancelled Subscriber');
});

test('does not show a pending_payment subscription with only a succeeded attempt', function () {
    // Edge case: status somehow desynchronised — succeeded attempt but still pending_payment.
    // Should NOT appear since there is no unresolved pending attempt.
    $shop = Shop::factory()->create([
        'status' => 'active',
        'country_code' => config('marketplace.instance_country'),
        'registered_business_name' => 'Desync Corp',
    ]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $this->plan->id,
        'status' => 'pending_payment',
    ]);
    PaymentAttempt::factory()->forSubscription($subscription)->succeeded()->create();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('switchTab', 'pending_payments')
        ->assertDontSee('Desync Corp');
});

test('does not show a pending_payment subscription with only a failed attempt', function () {
    $shop = Shop::factory()->create([
        'status' => 'active',
        'country_code' => config('marketplace.instance_country'),
        'registered_business_name' => 'Failed Only Corp',
    ]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $this->plan->id,
        'status' => 'pending_payment',
    ]);
    PaymentAttempt::factory()->forSubscription($subscription)->failed()->create();

    Livewire::actingAs($this->admin)
        ->test(SubscriptionQueue::class)
        ->call('switchTab', 'pending_payments')
        ->assertDontSee('Failed Only Corp');
});

// ──────────────────────────────────────────────────────────────
// Badge count includes no-attempt subscriptions
// ──────────────────────────────────────────────────────────────

test('pending payments badge count includes no-attempt subscriptions', function () {
    $countWithNoAttempt = 2;

    for ($i = 0; $i < $countWithNoAttempt; $i++) {
        $shop = Shop::factory()->create([
            'status' => 'active',
            'country_code' => config('marketplace.instance_country'),
        ]);
        Subscription::factory()->create([
            'shop_id' => $shop->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'pending_payment',
        ]);
    }

    $component = Livewire::actingAs($this->admin)->test(SubscriptionQueue::class);

    expect($component->get('pendingPaymentSubscriptions')->total())->toBe($countWithNoAttempt);
});
