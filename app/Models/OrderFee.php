<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrderFeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property string $label
 * @property string $amount
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class OrderFee extends Model
{
    /** @use HasFactory<OrderFeeFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'label',
        'amount',
        'sort_order',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }
}
