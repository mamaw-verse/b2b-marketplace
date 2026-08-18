<?php

declare(strict_types=1);

use App\Jobs\PruneStaleOnboardingDraftsJob;
use App\Jobs\ReconcileMediaOrphansJob;
use App\Jobs\ScanCertificateExpiryJob;
use App\Jobs\SyncSearchIndexJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SyncSearchIndexJob)->everyFiveMinutes();
Schedule::job(new ScanCertificateExpiryJob)->dailyAt('02:00')->timezone('UTC');
Schedule::job(new PruneStaleOnboardingDraftsJob)->weeklyOn(0, '03:00')->timezone('UTC');
Schedule::job(new ReconcileMediaOrphansJob)->weeklyOn(0, '04:00')->timezone('UTC');
