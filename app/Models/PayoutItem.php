<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PayoutItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $payout_id
 * @property int $order_id
 * @property int $ledger_entry_id
 * @property string $escrowed_amount
 * @property string $transaction_fee
 * @property string $commission_fee
 * @property string $net_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 */
final class PayoutItem extends Model
{
    /** @use HasFactory<PayoutItemFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public $timestamps = true;

    protected $fillable = [
        'payout_id',
        'order_id',
        'ledger_entry_id',
        'escrowed_amount',
        'transaction_fee',
        'commission_fee',
        'net_amount',
    ];

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class);
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
            'escrowed_amount' => 'decimal:2',
            'transaction_fee' => 'decimal:2',
            'commission_fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }
}
