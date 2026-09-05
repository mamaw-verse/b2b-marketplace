<?php

declare(strict_types=1);

use App\Jobs\ScanCertificateExpiryJob;
use App\Models\Shop;
use App\Models\ShopCertificate;
use App\Models\User;
use App\Notifications\CertificateExpiryNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    $this->user = User::factory()->create();
    $this->shop = Shop::factory()->create(['user_id' => $this->user->id]);
});

test('certificate at 65 days from expiry receives no status change or notification', function () {
    $certificate = ShopCertificate::factory()->create([
        'shop_id' => $this->shop->id,
        'expiry_date' => now()->addDays(65),
        'status' => 'active',
        'last_alert_threshold_days' => null,
    ]);

    (new ScanCertificateExpiryJob)->handle();

    $certificate->refresh();
    expect($certificate->status)->toBe('active');
    expect($certificate->last_alert_threshold_days)->toBeNull();

    Notification::assertNothingSent();
});

test('certificate at 55 days from expiry crosses 60-day threshold', function () {
    $certificate = ShopCertificate::factory()->create([
        'shop_id' => $this->shop->id,
        'expiry_date' => now()->addDays(55),
        'status' => 'active',
        'last_alert_threshold_days' => null,
    ]);

    (new ScanCertificateExpiryJob)->handle();

    $certificate->refresh();
    expect($certificate->status)->toBe('expiring_soon');
    expect($certificate->last_alert_threshold_days)->toBe(60);

    Notification::assertSentTo($this->user, CertificateExpiryNotification::class, function ($notification) {
        return $notification->daysUntilExpiry === 55;
    });
});

test('running job again after threshold 60 is idempotent', function () {
    $certificate = ShopCertificate::factory()->create([
        'shop_id' => $this->shop->id,
        'expiry_date' => now()->addDays(55),
        'status' => 'expiring_soon',
        'last_alert_threshold_days' => 60,
    ]);

    (new ScanCertificateExpiryJob)->handle();

    $certificate->refresh();
    expect($certificate->status)->toBe('expiring_soon');
    expect($certificate->last_alert_threshold_days)->toBe(60);

    Notification::assertNothingSent();
});

test('certificate at 25 days from expiry crosses 30-day threshold', function () {
    $certificate = ShopCertificate::factory()->create([
        'shop_id' => $this->shop->id,
        'expiry_date' => now()->addDays(25),
        'status' => 'expiring_soon',
        'last_alert_threshold_days' => 60,
    ]);

    (new ScanCertificateExpiryJob)->handle();

    $certificate->refresh();
    expect($certificate->status)->toBe('expiring_soon');
    expect($certificate->last_alert_threshold_days)->toBe(30);

    Notification::assertSentTo($this->user, CertificateExpiryNotification::class, function ($notification) {
        return $notification->daysUntilExpiry === 25;
    });
});

test('certificate at -1 days (expired) transitions to expired status', function () {
    $certificate = ShopCertificate::factory()->create([
        'shop_id' => $this->shop->id,
        'expiry_date' => now()->subDay(),
        'status' => 'expiring_soon',
        'last_alert_threshold_days' => 7,
    ]);

    (new ScanCertificateExpiryJob)->handle();

    $certificate->refresh();
    expect($certificate->status)->toBe('expired');
    expect($certificate->last_alert_threshold_days)->toBe(0);

    Notification::assertSentTo($this->user, CertificateExpiryNotification::class, function ($notification) {
        return $notification->daysUntilExpiry <= 0;
    });
});

test('certificate at 5 days from expiry crosses 7-day threshold', function () {
    $certificate = ShopCertificate::factory()->create([
        'shop_id' => $this->shop->id,
        'expiry_date' => now()->addDays(5),
        'status' => 'expiring_soon',
        'last_alert_threshold_days' => 30,
    ]);

    (new ScanCertificateExpiryJob)->handle();

    $certificate->refresh();
    expect($certificate->status)->toBe('expiring_soon');
    expect($certificate->last_alert_threshold_days)->toBe(7);

    Notification::assertSentTo($this->user, CertificateExpiryNotification::class, function ($notification) {
        return $notification->daysUntilExpiry === 5;
    });
});
