<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RfqRecipientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $rfq_id
 * @property int $shop_id
 * @property string $status
 * @property string|null $decline_reason
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class RfqRecipient extends Model
{
    /** @use HasFactory<RfqRecipientFactory> */
    use HasFactory;

    protected $fillable = [
        'rfq_id',
        'shop_id',
        'status',
        'decline_reason',
        'last_activity_at',
        'reviewed_at',
    ];

    /**
     * The parent RFQ.
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    /**
     * The target seller's shop.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Quotations submitted by this recipient (seller) for this RFQ.
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * Whether this recipient is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return $this->status === 'no_bid';
    }

    /**
     * Touch the last_activity_at timestamp (resets no-bid timer).
     */
    public function touchActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
