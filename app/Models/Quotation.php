<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QuotationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $rfq_id
 * @property int $rfq_recipient_id
 * @property string $status
 * @property string|null $notes
 * @property string $currency
 * @property string|null $total_amount
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property array|null $buyer_snapshot
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Quotation extends Model
{
    /** @use HasFactory<QuotationFactory> */
    use HasFactory;

    protected $fillable = [
        'rfq_id',
        'rfq_recipient_id',
        'status',
        'notes',
        'currency',
        'total_amount',
        'valid_until',
        'submitted_at',
        'buyer_snapshot',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function rfqRecipient(): BelongsTo
    {
        return $this->belongsTo(RfqRecipient::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function attachments(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'quotation_attachments');
    }

    /**
     * Whether this quotation is in a draft (editable) state.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Whether this quotation is submitted (visible to buyer, immutable).
     */
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Whether this quotation is in a terminal (read-only) state.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['awarded', 'rejected', 'withdrawn', 'not_awarded', 'expired']);
    }

    /**
     * Whether this quotation is immutable (any state other than draft).
     */
    public function isImmutable(): bool
    {
        return ! $this->isDraft();
    }

    /**
     * Calculate and set total_amount from line items.
     */
    public function recalculateTotal(): void
    {
        $this->total_amount = $this->items()->sum('subtotal');
        $this->saveQuietly();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'buyer_snapshot' => 'array',
            'total_amount' => 'decimal:2',
            'valid_until' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }
}
