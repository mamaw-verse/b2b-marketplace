<?php

declare(strict_types=1);

$file = 'app/Services/ShopAnalyticsService.php';
$contents = file_get_contents($file);

$newMethods = <<<METHODS

    public function platformSubscriptions(): array
    {
        $counts = DB::table('subscriptions')
            ->join('shops', 'shops.id', '=', 'subscriptions.shop_id')
            ->join('subscription_plans', 'subscription_plans.id', '=', 'subscriptions.subscription_plan_id')
            ->select('subscriptions.status', 'subscription_plans.name as plan_name', 'shops.country_code', DB::raw('COUNT(*) as count'))
            ->groupBy('subscriptions.status', 'subscription_plans.name', 'shops.country_code')
            ->get();

        \$trialToPaid = DB::table('subscriptions')
            ->whereNotNull('trial_ends_at')
            ->where('status', 'active')
            ->count();

        \$totalTrials = DB::table('subscriptions')
            ->whereNotNull('trial_ends_at')
            ->count();

        \$conversionRate = \$totalTrials > 0 ? round((\$trialToPaid / \$totalTrials) * 100, 2) : 0.0;

        return [
            'counts' => \$counts->toArray(),
            'trial_to_paid_conversion' => \$conversionRate,
        ];
    }

    public function platformCertifications(): array
    {
        return DB::table('certification_applications')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
    }

    public function platformRfqs(\Carbon\CarbonInterface \$from, \Carbon\CarbonInterface \$to): array
    {
        \$volume = DB::table('rfqs')
            ->whereBetween('created_at', [\$from, \$to])
            ->count();

        \$avgResolutionTimeSeconds = DB::table('rfqs')
            ->where('status', 'closed')
            ->whereBetween('created_at', [\$from, \$to])
            ->whereNotNull('closed_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (closed_at - created_at))) as avg_time')
            ->value('avg_time');

        return [
            'volume' => \$volume,
            'avg_resolution_time_seconds' => (float) \$avgResolutionTimeSeconds,
        ];
    }

    public function platformSponsoredContent(): array
    {
        return DB::table('sponsored_content_events')
            ->join('sponsored_content', 'sponsored_content.id', '=', 'sponsored_content_events.sponsored_content_id')
            ->join('sponsored_content_tiers', 'sponsored_content_tiers.id', '=', 'sponsored_content.sponsored_content_tier_id')
            ->select('sponsored_content_tiers.name as tier_name', 'sponsored_content_events.event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('sponsored_content_tiers.name', 'sponsored_content_events.event_type')
            ->get()
            ->toArray();
    }

    public function shopSubscription(Shop \$shop): ?array
    {
        \$subscription = DB::table('subscriptions')
            ->join('subscription_plans', 'subscription_plans.id', '=', 'subscriptions.subscription_plan_id')
            ->where('subscriptions.shop_id', \$shop->id)
            ->select('subscriptions.status', 'subscriptions.current_period_end', 'subscription_plans.name as plan_name')
            ->first();

        if (! \$subscription) {
            return null;
        }

        return [
            'status' => \$subscription->status,
            'plan_name' => \$subscription->plan_name,
            'renewal_date' => \$subscription->current_period_end,
        ];
    }

    public function shopCertification(Shop \$shop): array
    {
        return [
            'is_certified' => \$shop->is_halal_certified,
            'expiry_date' => \$shop->certified_expiry_date,
        ];
    }

    public function shopListings(Shop \$shop): array
    {
        return [
            'published' => DB::table('products')->where('shop_id', \$shop->id)->where('publish_status', 'published')->where('moderation_status', 'active')->count(),
            'draft' => DB::table('products')->where('shop_id', \$shop->id)->where('publish_status', 'draft')->count(),
            'flagged' => DB::table('products')->where('shop_id', \$shop->id)->where('moderation_status', 'flagged')->count(),
            'unpublished' => DB::table('products')->where('shop_id', \$shop->id)->where('moderation_status', 'admin_unpublished')->count(),
        ];
    }
}
METHODS;

$contents = preg_replace('/}\s*$/', $newMethods, $contents);
file_put_contents($file, $contents);

echo 'Patched ShopAnalyticsService.php';
