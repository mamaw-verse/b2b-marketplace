<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DisputeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $order_id
 * @property int $opened_by_user_id
 * @property string $type
 * @property string $status
 * @property string $reason
 * @property string|null $resolution_type
 * @property string|null $resolution_amount
 * @property string|null $resolution_notes
 * @property int|null $resolved_by_user_id
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $escalated_at
 * @property string|null $return_carrier
 * @property string|null $return_tracking_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Dispute extends Model
{
    /** @use HasFactory<DisputeFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'opened_by_user_id',
        'type',
        'status',
        'reason',
        'resolution_type',
        'resolution_amount',
        'resolution_notes',
        'resolved_by_user_id',
        'resolved_at',
        'escalated_at',
        'return_carrier',
        'return_tracking_code',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'dispute_media');
    }

    /**
     * The specific order items (and quantities) the buyer flagged for this dispute.
     */
    public function items(): HasMany
    {
        return $this->hasMany(DisputeItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolution_amount' => 'decimal:2',
            'resolved_at' => 'datetime',
            'escalated_at' => 'datetime',
        ];
    }
}
