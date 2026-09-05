<?php

declare(strict_types=1);

use App\Livewire\Admin\SubscriptionQueue;
use App\Livewire\CompanyHub\ProductForm;
use App\Livewire\CompanyHub\SubscriptionPage;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function fakeDltQuickLink(bool $createSucceeds = true): void
{
    Cache::forget('dlt:bearer_token');

    Http::fake(function (ClientRequest $request) use ($createSucceeds) {
        if (str_ends_with($request->url(), '/oauth/token')) {
            return Http::response(['access_token' => 'test-token', 'expires_in' => 3600]);
        }

        if ($request->method() === 'POST') {
            return $createSucceeds
                ? Http::response(['success' => true, 'data' => [
                    'token' => 'renewal-token',
                    'website' => 'https://checkout.example.test/pay',
                    'expires_at' => now()->addHour()->timestamp,
                ]])
                : Http::response(['success' => false], 500);
        }

        return Http::response(['success' => true, 'data' => ['token' => 'renewal-token']]);
    });
}

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

test('non-ph merchants complete the payment success flow', function () {
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

    fakeDltQuickLink();

    Livewire::actingAs($seller)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id)
        ->assertHasNoErrors();

    $subscription = Subscription::query()->where('shop_id', $shop->id)->firstOrFail();
    $attempt = PaymentAttempt::query()->where('subscription_id', $subscription->id)->firstOrFail();

    expect($subscription->status)->toBe('pending_payment')
        ->and($attempt->dlt_quick_link_token)->toBe('renewal-token')
        ->and($attempt->dlt_payment_url)->toBe('https://checkout.example.test/pay');

    $response = $this->actingAs($seller)->get(route('hub.subscriptions.payment.success', [
        'subscription' => $subscription->id,
        'attempt' => $attempt->id,
    ]));

    $response->assertRedirect(route('hub.subscription'));

    expect($subscription->fresh()->status)->toBe('active');
});

test('non-ph merchants are cancelled when payment session fails', function () {
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

    fakeDltQuickLink(false);

    Livewire::actingAs($seller)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id)
        ->assertHasNoErrors();

    $subscription = Subscription::query()->where('shop_id', $shop->id)->firstOrFail();
    $attempt = PaymentAttempt::query()->where('subscription_id', $subscription->id)->firstOrFail();

    expect($subscription->status)->toBe('cancelled')
        ->and($attempt->status)->toBe('failed');
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

    expect(fn () => Livewire::actingAs($seller)
        ->test(ProductForm::class, ['product' => $product])
        ->call('publish'))
        ->toThrow(AuthorizationException::class);
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
        ->assertSee($shop->storefront_name)
        ->assertSee($plan->name);
});

test('the admin subscription queue is scoped to the configured instance country', function () {
    config()->set('marketplace.instance_country', 'PH');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $phShop = Shop::factory()->create(['status' => 'active', 'country_code' => 'PH']);
    $myShop = Shop::factory()->create(['status' => 'active', 'country_code' => 'MY']);
    $plan = SubscriptionPlan::factory()->create();
    Subscription::factory()->trialing()->create(['shop_id' => $phShop->id, 'subscription_plan_id' => $plan->id]);
    Subscription::factory()->trialing()->create(['shop_id' => $myShop->id, 'subscription_plan_id' => $plan->id]);

    Livewire::actingAs($admin)
        ->test(SubscriptionQueue::class)
        ->assertSee($phShop->storefront_name)
        ->assertDontSee($myShop->storefront_name)
        ->assertDontSee('All countries');
});

test('merchants can renew an active subscription before it expires', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active', 'country_code' => 'PH']);
    $plan = SubscriptionPlan::factory()->philippines()->create(['price' => '1200.00']);
    $existing = Subscription::factory()->trialing()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'current_period_end' => now()->addDays(20),
    ]);

    fakeDltQuickLink();

    Livewire::actingAs($seller)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id)
        ->assertHasNoErrors();

    $renewal = Subscription::query()->where('shop_id', $shop->id)->latest('id')->firstOrFail();
    $attempt = PaymentAttempt::query()->where('subscription_id', $renewal->id)->firstOrFail();

    $this->actingAs($seller)->get(route('hub.subscriptions.payment.success', [
        'subscription' => $renewal->id,
        'attempt' => $attempt->id,
    ]))->assertRedirect(route('hub.subscription'));

    expect($existing->fresh()->status)->toBe('lapsed')
        ->and($renewal->fresh()->status)->toBe('active')
        ->and($renewal->fresh()->current_period_start->toDateString())
        ->toBe($existing->current_period_end->toDateString());
});
