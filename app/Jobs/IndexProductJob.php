<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class IndexProductJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Product $product,
    ) {}

    /**
     * Index or remove the product from the search index.
     */
    public function handle(): void
    {
        if ($this->product->shouldBeSearchable()) {
            $this->product->searchable();
        } else {
            $this->product->unsearchable();
        }
    }
}
