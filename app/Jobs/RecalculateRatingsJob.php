<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class RecalculateRatingsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $reviewableType,
        public readonly int $reviewableId,
    ) {}

    /**
     * Recalculate average_rating and total_reviews for the given entity.
     */
    public function handle(): void
    {
        $model = match ($this->reviewableType) {
            Product::class, 'product' => Product::find($this->reviewableId),
            Shop::class, 'shop' => Shop::find($this->reviewableId),
            default => null,
        };

        if (! $model) {
            return;
        }

        $stats = Review::where('reviewable_type', $model::class)
            ->where('reviewable_id', $model->id)
            ->where('is_hidden', false)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        $model->update([
            'average_rating' => $stats->avg_rating ? round((float) $stats->avg_rating, 2) : null,
            'total_reviews' => (int) $stats->total,
        ]);
    }
}
