<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductShareStatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property string $channel
 * @property Carbon $shared_on
 * @property int $count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 */
final class ProductShareStat extends Model
{
    /** @use HasFactory<ProductShareStatFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'channel',
        'shared_on',
        'count',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'shared_on' => 'date',
            'count' => 'integer',
        ];
    }

    /**
     * The product this share stat belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
