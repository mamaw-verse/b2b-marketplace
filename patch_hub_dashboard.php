<?php

declare(strict_types=1);

$file = 'app/Livewire/CompanyHub/Hub/Dashboard.php';
$contents = file_get_contents($file);

$analyticsServiceInjection = 'use App\Services\ShopAnalyticsService;';
if (mb_strpos($contents, $analyticsServiceInjection) === false) {
    $contents = str_replace('use App\Models\Shop;', "use App\Models\Shop;\nuse App\Services\ShopAnalyticsService;", $contents);
}

$newMount = <<<'PHP'
    public function mount(ShopAnalyticsService $analyticsService): void
    {
        $shop = Auth::user()->shop;
        if (! $shop) {
            return;
        }

        $this->counts = [
            'new_rfqs' => $shop->rfqRecipients()->where('status', 'received')->count(),
        ];

        if (config('marketplace.features.orders_enabled')) {
            $this->counts['orders_in_progress'] = Order::where('shop_id', $shop->id)->where('status', 'paid')->count();
            $this->counts['unfulfilled_orders'] = Order::where('shop_id', $shop->id)->where('status', 'paid')->whereDoesntHave('fulfilments', fn ($q) => $q->whereIn('status', ['shipped', 'delivered']))->count();
            $this->counts['draft_orders'] = Order::where('shop_id', $shop->id)->where('status', 'draft')->count();
        }

        if (config('marketplace.features.disputes_enabled')) {
            $this->counts['open_disputes'] = DB::table('disputes')->join('orders', 'orders.id', '=', 'disputes.order_id')->where('orders.shop_id', $shop->id)->whereNotIn('disputes.status', ['resolved_refunded', 'resolved_denied', 'withdrawn'])->count();
        }

        if (config('marketplace.features.subscriptions_enabled')) {
            $this->counts['subscription'] = $analyticsService->shopSubscription($shop);
        }

        $this->counts['certification'] = $analyticsService->shopCertification($shop);
        $this->counts['listings'] = $analyticsService->shopListings($shop);
    }
PHP;

$contents = preg_replace('/public function mount\(\): void\s*\{.*?\n    \}/s', $newMount, $contents);
file_put_contents($file, $contents);

echo 'Patched Hub/Dashboard.php';
