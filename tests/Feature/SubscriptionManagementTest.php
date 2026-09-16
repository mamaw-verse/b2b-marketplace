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
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    // This file tests DLT-flow subscription behaviour (trials, pending_payment).
    // Guard against .env having MARKETPLACE_PAYMENT_METHOD=manual.
    Config::set('marketplace.payment_method', 'dlt');
});

test('ph merchants receive a free trial when subscribing', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'country_code' => 'PH',
    ]);
    $plan = SubscriptionPlan::factory()->philippines()->create([
        'name' => 'PH Plan',
    ]);

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

test('non-ph merchants get a pending_payment subscription on subscribe', function () {
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

test('non-ph merchants are placed in pending_payment when subscribing to a paid plan', function () {
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
    ]);

    Livewire::actingAs($seller)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id)
        ->assertHasNoErrors();

    $subscription = Subscription::query()->where('shop_id', $shop->id)->firstOrFail();

    expect($subscription->status)->toBe('pending_payment');
});

test('publish is blocked when the shop has no subscription', function () {
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

test('publish is allowed while the shop is trialing', function () {
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

    Livewire::actingAs($seller)
        ->test(ProductForm::class, ['product' => $product])
        ->call('publish')
        ->assertHasNoErrors();

    expect($product->fresh()->status)->toBe('published');
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
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active', 'country_code' => 'PH']);
    $plan = SubscriptionPlan::factory()->philippines()->create(['price' => '1200.00']);
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
