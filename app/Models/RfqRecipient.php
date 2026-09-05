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
 * @property string|null $admin_status
 * @property string|null $admin_notes
 * @property int|null $managed_by
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
        'admin_status',
        'admin_notes',
        'managed_by',
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
     * The admin user currently/last managing this recipient.
     */
    public function managedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by');
    }

    /**
     * Quotations submitted by this recipient (seller) for this RFQ.
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * Whether this recipient is in a terminal (seller-facing) state.
     *
     * Both `closed` (explicit close by buyer or seller) and `no_bid` (legacy)
     * are terminal — no further transitions are allowed.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['closed', 'no_bid'], true);
    }

    /**
     * Whether the admin-side tracking is in a terminal state.
     */
    public function isAdminTerminal(): bool
    {
        return in_array($this->admin_status, ['resolved', 'closed'], true);
    }

    /**
     * Touch the last_activity_at timestamp (resets auto-close timer).
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
