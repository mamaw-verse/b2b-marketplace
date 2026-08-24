<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SellerPayoutAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property string $bank_name
 * @property string $bank_code
 * @property string $account_holder_name
 * @property string $account_number
 * @property string|null $branch
 * @property string $verification_status
 * @property Carbon|null $verified_at
 * @property int|null $verified_by_id
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class SellerPayoutAccount extends Model
{
    /** @use HasFactory<SellerPayoutAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'bank_name',
        'bank_code',
        'account_holder_name',
        'account_number',
        'branch',
        'verification_status',
        'verified_at',
        'verified_by_id',
        'rejection_reason',
    ];

    /**
     * The shop this payout account belongs to.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * The admin user who verified this account.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_number' => 'encrypted',
            'verification_status' => 'string',
            'verified_at' => 'datetime',
        ];
    }
}
