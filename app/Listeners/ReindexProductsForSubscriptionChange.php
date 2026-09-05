<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SubscriptionStatusChanged;
use App\Jobs\IndexProductJob;

final class ReindexProductsForSubscriptionChange
{
    public function handle(SubscriptionStatusChanged $event): void
    {
        $event->subscription->loadMissing('shop.products');

        $products = $event->subscription->shop?->products ?? collect();

        foreach ($products as $product) {
            IndexProductJob::dispatch($product)->afterCommit();
        }
    }
}
