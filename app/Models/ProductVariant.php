<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property string $sku
 * @property string|null $description
 * @property array<string, mixed> $attributes
 * @property string $price
 * @property string $currency
 * @property int $minimum_order_quantity
 * @property array<int, array{min_qty: int, price: float}> $quantity_breaks
 * @property string $unit
 * @property int|null $available_quantity
 * @property int|null $lead_time_days
 * @property string|null $pack_size
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'description',
        'attributes',
        'price',
        'currency',
        'minimum_order_quantity',
        'quantity_breaks',
        'unit',
        'available_quantity',
        'lead_time_days',
        'pack_size',
        'status',
    ];

    /**
     * The product this variant belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Quotation items that reference this variant (soft link — may be null on the item).
     */
    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class, 'variant_id');
    }

    /**
     * Snapshot of this variant's key fields, captured at quotation submission time.
     * The quotation item stores this so the line item is self-contained even if
     * the variant is later edited or deleted.
     *
     * @return array<string, mixed>
     */
    public function toQuotationSnapshot(): array
    {
        return [
            'sku' => $this->sku,
            'product_name' => $this->product->name ?? null,
            'description' => $this->description,
            'unit_price' => (string) $this->price,
            'unit' => $this->unit,
            'moq' => $this->minimum_order_quantity,
            'pack_size' => $this->pack_size,
        ];
    }

    /**
     * Scope a query to only include published variants.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'quantity_breaks' => 'array',
            'price' => 'decimal:2',
            'minimum_order_quantity' => 'integer',
            'available_quantity' => 'integer',
            'lead_time_days' => 'integer',
        ];
    }
}
