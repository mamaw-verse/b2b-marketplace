<?php

declare(strict_types=1);

use App\Livewire\Admin\SubscriptionQueue;
use App\Livewire\CompanyHub\ProductForm;
use App\Livewire\CompanyHub\SubscriptionPage;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('merchants receive a free trial when the plan has trial_days configured', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);
    $plan = SubscriptionPlan::factory()->create(['trial_days' => 180, 'is_active' => true]);

    Livewire::actingAs($seller)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id)
        ->assertHasNoErrors()
        ->assertNoRedirect();

    expect(Subscription::query()->where('shop_id', $shop->id)->firstOrFail()->status)->toBe('trialing');
});

test('merchants receive a free trial regardless of payment method', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);
    $plan = SubscriptionPlan::factory()->create(['trial_days' => 180, 'is_active' => true]);

    Livewire::actingAs($seller)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id)
        ->assertHasNoErrors();

    $subscription = Subscription::query()->where('shop_id', $shop->id)->firstOrFail();

    expect($subscription->status)->toBe('trialing')
        ->and($subscription->trial_ends_at)->not->toBeNull();

    $this->assertDatabaseMissing('payment_attempts', [
        'subscription_id' => $subscription->id,
    ]);
});

test('merchants go to pending_payment when the plan has no trial_days', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'country_code' => 'MY',
    ]);
    $plan = SubscriptionPlan::factory()->create([
        'country_scope' => null,
        'name' => 'International Plan',
        'price' => '1200.00',
        'trial_days' => null,
    ]);

    Livewire::actingAs($seller)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id)
        ->assertHasNoErrors();

    $subscription = Subscription::query()->where('shop_id', $shop->id)->firstOrFail();

    expect($subscription->status)->toBe('pending_payment');

    // Payment attempt is created on the payment page, not here.
    $this->assertDatabaseMissing('payment_attempts', [
        'subscription_id' => $subscription->id,
    ]);
});

test('merchants are placed in pending_payment when subscribing to a paid plan with no trial', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'country_code' => 'MY',
    ]);
    $plan = SubscriptionPlan::factory()->create([
        'country_scope' => null,
        'name' => 'International Plan',
        'price' => '1200.00',
        'trial_days' => null,
    ]);

    Livewire::actingAs($seller)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id)
        ->assertHasNoErrors();

    $subscription = Subscription::query()->where('shop_id', $shop->id)->firstOrFail();

    expect($subscription->status)->toBe('pending_payment');
});

test('publish is blocked when the shop has no subscription and subscriptions are enabled', function () {
    config(['marketplace.features.subscriptions_enabled' => true]);

    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'archived',
    ]);

    // Livewire catches AuthorizationException internally — the product must remain archived.
    Livewire::actingAs($seller)
        ->test(ProductForm::class, ['product' => $product])
        ->call('publish');

    expect($product->fresh()->status)->toBe('archived');
});

test('publish is allowed when subscriptions are disabled and the shop is active', function () {
    config(['marketplace.features.subscriptions_enabled' => false]);

    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'archived',
    ]);

    Livewire::actingAs($seller)
        ->test(ProductForm::class, ['product' => $product])
        ->call('publish')
        ->assertHasNoErrors();

    expect($product->fresh()->status)->toBe('published');
});

test('publish is blocked while the shop is trialing and subscriptions are enabled', function () {
    config(['marketplace.features.subscriptions_enabled' => true]);

    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);
    Subscription::factory()->trialing()->create([
        'shop_id' => $shop->id,
    ]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'archived',
    ]);

    // Livewire catches AuthorizationException internally — the product must remain archived.
    Livewire::actingAs($seller)
        ->test(ProductForm::class, ['product' => $product])
        ->call('publish');

    expect($product->fresh()->status)->toBe('archived');
});

test('the admin subscription queue renders subscription rows', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $shop = Shop::factory()->create([
        'status' => 'active',
        'country_code' => 'PH',
        'registered_business_name' => 'Test Business Corp',
    ]);
    $plan = SubscriptionPlan::factory()->philippines()->create([
        'name' => 'PH Plan',
    ]);
    Subscription::factory()->trialing()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionQueue::class)
        ->assertSee($shop->registered_business_name)
        ->assertSee($plan->name);
});

test('the admin subscription queue is scoped to the configured instance country', function () {
    config()->set('marketplace.instance_country', 'PH');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $phShop = Shop::factory()->create(['status' => 'active', 'country_code' => 'PH', 'registered_business_name' => 'PH Business Corp']);
    $myShop = Shop::factory()->create(['status' => 'active', 'country_code' => 'MY', 'registered_business_name' => 'MY Business Sdn Bhd']);
    $plan = SubscriptionPlan::factory()->create();
    Subscription::factory()->trialing()->create(['shop_id' => $phShop->id, 'subscription_plan_id' => $plan->id]);
    Subscription::factory()->trialing()->create(['shop_id' => $myShop->id, 'subscription_plan_id' => $plan->id]);

    Livewire::actingAs($admin)
        ->test(SubscriptionQueue::class)
        ->assertSee($phShop->registered_business_name)
        ->assertDontSee($myShop->registered_business_name)
        ->assertDontSee('All countries');
});

test('merchants can initiate renewal — creates a new pending_payment subscription', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $plan = SubscriptionPlan::factory()->create(['trial_days' => 180, 'price' => '1200.00', 'is_active' => true]);
    Subscription::factory()->trialing()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'current_period_end' => now()->addDays(20),
    ]);

    Livewire::actingAs($seller)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id)
        ->assertHasNoErrors();

    // A new subscription row is created for the renewal.
    $this->assertDatabaseCount('subscriptions', 2);

    $renewal = Subscription::query()->where('shop_id', $shop->id)->latest('id')->firstOrFail();
    // shouldStartTrial is false when an existing subscription already exists, so renewal is pending_payment.
    expect($renewal->status)->toBe('pending_payment');
});

test('merchants can still avail trial after a previously cancelled subscription', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);
    $plan = SubscriptionPlan::factory()->create(['trial_days' => 180, 'is_active' => true]);

    // Simulate a prior attempt that was cancelled (e.g. user dismissed pending payment).
    Subscription::factory()->cancelled()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
    ]);

    Livewire::actingAs($seller)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id)
        ->assertHasNoErrors();

    $latest = Subscription::query()->where('shop_id', $shop->id)->latest('id')->firstOrFail();

    expect($latest->status)->toBe('trialing')
        ->and($latest->trial_ends_at)->not->toBeNull();
});

test('merchants cannot avail trial again after a previously active subscription', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);
    $plan = SubscriptionPlan::factory()->create([
        'trial_days' => 180,
        'price' => '1200.00',
        'is_active' => true,
    ]);

    // A prior subscription that was active (and is now lapsed).
    Subscription::factory()->lapsed()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
    ]);

    Livewire::actingAs($seller)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id)
        ->assertHasNoErrors();

    $latest = Subscription::query()->where('shop_id', $shop->id)->latest('id')->firstOrFail();

    // Should go to pending_payment, not trialing.
    expect($latest->status)->toBe('pending_payment');
});
