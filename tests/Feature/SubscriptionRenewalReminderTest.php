<?php

declare(strict_types=1);

use App\Jobs\ScanSubscriptionRenewalsJob;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionRenewalReminderNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('renewal reminders are sent and tracked idempotently', function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::parse('2026-09-04 00:00:00', 'UTC'));

    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);
    $plan = SubscriptionPlan::factory()->create();

    $subscription = Subscription::factory()->active()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'current_period_end' => now()->addDays(7),
    ]);

    (new ScanSubscriptionRenewalsJob)->handle();

    Notification::assertSentTo($seller, SubscriptionRenewalReminderNotification::class);

    expect($subscription->fresh()->last_renewal_alert_threshold_days)->toBe(7);

    Notification::fake();
    (new ScanSubscriptionRenewalsJob)->handle();

    Notification::assertNothingSent();
});
