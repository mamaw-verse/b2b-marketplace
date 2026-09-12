<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PayoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property string $status
 * @property string $currency
 * @property string $gross_amount
 * @property string $total_transaction_fees
 * @property string $total_commission_fees
 * @property string $net_amount
 * @property int $orders_count
 * @property int|null $released_by_id
 * @property Carbon|null $released_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $failed_at
 * @property string|null $failure_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Payout extends Model
{
    /** @use HasFactory<PayoutFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'status',
        'currency',
        'gross_amount',
        'total_transaction_fees',
        'total_commission_fees',
        'net_amount',
        'orders_count',
        'released_by_id',
        'released_at',
        'completed_at',
        'failed_at',
        'failure_reason',
    ];

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayoutItem::class);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(PayoutDisbursement::class);
    }

    // ──────────────────────────────────────────────────────────────
    // Casts
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'total_transaction_fees' => 'decimal:2',
            'total_commission_fees' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'released_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
