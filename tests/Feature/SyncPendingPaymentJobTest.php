<?php

declare(strict_types=1);

use App\Jobs\SyncPendingPaymentJob;
use App\Models\PaymentAttempt;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\PaymentStuckAdminNotification;
use App\Services\DltPaymentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Notification::fake();

    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
    ]);
});

test('job activates subscription when sync returns PAID', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => true, 'data' => ['payment_status' => 'PAID']]),
    ]);

    [$subscription, $attempt] = makeJobSetup();

    (new SyncPendingPaymentJob($attempt->id))->handle(app(DltPaymentService::class));

    expect($attempt->fresh()->status)->toBe('succeeded')
        ->and($attempt->fresh()->dlt_payment_status)->toBe('PAID')
        ->and($subscription->fresh()->status)->toBe('active');

    Notification::assertNothingSent();
});

test('job rejects subscription when sync returns REJECTED', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => true, 'data' => ['payment_status' => 'REJECTED']]),
    ]);

    [$subscription, $attempt] = makeJobSetup();

    (new SyncPendingPaymentJob($attempt->id))->handle(app(DltPaymentService::class));

    expect($attempt->fresh()->status)->toBe('failed')
        ->and($attempt->fresh()->dlt_payment_status)->toBe('REJECTED')
        ->and($subscription->fresh()->status)->toBe('cancelled');

    Notification::assertNothingSent();
});

test('job re-dispatches with backoff when sync returns PENDING', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => true, 'data' => ['payment_status' => 'PENDING']]),
    ]);

    [$subscription, $attempt] = makeJobSetup();

    (new SyncPendingPaymentJob($attempt->id))->handle(app(DltPaymentService::class));

    expect($attempt->fresh()->sync_attempts)->toBe(1);
    expect($attempt->fresh()->next_sync_at)->not->toBeNull();
    expect($attempt->fresh()->status)->toBe('pending');

    Queue::assertPushed(SyncPendingPaymentJob::class);
    Notification::assertNothingSent();
});

test('job emails admin and gives up after max retries', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => true, 'data' => ['payment_status' => 'PENDING']]),
    ]);

    // sync_attempts = 4 means the next attempt (5th) hits the ceiling.
    [$subscription, $attempt] = makeJobSetup(syncAttempts: 4);

    (new SyncPendingPaymentJob($attempt->id))->handle(app(DltPaymentService::class));

    expect($attempt->fresh()->sync_attempts)->toBe(5);
    expect($attempt->fresh()->next_sync_at)->toBeNull();
    expect($attempt->fresh()->status)->toBe('pending');

    Notification::assertSentOnDemand(PaymentStuckAdminNotification::class);
    Queue::assertNotPushed(SyncPendingPaymentJob::class);
});

test('job treats sync exception as PENDING and re-schedules', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        '*/api/v1/collection/apn/sync' => Http::response(['success' => false, 'message' => 'Server error'], 503),
    ]);

    [$subscription, $attempt] = makeJobSetup();

    (new SyncPendingPaymentJob($attempt->id))->handle(app(DltPaymentService::class));

    expect($attempt->fresh()->sync_attempts)->toBe(1);
    Queue::assertPushed(SyncPendingPaymentJob::class);
});

test('job does nothing when attempt is already succeeded', function () {
    [$subscription, $attempt] = makeJobSetup();
    $attempt->update(['status' => 'succeeded', 'succeeded_at' => now()]);

    (new SyncPendingPaymentJob($attempt->id))->handle(app(DltPaymentService::class));

    // sync_attempts should not have changed.
    expect($attempt->fresh()->sync_attempts)->toBe(0);
    Queue::assertNotPushed(SyncPendingPaymentJob::class);
    Notification::assertNothingSent();
});

test('job does nothing when attempt is already failed', function () {
    [$subscription, $attempt] = makeJobSetup();
    $attempt->update(['status' => 'failed', 'failed_at' => now()]);

    (new SyncPendingPaymentJob($attempt->id))->handle(app(DltPaymentService::class));

    expect($attempt->fresh()->sync_attempts)->toBe(0);
    Queue::assertNotPushed(SyncPendingPaymentJob::class);
    Notification::assertNothingSent();
});

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

/**
 * @return array{0: Subscription, 1: PaymentAttempt}
 */
function makeJobSetup(int $syncAttempts = 0): array
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
        ->syncPending($syncAttempts)
        ->create();

    return [$subscription, $attempt];
}
