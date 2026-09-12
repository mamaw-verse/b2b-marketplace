<?php

declare(strict_types=1);

use App\Jobs\SyncPendingPaymentJob;
use App\Models\PaymentAttempt;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Event::fake();

    // Auth token used by every DltPaymentService call.
    Http::fake([
        '*/oauth/token' => Http::response([
            'access_token' => 'fake-bearer-token',
            'expires_in' => 3600,
        ]),
    ]);
});

// ──────────────────────────────────────────────────────────────
// failure_url — always rejects immediately, no /sync call
// ──────────────────────────────────────────────────────────────

test('failure_url rejects the attempt and cancels the subscription without calling sync', function () {
    // No /sync stub — if the controller calls it, the Http fake will throw because there's no matching stub.
    [$user, $subscription, $attempt] = makeCallbackSetup();

    $this->actingAs($user)
        ->get(route('hub.subscriptions.payment.failure', [
            'subscription' => $subscription->id,
            'attempt' => $attempt->id,
        ]))
        ->assertRedirectToRoute('hub.subscription');

    expect($attempt->fresh()->status)->toBe('failed')
        ->and($attempt->fresh()->dlt_payment_status)->toBe('REJECTED')
        ->and($subscription->fresh()->status)->toBe('cancelled');
});

test('failure_url is idempotent when attempt already failed', function () {
    [$user, $subscription, $attempt] = makeCallbackSetup();
    $attempt->update(['status' => 'failed', 'dlt_payment_status' => 'REJECTED', 'failed_at' => now()]);

    $this->actingAs($user)
        ->get(route('hub.subscriptions.payment.failure', [
            'subscription' => $subscription->id,
            'attempt' => $attempt->id,
        ]))
        ->assertRedirectToRoute('hub.subscription');

    expect($attempt->fresh()->status)->toBe('failed');
});

test('failure_url is forbidden for a different user', function () {
    [$user, $subscription, $attempt] = makeCallbackSetup();
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get(route('hub.subscriptions.payment.failure', [
            'subscription' => $subscription->id,
            'attempt' => $attempt->id,
        ]))
        ->assertForbidden();
});

// ──────────────────────────────────────────────────────────────
// success_url — calls /sync, branches on PAID / REJECTED / PENDING
// ──────────────────────────────────────────────────────────────

test('success_url activates subscription when sync returns PAID', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-bearer-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response([
            'success' => true,
            'data' => ['payment_status' => 'PAID'],
        ]),
    ]);

    [$user, $subscription, $attempt] = makeCallbackSetup();

    $this->actingAs($user)
        ->get(route('hub.subscriptions.payment.success', [
            'subscription' => $subscription->id,
            'attempt' => $attempt->id,
        ]))
        ->assertRedirectToRoute('hub.subscription');

    expect($attempt->fresh()->status)->toBe('succeeded')
        ->and($subscription->fresh()->status)->toBe('active');

    Queue::assertNothingPushed();
});

test('success_url rejects when sync returns REJECTED', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-bearer-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response([
            'success' => true,
            'data' => ['payment_status' => 'REJECTED'],
        ]),
    ]);

    [$user, $subscription, $attempt] = makeCallbackSetup();

    $this->actingAs($user)
        ->get(route('hub.subscriptions.payment.success', [
            'subscription' => $subscription->id,
            'attempt' => $attempt->id,
        ]))
        ->assertRedirectToRoute('hub.subscription');

    expect($attempt->fresh()->status)->toBe('failed')
        ->and($subscription->fresh()->status)->toBe('cancelled');

    Queue::assertNothingPushed();
});

test('success_url dispatches SyncPendingPaymentJob and redirects to payment page when sync returns PENDING', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-bearer-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response([
            'success' => true,
            'data' => ['payment_status' => 'PENDING'],
        ]),
    ]);

    [$user, $subscription, $attempt] = makeCallbackSetup();

    $this->actingAs($user)
        ->get(route('hub.subscriptions.payment.success', [
            'subscription' => $subscription->id,
            'attempt' => $attempt->id,
        ]))
        ->assertRedirectToRoute('hub.subscription.pay', ['subscription' => $subscription->id]);

    Queue::assertPushed(SyncPendingPaymentJob::class, fn ($job) => $job->paymentAttemptId === $attempt->id);

    expect($attempt->fresh()->status)->toBe('pending');
    expect($attempt->fresh()->next_sync_at)->not->toBeNull();
});

test('success_url dispatches SyncPendingPaymentJob when sync call fails (treats connection error as PENDING)', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-bearer-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::sequence()->pushStatus(503),
    ]);

    [$user, $subscription, $attempt] = makeCallbackSetup();

    $this->actingAs($user)
        ->get(route('hub.subscriptions.payment.success', [
            'subscription' => $subscription->id,
            'attempt' => $attempt->id,
        ]))
        ->assertRedirectToRoute('hub.subscription.pay', ['subscription' => $subscription->id]);

    Queue::assertPushed(SyncPendingPaymentJob::class);
});

test('success_url is idempotent when attempt already succeeded', function () {
    [$user, $subscription, $attempt] = makeCallbackSetup();
    $attempt->update(['status' => 'succeeded', 'succeeded_at' => now()]);
    $subscription->update(['status' => 'active']);

    $this->actingAs($user)
        ->get(route('hub.subscriptions.payment.success', [
            'subscription' => $subscription->id,
            'attempt' => $attempt->id,
        ]))
        ->assertRedirectToRoute('hub.subscription');

    Queue::assertNothingPushed();
    expect($attempt->fresh()->status)->toBe('succeeded');
});

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

/**
 * @return array{0: User, 1: Subscription, 2: PaymentAttempt}
 */
function makeCallbackSetup(): array
{
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $plan = SubscriptionPlan::factory()->create(['price' => '1200.00']);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'pending_payment',
    ]);
    $attempt = PaymentAttempt::factory()
        ->forSubscription($subscription)
        ->create();

    return [$user, $subscription, $attempt];
}
