<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DisputeItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dispute_id
 * @property int $order_item_id
 * @property string $quantity
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class DisputeItem extends Model
{
    /** @use HasFactory<DisputeItemFactory> */
    use HasFactory;

    protected $fillable = [
        'dispute_id',
        'order_item_id',
        'quantity',
        'reason',
    ];

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
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
