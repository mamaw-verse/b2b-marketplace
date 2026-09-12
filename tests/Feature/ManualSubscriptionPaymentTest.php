<?php

declare(strict_types=1);

use App\Livewire\Admin\SubscriptionQueue;
use App\Livewire\CompanyHub\ManualSubscriptionPaymentPage;
use App\Livewire\CompanyHub\SubscriptionPage;
use App\Livewire\CompanyHub\SubscriptionPaymentPage;
use App\Models\PaymentAttempt;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\ManualPaymentSubmittedAdminNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

// ──────────────────────────────────────────────────────────────
// ManualSubscriptionPaymentPage — mount guards
// ──────────────────────────────────────────────────────────────

test('manual payment page 403s when payment_method is dlt', function () {
    Config::set('marketplace.payment_method', 'dlt');

    [$user, $subscription] = makeManualSetup();

    Livewire::actingAs($user)
        ->test(ManualSubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertForbidden();
});

test('manual payment page loads form step when payment_method is manual', function () {
    Config::set('marketplace.payment_method', 'manual');

    [$user, $subscription] = makeManualSetup();

    Livewire::actingAs($user)
        ->test(ManualSubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertSet('step', 'form')
        ->assertOk();
});

test('manual payment page 403s when subscription belongs to a different shop', function () {
    Config::set('marketplace.payment_method', 'manual');

    $otherUser = User::factory()->create();
    Shop::factory()->create(['user_id' => $otherUser->id, 'status' => 'active']);

    [, $subscription] = makeManualSetup();

    Livewire::actingAs($otherUser)
        ->test(ManualSubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertForbidden();
});

test('manual payment page 403s when subscription is not pending_payment', function () {
    Config::set('marketplace.payment_method', 'manual');

    [$user, $subscription] = makeManualSetup(status: 'active');

    Livewire::actingAs($user)
        ->test(ManualSubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertForbidden();
});

// ──────────────────────────────────────────────────────────────
// ManualSubscriptionPaymentPage — double-submit guard
// ──────────────────────────────────────────────────────────────

test('manual payment page shows submitted step when a pending manual attempt already exists', function () {
    Config::set('marketplace.payment_method', 'manual');

    [$user, $subscription] = makeManualSetup();

    PaymentAttempt::factory()->forSubscription($subscription)->manualPending()->create();

    Livewire::actingAs($user)
        ->test(ManualSubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertSet('step', 'submitted');
});

// ──────────────────────────────────────────────────────────────
// ManualSubscriptionPaymentPage — submit validation
// ──────────────────────────────────────────────────────────────

test('submit requires reference number', function () {
    Config::set('marketplace.payment_method', 'manual');
    Storage::fake('s3');

    [$user, $subscription] = makeManualSetup();

    Livewire::actingAs($user)
        ->test(ManualSubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->set('receipt', UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg'))
        ->call('submit')
        ->assertHasErrors(['referenceNumber']);
});

test('submit requires receipt upload', function () {
    Config::set('marketplace.payment_method', 'manual');
    Storage::fake('s3');

    [$user, $subscription] = makeManualSetup();

    Livewire::actingAs($user)
        ->test(ManualSubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->set('referenceNumber', 'REF-12345')
        ->call('submit')
        ->assertHasErrors(['receipt']);
});

test('submit rejects invalid receipt mime type', function () {
    Config::set('marketplace.payment_method', 'manual');
    Storage::fake('s3');

    [$user, $subscription] = makeManualSetup();

    Livewire::actingAs($user)
        ->test(ManualSubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->set('referenceNumber', 'REF-12345')
        ->set('receipt', UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'))
        ->call('submit')
        ->assertHasErrors(['receipt']);
});

// ──────────────────────────────────────────────────────────────
// ManualSubscriptionPaymentPage — successful submit
// ──────────────────────────────────────────────────────────────

test('submit creates a manual payment attempt and moves to submitted step', function () {
    Config::set('marketplace.payment_method', 'manual');
    Storage::fake('s3');
    Notification::fake();

    [$user, $subscription] = makeManualSetup();

    Livewire::actingAs($user)
        ->test(ManualSubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->set('referenceNumber', 'REF-99887766')
        ->set('receipt', UploadedFile::fake()->image('receipt.jpg'))
        ->call('submit')
        ->assertSet('step', 'submitted')
        ->assertHasNoErrors();

    $attempt = PaymentAttempt::where('subscription_id', $subscription->id)->first();
    expect($attempt)->not->toBeNull()
        ->and($attempt->payment_method)->toBe('manual')
        ->and($attempt->status)->toBe('pending')
        ->and($attempt->manual_reference_number)->toBe('REF-99887766')
        ->and($attempt->manual_receipt_path)->not->toBeNull();
});

test('submit notifies admin after successful manual payment submission', function () {
    Config::set('marketplace.payment_method', 'manual');
    Storage::fake('s3');
    Notification::fake();

    [$user, $subscription] = makeManualSetup();

    Livewire::actingAs($user)
        ->test(ManualSubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->set('referenceNumber', 'REF-11223344')
        ->set('receipt', UploadedFile::fake()->image('receipt.png'))
        ->call('submit');

    Notification::assertSentOnDemand(ManualPaymentSubmittedAdminNotification::class);
});

test('submit stores receipt on s3 disk', function () {
    Config::set('marketplace.payment_method', 'manual');
    Storage::fake('s3');
    Notification::fake();

    [$user, $subscription] = makeManualSetup();

    Livewire::actingAs($user)
        ->test(ManualSubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->set('referenceNumber', 'REF-55443322')
        ->set('receipt', UploadedFile::fake()->image('receipt.jpg'))
        ->call('submit');

    $attempt = PaymentAttempt::where('subscription_id', $subscription->id)->first();
    Storage::disk('s3')->assertExists($attempt->manual_receipt_path);
});

// ──────────────────────────────────────────────────────────────
// DLT payment page gating
// ──────────────────────────────────────────────────────────────

test('dlt payment page 403s when payment_method is manual', function () {
    Config::set('marketplace.payment_method', 'manual');

    [$user, $subscription] = makeManualSetup();

    Livewire::actingAs($user)
        ->test(SubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->assertForbidden();
});

// ──────────────────────────────────────────────────────────────
// SubscriptionPage — subscribe() routing
// ──────────────────────────────────────────────────────────────

test('subscribe redirects to manual payment route when payment_method is manual', function () {
    Config::set('marketplace.payment_method', 'manual');

    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $plan = SubscriptionPlan::factory()->create(['price' => '1200.00', 'is_active' => true, 'trial_days' => null]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id);

    $redirectUrl = $component->effects['redirect'] ?? '';
    expect($redirectUrl)->toContain('/pay/manual');
});

test('subscribe redirects to dlt payment route when payment_method is dlt', function () {
    Config::set('marketplace.payment_method', 'dlt');

    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $plan = SubscriptionPlan::factory()->create(['price' => '1200.00', 'is_active' => true, 'trial_days' => null]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionPage::class)
        ->call('subscribe', $plan->id);

    $redirectUrl = $component->effects['redirect'] ?? '';
    expect($redirectUrl)->toContain('/pay')
        ->and($redirectUrl)->not->toContain('/pay/manual');
});

// ──────────────────────────────────────────────────────────────
// Admin: pending payments tab includes manual attempts
// ──────────────────────────────────────────────────────────────

test('admin pending payments tab shows manual payment subscriptions', function () {
    $this->seed(RolePermissionSeeder::class);
    Event::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $plan = SubscriptionPlan::factory()->create();
    $shop = Shop::factory()->create([
        'status' => 'active',
        'country_code' => config('marketplace.instance_country'),
        'registered_business_name' => 'Manual Pay Corp',
    ]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'pending_payment',
    ]);
    PaymentAttempt::factory()->forSubscription($subscription)->manualPending()->create();

    Livewire::actingAs($admin)
        ->test(SubscriptionQueue::class)
        ->call('switchTab', 'pending_payments')
        ->assertSee('Manual Pay Corp');
});

// ──────────────────────────────────────────────────────────────
// Admin: forceActivate on manual attempt — no dlt_payment_status set
// ──────────────────────────────────────────────────────────────

test('forceActivate on manual attempt does not set dlt_payment_status', function () {
    $this->seed(RolePermissionSeeder::class);
    Event::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

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
    $attempt = PaymentAttempt::factory()->forSubscription($subscription)->manualPending()->create();

    Livewire::actingAs($admin)
        ->test(SubscriptionQueue::class)
        ->call('confirmActivate', $subscription->id)
        ->call('forceActivate');

    expect($subscription->fresh()->status)->toBe('active');
    expect($attempt->fresh()->status)->toBe('succeeded');
    expect($attempt->fresh()->dlt_payment_status)->toBeNull();
});

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

/**
 * @return array{0: User, 1: Subscription}
 */
function makeManualSetup(string $status = 'pending_payment'): array
{
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $plan = SubscriptionPlan::factory()->create(['price' => '1200.00', 'currency' => 'PHP', 'trial_days' => null]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => $status,
    ]);

    return [$user, $subscription];
}
