<?php

declare(strict_types=1);

use App\Jobs\PruneStaleOnboardingDraftsJob;
use App\Jobs\ReconcileMediaOrphansJob;
use App\Jobs\ScanCertificateExpiryJob;
use App\Jobs\SyncSearchIndexJob;

test('all scheduled jobs are registered with correct frequencies', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain(SyncSearchIndexJob::class)
        ->expectsOutputToContain(ScanCertificateExpiryJob::class)
        ->expectsOutputToContain(PruneStaleOnboardingDraftsJob::class)
        ->expectsOutputToContain(ReconcileMediaOrphansJob::class)
        ->assertSuccessful();
});

test('ScanCertificateExpiryJob runs daily at 02:00 UTC', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('0   2 * * *')
        ->assertSuccessful();
});

test('PruneStaleOnboardingDraftsJob runs weekly on Sundays at 03:00 UTC', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('0   3 * * 0')
        ->assertSuccessful();
});

test('ReconcileMediaOrphansJob runs weekly on Sundays at 04:00 UTC', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('0   4 * * 0')
        ->assertSuccessful();
});
