<?php

declare(strict_types=1);

namespace App\Actions\Storefront;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecordProductShareAction
{
    /**
     * Record a product share event.
     *
     * Returns true if the share was recorded, or false if the channel is invalid or de-duplicated.
     */
    public function execute(Product $product, string $channel, string $fingerprint): bool
    {
        $channels = (array) config('marketplace.share.channels', []);
        if (! in_array($channel, $channels, true)) {
            return false;
        }

        $dedupeMinutes = (int) config('marketplace.share.dedupe_minutes', 10);
        $cacheKey = "share:{$product->id}:{$channel}:{$fingerprint}";

        if (! Cache::add($cacheKey, 1, now()->addMinutes($dedupeMinutes))) {
            return false;
        }

        DB::transaction(function () use ($product, $channel): void {
            DB::table('product_share_stats')->upsert(
                [
                    [
                        'product_id' => $product->id,
                        'channel' => $channel,
                        'shared_on' => today()->toDateString(),
                        'count' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ],
                ['product_id', 'channel', 'shared_on'],
                ['count' => DB::raw('product_share_stats.count + 1'), 'updated_at' => now()],
            );

            Product::whereKey($product->id)->increment('share_count');
        });

        return true;
    }
}
