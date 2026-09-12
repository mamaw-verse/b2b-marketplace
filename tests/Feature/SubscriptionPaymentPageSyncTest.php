<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\SubscriptionPaymentPage;
use App\Models\PaymentAttempt;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\PaymentStuckAdminNotification;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();

    // Ensure this test file always runs with the DLT payment method, regardless
    // of what other test files may have set via Config::set.
    Config::set('marketplace.payment_method', 'dlt');
    // Clear the DLT bearer token cache so Http::fake intercepts /oauth/token.
    Cache::forget('dlt:bearer_token');

    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/brands' => Http::response([
            'success' => true,
            'data' => [
                ['value' => 'Visa', 'code' => 'VISA', 'image' => null, 'is_card' => true],
                ['value' => 'GCash', 'code' => 'GCASH', 'image' => null, 'is_card' => false],
            ],
        ]),
    ]);
});

// ──────────────────────────────────────────────────────────────
// No prior pending attempt — normal flow
// ──────────────────────────────────────────────────────────────

test('payment page loads brand picker when no prior pending attempt exists', function () {
    [$user, $subscription] = makePageSetup();

    Livewire::actingAs($user)
        ->test(SubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertSet('step', 'brands');
});

// ──────────────────────────────────────────────────────────────
// Gap 2 — prior pending attempt exists, /sync called on mount
// ──────────────────────────────────────────────────────────────

test('mount activates subscription and shows success when prior attempt is PAID on sync', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => true, 'data' => ['payment_status' => 'PAID']]),
        '*/api/v1/collection/apn/brands' => Http::response(['success' => true, 'data' => []]),
    ]);

    [$user, $subscription] = makePageSetup();
    PaymentAttempt::factory()->forSubscription($subscription)->syncPending()->create();

    Livewire::actingAs($user)
        ->test(SubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertSet('step', 'success');

    expect($subscription->fresh()->status)->toBe('active');
});

test('mount marks prior attempt failed and shows brand picker when sync returns REJECTED', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => true, 'data' => ['payment_status' => 'REJECTED']]),
        '*/api/v1/collection/apn/brands' => Http::response(['success' => true, 'data' => [
            ['value' => 'GCash', 'code' => 'GCASH', 'image' => null, 'is_card' => false],
        ]]),
    ]);

    [$user, $subscription] = makePageSetup();
    $attempt = PaymentAttempt::factory()->forSubscription($subscription)->syncPending()->create();

    Livewire::actingAs($user)
        ->test(SubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertSet('step', 'brands');

    expect($attempt->fresh()->status)->toBe('failed');
});

test('mount shows sync_pending and emails admin when prior attempt is still PENDING on sync', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => true, 'data' => ['payment_status' => 'PENDING']]),
    ]);

    [$user, $subscription] = makePageSetup();
    PaymentAttempt::factory()->forSubscription($subscription)->syncPending()->create();

    Livewire::actingAs($user)
        ->test(SubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertSet('step', 'sync_pending');

    Notification::assertSentOnDemand(PaymentStuckAdminNotification::class);
});

test('mount shows sync_pending and emails admin when sync returns a server error', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => false, 'message' => 'Server error'], 503),
    ]);

    [$user, $subscription] = makePageSetup();
    PaymentAttempt::factory()->forSubscription($subscription)->syncPending()->create();

    Livewire::actingAs($user)
        ->test(SubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertSet('step', 'sync_pending');

    Notification::assertSentOnDemand(PaymentStuckAdminNotification::class);
});

// ──────────────────────────────────────────────────────────────
// Gap 1 — confirming step via session flash
// ──────────────────────────────────────────────────────────────

test('mount enters confirming step when session has confirming_attempt_id', function () {
    [$user, $subscription] = makePageSetup();
    $attempt = PaymentAttempt::factory()->forSubscription($subscription)->syncPending()->create();

    session(['confirming_attempt_id' => $attempt->id]);

    Livewire::actingAs($user)
        ->test(SubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertSet('step', 'confirming')
        ->assertSet('confirmingAttemptId', $attempt->id);
});

test('checkConfirmingStatus transitions to success when job settles attempt as succeeded', function () {
    [$user, $subscription] = makePageSetup();
    $attempt = PaymentAttempt::factory()->forSubscription($subscription)->syncPending()->create();

    // Enter confirming step via session flash (the proper path that sets the locked property).
    session(['confirming_attempt_id' => $attempt->id]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertSet('step', 'confirming');

    // Simulate job settling the attempt.
    $attempt->update(['status' => 'succeeded', 'succeeded_at' => now()]);

    $component->call('checkConfirmingStatus')
        ->assertSet('step', 'success');
});

test('checkConfirmingStatus transitions to failed when job settles attempt as REJECTED', function () {
    [$user, $subscription] = makePageSetup();
    $attempt = PaymentAttempt::factory()->forSubscription($subscription)->syncPending()->create();

    session(['confirming_attempt_id' => $attempt->id]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionPaymentPage::class, ['subscription' => $subscription]);

    $attempt->update(['status' => 'failed', 'dlt_payment_status' => 'REJECTED', 'failed_at' => now()]);

    $component->call('checkConfirmingStatus')
        ->assertSet('step', 'failed');
});

test('checkConfirmingStatus transitions to sync_pending when job gives up after max retries', function () {
    [$user, $subscription] = makePageSetup();
    $attempt = PaymentAttempt::factory()->forSubscription($subscription)->syncPending(5)->create();

    session(['confirming_attempt_id' => $attempt->id]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionPaymentPage::class, ['subscription' => $subscription]);

    // Job gave up — failed but DLT status still shows PENDING (not REJECTED).
    $attempt->update(['status' => 'failed', 'dlt_payment_status' => 'PENDING', 'failed_at' => now()]);

    $component->call('checkConfirmingStatus')
        ->assertSet('step', 'sync_pending');
});

// ──────────────────────────────────────────────────────────────
// Gap 3 — is_card_payment absent from submit payload
// ──────────────────────────────────────────────────────────────

test('submitPayment does not include is_card_payment in the DLT request payload', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/brands' => Http::response([
            'success' => true,
            'data' => [['value' => 'GCash', 'code' => 'GCASH', 'image' => null, 'is_card' => false]],
        ]),
        '*/api/v1/collection/apn/submit' => function (Request $request) {
            expect($request->data())->not->toHaveKey('is_card_payment');

            return Http::response([
                'success' => true,
                'data' => [
                    'payment_url' => 'https://checkout.dxp.dtic.com.ph/pay/test',
                    'payment_status' => 'PENDING',
                    'amount' => '1200.00',
                ],
            ]);
        },
    ]);

    [$user, $subscription] = makePageSetup();

    Livewire::actingAs($user)
        ->test(SubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->set('step', 'payment_form')
        ->set('selectedBrand', ['value' => 'GCash', 'code' => 'GCASH', 'image' => null, 'is_card' => false])
        ->set('firstName', 'Juan')
        ->set('lastName', 'Cruz')
        ->set('email', 'juan@example.com')
        ->set('addressLine1', '123 Rizal St')
        ->set('city', 'Manila')
        ->set('province', 'Metro Manila')
        ->set('postalCode', '1000')
        ->call('submitPayment')
        ->assertHasNoErrors();
});

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

/**
 * @return array{0: User, 1: Subscription}
 */
function makePageSetup(): array
{
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $plan = SubscriptionPlan::factory()->create(['price' => '1200.00', 'currency' => 'PHP']);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'pending_payment',
    ]);

    return [$user, $subscription];
}
