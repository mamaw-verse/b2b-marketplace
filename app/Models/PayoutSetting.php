<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 * @property int|null $updated_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class PayoutSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'updated_by_id',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key): string
    {
        return self::where('key', $key)->value('value') ?? '';
    }

    public static function getDisputeHoldDays(): int
    {
        return (int) self::get('dispute_hold_days');
    }

    public static function getMinimumPayoutThreshold(): float
    {
        return (float) self::get('minimum_payout_threshold');
    }

    public static function getTransactionFeeAmount(): float
    {
        return (float) self::get('transaction_fee_amount');
    }

    public static function getCommissionFeeRate(): float
    {
        return (float) self::get('commission_fee_rate');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
