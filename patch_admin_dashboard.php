<?php

declare(strict_types=1);

$file = 'app/Livewire/Admin/Finance/AnalyticsDashboard.php';
$contents = file_get_contents($file);

$analyticsServiceInjection = 'use App\Services\ShopAnalyticsService;';
if (mb_strpos($contents, $analyticsServiceInjection) === false) {
    $contents = str_replace('use App\Services\GoogleAnalyticsService;', "use App\Services\GoogleAnalyticsService;\nuse App\Services\ShopAnalyticsService;", $contents);
}

$newMetrics = <<<'PHP'
        // M8 metrics
        $analyticsService = app(ShopAnalyticsService::class);
        $platformSubscriptions = $analyticsService->platformSubscriptions();
        $platformCertifications = $analyticsService->platformCertifications();
        $platformRfqs = $analyticsService->platformRfqs($from, $to);
        $platformSponsoredContent = $analyticsService->platformSponsoredContent();

        $this->metrics = [
            'gmv' => $gmv,
            'total_orders' => $totalOrders,
            'total_payouts_disbursed' => $totalPayoutsDisbursed,
            'total_transaction_fees_collected' => $totalTransactionFees,
            'total_commission_fees_collected' => $totalCommissionFees,
            'platform_average_rating' => round($platformAverageRating, 2),
            'total_active_sellers' => $totalActiveSellers,
            'ga' => $gaOverview,
            'subscriptions' => $platformSubscriptions,
            'certifications' => $platformCertifications,
            'rfqs' => $platformRfqs,
            'sponsored_content' => $platformSponsoredContent,
        ];
PHP;

$contents = preg_replace('/\$this->metrics = \[.*?\];/s', $newMetrics, $contents);
file_put_contents($file, $contents);

echo 'Patched AnalyticsDashboard.php';
