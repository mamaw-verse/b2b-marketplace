<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FulfilmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $order_id
 * @property string $status
 * @property string|null $carrier
 * @property string|null $tracking_code
 * @property string|null $tracking_url
 * @property \Illuminate\Support\Carbon|null $shipped_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $returned_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Fulfilment extends Model
{
    /** @use HasFactory<FulfilmentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'carrier',
        'tracking_code',
        'tracking_url',
        'shipped_at',
        'delivered_at',
        'returned_at',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<FulfilmentItem, $this>
     */
    public function fulfilmentItems(): HasMany
    {
        return $this->hasMany(FulfilmentItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }
}
