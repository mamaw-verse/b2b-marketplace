<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

final class SyncSearchIndexJob implements ShouldQueue
{
    use Queueable;

    /**
     * Reconcile stale search index entries.
     *
     * Re-indexes shops and products updated within the last 5 minutes
     * as a safety-net to catch any missed index writes.
     */
    public function handle(): void
    {
        $since = Carbon::now()->subMinutes(5);

        Shop::withoutGlobalScopes()
            ->where('updated_at', '>=', $since)
            ->chunk(100, function ($shops) {
                foreach ($shops as $shop) {
                    if ($shop->shouldBeSearchable()) {
                        $shop->searchable();
                    } else {
                        $shop->unsearchable();
                    }
                }
            });

        Product::withoutGlobalScopes()
            ->where('updated_at', '>=', $since)
            ->chunk(100, function ($products) {
                foreach ($products as $product) {
                    if ($product->shouldBeSearchable()) {
                        $product->searchable();
                    } else {
                        $product->unsearchable();
                    }
                }
            });
    }
}
