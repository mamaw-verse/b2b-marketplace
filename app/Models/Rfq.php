<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RfqFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property array $buyer_snapshot
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Rfq extends Model
{
    /** @use HasFactory<RfqFactory> */
    use HasFactory;

    protected $table = 'rfqs';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'buyer_snapshot',
        'last_activity_at',
        'closed_at',
    ];

    /**
     * The buyer who created this RFQ.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for readability: the buyer who created this RFQ.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The recipient sellers for this RFQ.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(RfqRecipient::class);
    }

    /**
     * Quotations submitted against this RFQ (across all recipients).
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * Attached media files (buyer attachments).
     */
    public function attachments(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'rfq_attachments');
    }

    /**
     * Check if the RFQ is in an open state.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if the RFQ is in a terminal (closed) state.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
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
            'buyer_snapshot' => 'array',
            'last_activity_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
