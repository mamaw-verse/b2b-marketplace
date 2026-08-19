<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Shop;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class IndexShopJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Shop $shop,
    ) {}

    /**
     * Index or remove the shop from the search index.
     */
    public function handle(): void
    {
        if ($this->shop->shouldBeSearchable()) {
            $this->shop->searchable();
        } else {
            $this->shop->unsearchable();
        }
    }
}
