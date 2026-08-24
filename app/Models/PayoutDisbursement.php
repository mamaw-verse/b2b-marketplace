<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PayoutDisbursementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $payout_id
 * @property string $status
 * @property string|null $dlt_reference_id
 * @property string|null $dlt_transaction_id
 * @property string $amount
 * @property array|null $gateway_response
 * @property \Illuminate\Support\Carbon|null $attempted_at
 * @property \Illuminate\Support\Carbon|null $succeeded_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class PayoutDisbursement extends Model
{
    /** @use HasFactory<PayoutDisbursementFactory> */
    use HasFactory;

    protected $fillable = [
        'payout_id',
        'status',
        'dlt_reference_id',
        'dlt_transaction_id',
        'amount',
        'gateway_response',
        'attempted_at',
        'succeeded_at',
        'failed_at',
    ];

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
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
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'attempted_at' => 'datetime',
            'succeeded_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
