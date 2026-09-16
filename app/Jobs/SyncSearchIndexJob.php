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
     * Only runs for the external Typesense driver. Under `pgsql` the
     * generated `search_vector` columns are always fresh — nothing to do.
     */
    public function handle(): void
    {
        if (config('scout.driver') !== 'typesense') {
            return;
        }

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
