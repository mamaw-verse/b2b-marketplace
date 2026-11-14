<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Shop;
use App\Support\Slugs\SlugGenerator;
use Illuminate\Console\Command;

final class BackfillSlugsCommand extends Command
{
    protected $signature = 'slugs:backfill';

    protected $description = 'Generate missing slugs for shops and products';

    public function handle(SlugGenerator $generator): int
    {
        $shopsUpdated = $this->backfillShops($generator);
        $productsUpdated = $this->backfillProducts($generator);
        $remaining = Shop::withTrashed()->whereNull('slug')->count()
            + Product::withTrashed()->whereNull('slug')->count();

        $this->info("Updated {$shopsUpdated} shop slug(s) and {$productsUpdated} product slug(s).");

        if ($remaining > 0) {
            $this->error("{$remaining} row(s) still have a null slug.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function backfillShops(SlugGenerator $generator): int
    {
        $updated = 0;

        Shop::withTrashed()
            ->whereNull('slug')
            ->chunkById(500, function ($shops) use ($generator, &$updated): void {
                foreach ($shops as $shop) {
                    $shop->slug = $generator->generate(Shop::class, $shop->slugSource(), $shop->id);
                    $shop->saveQuietly();
                    $updated++;
                }
            });

        return $updated;
    }

    private function backfillProducts(SlugGenerator $generator): int
    {
        $updated = 0;

        Product::withTrashed()
            ->whereNull('slug')
            ->chunkById(500, function ($products) use ($generator, &$updated): void {
                foreach ($products as $product) {
                    $product->slug = $generator->generate(Product::class, $product->slugSource(), $product->id);
                    $product->saveQuietly();
                    $updated++;
                }
            });

        return $updated;
    }
}
