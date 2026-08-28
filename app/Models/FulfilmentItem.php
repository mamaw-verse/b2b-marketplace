<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FulfilmentItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $fulfilment_id
 * @property int $order_item_id
 * @property string $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class FulfilmentItem extends Model
{
    /** @use HasFactory<FulfilmentItemFactory> */
    use HasFactory;

    protected $fillable = [
        'fulfilment_id',
        'order_item_id',
        'quantity',
    ];

    public function fulfilment(): BelongsTo
    {
        return $this->belongsTo(Fulfilment::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }
}
