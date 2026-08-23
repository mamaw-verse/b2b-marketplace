<?php

declare(strict_types=1);

use App\Jobs\OrderAutoAcceptJob;
use App\Jobs\PruneStaleOnboardingDraftsJob;
use App\Jobs\QuotationExpiryJob;
use App\Jobs\ReconcileMediaOrphansJob;
use App\Jobs\RfqAutoCloseJob;
use App\Jobs\ScanCertificateExpiryJob;
use App\Jobs\SellerNoBidTimeoutJob;
use App\Jobs\SyncSearchIndexJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SyncSearchIndexJob)->everyFiveMinutes();
Schedule::job(new ScanCertificateExpiryJob)->dailyAt('02:00')->timezone('UTC');
Schedule::job(new SellerNoBidTimeoutJob)->dailyAt('03:00')->timezone('UTC')->withoutOverlapping();
Schedule::job(new RfqAutoCloseJob)->dailyAt('03:15')->timezone('UTC')->withoutOverlapping();
Schedule::job(new QuotationExpiryJob)->dailyAt('03:30')->timezone('UTC')->withoutOverlapping();
Schedule::job(new PruneStaleOnboardingDraftsJob)->weeklyOn(0, '03:00')->timezone('UTC');
Schedule::job(new ReconcileMediaOrphansJob)->weeklyOn(0, '04:00')->timezone('UTC');
Schedule::job(new OrderAutoAcceptJob)->dailyAt('04:00')->timezone('UTC')->withoutOverlapping();
