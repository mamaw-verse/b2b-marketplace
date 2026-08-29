<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $variant_id
 * @property int|null $product_id
 * @property array<string, mixed>|null $variant_snapshot
 * @property string $description
 * @property string $quantity
 * @property string $unit
 * @property string $unit_price
 * @property string $subtotal
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'variant_id',
        'product_id',
        'variant_snapshot',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
        'sort_order',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The product variant this line item was sourced from (nullable — freeform items have none).
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * The parent product this line item was sourced from (nullable — freeform items have none).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Dispute line entries referencing this order item.
     */
    public function disputeItems(): HasMany
    {
        return $this->hasMany(DisputeItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'variant_snapshot' => 'array',
        ];
    }
}
